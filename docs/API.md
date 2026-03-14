# API Reference

Complete reference for HelpDesk API endpoints.

## Overview

Base URL: `http://localhost:8080/api/v1`

All responses return JSON with consistent structure.

## Response Format

### Success Response

```json
{
  "success": true,
  "data": {},
  "message": "Operation successful",
  "meta": {
    "total": 10,
    "per_page": 15,
    "current_page": 1
  }
}
```

### Error Response

```json
{
  "success": false,
  "data": null,
  "message": "Operation failed",
  "errors": ["Error message 1"]
}
```

## Resources

### Departments

```
GET    /departments                 List all departments
POST   /departments                 Create department
GET    /departments/{id}            Get department
PUT    /departments/{id}            Update department
DELETE /departments/{id}            Delete department
```

### Locations

```
GET    /locations                   List all locations
POST   /locations                   Create location
GET    /locations/{id}              Get location
PUT    /locations/{id}              Update location
DELETE /locations/{id}              Delete location
```

### Users

```
GET    /users                       List all users
POST   /users                       Create user
GET    /users/{id}                  Get user
PUT    /users/{id}                  Update user
DELETE /users/{id}                  Delete user
```

### Roles

```
GET    /roles                       List all roles
POST   /roles                       Create role
GET    /roles/{id}                  Get role
PUT    /roles/{id}                  Update role
DELETE /roles/{id}                  Delete role
```

### Permissions

```
GET    /permissions                 List all permissions
POST   /permissions                 Create permission
GET    /permissions/{id}            Get permission
PUT    /permissions/{id}            Update permission
DELETE /permissions/{id}            Delete permission
```

### Tickets

```
GET    /tickets                     List all tickets
POST   /tickets                     Create ticket
GET    /tickets/{id}                Get ticket
PUT    /tickets/{id}                Update ticket
DELETE /tickets/{id}                Delete ticket
```

### Ticket Categories

```
GET    /ticket-categories           List categories
POST   /ticket-categories           Create category
GET    /ticket-categories/{id}      Get category
PUT    /ticket-categories/{id}      Update category
DELETE /ticket-categories/{id}      Delete category
```

### Ticket Priorities

```
GET    /ticket-priorities           List priorities
POST   /ticket-priorities           Create priority
GET    /ticket-priorities/{id}      Get priority
PUT    /ticket-priorities/{id}      Update priority
DELETE /ticket-priorities/{id}      Delete priority
```

### Ticket Statuses

```
GET    /ticket-statuses             List statuses
POST   /ticket-statuses             Create status
GET    /ticket-statuses/{id}        Get status
PUT    /ticket-statuses/{id}        Update status
DELETE /ticket-statuses/{id}        Delete status
```

### Ticket Assignments

```
GET    /ticket-assignments          List assignments
POST   /ticket-assignments          Create assignment
GET    /ticket-assignments/{id}     Get assignment
PUT    /ticket-assignments/{id}     Update assignment
DELETE /ticket-assignments/{id}     Delete assignment
```

### Ticket Comments

```
GET    /ticket-comments             List comments
POST   /ticket-comments             Create comment
GET    /ticket-comments/{id}        Get comment
PUT    /ticket-comments/{id}        Update comment
DELETE /ticket-comments/{id}        Delete comment
```

### Ticket Attachments

```
GET    /ticket-attachments          List attachments
POST   /ticket-attachments          Create attachment
GET    /ticket-attachments/{id}     Get attachment
PUT    /ticket-attachments/{id}     Update attachment
DELETE /ticket-attachments/{id}     Delete attachment
```

### Ticket History

```
GET    /ticket-history              List history entries
POST   /ticket-history              Create history entry
GET    /ticket-history/{id}         Get history entry
PUT    /ticket-history/{id}         Update history entry
DELETE /ticket-history/{id}         Delete history entry
```

## Query Parameters

### Pagination

All list endpoints support pagination:

```
page=1                              Page number (default: 1)
per_page=15                         Records per page (default: 15)
```

Example:

```bash
curl "http://localhost:8080/api/v1/tickets?page=2&per_page=20"
```

## Examples

### Create a Ticket

```bash
curl -X POST http://localhost:8080/api/v1/tickets \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Printer not responding",
    "description": "Office printer offline",
    "category_id": 1,
    "priority_id": 2,
    "status_id": 1
  }'
```

### Update a Ticket

```bash
curl -X PUT http://localhost:8080/api/v1/tickets/1 \
  -H "Content-Type: application/json" \
  -d '{
    "status_id": 5,
    "assigned_to": 3
  }'
```

### List Users with Pagination

```bash
curl "http://localhost:8080/api/v1/users?page=1&per_page=20"
```

## Interactive Testing

For interactive API testing:

1. Start server: `php spark serve`
2. Open http://localhost:8080/api/docs
3. Use "Try it out" feature on any endpoint

## Error Codes

- `200` - Successful GET
- `201` - Successful POST (created)
- `400` - Invalid input
- `404` - Resource not found
- `500` - Server error

## Support

For API documentation support, contact: zoran@makrevski.com
