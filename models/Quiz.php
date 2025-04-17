<?php
class Quiz {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getQuestions() {
        return [
            [
                'id' => 'mood',
                'question' => 'What are you looking for today?',
                'options' => [
                    'relaxation' => [
                        'label' => 'Relaxation',
                        'icon' => 'fa-spa',
                        'description' => 'Find calm and peace in your daily routine'
                    ],
                    'energy' => [
                        'label' => 'Energy',
                        'icon' => 'fa-bolt',
                        'description' => 'Boost your vitality and motivation'
                    ],
                    'focus' => [
                        'label' => 'Focus',
                        'icon' => 'fa-brain',
                        'description' => 'Enhance concentration and clarity'
                    ],
                    'balance' => [
                        'label' => 'Balance',
                        'icon' => 'fa-yin-yang',
                        'description' => 'Find harmony in body and mind'
                    ]
                ]
            ]
        ];
    }
    
    public function getRecommendations($answers) {
        try {
            $moodEffectMap = [
                'relaxation' => 'calming',
                'energy' => 'energizing',
                'focus' => 'focusing',
                'balance' => 'balancing'
            ];

            $mood = $answers['mood'] ?? 'relaxation';
            $moodEffect = $moodEffectMap[$mood] ?? 'calming';
            
            // Get matching products based on mood and attributes
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT p.*, pa.mood_effect, pa.scent_type, pa.intensity_level
                FROM products p
                JOIN product_attributes pa ON p.id = pa.product_id
                WHERE pa.mood_effect = ?
                ORDER BY RAND()
                LIMIT 3
            ");
            
            $stmt->execute([$moodEffect]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no exact matches, get featured products as fallback
            if (empty($products)) {
                $stmt = $this->pdo->prepare("
                    SELECT DISTINCT p.*, pa.mood_effect, pa.scent_type, pa.intensity_level
                    FROM products p
                    JOIN product_attributes pa ON p.id = pa.product_id
                    WHERE p.is_featured = 1
                    ORDER BY RAND()
                    LIMIT 3
                ");
                $stmt->execute();
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Add scent descriptions
            foreach ($products as &$product) {
                $product['scent_description'] = $this->getScentDescription($product['scent_type']);
                $product['mood_description'] = $this->getMoodDescription($product['mood_effect']);
            }
            
            return $products;
        } catch (PDOException $e) {
            error_log("Error getting recommendations: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function saveQuizResult($userId, $email, $answers, $recommendations) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO quiz_results 
                (user_id, email, answers, recommendations, created_at)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            return $stmt->execute([
                $userId,
                $email,
                json_encode($answers),
                json_encode($recommendations)
            ]);
        } catch (PDOException $e) {
            error_log("Error saving quiz result: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function getScentDescription($scentType) {
        $descriptions = [
            'floral' => 'Delicate and romantic floral notes that bring peace and harmony',
            'woody' => 'Rich, grounding woody scents that promote stability and strength',
            'citrus' => 'Bright, uplifting citrus notes that energize and refresh',
            'oriental' => 'Warm, exotic notes that create a sense of luxury and comfort',
            'fresh' => 'Clean, crisp scents that invigorate and purify'
        ];
        
        return $descriptions[$scentType] ?? '';
    }
    
    private function getMoodDescription($moodEffect) {
        $descriptions = [
            'calming' => 'Perfect for relaxation and stress relief',
            'energizing' => 'Ideal for boosting energy and motivation',
            'focusing' => 'Helps improve concentration and mental clarity',
            'balancing' => 'Promotes overall harmony and well-being'
        ];
        
        return $descriptions[$moodEffect] ?? '';
    }
    
    public function getAnalytics($timeRange = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_quizzes,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN user_id END) as registered_users,
                    COUNT(DISTINCT CASE WHEN user_id IS NULL THEN email END) as guest_users
                FROM quiz_results
                WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$timeRange]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting quiz analytics: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getPopularMoods($timeRange = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    JSON_UNQUOTE(JSON_EXTRACT(answers, '$.mood')) as mood,
                    COUNT(*) as count
                FROM quiz_results
                WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
                GROUP BY mood
                ORDER BY count DESC
            ");
            
            $stmt->execute([$timeRange]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting popular moods: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getPersonalizedRecommendations($userId, $limit = 3) {
        try {
            // Get user's previous quiz results
            $stmt = $this->pdo->prepare("
                SELECT answers, recommendations
                FROM quiz_results
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 5
            ");
            
            $stmt->execute([$userId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($history)) {
                // If no history, return featured products
                $stmt = $this->pdo->prepare("
                    SELECT p.*, pa.mood_effect, pa.scent_type
                    FROM products p
                    JOIN product_attributes pa ON p.id = pa.product_id
                    WHERE p.is_featured = 1
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Analyze preferences
            $moodCounts = [];
            $scentCounts = [];
            foreach ($history as $result) {
                $answers = json_decode($result['answers'], true);
                $recommendations = json_decode($result['recommendations'], true);
                
                if (isset($answers['mood'])) {
                    $moodCounts[$answers['mood']] = ($moodCounts[$answers['mood']] ?? 0) + 1;
                }
                
                // Get scent types from recommended products
                $stmt = $this->pdo->prepare("
                    SELECT scent_type
                    FROM product_attributes
                    WHERE product_id IN (" . implode(',', $recommendations) . ")
                ");
                $stmt->execute();
                $scents = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($scents as $scent) {
                    $scentCounts[$scent] = ($scentCounts[$scent] ?? 0) + 1;
                }
            }
            
            // Get preferred mood and scent
            arsort($moodCounts);
            arsort($scentCounts);
            $preferredMood = key($moodCounts);
            $preferredScent = key($scentCounts);
            
            // Get personalized recommendations
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT p.*, pa.mood_effect, pa.scent_type
                FROM products p
                JOIN product_attributes pa ON p.id = pa.product_id
                WHERE (pa.mood_effect = ? OR pa.scent_type = ?)
                AND p.id NOT IN (
                    SELECT JSON_UNQUOTE(JSON_EXTRACT(recommendations, '$[*]'))
                    FROM quiz_results
                    WHERE user_id = ?
                )
                ORDER BY RAND()
                LIMIT ?
            ");
            
            $stmt->execute([$preferredMood, $preferredScent, $userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting personalized recommendations: " . $e->getMessage());
            throw $e;
        }
    }
}