<?php 
$section = 'quiz_analytics';
require_once __DIR__ . '/../layout/admin_header.php'; 
?>
<body class="page-admin-quiz-analytics">
<section class="admin-section">
    <div class="container">
        <div class="admin-container" data-aos="fade-up">
            <div class="admin-header">
                <h1>Quiz Analytics Dashboard</h1>
                <div class="date-filter">
                    <select id="timeRange" onchange="updateAnalytics()">
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 3 Months</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="analytics-grid">
                <div class="stat-card" data-aos="fade-up">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Participants</h3>
                        <p class="stat-value" id="totalParticipants">Loading...</p>
                    </div>
                </div>
                
                <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Conversion Rate</h3>
                        <p class="stat-value" id="conversionRate">Loading...</p>
                    </div>
                </div>
                
                <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Avg. Completion Time</h3>
                        <p class="stat-value" id="avgCompletionTime">Loading...</p>
                    </div>
                </div>
            </div>

            <!-- Preferences Analysis -->
            <div class="analytics-row">
                <div class="chart-container" data-aos="fade-up">
                    <h2>Popular Scent Types</h2>
                    <canvas id="scentChart"></canvas>
                </div>
                
                <div class="chart-container" data-aos="fade-up">
                    <h2>Mood Effects Distribution</h2>
                    <canvas id="moodChart"></canvas>
                </div>
            </div>

            <!-- Daily Quiz Completions -->
            <div class="analytics-row">
                <div class="chart-container full-width" data-aos="fade-up">
                    <h2>Daily Quiz Completions</h2>
                    <canvas id="completionsChart"></canvas>
                </div>
            </div>

            <!-- Top Product Recommendations -->
            <div class="recommendations-table" data-aos="fade-up">
                <h2>Most Recommended Products</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Times Recommended</th>
                                <th>Conversion Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recommendationsTable">
                            <tr>
                                <td colspan="5">Loading data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/admin_footer.php'; ?>