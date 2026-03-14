# Architecture Guide

Overview of HelpDesk API architecture and design patterns.

## Layered Architecture

```
HTTP Requests/Responses
        |
        v
Controllers (REST Endpoints)
  - Route handling
  - Input validation
  - Response formatting
        |
        v
Services (Business Logic)
  - Core operations
  - Data transformation
  - Business rules
        |
        v
Models (Data Access)
  - Database tables
  - Validation rules
  - Relationships
        |
        v
Database (PostgreSQL)
  - 15 tables
  - Indexes
  - Relationships
```

## Component Responsibilities

### Controllers

Handle HTTP requests and responses.

- Receive and parse JSON input
- Call service methods
- Format and return responses
- Handle error responses

Location: `app/Controllers/`

Example: `TicketController::index()` receives GET request, calls `TicketService::getAll()`, returns JSON response.

### Services

Implement business logic and validation.

- Validate input data
- Implement business rules
- Coordinate database operations
- Handle transactions
- Return standardized response arrays

Location: `app/Services/`

Example: `TicketService::createTicket()` validates data, creates ticket, records history, returns result.

### Models

Define database tables and validation rules.

- Table mapping to classes
- Validation rules
- Relationships
- Database queries

Location: `app/Models/`

Example: `TicketModel` maps to tickets table with validation rules.

## Data Flow

### Create Ticket Request

```
1. POST /api/v1/tickets
   |
2. TicketController::create()
   - Parse JSON input
   - Call TicketService::createTicket($data)
   |
3. TicketService::createTicket()
   - Validate data with TicketModel::validate()
   - Insert ticket: $this->model->insert($data)
   - Create history entry
   - Return result array
   |
4. TicketController
   - Check result['success']
   - Return response with status code
   |
5. JSON Response (201 Created)
   {
     "success": true,
     "data": { "id": 1, ... }
   }
```

## Database Schema

### Table Organization

**User Management:**
- users
- roles
- permissions
- user_roles
- role_permissions

**Company Structure:**
- departments
- locations

**Ticket Management:**
- tickets
- ticket_categories
- ticket_priorities
- ticket_statuses

**Ticket Operations:**
- ticket_assignments
- ticket_comments
- ticket_attachments
- ticket_history

### Key Relationships

```
users --> tickets (created_by, assigned_to)
users --> ticket_assignments (user_id)
users --> ticket_comments (created_by)
users --> ticket_history (created_by)

roles --> role_permissions --> permissions
users --> user_roles --> roles

tickets --> ticket_assignments
tickets --> ticket_comments
tickets --> ticket_attachments
tickets --> ticket_history

tickets --> ticket_categories (category_id)
tickets --> ticket_priorities (priority_id)
tickets --> ticket_statuses (status_id)
```

## Response Format

All endpoints return consistent JSON structure:

```json
{
  "success": true|false,
  "data": {} or null,
  "message": "Human readable message",
  "errors": ["error1", "error2"],
  "meta": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```

## Error Handling

- Validation errors return 400 with error messages
- Not found errors return 404
- Server errors return 500 with details
- All errors use consistent response format

## Service Pattern

All services follow consistent pattern:

```php
class YourService
{
    protected $model;

    public function __construct()
    {
        $this->model = new YourModel();
    }

    public function getAll(): array
    {
        // return list of items
    }

    public function getById(int $id): array
    {
        // return single item or error
    }

    public function create(array $data): array
    {
        // validate, insert, return result
    }

    public function update(int $id, array $data): array
    {
        // validate, update, return result
    }

    public function delete(int $id): array
    {
        // delete, return success
    }
}
```

## Validation

Validation rules defined in Models:

```php
protected $validationRules = [
    'title' => 'required|string|max_length[255]',
    'email' => 'required|valid_email|is_unique[users.email]'
];
```

Services validate before operations:

```php
if (!$this->model->validate($data)) {
    return ['success' => false, 'errors' => $this->model->errors()];
}
```

## Testing Strategy

### Service Tests

Test business logic in isolation:

```php
public function testCreateTicket()
{
    $data = ['title' => 'Test', ...];
    $result = $this->service->create($data);
    $this->assertTrue($result['success']);
}
```

### Feature Tests

Test API endpoints end-to-end:

```php
public function testCreateTicketEndpoint()
{
    $response = $this->post('/api/v1/tickets', $data);
    $this->assertResponseStatus(201);
}
```

## Performance Considerations

### Pagination

All list endpoints support pagination:

```php
$perPage = $this->request->getGet('per_page') ?? 15;
$page = $this->request->getGet('page') ?? 1;
```

### Database Indexing

Key columns indexed for performance:
- Primary keys
- Foreign keys
- Frequently searched columns (email, status)

### Caching

Not currently implemented but planned for v1.1.

## Security

### Current Implementation

- Password hashing with bcrypt
- Input validation
- Type hinting
- SQL injection prevention via Query Builder

### Planned Features

- API authentication and authorization
- Rate limiting
- CORS configuration
- HTTPS enforcement
- Audit logging

## Scalability

### Horizontal Scaling

- Stateless API design allows load balancing
- Database connection pooling recommended
- Cache layer for frequently accessed data

### Vertical Scaling

- Query optimization
- Proper indexing
- Connection pooling
- Code profiling and optimization

## Deployment Architecture

### Production Setup

```
Load Balancer (optional)
    |
    v
Web Server (Nginx/Apache)
    |
    v
PHP-FPM
    |
    v
HelpDesk API Application
    |
    v
PostgreSQL Database
```

## Configuration

Environment-specific configuration via .env:

```env
CI_ENVIRONMENT = production
database.default.hostname = db.example.com
database.default.database = helpdesk_prod
```

## Monitoring

Planned monitoring features:

- Request/response logging
- Error tracking
- Performance metrics
- Database query logging
- API usage analytics

## Design Principles

1. **Separation of Concerns** - Each layer has single responsibility
2. **DRY** - Don't repeat yourself, reuse business logic
3. **SOLID** - Follow SOLID principles
4. **Type Safety** - Use type hints throughout
5. **Error Handling** - Proper exception handling
6. **Code Reusability** - Target 95-100% code reuse

## Project Author

Zoran Makrevski (zoran@makrevski.com)
