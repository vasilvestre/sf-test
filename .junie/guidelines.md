# Symfony Quiz Application Guidelines

## Build/Configuration Instructions

### Requirements
- PHP 8.2 or higher
- Composer
- Symfony CLI (recommended for development)
- Database (MySQL, PostgreSQL, SQLite)

### Setup
1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Configure the database in `.env` file:
   ```
   DATABASE_URL="mysql://user:password@127.0.0.1:3306/db_name"
   ```
4. Create the database:
   ```bash
   php bin/console doctrine:database:create
   ```
5. Run migrations:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```
6. Load quiz data:
   ```bash
   php bin/console cache:clear
   ```
   The application will automatically load quizzes from configuration files on first access.

### Development Server
```bash
symfony server:start
```
or
```bash
php -S localhost:8000 -t public/
```

## Configuration Guidelines

### Entity Configuration
- Use Doctrine ORM Attributes for entity mapping
- Example:
  ```php
  #[ORM\Entity(repositoryClass: CategoryRepository::class)]
  class Category
  {
      #[ORM\Id]
      #[ORM\GeneratedValue]
      #[ORM\Column]
      private ?int $id = null;

      #[ORM\Column(length: 255)]
      private ?string $name = null;
  }
  ```

### Route Configuration
- Use Symfony Attributes for route configuration
- Example:
  ```php
  #[Route('/quiz', name: 'quiz_index')]
  public function index(): Response
  {
      // ...
  }
  ```

### Dependency Injection Configuration
- Use the #[Autowire] attribute for dependency injection
- Example:
  ```php
  use Symfony\Component\DependencyInjection\Attribute\Autowire;

  class MyService
  {
      public function __construct(
          #[Autowire(service: OtherService::class)] private OtherService $otherService,
          #[Autowire(param: 'kernel.project_dir')] string $projectDir
      ) {
          // ...
      }
  }
  ```
- The #[Autowire] attribute must specify one of these parameters:
  - `service`: The service to inject
  - `param`: The parameter to inject
  - `value`: A literal value to inject
  - `expression`: An expression to evaluate and inject
  - `env`: An environment variable to inject

### Quiz Configuration
The application supports defining quizzes in both YAML and PHP formats:

#### YAML Format (preferred)
```yaml
category_name: "Category Name"
category_description: "Category Description"
questions:
  - text: "Question text"
    difficulty: 1
    answers:
      - text: "Answer 1"
        correct: true
      - text: "Answer 2"
        correct: false
```

#### PHP Format
```php
return [
    'category_name' => 'Category Name',
    'category_description' => 'Category Description',
    'questions' => [
        [
            'text' => 'Question text',
            'difficulty' => 1,
            'answers' => [
                ['text' => 'Answer 1', 'correct' => true],
                ['text' => 'Answer 2', 'correct' => false],
            ],
        ],
    ],
];
```

Place quiz configuration files in the `config/quizzes/` directory with `.yaml` or `.php` extension.

## Architecture

### Data Model
- **Category**: Represents a quiz category with a name and description
- **Question**: Belongs to a category and has text, difficulty level, and multiple answers
- **Answer**: Belongs to a question and has text and a boolean indicating if it's correct

### Services
- **QuizLoader**: Loads quiz data from configuration files and persists it to the database

### Controllers
- **HomeController**: Redirects to the quiz index
- **QuizController**: Handles quiz-related actions (listing, displaying, submitting)

## Additional Development Information

### Code Style
- Follow Symfony best practices
- Use Attributes for configuration instead of YAML/PHP files
- Use dependency injection for services
- Use type hints for method parameters and return types

### Quiz Loading
The `QuizLoader` service loads quizzes from configuration files in the `config/quizzes/` directory. It supports both YAML and PHP formats. Quizzes are loaded on first access to the application.

### Adding New Quizzes
To add a new quiz:
1. Create a new file in `config/quizzes/` with `.yaml` or `.php` extension
2. Define the quiz structure as shown in the examples above
3. Access the application to trigger the quiz loading process
