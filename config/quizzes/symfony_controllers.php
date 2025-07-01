<?php

return [
    'category_name' => 'Symfony Controllers',
    'category_description' => 'Controllers and routing in Symfony 7.0',
    'questions' => [
        [
            'text' => 'What is the base class for controllers in Symfony 7.0?',
            'difficulty' => 1,
            'answers' => [
                ['text' => 'AbstractController', 'correct' => true],
                ['text' => 'BaseController', 'correct' => false],
                ['text' => 'SymfonyController', 'correct' => false],
                ['text' => 'MainController', 'correct' => false],
            ],
        ],
        [
            'text' => 'Which method is used to render a template in a Symfony controller?',
            'difficulty' => 1,
            'answers' => [
                ['text' => '$this->render()', 'correct' => true],
                ['text' => '$this->display()', 'correct' => false],
                ['text' => '$this->view()', 'correct' => false],
                ['text' => '$this->template()', 'correct' => false],
            ],
        ],
        [
            'text' => 'What attribute is used to define a route in Symfony 7.0?',
            'difficulty' => 2,
            'answers' => [
                ['text' => '#[Route]', 'correct' => true],
                ['text' => '#[Path]', 'correct' => false],
                ['text' => '#[URL]', 'correct' => false],
                ['text' => '#[Endpoint]', 'correct' => false],
            ],
        ],
        [
            'text' => 'Which of the following is NOT a valid HTTP method in Symfony routing?',
            'difficulty' => 2,
            'answers' => [
                ['text' => 'GET', 'correct' => false],
                ['text' => 'POST', 'correct' => false],
                ['text' => 'PUT', 'correct' => false],
                ['text' => 'FETCH', 'correct' => true],
            ],
        ],
        [
            'text' => 'What is the correct way to get a query parameter in a Symfony controller?',
            'difficulty' => 1,
            'answers' => [
                ['text' => '$request->query->get(\'param\')', 'correct' => true],
                ['text' => '$request->get(\'param\')', 'correct' => false],
                ['text' => '$request->getQuery(\'param\')', 'correct' => false],
                ['text' => '$request->params->get(\'param\')', 'correct' => false],
            ],
        ],
    ],
];
