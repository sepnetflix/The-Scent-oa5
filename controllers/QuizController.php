<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Quiz.php';
require_once __DIR__ . '/../models/Product.php';

class QuizController extends BaseController {
    private $quizModel;
    private $pdo;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->quizModel = new Quiz($pdo);
        $this->pdo = $pdo;
    }
    
    public function showQuiz() {
        try {
            $questions = $this->quizModel->getQuestions();
            require_once __DIR__ . '/../views/quiz.php';
        } catch (Exception $e) {
            error_log("Error loading quiz questions: " . $e->getMessage());
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'Failed to load quiz questions'], 500);
            } else {
                $this->setFlashMessage('Failed to load quiz questions. Please try again.', 'error');
                $this->redirect('home');
            }
        }
    }

    public function handleQuizSubmission() {
        try {
            $this->validateCSRF();
            
            // Validate and sanitize answers
            $answers = [];
            foreach ($_POST as $question => $answer) {
                if (strpos($question, 'q_') === 0) {
                    $questionId = substr($question, 2);
                    $answers[$questionId] = $this->validateInput($answer, 'string');
                }
            }
            
            if (empty($answers)) {
                $this->setFlashMessage('Please answer all questions to get your recommendations.', 'error');
                $this->redirect('quiz', ['error' => 'missing_answers']);
            }
            
            $this->beginTransaction();
            
            // Get personalized recommendations
            $recommendations = $this->quizModel->getRecommendations($answers);
            
            // Save quiz results if user is logged in
            $currentUser = $this->getCurrentUser();
            if ($currentUser) {
                $this->quizModel->saveQuizResult($currentUser['id'], $answers, $recommendations);
            }
            
            $this->commit();
            
            $_SESSION['quiz_recommendations'] = $recommendations;
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'recommendations' => $recommendations
                ]);
            } else {
                $this->redirect('quiz_results');
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error processing quiz submission: " . $e->getMessage());
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'Failed to process quiz submission'], 500);
            } else {
                $this->setFlashMessage('An error occurred. Please try again.', 'error');
                $this->redirect('quiz');
            }
        }
    }
    
    public function showResults() {
        if (!isset($_SESSION['quiz_recommendations'])) {
            $this->redirect('quiz');
        }
        
        $recommendations = $_SESSION['quiz_recommendations'];
        require_once __DIR__ . '/../views/quiz_results.php';
        
        // Clear recommendations after showing them
        unset($_SESSION['quiz_recommendations']);
    }
    
    public function getAnalytics() {
        try {
            $this->requireAdmin();
            
            // Get time range from request
            $timeRange = $this->validateInput($_GET['range'] ?? 'all', 'string');
            if (!in_array($timeRange, ['7', '30', '90', 'all'])) {
                throw new Exception('Invalid time range');
            }
            
            $data = $this->quizModel->getDetailedAnalytics($timeRange);
            
            if ($this->isAjaxRequest()) {
                return $this->jsonResponse([
                    'success' => true,
                    'data' => $data
                ]);
            }
            
            // For non-AJAX requests, render the analytics view
            return $this->renderView('admin/quiz_analytics', [
                'pageTitle' => 'Quiz Analytics - Admin Dashboard',
                'analytics' => $data
            ]);
            
        } catch (Exception $e) {
            error_log("Quiz analytics error: " . $e->getMessage());
            
            if ($this->isAjaxRequest()) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Failed to retrieve quiz analytics'
                ], 500);
            }
            
            $this->setFlashMessage('Failed to load analytics. Please try again.', 'error');
            return $this->redirect('admin/dashboard');
        }
    }
    
    public function getPersonalizedRecommendations($userId = null) {
        try {
            if (!$userId) {
                $userId = $this->getUserId();
            }
            
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            
            $recommendations = $this->quizModel->getPersonalizedRecommendations($userId);
            $preferences = $this->quizModel->getUserPreferenceHistory($userId);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'preferences' => $preferences
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Personalization error: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to get personalized recommendations'
            ], 500);
        }
    }
    
    public function processQuiz() {
        try {
            $startTime = isset($_SESSION['quiz_start_time']) ? $_SESSION['quiz_start_time'] : time();
            $completionTime = time() - $startTime;
            
            $answers = [];
            foreach ($_POST as $question => $answer) {
                if (strpos($question, 'q_') === 0) {
                    $answers[substr($question, 2)] = $this->validateInput($answer, 'string');
                }
            }
            
            if (empty($answers)) {
                throw new Exception('Please answer all questions');
            }
            
            $this->beginTransaction();
            
            // Get personalized recommendations
            $recommendations = $this->quizModel->getRecommendations($answers);
            
            // Save quiz results if user is logged in
            $userId = $this->getUserId();
            $sessionId = session_id();
            $browserInfo = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            if ($userId) {
                $this->quizModel->saveQuizResult($userId, $answers, $recommendations, [
                    'session_id' => $sessionId,
                    'browser_info' => $browserInfo,
                    'completion_time' => $completionTime
                ]);
            }
            
            $this->commit();
            
            // Store recommendations in session for non-logged in users
            $_SESSION['quiz_recommendations'] = $recommendations;
            
            if ($this->isAjaxRequest()) {
                return $this->jsonResponse([
                    'success' => true,
                    'recommendations' => $recommendations
                ]);
            }
            
            return $this->redirect('quiz_results');
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Quiz processing error: " . $e->getMessage());
            
            if ($this->isAjaxRequest()) {
                return $this->jsonResponse([
                    'success' => false, 
                    'error' => $e->getMessage()
                ], 500);
            }
            
            $this->setFlashMessage($e->getMessage(), 'error');
            return $this->redirect('quiz');
        }
    }
    
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function getQuizHistory() {
        $this->requireLogin();
        
        try {
            $userId = $this->getUserId();
            $history = $this->quizModel->getUserQuizHistory($userId);
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'data' => $history
                ]);
            } else {
                return $history;
            }
            
        } catch (Exception $e) {
            error_log("Error retrieving quiz history: " . $e->getMessage());
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Failed to retrieve quiz history'
                ], 500);
            } else {
                throw $e;
            }
        }
    }

    public function handleQuiz() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $mood = $_POST['mood'] ?? null;
            if (!$mood) {
                die("Please select a mood.");
            }

            // Map mood to product attribute criteria
            $moodEffectMap = [
                'relaxation' => 'calming',
                'energy' => 'energizing',
                'focus' => 'focusing',
                'balance' => 'balancing'
            ];

            $moodEffect = $moodEffectMap[$mood] ?? 'calming';
            
            // Get matching products based on attributes
            $stmt = $this->pdo->prepare("
                SELECT p.* 
                FROM products p
                JOIN product_attributes pa ON p.id = pa.product_id
                WHERE pa.mood_effect = ?
                ORDER BY RAND()
                LIMIT 3
            ");
            $stmt->execute([$moodEffect]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Store quiz result
            session_start();
            $userId = $_SESSION['user']['id'] ?? null;
            $email = $_SESSION['user']['email'] ?? null;
            
            $answers = json_encode(['mood' => $mood]);
            $recommendations = json_encode(array_column($products, 'id'));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO quiz_results 
                (user_id, email, answers, recommendations) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $email, $answers, $recommendations]);

            // Show results page
            include __DIR__ . '/../views/quiz_results.php';
        } else {
            include __DIR__ . '/../views/quiz.php';
        }
    }
}