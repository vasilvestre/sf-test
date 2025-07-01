<?php

return [
    'category' => 'Symfony Controllers',
    'category_description' => 'Controllers and routing in Symfony 7.0',
    'questions' => [
        [
            'question' => 'What is the base class for controllers in Symfony 7.0?',
            'difficulty' => 1,
            'answers' => [
                ['value' => 'AbstractController', 'correct' => true],
                ['value' => 'BaseController', 'correct' => false],
                ['value' => 'SymfonyController', 'correct' => false],
                ['value' => 'MainController', 'correct' => false],
            ],
        ],
        [
            'question' => 'Which method is used to render a template in a Symfony controller?',
            'difficulty' => 1,
            'answers' => [
                ['value' => '$this->render()', 'correct' => true],
                ['value' => '$this->display()', 'correct' => false],
                ['value' => '$this->view()', 'correct' => false],
                ['value' => '$this->template()', 'correct' => false],
            ],
        ],
        [
            'question' => 'What attribute is used to define a route in Symfony 7.0?',
            'difficulty' => 2,
            'answers' => [
                ['value' => '#[Route]', 'correct' => true],
                ['value' => '#[Path]', 'correct' => false],
                ['value' => '#[URL]', 'correct' => false],
                ['value' => '#[Endpoint]', 'correct' => false],
            ],
        ],
        [
            'question' => 'Which of the following is NOT a valid HTTP method in Symfony routing?',
            'difficulty' => 2,
            'answers' => [
                ['value' => 'GET', 'correct' => false],
                ['value' => 'POST', 'correct' => false],
                ['value' => 'PUT', 'correct' => false],
                ['value' => 'FETCH', 'correct' => true],
            ],
        ],
        [
            'question' => 'What is the correct way to get a query parameter in a Symfony controller?',
            'difficulty' => 1,
            'answers' => [
                ['value' => '$request->query->get(\'param\')', 'correct' => true],
                ['value' => '$request->get(\'param\')', 'correct' => false],
                ['value' => '$request->getQuery(\'param\')', 'correct' => false],
                ['value' => '$request->params->get('param')', 'correct' => false],
            ],
        ],
        [
            'question' => 'How do you define a service in Symfony 7 using attributes?',
            'difficulty' => 3,
            'answers' => [
                ['value' => '#[AsService]', 'correct' => true],
                ['value' => '#[Service]', 'correct' => false],
                ['value' => '#[Inject]', 'correct' => false],
                ['value' => '#[Component]', 'correct' => false],
            ],
        ],
        [
            'question' => 'Which attribute is used to autowire a service in a controller\'s constructor?',
            'difficulty' => 2,
            'answers' => [
                ['value' => '#[Autowire]', 'correct' => true],
                ['value' => '#[Inject]', 'correct' => false],
                ['value' => '#[Wants]', 'correct' => false],
                ['value' => '#[Needs]', 'correct' => false],
            ],
        ],
    ],
];
