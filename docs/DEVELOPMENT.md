# Development Guide

Guidelines for developing HelpDesk API.

## Development Environment Setup

```bash
# Clone and setup
git clone https://github.com/yourusername/helpdesk-api.git
cd helpdesk-api
composer install

# Configure
cp .env.example .env
# Edit .env with your database

# Initialize
php spark migrate
php spark db:seed DatabaseSeeder

# Start development
php spark serve
```

## Project Structure

```
app/
├── Controllers/          REST API controllers
├── Models/               Database models with validation
├── Services/             Business logic layer
├── Config/               Configuration
└── Database/
    └── Seeds/            Database seeders

tests/
└── Unit/                 Service tests and integration tests

docs/                     Documentation files
public/
└── openapi.json         OpenAPI specification

vendor/                   Composer dependencies
```

## Coding Standards

### PHP Code Style

Follow PSR-12:
- 4 spaces for indentation
- Meaningful names for variables/functions
- Type hints on all parameters and return types
- Single responsibility principle

### Example Service

```php
public function createTicket(array $data): array
{
    if (!$this->model->validate($data)) {
        return [
            'success' => false,
            'errors' => $this->model->errors()
        ];
    }

    $ticketId = $this->model->insert($data);

    return [
        'success' => true,
        'data' => $this->model->find($ticketId)
    ];
}
```

### Comments

No emojis or symbols. Use clear language:

```php
// Good
// Calculate total cost including tax
$total = $price * 1.20;

// Bad
// Calculate total cost including tax
$total = $price * 1.20;  // adds 20% tax
```

## Writing Tests

All new features require tests.

### Test File Location

`tests/Unit/Services/YourServiceTest.php`

### Test Structure

```php
public function testCreateTicket()
{
    // Arrange
    $data = [
        'title' => 'Test Ticket',
        'category_id' => 1
    ];

    // Act
    $result = $this->service->createTicket($data);

    // Assert
    $this->assertTrue($result['success']);
    $this->assertNotEmpty($result['data']);
}
```

### Running Tests

```bash
# All tests
php spark test

# Specific file
vendor/bin/phpunit tests/Unit/Services/TicketServiceTest.php

# Specific test
vendor/bin/phpunit --filter testCreateTicket
```

## Adding New Endpoints

### 1. Create Model (if needed)

```php
// app/Models/YourModel.php
class YourModel extends Model
{
    protected $table = 'your_table';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields = ['field1', 'field2'];

    protected $validationRules = [
        'field1' => 'required|string|max_length[100]'
    ];
}
```

### 2. Create Service

```php
// app/Services/YourService.php
class YourService
{
    protected $model;

    public function __construct()
    {
        $this->model = new YourModel();
    }

    public function create(array $data): array
    {
        // Validate
        if (!$this->model->validate($data)) {
            return ['success' => false, 'errors' => $this->model->errors()];
        }

        // Insert
        $id = $this->model->insert($data);
        return ['success' => true, 'data' => $this->model->find($id)];
    }
}
```

### 3. Create Controller

```php
// app/Controllers/YourController.php
class YourController extends ResourceController
{
    protected $service;
    protected $format = 'json';

    public function __construct()
    {
        $this->service = new YourService();
    }

    public function index()
    {
        $result = $this->service->getAll();
        return $this->respond($result);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        $result = $this->service->create($data);

        if ($result['success']) {
            return $this->respondCreated($result['data']);
        }
        return $this->respond($result, 400);
    }
}
```

### 4. Add Routes

```php
// app/Config/Routes.php
$routes->group('api/v1', static function ($routes) {
    $routes->resource('your-resource');
});
```

### 5. Update OpenAPI Spec

Add to `public/openapi.json` in the paths and components sections.

### 6. Write Tests

```php
// tests/Unit/Services/YourServiceTest.php
class YourServiceTest extends CIUnitTestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new YourService();
    }

    public function testCreate()
    {
        $data = ['field1' => 'value1'];
        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['id']);
    }
}
```

## Database Migrations

For schema changes:

```php
// Create migration
php spark make:migration CreateNewTable

// Edit app/Database/Migrations/
// Then run
php spark migrate
```

## Debugging

### Enable Debug Mode

Edit `.env`:

```env
CI_ENVIRONMENT = development
```

### Log Messages

```php
log_message('error', 'Error message here');
log_message('info', 'Info message here');
```

View logs in `writable/logs/`.

## Performance Tips

1. Use indexes on frequently queried columns
2. Lazy load relationships when needed
3. Cache frequently accessed data
4. Use pagination for large result sets
5. Avoid N+1 query problems

## Version Control

### Commit Messages

```
Add new feature
Fix bug in service
Update documentation
Refactor controller logic
```

### Branch Naming

- `feature/ticket-assignment` - New features
- `bugfix/null-pointer` - Bug fixes
- `docs/readme-update` - Documentation
- `refactor/service-cleanup` - Refactoring

## Deployment

For production deployment:

1. Set `CI_ENVIRONMENT = production` in .env
2. Configure proper database backups
3. Set up HTTPS/TLS
4. Enable logging and monitoring
5. Configure rate limiting (planned)
6. Implement authentication (planned)

## Contact & Questions

For development questions or contributions, reach out to Zoran Makrevski at zoran@makrevski.com

## Resources

- [CodeIgniter 4 Documentation](https://codeigniter.com/user_guide/)
- [PSR-12 Coding Standards](https://www.php-fig.org/psr/psr-12/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
