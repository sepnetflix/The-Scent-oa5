-- Create quiz_results table
CREATE TABLE IF NOT EXISTS quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    email VARCHAR(150) NULL,
    answers TEXT,
    recommendations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create product_attributes table if not exists
CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    scent_type ENUM('floral', 'woody', 'citrus', 'oriental', 'fresh') NOT NULL,
    mood_effect ENUM('calming', 'energizing', 'focusing', 'balancing') NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Add indexes to improve query performance

-- Quiz results indexes
ALTER TABLE quiz_results ADD INDEX idx_created_at (created_at);
ALTER TABLE quiz_results ADD INDEX idx_user_timestamp (user_id, created_at);

-- Product attributes indexes
ALTER TABLE product_attributes ADD INDEX idx_scent_type (scent_type);
ALTER TABLE product_attributes ADD INDEX idx_mood_effect (mood_effect);

-- Combined index for recommendations query
ALTER TABLE product_attributes ADD INDEX idx_scent_mood (scent_type, mood_effect);

-- Add missing indexes for performance optimization

-- Indexes for quiz results
CREATE INDEX IF NOT EXISTS idx_quiz_results_user_created ON quiz_results(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_quiz_results_email ON quiz_results(email);

-- Indexes for product attributes
CREATE INDEX IF NOT EXISTS idx_product_scent_mood ON product_attributes(scent_type, mood_effect);
CREATE INDEX IF NOT EXISTS idx_product_intensity ON product_attributes(intensity_level);

-- Indexes for improved product search
CREATE INDEX IF NOT EXISTS idx_products_featured ON products(is_featured);
CREATE INDEX IF NOT EXISTS idx_products_category_price ON products(category_id, price);

-- Add index for newsletter subscriptions date
CREATE INDEX IF NOT EXISTS idx_newsletter_date ON newsletter_subscribers(subscribed_at);

-- Add indexes for user preferences and analytics queries

-- Indexes for user preferences
CREATE INDEX IF NOT EXISTS idx_user_preferences_score ON user_preferences(user_id, preference_score DESC);
CREATE INDEX IF NOT EXISTS idx_preferences_updated ON user_preferences(last_updated);

-- Compound indexes for analytics
CREATE INDEX IF NOT EXISTS idx_quiz_analytics ON quiz_results(user_id, created_at, completion_time);
CREATE INDEX IF NOT EXISTS idx_preferences_type_effect ON user_preferences(scent_type, mood_effect, preference_score);

-- Index for recommendation queries
CREATE INDEX IF NOT EXISTS idx_product_stock_featured ON products(stock_quantity, is_featured, created_at);