<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';

// Date range filter
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Overall Statistics
$stats = [];

// Total complaints
$result = $conn->query("SELECT COUNT(*) as total FROM complaints");
$stats['total'] = $result->fetch_assoc()['total'];

// By status
$result = $conn->query("SELECT status, COUNT(*) as count FROM complaints GROUP BY status");
$stats['by_status'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['by_status'][$row['status']] = $row['count'];
}

// By priority
$result = $conn->query("SELECT priority, COUNT(*) as count FROM complaints GROUP BY priority");
$stats['by_priority'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['by_priority'][$row['priority']] = $row['count'];
}

// By category
$result = $conn->query("SELECT category, COUNT(*) as count FROM complaints GROUP BY category ORDER BY count DESC");
$stats['by_category'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['by_category'][$row['category']] = $row['count'];
}

// Monthly trend (last 12 months)
$result = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM complaints WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC");
$stats['monthly_trend'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['monthly_trend'][$row['month']] = $row['count'];
}

// Resolution rate
$resolved = $stats['by_status']['Resolved'] ?? 0;
$stats['resolution_rate'] = $stats['total'] > 0 ? round(($resolved / $stats['total']) * 100, 1) : 0;

// Average resolution time (days)
$result = $conn->query("SELECT AVG(DATEDIFF(resolved_at, created_at)) as avg_days FROM complaints WHERE resolved_at IS NOT NULL");
$avg = $result->fetch_assoc()['avg_days'];
$stats['avg_resolution_days'] = $avg ? round($avg, 1) : 'N/A';

// Complaints in date range
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM complaints WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->bind_param('ss', $date_from, $date_to);
$stmt->execute();
$stats['in_range'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Top complainants
$result = $conn->query("SELECT u.full_name, COUNT(c.id) as count FROM complaints c JOIN users u ON c.user_id = u.id GROUP BY c.user_id ORDER BY count DESC LIMIT 5");
$stats['top_users'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['top_users'][] = $row;
}

// Recent activity (last 7 days vs previous 7 days)
$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['last_7_days'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['prev_7_days'] = $result->fetch_assoc()['count'];

// Prepare chart data as JSON
$chart_status = json_encode($stats['by_status']);
$chart_priority = json_encode($stats['by_priority']);
$chart_category = json_encode($stats['by_category']);
$chart_monthly = json_encode($stats['monthly_trend']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Complaint Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="d-flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="flex-grow-1">
            <?php include 'includes/topbar.php'; ?>
            <main class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0"><i class="bi bi-graph-up me-2"></i>Reports & Analytics</h4>
                    <form class="d-flex gap-2 align-items-center" method="GET">
                        <label class="form-label mb-0 small text-muted">From:</label>
                        <input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        <label class="form-label mb-0 small text-muted">To:</label>
                        <input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    </form>
                </div>

                <!-- KPI Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="display-6 fw-bold text-primary"><?= $stats['total'] ?></div>
                                <div class="text-muted small">Total Complaints</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="display-6 fw-bold text-success"><?= $stats['resolution_rate'] ?>%</div>
                                <div class="text-muted small">Resolution Rate</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="display-6 fw-bold text-info"><?= $stats['avg_resolution_days'] ?></div>
                                <div class="text-muted small">Avg. Resolution (Days)</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="display-6 fw-bold text-warning"><?= $stats['in_range'] ?></div>
                                <div class="text-muted small">In Selected Range</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Indicator -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-semibold"><i class="bi bi-activity me-2"></i>Weekly Activity</h6>
                                <div class="d-flex align-items-center gap-3 mt-3">
                                    <div>
                                        <span class="fs-4 fw-bold"><?= $stats['last_7_days'] ?></span>
                                        <span class="text-muted small">complaints this week</span>
                                    </div>
                                    <?php
                                    $diff = $stats['last_7_days'] - $stats['prev_7_days'];
                                    $arrow = $diff >= 0 ? 'arrow-up' : 'arrow-down';
                                    $color = $diff >= 0 ? 'danger' : 'success';
                                    ?>
                                    <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>">
                                        <i class="bi bi-<?= $arrow ?>"></i> <?= abs($diff) ?> vs last week
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-semibold"><i class="bi bi-people me-2"></i>Top Complainants</h6>
                                <div class="mt-3">
                                    <?php if (!empty($stats['top_users'])): ?>
                                        <?php foreach ($stats['top_users'] as $i => $user): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small"><?= ($i+1) . '. ' . htmlspecialchars($user['full_name']) ?></span>
                                            <span class="badge bg-primary rounded-pill"><?= $user['count'] ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted small mb-0">No data available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3"><i class="bi bi-pie-chart me-2"></i>By Status</h6>
                                <canvas id="statusChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3"><i class="bi bi-exclamation-triangle me-2"></i>By Priority</h6>
                                <canvas id="priorityChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3"><i class="bi bi-bar-chart-line me-2"></i>Monthly Trend (Last 12 Months)</h6>
                                <canvas id="trendChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 3 -->
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3"><i class="bi bi-tags me-2"></i>By Category</h6>
                                <canvas id="categoryChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Chart data from PHP
    const statusData = <?= $chart_status ?>;
    const priorityData = <?= $chart_priority ?>;
    const categoryData = <?= $chart_category ?>;
    const monthlyData = <?= $chart_monthly ?>;

    // Status Doughnut Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#dc3545'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Priority Doughnut Chart
    new Chart(document.getElementById('priorityChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(priorityData),
            datasets: [{
                data: Object.values(priorityData),
                backgroundColor: ['#6c757d', '#0dcaf0', '#ffc107', '#dc3545'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Monthly Trend Line Chart
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: Object.keys(monthlyData).map(m => {
                const [y, mo] = m.split('-');
                const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                return months[parseInt(mo)-1] + ' ' + y;
            }),
            datasets: [{
                label: 'Complaints',
                data: Object.values(monthlyData),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#0d6efd',
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Category Horizontal Bar Chart
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(categoryData),
            datasets: [{
                label: 'Complaints',
                data: Object.values(categoryData),
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: '#0d6efd',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
    </script>
</body>
</html>