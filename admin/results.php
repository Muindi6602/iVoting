<?php
require_once '../includes/connection.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$election_id = $_GET['election_id'] ?? 0;

// Get election details
$stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
$stmt->execute([$election_id]);
$election = $stmt->fetch();

if (!$election) {
    header('Location: elections.php');
    exit;
}

// Get results
$results = getVoteResults($election_id);

// Check if election has ended
$now = new DateTime();
$end_date = new DateTime($election['end_date']);
$is_completed = $now > $end_date;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/sweetalert2.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Election Results</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="elections.php" class="btn btn-sm btn-outline-secondary">
                                Back to Elections
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                Print Results
                            </button>
                        </div>
                    </div>
                </div>
                
                <h3><?= htmlspecialchars($election['title']) ?></h3>
                <p class="text-muted">
                    <?= date('M j, Y H:i', strtotime($election['start_date'])) ?> to 
                    <?= date('M j, Y H:i', strtotime($election['end_date'])) ?>
                </p>
                
                <?php if ($is_completed): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> This election has ended. Below are the final results.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> This election is still ongoing. Results will update in real-time.
                        <span id="lastUpdated" class="small text-muted"></span>
                    </div>
                <?php endif; ?>
                
                <div id="resultsContainer">
                    <?php foreach ($results as $position_data): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4><?= htmlspecialchars($position_data['position']['title']) ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Candidate</th>
                                                <th>Votes</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($position_data['candidates'] as $candidate): 
                                                $is_winner = $is_completed && 
                                                    $candidate['vote_count'] === max(array_column($position_data['candidates'], 'vote_count')) &&
                                                    $candidate['vote_count'] > 0;
                                            ?>
                                                <tr class="<?= $is_winner ? 'table-success' : '' ?>">
                                                    <td>
                                                        <?= htmlspecialchars($candidate['name']) ?>
                                                        <?php if ($is_winner): ?>
                                                            <span class="badge bg-primary ms-2">
                                                                <i class="fas fa-trophy"></i> Winner
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $candidate['vote_count'] ?></td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar" 
                                                                 role="progressbar" 
                                                                 style="width: <?= $candidate['percentage'] ?>%" 
                                                                 aria-valuenow="<?= $candidate['percentage'] ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                                <?= $candidate['percentage'] ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="chart-container" style="position: relative; height:300px;">
                                    <canvas id="chart-<?= $position_data['position']['id'] ?>"></canvas>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sweetalert2.min.js"></script>
    <script src="../assets/js/chart.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <script>
    $(document).ready(function() {
        // Update last updated time
        function updateLastUpdated() {
            const now = new Date();
            $('#lastUpdated').text('Last updated: ' + now.toLocaleTimeString());
        }
        
        updateLastUpdated();
        
        // Render charts
        function renderCharts() {
            <?php foreach ($results as $position_data): ?>
                const ctx<?= $position_data['position']['id'] ?> = document.getElementById('chart-<?= $position_data['position']['id'] ?>').getContext('2d');
                const labels<?= $position_data['position']['id'] ?> = <?= json_encode(array_column($position_data['candidates'], 'name')) ?>;
                const votes<?= $position_data['position']['id'] ?> = <?= json_encode(array_column($position_data['candidates'], 'vote_count')) ?>;
                const backgroundColors<?= $position_data['position']['id'] ?> = [
                    <?php foreach ($position_data['candidates'] as $i => $candidate): 
                        $is_winner = $is_completed && 
                            $candidate['vote_count'] === max(array_column($position_data['candidates'], 'vote_count')) &&
                            $candidate['vote_count'] > 0;
                    ?>
                        <?= $is_winner ? "'rgba(54, 162, 235, 0.7)'" : "'rgba(255, 99, 132, 0.7)'" ?>,
                    <?php endforeach; ?>
                ];
                
                new Chart(ctx<?= $position_data['position']['id'] ?>, {
                    type: 'bar',
                    data: {
                        labels: labels<?= $position_data['position']['id'] ?>,
                        datasets: [{
                            label: 'Votes',
                            data: votes<?= $position_data['position']['id'] ?>,
                            backgroundColor: backgroundColors<?= $position_data['position']['id'] ?>,
                            borderColor: backgroundColors<?= $position_data['position']['id'] ?>.map(c => c.replace('0.7', '1')),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            <?php if ($is_completed): ?>
                            title: {
                                display: true,
                                text: 'Final Results'
                            }
                            <?php endif; ?>
                        }
                    }
                });
            <?php endforeach; ?>
        }
        
        renderCharts();
        
        // Auto-refresh if election is ongoing
        <?php if (!$is_completed): ?>
            setInterval(function() {
                $.ajax({
                    url: '../api/results.php',
                    method: 'GET',
                    data: { election_id: <?= $election_id ?> },
                    success: function(response) {
                        if (response.success) {
                            $('#resultsContainer').html(response.html);
                            renderCharts();
                            updateLastUpdated();
                        }
                    }
                });
            }, 5000);
        <?php endif; ?>
    });
    </script>
</body>
</html>