<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\QuizResult;
use App\Repository\CategoryRepository;
use App\Repository\QuestionRepository;
use App\Repository\QuestionFailureRepository;
use App\Repository\CategoryFailureRepository;
use App\Repository\QuizResultRepository;
use App\Service\QuizLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    private const QUESTION_PER_QUIZ = 15;

    public function __construct(
        #[Autowire(service: QuizLoader::class)] private readonly QuizLoader                         $quizLoader,
        #[Autowire(service: CategoryRepository::class)] private readonly CategoryRepository         $categoryRepository,
        #[Autowire(service: QuestionRepository::class)] private readonly QuestionRepository         $questionRepository,
        #[Autowire(service: EntityManagerInterface::class)] private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: QuizResultRepository::class)] private readonly QuizResultRepository     $quizResultRepository,
        #[Autowire(service: QuestionFailureRepository::class)] private readonly QuestionFailureRepository $questionFailureRepository,
        #[Autowire(service: CategoryFailureRepository::class)] private readonly CategoryFailureRepository $categoryFailureRepository,
        private readonly ChartBuilderInterface                                                      $chartBuilder
    ) {
    }

    #[Route('/', name: 'quiz_index')]
    public function index(): Response
    {
        // Load quizzes from configuration files
        $this->quizLoader->loadQuizzes();

        // Get all categories
        $categories = $this->categoryRepository->findAll();

        // Get quiz statistics
        $totalQuizzesTaken = $this->quizResultRepository->getTotalQuizzesTaken();
        $averageSuccessRate = $this->quizResultRepository->getAverageSuccessRate();
        $totalQuestions = $this->questionRepository->count([]);

        // Get chart data
        $chartData = $this->quizResultRepository->getChartData();

        // Create chart using Symfony UX Chart.js
        $chart = $this->createChart($chartData);

        // Get category-specific statistics
        $categoryStats = [];
        foreach ($categories as $category) {
            $categoryStats[$category->getId()] = [
                'totalQuizzes' => $this->quizResultRepository->getTotalQuizzesTaken($category->getId()),
                'successRate' => $this->quizResultRepository->getAverageSuccessRate($category->getId()),
            ];
        }

        // Get the most failed questions
        $mostFailedQuestions = $this->questionFailureRepository->findMostFailedQuestions(5);

        // Get the total count of failed questions
        $totalFailedQuestions = count($this->questionFailureRepository->findAllFailedQuestions());

        // Get the most failed categories
        $mostFailedCategories = $this->categoryFailureRepository->findMostFailedCategories(5);

        return $this->render('quiz/index.html.twig', [
            'categories' => $categories,
            'totalQuizzesTaken' => $totalQuizzesTaken,
            'averageSuccessRate' => $averageSuccessRate,
            'totalQuestions' => $totalQuestions,
            'chart' => $chart,
            'categoryStats' => $categoryStats,
            'mostFailedQuestions' => $mostFailedQuestions,
            'totalFailedQuestions' => $totalFailedQuestions,
            'mostFailedCategories' => $mostFailedCategories,
        ]);
    }

    #[Route('/category/{id}', name: 'quiz_category')]
    public function category(Category $category): Response
    {
        // Convert questions collection to array and shuffle
        $questions = $category->getQuestions()->toArray();
        shuffle($questions);

        return $this->render('quiz/quiz.html.twig', [
            'category' => $category,
            'questions' => $questions,
            'singleCategory' => true,
        ]);
    }

    #[Route('/all', name: 'quiz_all')]
    public function all(): Response
    {
        $categories = $this->categoryRepository->findAllWithQuestionsAndAnswers();

        $allQuestions = [];
        foreach ($categories as $category) {
            foreach ($category->getQuestions() as $question) {
                $allQuestions[] = $question;
            }
        }

        // Randomize the order of questions
        shuffle($allQuestions);

        $allQuestions = array_slice($allQuestions, 0, self::QUESTION_PER_QUIZ);

        return $this->render('quiz/quiz.html.twig', [
            'categories' => $categories,
            'questions' => $allQuestions,
            'singleCategory' => false,
        ]);
    }

    #[Route('/submit', name: 'quiz_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $answers = $request->request->all('answers');
        $categoryId = $request->request->get('category_id');
        $isFailedQuestionsQuiz = $request->request->has('is_failed_questions_quiz');
        $correctAnswers = 0;
        $totalQuestions = count($answers);

        foreach ($answers as $questionId => $answerIds) {
            $question = $this->questionRepository->find($questionId);
            if (!$question) {
                continue;
            }

            // Get all correct answers for this question
            $correctAnswerIds = [];
            $selectedCorrectCount = 0;
            $totalCorrectCount = 0;
            $incorrectlySelected = 0;

            // Count total correct answers and build array of correct answer IDs
            foreach ($question->getAnswers() as $answer) {
                if ($answer->isIsCorrect()) {
                    $correctAnswerIds[] = $answer->getId();
                    $totalCorrectCount++;
                }
            }

            // If no answers were selected, skip this question
            if (!is_array($answerIds) || empty($answerIds)) {
                continue;
            }

            // Count how many correct answers were selected
            foreach ($answerIds as $answerId) {
                $found = false;
                foreach ($question->getAnswers() as $answer) {
                    if ($answer->getId() == $answerId) {
                        $found = true;
                        if ($answer->isIsCorrect()) {
                            $selectedCorrectCount++;
                        } else {
                            $incorrectlySelected++;
                        }
                        break;
                    }
                }
            }

            // Question is correct if all correct answers were selected and no incorrect answers were selected
            if ($selectedCorrectCount == $totalCorrectCount && $incorrectlySelected == 0) {
                $correctAnswers++;
            } else {
                // Record this question as failed
                $questionFailure = $this->questionFailureRepository->findByQuestion($question);

                if (!$questionFailure) {
                    // Create a new record if it doesn't exist
                    $questionFailure = new \App\Entity\QuestionFailure();
                    $questionFailure->setQuestion($question);
                    $questionFailure->setFailureCount(1);
                } else {
                    // Increment the failure count
                    $questionFailure->incrementFailureCount();
                }

                $this->entityManager->persist($questionFailure);
            }
        }

        $score = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;

        // Get questions and correct answers for display and storage
        $questionsWithAnswers = [];
        foreach ($answers as $questionId => $selectedAnswerIds) {
            $question = $this->questionRepository->find($questionId);
            if (!$question) {
                continue;
            }

            $questionData = [
                'text' => $question->getText(),
                'answers' => [],
                'selectedAnswers' => $selectedAnswerIds
            ];

            foreach ($question->getAnswers() as $answer) {
                $questionData['answers'][] = [
                    'id' => $answer->getId(),
                    'text' => $answer->getText(),
                    'isCorrect' => $answer->isIsCorrect(),
                    'explanation' => $answer->getExplanation(),
                ];
            }

            $questionsWithAnswers[] = $questionData;
        }

        // Save the quiz result
        $quizResult = new QuizResult();
        $quizResult->setScore($score);
        $quizResult->setCorrectAnswers($correctAnswers);
        $quizResult->setTotalQuestions($totalQuestions);
        $quizResult->setQuestionsData($questionsWithAnswers);

        // Set category if it's a single category quiz
        $category = null;
        if ($categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if ($category) {
                $quizResult->setCategory($category);
            }
        }

        $this->entityManager->persist($quizResult);

        // Track category failures if score is below 60%
        if ($category && $score < 60) {
            $categoryFailure = $this->categoryFailureRepository->findByCategory($category);

            if (!$categoryFailure) {
                // Create a new record if it doesn't exist
                $categoryFailure = new \App\Entity\CategoryFailure();
                $categoryFailure->setCategory($category);
                $categoryFailure->setFailureCount(1);
            } else {
                // Increment the failure count
                $categoryFailure->incrementFailureCount();
            }

            $this->entityManager->persist($categoryFailure);
        }

        $this->entityManager->flush();

        // Redirect to the history detail page for this quiz result
        return $this->redirectToRoute('quiz_history_detail', ['id' => $quizResult->getId()]);
    }


    /**
     * Create a Chart object for quiz results
     *
     * @param array $chartData The chart data from repository
     * @return Chart The configured chart
     */
    private function createChart(array $chartData): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        // Prepare data
        $labels = [];
        $scores = [];

        foreach ($chartData as $item) {
            $labels[] = $item['createdAt'] instanceof \DateTimeInterface ? $item['createdAt']->format('Y-m-d') : $item['createdAt'];
            $scores[] = $item['score'];
        }

        // Set chart data
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Quiz Score (%)',
                    'backgroundColor' => 'rgba(255, 99, 132, .4)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $scores,
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 2,
                ],
            ],
        ]);

        // Set chart options
        $chart->setOptions([
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => 'Score (%)'
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Date'
                    ]
                ]
            ],
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Quiz Performance Trend'
                ]
            ]
        ]);

        return $chart;
    }

    #[Route('/chart-data/{categoryId}', name: 'quiz_chart_data', methods: ['GET'])]
    public function getChartData(?int $categoryId = null): Response
    {
        // Get chart data for the specified category (or all categories if null)
        $chartData = $this->quizResultRepository->getChartData($categoryId);

        // Create chart using Symfony UX Chart.js
        $chart = $this->createChart($chartData);

        // Return the chart data as JSON
        return $this->json([
            'chart' => $chart->getOptions(),
            'data' => $chart->getData(),
        ]);
    }

    #[Route('/failed-questions', name: 'quiz_failed_questions')]
    public function failedQuestions(): Response
    {
        // Get all failed questions
        $questionFailures = $this->questionFailureRepository->findAllFailedQuestions();

        // Extract questions from question failures
        $questions = [];
        foreach ($questionFailures as $questionFailure) {
            $questions[] = $questionFailure->getQuestion();
        }

        // If no failed questions, redirect to index with a message
        if (empty($questions)) {
            $this->addFlash('info', 'You have no failed questions yet. Take some quizzes first!');
            return $this->redirectToRoute('quiz_index');
        }

        return $this->render('quiz/quiz.html.twig', [
            'questions' => $questions,
            'singleCategory' => false,
            'isFailedQuestionsQuiz' => true,
            'title' => 'Failed Questions Quiz'
        ]);
    }

    #[Route('/history', name: 'quiz_history')]
    public function history(): Response
    {
        // Get all quiz results ordered by date (newest first)
        $quizResults = $this->quizResultRepository->findAllOrderedByDate();

        // Get categories for filter dropdown
        $categories = $this->categoryRepository->findAll();

        // Create chart for overall performance
        $chartData = $this->quizResultRepository->getChartData();
        $chart = $this->createChart($chartData);

        return $this->render('quiz/history.html.twig', [
            'quizResults' => $quizResults,
            'categories' => $categories,
            'chart' => $chart,
            'totalQuizzesTaken' => count($quizResults),
            'averageSuccessRate' => $this->quizResultRepository->getAverageSuccessRate(),
        ]);
    }

    #[Route('/history/{id}', name: 'quiz_history_detail')]
    public function historyDetail(QuizResult $quizResult): Response
    {
        // Get the questions and answers data for this quiz result
        $questionsWithAnswers = $quizResult->getQuestionsData();

        // Get categories for filter dropdown
        $categories = $this->categoryRepository->findAll();

        // Get category stats if available
        $categoryStats = null;
        if ($quizResult->getCategory()) {
            $categoryStats = [
                'totalQuizzes' => $this->quizResultRepository->getTotalQuizzesTaken($quizResult->getCategory()->getId()),
                'successRate' => $this->quizResultRepository->getAverageSuccessRate($quizResult->getCategory()->getId()),
                'name' => $quizResult->getCategory()->getName()
            ];
        }

        // Get overall success rate
        $overallSuccessRate = $this->quizResultRepository->getAverageSuccessRate();

        return $this->render('quiz/result.html.twig', [
            'score' => $quizResult->getScore(),
            'correctAnswers' => $quizResult->getCorrectAnswers(),
            'totalQuestions' => $quizResult->getTotalQuestions(),
            'quizResult' => $quizResult,
            'categoryStats' => $categoryStats,
            'overallSuccessRate' => $overallSuccessRate,
            'categories' => $categories,
            'questionsWithAnswers' => $questionsWithAnswers,
            'isHistoryDetail' => true,
        ]);
    }
}
