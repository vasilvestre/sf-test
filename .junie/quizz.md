# Symfony 7.0 Quiz System Guidelines

## Overview
This document provides guidelines for the Symfony 7.0 Quiz System, a tool designed to help users test and improve their knowledge of Symfony 7.0 through interactive quizzes.

## Features
- Display quizzes from configuration files (YAML or PHP)
- Run tests for a single category or all categories at once
- Display statistics on the main page (quizzes taken, success rate)
- Show performance trend chart on the main page
- Track failed questions for learning purposes (future feature)

## Architecture

### Data Structure
- **Category**: A group of related questions (e.g., Routing, Controllers, Doctrine)
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
   - YAML or PHP files containing quiz questions, answers, and categories
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

### PHP Example
```php
return [
    'category_name' => 'Controllers',
    'questions' => [
        [
            'text' => 'What is the base class for controllers in Symfony 7?',
            'answers' => [
                ['text' => 'AbstractController', 'correct' => true],
                ['text' => 'BaseController', 'correct' => false],
                ['text' => 'SymfonyController', 'correct' => false],
                ['text' => 'MainController', 'correct' => false],
            ],
        ],
        [
            'text' => 'Which of the following are valid controller return types in Symfony 7?',
            'answers' => [
                ['text' => 'Response', 'correct' => true],
                ['text' => 'JsonResponse', 'correct' => true],
                ['text' => 'RedirectResponse', 'correct' => true],
                ['text' => 'StreamedResponse', 'correct' => true],
                ['text' => 'ViewResponse', 'correct' => false],
            ],
        ],
    ],
];
```

## Future Enhancements
- User accounts to track progress
- Spaced repetition system for failed questions
- Difficulty levels for questions
- Timed quizzes
- Achievement system
