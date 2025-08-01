# Agent Guidelines - Symfony PHP

## Mandatory Instructions

📋 **IMPORTANT**: Refer to [docs/agent/INSTRUCTIONS.md](docs/agent/INSTRUCTIONS.md) for specific agent instructions, including:
- **Git Workflow**: Semantic commits, versioning, branch management
- **Pull Request Management**: Standards for creating and reviewing PRs
- **Mandatory Quality Checks**: QA tools to run before any commit

These instructions are **MANDATORY** and supplement the guidelines below.

## Docker Environment
⚠️ **IMPORTANT**: This project uses Docker Compose for development. Always execute commands inside the appropriate Docker containers.

### Docker Commands
- `docker compose up -d` - Start all services in detached mode
- `docker compose down` - Stop all services
- `docker compose exec app bash` - Access the application container shell
- `docker compose exec app php bin/console [command]` - Run Symfony console commands
- `docker compose exec app composer [command]` - Run Composer commands
- `docker compose logs app` - View application logs
- `docker compose ps` - Check service status

## Project Architecture

This is a **Domain-Driven Design (DDD)** Symfony application with the following characteristics:

### Technology Stack
- **PHP**: 8.3+
- **Symfony**: 7.3.*
- **API Platform**: 4.0+ (REST API with OpenAPI)
- **Doctrine ORM**: 2.20+ with PostgreSQL
- **Messenger**: AMQP-based async processing
- **Authentication**: JWT + 2FA Email
- **File Storage**: Flysystem with S3 support
- **Frontend**: Symfony UX with Twig Components

### Domain Modules
The application is organized into bounded contexts:
- **Account**: User management, organizations, contracts, invitations
- **Declaration**: Document declarations and statements 
- **File**: File management and storage
- **Security**: Authentication and authorization
- **Shared**: Common infrastructure and utilities
- **Statistics**: Business metrics and reporting
- **ThirdParty**: External integrations (ERP, Chorus, YellowBox)

### Architecture Patterns
- **CQRS**: Separate Command/Query buses with dedicated handlers
- **Event Sourcing**: Domain events with async processing
- **Hexagonal Architecture**: Clean separation of concerns
- **API-First**: REST API with frontend consuming it
- **Gateway Pattern**: For external service integrations
- **ADR Pattern**: Action-Domain-Responder for controllers

## Development Methodology

### Test-Driven Development (TDD)
Follow Kent Beck's TDD methodology strictly:

#### TDD Cycle: Red → Green → Refactor
1. **Red**: Write the simplest failing test first
2. **Green**: Implement minimum code needed to make tests pass
3. **Refactor**: Improve code structure while keeping tests green

#### TDD Best Practices
- Use meaningful test names describing behavior (e.g., `shouldCreateAccountWithValidData`)
- Write just enough code to make the test pass - no more
- Run all tests after each change (except long-running tests)
- Only refactor when tests are passing
- Make test failures clear and informative

#### Example TDD Workflow
```php
// 1. RED: Write failing test
public function it_should_create_account_with_valid_email(): void
{
    $command = new CreateAccountCommand('test@example.com');
    $account = $this->handler->handle($command);
    
    expect($account->getEmail())->toBe('test@example.com');
}

// 2. GREEN: Minimal implementation
public function handle(CreateAccountCommand $command): Account
{
    return new Account($command->email);
}

// 3. REFACTOR: Improve structure (if needed)
```

### Tidy First Approach
Separate all changes into two distinct types:

#### 1. Structural Changes (Make First)
- Renaming variables, methods, classes
- Extracting methods or classes
- Moving code between files
- Reorganizing imports
- **Never change behavior**

#### 2. Behavioral Changes (Make Second)
- Adding new functionality
- Modifying existing behavior
- Fixing bugs
- **Never mix with structural changes**

#### Validation Process
- Run tests before structural changes
- Run tests after structural changes
- Ensure no behavior changed during structural modifications

## Build/Test Commands
**Note**: Execute these commands inside the Docker container (`docker compose exec app [command]`) or prefix with `docker compose exec app`

### Dependencies & Setup
- `composer install` - Install PHP dependencies
- `composer dump-autoload` - Regenerate autoloader
- `composer check-platform-reqs` - Check platform requirements
- `composer audit` - Check for security vulnerabilities

### Database Operations
- `php bin/console doctrine:database:create` - Create database
- `php bin/console doctrine:migrations:migrate` - Run database migrations
- `php bin/console doctrine:schema:validate` - Validate database schema
- `php bin/console doctrine:fixtures:load` - Load test fixtures

### Application Management
- `php bin/console cache:clear` - Clear application cache
- `php bin/console app:install` - Run application installer
- `php bin/console assets:install` - Install public assets
- `php bin/console importmap:install` - Install frontend dependencies

### JWT & Security
- `php bin/console lexik:jwt:generate-keypair` - Generate JWT keypair
- `php bin/console security:check` - Security vulnerability check

### Testing
- `vendor/bin/phpunit` - Run unit tests
- `vendor/bin/phpspec run` - Run specification tests
- `vendor/bin/behat` - Run acceptance tests (all)
- `vendor/bin/behat --tags="@cli"` - CLI acceptance tests
- `vendor/bin/behat --tags="~@javascript"` - Non-JS acceptance tests
- `vendor/bin/behat --tags="@javascript"` - JavaScript acceptance tests

### QA Tools (via Docker)
- `docker compose exec app vendor/bin/ecs check` - Code style validation (PSR-12)
- `docker compose exec app vendor/bin/ecs check --fix` - Fix code style issues
- `docker compose exec app vendor/bin/phpstan analyse src` - Static analysis
- `docker compose exec app vendor/bin/rector process --dry-run` - Code modernization analysis
- `docker compose exec app vendor/bin/phparkitect check` - Architecture validation
- `docker compose exec app vendor/bin/twig-cs-fixer lint` - Twig template linting
- `docker compose exec app php bin/console lint:twig templates` - Validate Twig syntax
- `docker compose exec app php bin/console lint:yaml config` - Validate YAML syntax

### Messenger (Async Processing)
- `php bin/console messenger:consume` - Consume messages from all transports
- `php bin/console messenger:consume sending_mail` - Consume email queue
- `php bin/console messenger:consume updating_yellow_box` - Consume YellowBox updates
- `php bin/console messenger:stats` - Show queue statistics
- `php bin/console messenger:failed:show` - Show failed messages

## Commit Discipline

### Commit Rules
Only commit when:
1. **ALL tests are passing**
2. **ALL compiler/linter warnings resolved**
3. **Change represents single logical unit**
4. **Commit message clearly states structural vs behavioral**

### Commit Types
- **Structural**: `refactor: extract method for user validation`
- **Behavioral**: `feat: add email validation to user registration`
- **Fix**: `fix: handle null email in user creation`

### Commit Frequency
- Use small, frequent commits
- Commit structural changes separately from behavioral changes
- Never mix different types of changes in same commit

## Code Style & Conventions

### PHP Standards
- Follow **PSR-12** coding standard
- Use **strict types** declaration (`declare(strict_types=1);`) in all PHP files
- Use **type hints** for all method parameters and return types
- Use **readonly properties** when appropriate (PHP 8.1+)
- Prefer **constructor property promotion** (PHP 8.0+)
- Use **match expressions** over switch when suitable (PHP 8.0+)

### Naming Conventions
- **PascalCase** for classes, interfaces, traits, and enums
- **camelCase** for methods, variables, and properties
- **snake_case** for configuration keys and database columns
- **SCREAMING_SNAKE_CASE** for constants

### Code Quality Standards
- **Eliminate duplication ruthlessly**
- **Express intent clearly** through naming and structure
- **Make dependencies explicit**
- **Keep methods small** and focused on single responsibility
- **Minimize state and side effects**
- **Use simplest solution** that could possibly work

### Documentation & Error Handling
- Add **PHPDoc blocks** for all public methods and complex logic
- Handle exceptions explicitly with **try/catch blocks**
- Use **custom exception classes** for domain-specific errors

### DDD & Architecture
- Use **dependency injection** instead of static calls
- Follow **single responsibility principle** for services
- Implement **value objects** for complex data structures
- Use **domain events** for cross-context communication
- Keep **controllers thin** - delegate to application services

## Domain-Driven Design Patterns

### CQRS Implementation
```php
// Command (Write operations)
$this->commandBus->dispatch(new CreateAccountCommand($data));

// Query (Read operations) 
$account = $this->queryBus->ask(new FindAccountQuery($id));
```

### Event Handling
```php
// Domain events are automatically dispatched
$account->edit($newData); // Triggers AccountWasEdited event
$this->eventBus->dispatch($account->getEvents());
```

### Repository Pattern
- Use **custom repositories** for complex queries
- Implement **specification pattern** for business rules
- Separate **read/write models** when appropriate

## Refactoring Guidelines

### When to Refactor
- **Only when tests are passing** (Green phase)
- After implementing new functionality
- When code duplication is identified
- When clarity can be improved

### Refactoring Process
1. **Make one refactoring change at a time**
2. **Run tests after each refactoring step**
3. **Use established refactoring patterns** with proper names
4. **Prioritize removing duplication** and improving clarity

### Common Refactoring Patterns
- **Extract Method**: Break down long methods
- **Extract Class**: Separate responsibilities
- **Rename**: Improve clarity of intent
- **Move Method**: Better organize responsibilities

## File Structure (DDD-based)

### Domain Organization
```
src/
├── Account/               # Account bounded context
│   ├── Common/           # Shared Account logic
│   │   ├── Invitation/   # Invitation subdomain
│   │   ├── Organization/ # Organization subdomain
│   │   └── Contract/     # Contract subdomain
│   ├── Chorus/           # Chorus integration
│   ├── Dashboard/        # Account dashboard
│   └── UI/              # Account UI controllers
├── Declaration/          # Declaration bounded context
│   ├── Common/          # Shared Declaration logic
│   │   ├── Contract/    # Declaration contracts
│   │   ├── Draft/       # Declaration drafts
│   │   └── Statement/   # Final statements
│   └── UI/             # Declaration UI controllers
├── File/                # File management context
│   ├── Application/     # Application services
│   ├── Domain/         # File domain logic
│   └── Infrastructure/ # File persistence
├── Security/           # Security context
│   ├── Common/        # Shared security logic
│   │   ├── AdminUser/ # Admin user management
│   │   └── User/      # Regular user management
│   ├── Shared/        # Security services
│   └── UI/           # Security UI controllers
├── Shared/           # Shared kernel
├── Statistics/       # Statistics bounded context
├── ThirdParty/       # External integrations
│   ├── ERP/         # ERP integration
│   ├── Sulu/        # CMS integration
│   └── Yellowbox/   # YellowBox integration
└── UI/              # Cross-cutting UI concerns
    ├── Admin/       # Admin interface
    ├── Api/         # REST API
    ├── Frontend/    # User interface
    ├── Mailer/      # Email handling
    ├── Sentry/      # Error tracking
    └── Webhook/     # Webhook handlers
```

### Configuration Organization
- **Domain configs** in `config/packages/app_[domain].php`
- **Routes** organized by UI layer in `config/routes/`
- **Services** in `config/services/` with domain-specific includes
- **Workflows** in `config/workflows/` per bounded context
- **Behat suites** organized by domain and UI layer

## Database & Doctrine

### Entity Mapping by Bounded Context
```php
'mappings' => [
    'Security' => [
        'dir' => '%kernel.project_dir%/src/Security/Shared/Infrastructure/Persistence/Doctrine/ORM/Entity',
        'prefix' => 'App\Security\Shared\Infrastructure\Persistence\Doctrine\ORM\Entity',
    ],
    'Declaration' => [
        'dir' => '%kernel.project_dir%/src/Declaration/Shared/Infrastructure/Persistence/Doctrine/ORM/Entity',
        'prefix' => 'App\Declaration\Shared\Infrastructure\Persistence\Doctrine\ORM\Entity',
    ],
    'Account' => [
        'dir' => '%kernel.project_dir%/src/Account/Shared/Infrastructure/Persistence/Doctrine/ORM/Entity',
        'prefix' => 'App\Account\Shared\Infrastructure\Persistence\Doctrine\ORM\Entity',
    ],
    'File' => [
        'dir' => '%kernel.project_dir%/src/File/Infrastructure/Persistence/Doctrine/ORM/Entity',
        'prefix' => 'App\File\Infrastructure\Persistence\Doctrine\ORM\Entity',
    ],
]
```

### PostgreSQL Features
- **JSONB** support with custom functions
- **Custom DQL functions**: `JSONB_EXTRACT_TEXT`, `DAY`, `YEAR`, `MONTH`, `CAST`
- **UUID** primary keys with `Types::GUID`
- **Custom data types** for domain modeling

### Migrations & Fixtures
- Use **Doctrine migrations** for database changes
- **Foundry** for test data generation with domain-specific factories
- **Fixtures** organized by bounded context

## API Platform Integration

### REST API Configuration
```php
'mapping' => [
    'paths' => [
        '%kernel.project_dir%/src/File/Infrastructure/Persistence/Doctrine/ORM/Entity/',
        '%kernel.project_dir%/src/Declaration/Common/Statement/Infrastructure/ApiPlatform/Resource/',
        '%kernel.project_dir%/src/Declaration/Common/Draft/Infrastructure/ApiPlatform/Resource/',
        '%kernel.project_dir%/src/Security/Shared/Infrastructure/Persistence/Doctrine/ORM/Entity',
    ],
]
```

### API Features
- **OpenAPI documentation** auto-generated
- **JWT authentication** with refresh tokens via Lexik bundle
- **CORS** support for frontend integration
- **Multiple formats**: JSON, JSON:API, JSON-LD, multipart
- **Custom operations** with dedicated controllers

## Messenger Configuration

### AMQP Transports by Domain
```php
'transports' => [
    'sending_mail' => [...],              // Email notifications
    'updating_yellow_box' => [...],       // YellowBox synchronization
    'create_declaration_webhook_erp' => [...], // ERP webhooks
    'send_declaration_status_erp' => [...],    // Status updates
    'update_declaration_webhook_erp' => [...], // Declaration updates
    'import_contract_from_yellow_box' => [...], // Contract imports
]
```

### CQRS Buses
- **command.bus**: Write operations with logging middleware
- **query.bus**: Read operations with logging middleware
- **event.bus**: Domain events with `allow_no_handlers`

### Message Routing
Domain events are automatically routed to appropriate transports:
- `AccountWasEdited` → `updating_yellow_box`
- `SendEmail` → `sending_mail`
- Declaration events → ERP webhooks

## Workflow Configuration

### State Machines by Domain
```php
// Declaration workflow
'declaration' => [
    'places' => ['CREATED', 'CLOSED', 'OPENED', 'SUBMITTED', 'COMPLETED'],
    'transitions' => ['close', 'open', 'submit', 'complete'],
]

// Account onboarding workflows
'account_account' => [...],      // INVITED → CREATED → REGISTERED
'account_contract' => [...],     // CREATED → VERIFIED → BILLING_VERIFIED
'account_organization' => [...], // CREATED → STARTED → VERIFIED → ONBOARDED
```

## Testing Strategy

### Test Organization by Domain
```
config/behat/suites/
├── cli/           # Command-line tests
├── backend/       # Admin interface tests
│   ├── account/   # Account domain tests
│   ├── declaration/ # Declaration domain tests
│   └── security/  # Security domain tests
├── frontend/      # User interface tests
├── webhook/       # Webhook tests
└── api/          # API tests
```

### Test Types
- **PHPSpec**: Specification/behavior tests for domain logic
- **PHPUnit**: Unit tests for application services
- **Behat**: Acceptance tests organized by bounded context

### Test Environment
- **Separate test database** with `_test` suffix
- **Synchronous message processing** with `sync://` transports
- **VCR mocking** for external API calls
- **Foundry factories** for consistent test data

## Third-Party Integrations

### CFC Services
- **Chorus**: French government invoicing system
- **ERP**: Internal ERP system with custom authentication
- **YellowBox**: CFC's proprietary service
- **Site**: CFC website integration

### Infrastructure Services
- **Sentry**: Error tracking (production only)
- **Brevo**: Email delivery with templates
- **S3**: File storage with Flysystem
- **PostgreSQL**: Primary database with JSONB support

### Gateway Pattern Implementation
```php
/**
 * @template T of GatewayResponse
 */
class DefaultGateway
{
    public function __construct(protected array $middlewares) {}

    /**
     * @return T
     */
    public function __invoke(GatewayRequest $request): GatewayResponse
    {
        return (new Pipe($this->middlewares))($request);
    }
}
```

## Deployment & Environment

### Environment Configuration
- **Environment variables** for all external service configuration
- **Symfony Flex** for package management
- **Asset compilation** with Webpack Encore and Symfony Asset Mapper
- **Multi-environment** support (dev, test, stage, prod)

### Observability
- **Structured logging** with Monolog
- **Workflow auditing** enabled
- **Messenger monitoring** with failure transport
- **Sentry integration** for error tracking
