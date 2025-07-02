# Symfony 7.0 Quiz System Guidelines

## Overview
This document provides guidelines for the Symfony 7.0 Quiz System, a tool designed to help users test and improve their knowledge of Symfony 7.0 through interactive quizzes.

## Features
- Display quizzes from configuration files (YAML)
- Run tests for a single category or all categories at once
- Display statistics on the main page (quizzes taken, success rate)
- Show performance trend chart on the main page
- Track failed questions for learning purposes (future feature)

## Architecture

### Data Structure
- **Category**: A group of related questions (e.g., Routing, Controllers, Doctrine) with a name and description
- **Quiz**: A collection of questions from one or more categories
- **Question**: A single quiz item with a question text and multiple answer options
- **Answer**: A possible response to a question, marked as correct or incorrect

Questions can have multiple correct answers. When creating quiz questions, you can mark any number of answers as correct by setting `correct: true` for each correct answer.

### Components
1. **Entity Classes**:
   - `Category`: Represents a quiz category
   - `Question`: Represents a quiz question with multiple answers
   - `Answer`: Represents a possible answer to a question
   - `QuizResult`: Stores quiz results including score and date

2. **Configuration Files**:
   - YAML files containing quiz questions, answers, and categories
   - Located in `config/quizzes/` directory

3. **Controllers**:
   - `QuizController`: Handles quiz display, selection, submission, and statistics

4. **Templates**:
   - Main page with statistics and performance chart
   - Quiz display page
   - Results page with performance chart

## Usage

### Running a Quiz
1. Navigate to the quiz homepage
2. Select a specific category or choose "All Categories"
3. Answer the questions presented
4. Submit your answers to see your results

### Adding New Questions
1. Create or edit a configuration file in the `config/quizzes/` directory
2. Follow the format specified in the Configuration Format section
3. New questions will be automatically loaded into the system

## Configuration Format

### YAML Example
```yaml
category_name: "Routing"
category_description: "Questions about Symfony routing system"
questions:
  - text: "Which annotation is used to define a route in a controller?"
    answers:
      - text: "@Route"
        correct: true
      - text: "@Path"
        correct: false
      - text: "@URL"
        correct: false
      - text: "@Link"
        correct: false
  - text: "Which of the following are valid route requirements?"
    answers:
      - text: "id: \\d+"
        correct: true
      - text: "slug: [a-z0-9-]+"
        correct: true
      - text: "page: \\w+"
        correct: true
      - text: "sort: asc|desc"
        correct: true
      - text: "filter: {word}"
        correct: false
```

## Future Enhancements
- User accounts to track progress
- Spaced repetition system for failed questions
- Timed quizzes
- Achievement system

## Quiz Loading Process
The `QuizLoader` service is responsible for loading quiz data from configuration files in the `config/quizzes/` directory. It supports YAML format. The process works as follows:

1. On first access to the application, the QuizLoader service scans the `config/quizzes/` directory
2. For each configuration file found, it parses the content and creates the corresponding entities
3. The entities are persisted to the database
4. Subsequent accesses to the application will use the data from the database

To add a new quiz:
1. Create a new file in `config/quizzes/` with `.yaml` or `.yml` extension
2. Define the quiz structure as shown in the Configuration Format section
3. Access the application to trigger the quiz loading process

## Symfony UX Integration
The application uses Symfony UX for enhanced frontend functionality, particularly for displaying performance charts. Symfony UX is a collection of JavaScript tools that integrate with Symfony, allowing you to use JavaScript libraries without writing custom JavaScript code.

### Chart.js Integration
The application uses Symfony UX Chart.js for displaying performance charts on the main page and results page. This integration allows for creating interactive charts with minimal code:

```php
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class QuizController extends AbstractController
{
    public function __construct(private ChartBuilderInterface $chartBuilder) {}

    public function stats(): Response
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => ['Quiz 1', 'Quiz 2', 'Quiz 3'],
            'datasets' => [
                [
                    'label' => 'Performance',
                    'data' => [75, 80, 90],
                ],
            ],
        ]);

        return $this->render('quiz/stats.html.twig', [
            'chart' => $chart,
        ]);
    }
}
```

In the template:
```twig
{{ render_chart(chart) }}
```

### Other Symfony UX Packages
The application may use other Symfony UX packages in the future, such as:
- Symfony UX Turbo: For creating reactive applications without writing JavaScript
- Symfony UX Dropzone: For file uploads with drag and drop
- Symfony UX Notify: For browser notifications

For a complete list of available packages, visit the [Symfony UX website](https://ux.symfony.com/).
