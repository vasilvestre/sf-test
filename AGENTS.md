# Agent Guidelines for sf-test

## Project Setup

### Prerequisites
- PHP 8.3+
- Docker (for PostgreSQL)
- Symfony CLI

### Starting the Application

1. **Start Docker containers** (PostgreSQL database):
   ```bash
   docker compose up -d
   ```

2. **Install dependencies**:
   ```bash
   php composer.phar install
   ```

3. **Create database schema** (IMPORTANT: use `symfony console`, not `php bin/console`):
   ```bash
   symfony console doctrine:schema:create
   ```

4. **Start the server**:
   ```bash
   symfony serve
   ```

The application will be available at **https://127.0.0.1:8000**

## Critical: Use `symfony console` Instead of `bin/console`

**Always use `symfony console` instead of `php bin/console` when the Symfony server is running.**

The Symfony CLI injects the correct environment variables (including PostgreSQL connection from Docker), while `php bin/console` uses the default `.env` configuration (SQLite). Using the wrong command will cause "relation does not exist" errors and other environment mismatches.

This applies to ALL console commands, not just database operations:

```bash
# CORRECT
symfony console doctrine:schema:create
symfony console doctrine:migrations:migrate

# WRONG - will use SQLite instead of PostgreSQL
php bin/console doctrine:schema:create
php bin/console doctrine:migrations:migrate
```

## Project Structure

- **Symfony 7.0** quiz application
- **PostgreSQL** database (via Docker)
- **Doctrine ORM** for database management
- **Twig** templating
- **Stimulus** for frontend controllers

## Common Issues

### Error 500: "relation category does not exist"
The database schema is missing. Run:
```bash
docker compose up -d
symfony console doctrine:schema:create
```

### Schema commands succeed but tables don't exist
You're using `php bin/console` instead of `symfony console`. The Symfony CLI provides the correct DATABASE_URL for the Docker PostgreSQL instance.
