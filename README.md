# Symfony Quiz Application

This repository contains a Symfony-based quiz application that allows users to take quizzes on various topics.

## Requirements

- PHP 8.2 or higher
- Composer
- Symfony CLI (recommended for development)
- SQLite (required for running the project)

## Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd <repository-directory>
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Database configuration (optional):
   By default, the application uses SQLite which requires no configuration. If you want to use a different database:
   ```
   # Edit .env file to change the database connection
   DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" # Default SQLite configuration
   # Or use another database like MySQL
   # DATABASE_URL="mysql://user:password@127.0.0.1:3306/db_name"
   ```

4. Create the database and run migrations:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. Compile assets:
   ```bash
   php bin/console asset-map:compile
   ```

## Running the Application

Start the Symfony development server:
```bash
symfony serve
```

This will start a local web server, typically at `http://localhost:8000`.

## Loading Quiz Data

The application will automatically load quizzes from configuration files on first access. If you need to manually load or reload quiz data:

```bash
php bin/console cache:clear
```

## Development

### Asset Management

When making changes to assets (JavaScript, CSS), you need to recompile them:

```bash
php bin/console asset-map:compile
```

### Running Tests

```bash
php bin/phpunit
```

## Project Structure

- `config/quizzes/`: Contains quiz configuration files in YAML format
- `src/Controller/`: Contains application controllers
- `src/Entity/`: Contains Doctrine entities
- `src/Service/`: Contains application services
- `templates/`: Contains Twig templates
- `assets/`: Contains JavaScript and CSS files

## Documentation

For more detailed documentation, see the files in the `.junie` directory.
