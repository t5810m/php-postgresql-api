# HelpDesk API

Enterprise-grade helpdesk API built with PHP 8.5.1, CodeIgniter 4.7.0, and PostgreSQL 18. Provides complete REST API for managing tickets, users, roles, permissions, and helpdesk operations.

## Tech Stack

| Component | Version |
|-----------|---------|
| PHP | 8.5.1 |
| CodeIgniter | 4.7.0 |
| PostgreSQL | 18 |
| firebase/php-jwt | 7.0.3 |
| zircote/swagger-php | 6.0.6 |
| PHPUnit | 10.5.63 |
| FakerPHP | 1.24.1 (dev) |

## Features

- **Complete REST API** - 75 endpoints covering all helpdesk operations
- **15 Database Tables** - Well-designed schema with proper relationships
- **User Management** - Users, roles, permissions, and access control
- **Ticket Management** - Full lifecycle ticket tracking with assignments, comments, attachments, and history
- **Interactive API Documentation** - Swagger UI with "Try it out" functionality
- **JWT Authentication** - Stateless Bearer token authentication with refresh token support
- **Comprehensive Test Suite** - 165 tests covering all services and controllers
- **Production Ready** - Built with best practices and design patterns

## Quick Start

### Requirements

- PHP 8.5.1+
- PostgreSQL 18+
- Composer

### Installation

1. Clone the repository:
```bash
git clone https://github.com/t5810m/php-postgresql-api.git
cd php-postgresql-api
```

2. Install dependencies:
```bash
composer install
```

This also generates `public/openapi.json` automatically via a post-install script.

3. Configure environment:
```bash
cp .env.example .env
```

Edit `.env` with your PostgreSQL credentials:
```
database.default.hostname = localhost
database.default.database = help_desk
database.default.username = postgres
database.default.password = your_password
```

4. Create database schema:
```bash
psql -U postgres -d help_desk -f helpdesk_schema.sql
```

5. Seed sample data:
```bash
php spark db:seed DatabaseSeeder
```

6. Start development server:
```bash
php spark serve
```

7. Access API documentation:
- Interactive Swagger UI: http://localhost:8080/api/docs
- Raw OpenAPI spec: http://localhost:8080/api/v1/docs

## API Documentation

Full API documentation is available at `/api/docs` when the server is running.

All endpoints follow RESTful conventions and return JSON responses.

### Example: List Departments

```bash
curl http://localhost:8080/api/v1/departments \
  -H "Authorization: Bearer <your_token>"
```

### Example: Create a Ticket

```bash
curl -X POST http://localhost:8080/api/v1/tickets \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <your_token>" \
  -d '{
    "title": "Printer not working",
    "description": "Office printer offline",
    "category_id": 1,
    "priority_id": 2,
    "status_id": 1
  }'
```

## Authentication

All API endpoints (except `/api/v1/auth/login` and `/api/v1/auth/refresh`) require a JWT Bearer token.

### Login

```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

Response:

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
  }
}
```

### Using the Token

Include the token in the `Authorization` header for all subsequent requests:

```
Authorization: Bearer <access_token>
```

### Refresh Token

```bash
curl -X POST http://localhost:8080/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "<refresh_token>"}'
```

For full details see [docs/AUTHENTICATION.md](docs/AUTHENTICATION.md).

## Database Schema

The API uses 15 tables organized in three layers:

### User Management
- `users` - User accounts with department and location assignment
- `roles` - System roles (Company IT Manager, IT Department Manager, etc.)
- `permissions` - Fine-grained permissions
- `user_roles` - Many-to-many user-role mapping
- `role_permissions` - Many-to-many role-permission mapping

### Company Structure
- `departments` - Organizational departments
- `locations` - Physical office locations (Italy, Belgium, Germany)

### Ticket Management
- `tickets` - Support tickets with category, priority, status
- `ticket_categories` - Ticket classification
- `ticket_priorities` - Priority levels (Low, Medium, High, Critical)
- `ticket_statuses` - Ticket states (Open, In Progress, Resolved, etc.)
- `ticket_assignments` - Ticket-to-user assignments
- `ticket_comments` - Comments and notes on tickets
- `ticket_attachments` - File attachments to tickets
- `ticket_history` - Audit trail of all ticket changes

## Project Structure

```
helpdesk-api/
├── app/
│   ├── Controllers/       # REST API controllers
│   ├── Models/            # Database models
│   ├── Services/          # Business logic layer
│   ├── Config/            # Application configuration
│   └── Database/
│       └── Seeds/         # Database seeders
├── public/
│   └── openapi.json       # OpenAPI specification (generated, not committed)
├── tests/
│   └── Unit/              # Unit and service tests
├── docs/                  # Documentation
├── vendor/                # Composer dependencies
├── .env                   # Environment variables
└── README.md              # This file
```

## Architecture

The API follows a layered architecture:

1. **Controllers** - Handle HTTP requests and responses
2. **Services** - Implement business logic and validation
3. **Models** - Define database tables and validation rules
4. **Database** - PostgreSQL with proper indexing and relationships

## Testing

Run the complete test suite:

```bash
vendor/bin/phpunit
```

Tests include:
- Service unit tests
- Feature tests for API endpoints
- Database integration tests

## Available Commands

```bash
# Database operations
php spark db:clean                    # Truncate all tables
php spark db:seed DatabaseSeeder      # Seed demo data
php spark db:clean && php spark db:seed DatabaseSeeder  # Reset database

# Server
php spark serve                       # Start development server
php spark serve --host 0.0.0.0        # Listen on all interfaces

# Testing
vendor/bin/phpunit                            # Run all tests
vendor/bin/phpunit --filter TestName         # Run specific test
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Code of Conduct

This project adheres to a [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## Support

For issues, questions, or suggestions, please open an issue on GitHub.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and updates.

## Additional Resources

- [Installation Guide](docs/INSTALLATION.md)
- [API Reference](docs/API.md)
- [Architecture Guide](docs/ARCHITECTURE.md)
- [Development Guide](docs/DEVELOPMENT.md)
