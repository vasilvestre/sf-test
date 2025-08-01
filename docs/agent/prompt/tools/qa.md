---
name: tools:qa
description: Execute QA tools for code quality
---

# Detected QA Tools

This document lists all quality assurance (QA) tools configured in the GitHub workflows for this Symfony project.

## Static Analysis Tools

### 1. **Composer Validate**
- **Command**: `composer validate --strict`
- **Description**: Validates the structure and content of the composer.json file

### 2. **Symfony Security Check**
- **Command**: `symfony security:check`
- **Description**: Checks for security vulnerabilities in dependencies

### 3. **ECS (Easy Coding Standard)**
- **Command**: `vendor/bin/ecs check`
- **Description**: Validates and fixes PHP coding standards

### 4. **Rector**
- **Command**: `vendor/bin/rector process --dry-run`
- **Description**: Detects outdated code and suggests automatic improvements

### 5. **PHPStan**
- **Command**: `vendor/bin/phpstan analyse src`
- **Description**: Static analyzer for PHP to detect type errors and bugs

### 6. **PHPArkitect**
- **Command**: `vendor/bin/phparkitect check`
- **Description**: Validates architecture and code design rules

## Template and File Validation Tools

### 7. **Twig Lint**
- **Command**: `bin/console lint:twig templates`
- **Description**: Validates Twig template syntax

### 8. **YAML Lint**
- **Command**: `bin/console lint:yaml config --parse-tags`
- **Description**: Validates YAML file syntax

### 9. **Twig-CS-Fixer**
- **Command**: `vendor/bin/twig-cs-fixer`
- **Description**: Applies coding standards to Twig templates

## Testing Tools

### 10. **PHPSpec**
- **Command**: `phpdbg -qrr vendor/bin/phpspec run --no-interaction -f dot`
- **Description**: Behavior-driven development (BDD) testing framework

### 11. **PHPUnit**
- **Command**: `vendor/bin/phpunit`
- **Description**: PHP unit testing framework

### 12. **Behat CLI**
- **Command**: `vendor/bin/behat --colors --strict --no-interaction -vvv -f progress --tags="@cli&&~@todo"`
- **Description**: Acceptance tests for CLI interface

### 13. **Behat Non-JS**
- **Command**: `vendor/bin/behat --colors --strict --no-interaction -vvv -f progress --tags="~@javascript&&~@todo&&~@cli"`
- **Description**: Acceptance tests without JavaScript

### 14. **Behat JavaScript**
- **Command**: `vendor/bin/behat --colors --strict --no-interaction -vvv -f progress --tags="@javascript&&~@todo&&~@cli"`
- **Description**: Acceptance tests with JavaScript (browser-based)

## Database Validation

### 15. **Doctrine Schema Validate**
- **Command**: `bin/console doctrine:schema:validate -vvv --skip-sync`
- **Description**: Validates Doctrine mapping and database consistency

## Quick Execution Commands

### Run all static analysis tools (without tests)
```bash
# Basic validation
composer validate --strict
symfony security:check

# Code analysis
vendor/bin/ecs check
vendor/bin/rector process --dry-run
vendor/bin/phpstan analyse src
vendor/bin/phparkitect check

# Template and file validation
bin/console lint:twig templates
bin/console lint:yaml config --parse-tags
vendor/bin/twig-cs-fixer
```

### Run all tests
```bash
# Unit and behavioral tests
vendor/bin/phpspec run
vendor/bin/phpunit

# Acceptance tests (requires DB configuration)
vendor/bin/behat --tags="@cli&&~@todo"
vendor/bin/behat --tags="~@javascript&&~@todo&&~@cli"
```

### Database validation
```bash
bin/console doctrine:schema:validate --skip-sync
```

## Important Notes

- JavaScript tests require a configured web server and browser
- Acceptance tests require a configured PostgreSQL database
- Some tools may require specific dependencies installed via Composer
- Tools are configured to run with PHP 8.3
