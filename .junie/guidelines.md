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
- Only use the #[Autowire] attribute when the type hint alone isn't sufficient for Symfony to determine which service to inject
- Example:
  ```php
  use Symfony\Component\DependencyInjection\Attribute\Autowire;

  class MyService
  {
      public function __construct(
          // Type hint is sufficient for unique services
          private OtherService $otherService,

          // Use #[Autowire] when type isn't enough
          #[Autowire(service: 'specific.service.id')] private SomeInterface $specificService,

          // Use #[Autowire] for parameters and values
          #[Autowire(param: 'kernel.project_dir')] string $projectDir
      ) {
          // ...
      }
  }
  ```
- The #[Autowire] attribute must specify one of these parameters:
  - `service`: The service to inject (use when multiple services implement the same interface)
  - `param`: The parameter to inject
  - `value`: A literal value to inject
  - `expression`: An expression to evaluate and inject
  - `env`: An environment variable to inject

### Quiz Configuration
The application supports defining quizzes in both YAML and PHP formats:

```yaml
category_name: "Category Name"
category_description: "Category Description"
questions:
  - text: "Question text"
    answers:
      - text: "Answer 1"
        correct: true
      - text: "Answer 2"
        correct: false
```

Place quiz configuration files in the `config/quizzes/` directory with `.yaml` extension.

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
- Use Symfony UX if a plugin is available instead of including JavaScript libraries directly

### Quiz Loading
The `QuizLoader` service loads quizzes from configuration files in the `config/quizzes/` directory. It supports both YAML and PHP formats. Quizzes are loaded on first access to the application.

### Adding New Quizzes
To add a new quiz:
1. Create a new file in `config/quizzes/` with `.yaml` or `.php` extension
2. Define the quiz structure as shown in the examples above
3. Access the application to trigger the quiz loading process

### More documentation

Use files in .junie and use markdown files that document features and guidelines.

### Symfony UX

Symfony UX is a collection of JavaScript tools that integrate with Symfony. Instead of including JavaScript libraries directly, use the corresponding Symfony UX package when available.

#### Available Symfony UX Packages

- **Symfony UX Chart.js**: For creating charts using Chart.js
  ```bash
  composer require symfony/ux-chartjs
  ```
  Usage in controllers:
  ```php
  use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
  use Symfony\UX\Chartjs\Model\Chart;

  class MyController extends AbstractController
  {
      public function __construct(private ChartBuilderInterface $chartBuilder) {}

      public function index(): Response
      {
          $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
          $chart->setData([
              'labels' => ['January', 'February', 'March'],
              'datasets' => [
                  [
                      'label' => 'My Dataset',
                      'data' => [10, 20, 30],
                  ],
              ],
          ]);

          return $this->render('my_template.html.twig', [
              'chart' => $chart,
          ]);
      }
  }
  ```
  Usage in templates:
  ```twig
  {{ render_chart(chart) }}
  ```

- **Symfony UX Turbo**: For creating reactive applications without writing JavaScript
- **Symfony UX Dropzone**: For file uploads with drag and drop
- **Symfony UX Notify**: For browser notifications
- **Symfony UX Swup**: For page transitions
- **Symfony UX Typed**: For animated typing effects

For a complete list of available packages, visit the [Symfony UX website](https://ux.symfony.com/).
