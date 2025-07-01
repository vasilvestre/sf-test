# Symfony 7.0 Quiz System Guidelines

## Overview
This document provides guidelines for the Symfony 7.0 Quiz System, a tool designed to help users test and improve their knowledge of Symfony 7.0 through interactive quizzes.

## Features
- Display quizzes from configuration files (YAML or PHP)
- Run tests for a single category or all categories at once
- Track failed questions for learning purposes (future feature)

## Architecture

### Data Structure
- **Category**: A group of related questions (e.g., Routing, Controllers, Doctrine)
- **Quiz**: A collection of questions from one or more categories
- **Question**: A single quiz item with a question text and multiple answer options
- **Answer**: A possible response to a question, marked as correct or incorrect

### Components
1. **Entity Classes**:
   - `Category`: Represents a quiz category
   - `Question`: Represents a quiz question with multiple answers
   - `Answer`: Represents a possible answer to a question

2. **Configuration Files**:
   - YAML or PHP files containing quiz questions, answers, and categories
   - Located in `config/quizzes/` directory

3. **Controllers**:
   - `QuizController`: Handles quiz display, selection, and submission

4. **Templates**:
   - Quiz selection page
   - Quiz display page
   - Results page

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
    ],
];
```

## Future Enhancements
- User accounts to track progress
- Spaced repetition system for failed questions
- Difficulty levels for questions
- Timed quizzes
- Achievement system
