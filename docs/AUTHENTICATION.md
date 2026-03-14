# Authentication

The HelpDesk API uses JWT (JSON Web Token) Bearer authentication via the `firebase/php-jwt` library (v7.0.3).

## Overview

- All endpoints under `/api/v1/*` require a valid access token except the two auth endpoints listed below.
- Tokens are passed in the `Authorization` header as a Bearer token.
- Access tokens expire after 1 hour (configurable).
- Refresh tokens expire after 7 days (configurable).
- Tokens are signed with HS256.

## Public Endpoints (no token required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Obtain access and refresh tokens |
| POST | `/api/v1/auth/refresh` | Exchange a refresh token for a new access token |

---

## Login

**POST** `/api/v1/auth/login`

Request:

```json
{
  "email": "admin@example.com",
  "password": "password123"
}
```

Response `200 OK`:

```json
{
  "success": true,
  "data": {
    "token": "<access_token>",
    "refresh_token": "<refresh_token>",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "department_name": "IT",
      "location_name": "Head Office"
    },
    "roles": ["Company IT Manager"],
    "permissions": ["manage_users", "manage_tickets"]
  },
  "message": "Login successful"
}
```

Response `401 Unauthorized` (wrong credentials or inactive account):

```json
{
  "success": false,
  "data": null,
  "message": "Invalid credentials"
}
```

---

## Using the Access Token

Include the token in every protected request:

```
Authorization: Bearer <access_token>
```

Example:

```bash
curl http://localhost:8080/api/v1/tickets \
  -H "Authorization: Bearer <access_token>"
```

If the token is missing, malformed, expired, or uses the wrong type, the API returns `401 Unauthorized`:

| Condition | Message |
|-----------|---------|
| No Authorization header | `Missing or malformed Authorization header` |
| Token expired | `Token has expired` |
| Invalid signature | `Invalid token signature` |
| Malformed JWT | `Invalid token` |
| Refresh token used as access token | `Invalid token type` |

---

## Refresh Token

When the access token expires, obtain a new one without re-authenticating.

**POST** `/api/v1/auth/refresh`

Request:

```json
{
  "refresh_token": "<refresh_token>"
}
```

Response `200 OK`:

```json
{
  "success": true,
  "data": {
    "token": "<new_access_token>",
    "expires_in": 3600
  },
  "message": "Token refreshed successfully"
}
```

Response `401 Unauthorized` (expired or invalid refresh token):

```json
{
  "success": false,
  "data": null,
  "message": "Invalid or expired refresh token"
}
```

Note: The refresh endpoint does not issue a new refresh token. When the refresh token itself expires (after 7 days), the user must log in again.

---

## Token Payload

Both token types share the same payload structure:

| Claim | Description |
|-------|-------------|
| `iss` | Issuer - always `helpdesk-api` |
| `sub` | User ID (integer) |
| `iat` | Issued at (Unix timestamp) |
| `exp` | Expiry (Unix timestamp) |
| `type` | `access` or `refresh` |

The filter rejects any token where `type` is not `access`. Passing a refresh token to a protected endpoint returns `401 Invalid token type`.

---

## Configuration

JWT settings are read from `.env`:

```
JWT_SECRET = your-256-bit-secret-change-this-in-production
JWT_EXPIRATION = 3600
JWT_REFRESH_EXPIRATION = 604800
```

| Variable | Default | Description |
|----------|---------|-------------|
| `JWT_SECRET` | (none) | Signing secret - must be set in production |
| `JWT_EXPIRATION` | `3600` | Access token lifetime in seconds (1 hour) |
| `JWT_REFRESH_EXPIRATION` | `604800` | Refresh token lifetime in seconds (7 days) |

The algorithm is fixed to HS256 and is not configurable via `.env`.

---

## Demo Credentials

The database seeder creates 50 demo users. All use the same password:

```
password: password123
```

Use the Swagger UI at `/api/docs` to log in and click "Authorize" to set the token for all subsequent requests in the session.
