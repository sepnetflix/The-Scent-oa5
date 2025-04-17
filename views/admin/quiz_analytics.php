<?php 
$section = 'quiz_analytics';
require_once __DIR__ . '/../layout/admin_header.php'; 
?>

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

<style>
.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
}

.stat-icon {
    width: 48px;
    height: 48px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.stat-icon i {
    font-size: 1.5rem;
    color: #4a5568;
}

.stat-content h3 {
    font-size: 0.875rem;
    color: #4a5568;
    margin: 0;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2d3748;
    margin: 0.25rem 0 0;
}

.analytics-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.chart-container {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chart-container.full-width {
    grid-column: 1 / -1;
}

.chart-container h2 {
    font-size: 1.25rem;
    margin-bottom: 1rem;
}

.recommendations-table {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table-responsive {
    overflow-x: auto;
}

.admin-table th {
    white-space: nowrap;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let charts = {};

function updateAnalytics() {
    const timeRange = document.getElementById('timeRange').value;
    fetchAnalyticsData(timeRange);
}

async function fetchAnalyticsData(timeRange) {
    try {
        const response = await fetch(`index.php?page=admin&action=quiz_analytics&range=${timeRange}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();
        if (!data.success) throw new Error(data.error || 'Failed to fetch analytics');
        
        updateStatCards(data.data.statistics);
        updateCharts(data.data.preferences);
        updateRecommendationsTable(data.data.recommendations);
        
    } catch (error) {
        console.error('Error fetching analytics:', error);
        alert('Failed to load analytics data. Please try again.');
    }
}

function updateStatCards(stats) {
    document.getElementById('totalParticipants').textContent = stats.total_quizzes;
    document.getElementById('conversionRate').textContent = `${stats.conversion_rate}%`;
    document.getElementById('avgCompletionTime').textContent = `${stats.avg_completion_time}s`;
}

function updateCharts(preferences) {
    // Scent Types Chart
    if (charts.scent) charts.scent.destroy();
    charts.scent = new Chart(document.getElementById('scentChart'), {
        type: 'doughnut',
        data: {
            labels: preferences.scent_types.map(p => p.type),
            datasets: [{
                data: preferences.scent_types.map(p => p.count),
                backgroundColor: [
                    '#4299e1',
                    '#48bb78',
                    '#ed8936',
                    '#9f7aea',
                    '#f56565'
                ]
            }]
        }
    });
    
    // Mood Effects Chart
    if (charts.mood) charts.mood.destroy();
    charts.mood = new Chart(document.getElementById('moodChart'), {
        type: 'bar',
        data: {
            labels: preferences.mood_effects.map(p => p.effect),
            datasets: [{
                data: preferences.mood_effects.map(p => p.count),
                backgroundColor: '#4299e1'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Daily Completions Chart
    if (charts.completions) charts.completions.destroy();
    charts.completions = new Chart(document.getElementById('completionsChart'), {
        type: 'line',
        data: {
            labels: preferences.daily_completions.map(d => d.date),
            datasets: [{
                label: 'Completions',
                data: preferences.daily_completions.map(d => d.count),
                borderColor: '#4299e1',
                tension: 0.1
            }]
        }
    });
}

function updateRecommendationsTable(recommendations) {
    const tbody = document.getElementById('recommendationsTable');
    tbody.innerHTML = recommendations.map(product => `
        <tr>
            <td>${product.name}</td>
            <td>${product.category}</td>
            <td>${product.recommendation_count}</td>
            <td>${product.conversion_rate}%</td>
            <td>
                <a href="index.php?page=admin&action=products&id=${product.id}" 
                   class="btn-icon" title="View Product">
                    <i class="fas fa-eye"></i>
                </a>
            </td>
        </tr>
    `).join('');
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    updateAnalytics();
});
</script>

<?php require_once __DIR__ . '/../layout/admin_footer.php'; ?>