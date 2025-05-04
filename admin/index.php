<?php
require_once '../includes/connection.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login');
    exit;
}

$active_elections = getActiveElections();
$upcoming_elections = getUpcomingElections();
$completed_elections = getCompletedElections();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - eVoting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Active Elections</h5>
                                <p class="card-text display-4"><?= count($active_elections) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Upcoming Elections</h5>
                                <p class="card-text display-4"><?= count($upcoming_elections) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Completed Elections</h5>
                                <p class="card-text display-4"><?= count($completed_elections) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h4>Active Elections</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_elections as $election): ?>
                                <tr>
                                    <td><?= htmlspecialchars($election['title']) ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($election['start_date'])) ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($election['end_date'])) ?></td>
                                    <td>
                                        <a href="results.php?election_id=<?= $election['id'] ?>" class="btn btn-sm btn-info">View Results</a>
                                        <a href="elections.php?action=edit&id=<?= $election['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <br><br>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h4>Quick Actions</h4>
                        <div class="list-group">
                            <a href="elections.php?action=create" class="list-group-item list-group-item-action">Create New Election</a>
                            <a href="positions.php" class="list-group-item list-group-item-action">Manage Positions</a>
                            <a href="candidates.php" class="list-group-item list-group-item-action">Manage Candidates</a>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Recent Activity</h4>
        <button class="btn btn-sm btn-outline-secondary refresh-activity">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
    <div class="card">
        <div class="card-body">
            <div id="recentActivity">
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize activity loader
    loadRecentActivity();
    
    // Set up refresh button
    $('.refresh-activity').on('click', function() {
        loadRecentActivity();
    });
    
    // Auto-refresh every 30 seconds
    setInterval(loadRecentActivity, 30000);
});

function loadRecentActivity() {
    $.ajax({
        url: '../api/activity.php',
        method: 'GET',
        dataType: 'json',
        beforeSend: function() {
            $('#recentActivity').html(`
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
        },
        success: function(response) {
            if (response && response.success) {
                $('#recentActivity').hide().html(response.html).fadeIn(300);
                
                // Add hover effects
                $('.activity-list li').hover(
                    function() {
                        $(this).css('transform', 'translateX(5px)');
                        $(this).css('transition', 'transform 0.2s ease');
                    },
                    function() {
                        $(this).css('transform', 'translateX(0)');
                    }
                );
            } else {
                showActivityError(response?.message || 'Invalid response from server');
            }
        },
        error: function(xhr, status, error) {
            let errorMsg = 'Error loading activity';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMsg = response.message || errorMsg;
            } catch (e) {
                errorMsg = `${errorMsg}: ${error}`;
            }
            showActivityError(errorMsg);
        }
    });
}

function showActivityError(message) {
    $('#recentActivity').html(`
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            ${message}
            <button class="btn btn-sm btn-outline-danger float-end" onclick="loadRecentActivity()">
                <i class="fas fa-sync-alt"></i> Retry
            </button>
        </div>
    `);
}
</script>


                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>