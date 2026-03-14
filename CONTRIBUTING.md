# Contributing to HelpDesk API

Thank you for your interest in contributing! This document provides guidelines and instructions for contributing.

## Code of Conduct

Please read and follow our [Code of Conduct](CODE_OF_CONDUCT.md).

## How to Contribute

### Reporting Issues

Before creating a bug report, please check the issue list to avoid duplicates.

**Create a bug report including:**
- Clear, descriptive title
- Detailed description of the issue
- Steps to reproduce
- Expected vs actual behavior
- Screenshots if applicable
- Your environment (OS, PHP version, PostgreSQL version)

### Suggesting Enhancements

Submit enhancement suggestions as GitHub issues with:
- Clear, descriptive title
- Detailed description of the enhancement
- Use case and benefits
- Possible implementation approach (optional)

### Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes following the code style guide
4. Add or update tests
5. Run tests: `php spark test`
6. Commit with clear messages: `git commit -m 'Add feature X'`
7. Push to your fork: `git push origin feature/your-feature`
8. Open a Pull Request with a clear description

## Development Setup

1. Clone your fork:
```bash
git clone https://github.com/yourusername/helpdesk-api.git
cd helpdesk-api
```

2. Install dependencies:
```bash
composer install
```

3. Configure environment:
```bash
cp .env.example .env
# Edit .env with your database credentials
```

4. Create database:
```bash
php spark migrate
php spark db:seed DatabaseSeeder
```

5. Run tests:
```bash
php spark test
```

## Code Style Guide

- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- No emojis or symbols in code/comments (keep professional)
- Type hint all function parameters and return types

### Example Service Method

```php
public function updateTicket(int $id, array $data): array
{
    $ticket = $this->model->find($id);
    
    if (!$ticket) {
        return ['success' => false, 'message' => 'Ticket not found'];
    }
    
    if (!$this->model->validate($data)) {
        return ['success' => false, 'errors' => $this->model->errors()];
    }
    
    $this->model->update($id, $data);
    return ['success' => true, 'data' => $this->model->find($id)];
}
```

## Testing Requirements

- Write tests for new features
- Update tests when modifying existing code
- Maintain test coverage above 80%
- Run full test suite before submitting PR

```bash
# Run all tests
php spark test

# Run specific test file
vendor/bin/phpunit tests/Unit/Services/TicketServiceTest.php
```

## Documentation

- Update README.md if changing user-facing features
- Add docs for new endpoints in docs/API.md
- Update CHANGELOG.md with your changes
- Add code comments for complex logic

## Commit Messages

Use clear, descriptive commit messages:
- Good: "Add ticket assignment feature"
- Good: "Fix validation error in user creation"
- Bad: "fix stuff"
- Bad: "wip"

## Review Process

1. All PRs require at least one review
2. CI/CD tests must pass
3. Code review focuses on:
   - Code quality and style
   - Test coverage
   - Documentation
   - Performance
   - Security

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Questions?

Feel free to open an issue or discussion for any questions about contributing.

Thank you for helping make HelpDesk API better!
