# Installation Guide

Detailed instructions for setting up HelpDesk API in your environment.

## System Requirements

### Minimum
- PHP 8.5.1 or higher
- PostgreSQL 18 or higher
- Composer 2.0+
- 512MB RAM
- 200MB disk space

### Recommended
- PHP 8.5.1 (latest)
- PostgreSQL 18
- Composer latest
- 2GB RAM
- 1GB disk space
- Linux or macOS

## Step-by-Step Installation

### 1. Clone Repository

```bash
git clone https://github.com/yourusername/helpdesk-api.git
cd helpdesk-api
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
database.default.driver = Postgre
database.default.hostname = localhost
database.default.port = 5432
database.default.database = help_desk
database.default.username = postgres
database.default.password = your_password
database.default.DBDebug = true
```

### 4. PostgreSQL Setup

Create the database:

```bash
createdb help_desk -U postgres
```

### 5. Database Schema

Create tables:

```bash
php spark migrate
```

### 6. Seed Demo Data

Populate with sample data:

```bash
php spark db:seed DatabaseSeeder
```

This seeds:
- 4 roles with 10 permissions
- 5 departments
- 6 locations (Italy, Belgium, Germany)
- 50 users
- 100 sample tickets with relationships

### 7. Start Development Server

```bash
php spark serve
```

### 8. Access API

- Interactive API Docs: http://localhost:8080/api/docs
- Raw OpenAPI Spec: http://localhost:8080/api/v1/docs

## Database Reset

```bash
php spark db:clean && php spark db:seed DatabaseSeeder
```

## Running Tests

```bash
php spark test
```

## Contact & Support

For questions or issues, contact Zoran Makrevski at zoran@makrevski.com

## Troubleshooting

### PostgreSQL Connection Error

Enable PostgreSQL extension in php.ini and restart server:

```ini
extension=pdo_pgsql
extension=pgsql
```

### Port Already in Use

Use different port:

```bash
php spark serve --port 8081
```

### Tests Failing

```bash
php spark db:clean
php spark migrate
php spark test
```
