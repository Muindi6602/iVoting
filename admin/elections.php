<?php
require_once '../includes/connection.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set timezone to Nairobi
date_default_timezone_set('Africa/Nairobi');

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login');
    exit;
}

$action = $_GET['action'] ?? 'list';
$election_id = $_GET['id'] ?? 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        // Convert to Nairobi timezone before saving to database
        $start_datetime = new DateTime($start_date, new DateTimeZone('Africa/Nairobi'));
        $end_datetime = new DateTime($end_date, new DateTimeZone('Africa/Nairobi'));
        
        $start_date_db = $start_datetime->format('Y-m-d H:i:s');
        $end_date_db = $end_datetime->format('Y-m-d H:i:s');
        
        // Validate
        if (empty($title) || empty($start_date) || empty($end_date)) {
            $error = 'Title, start date, and end date are required';
        } elseif ($start_datetime >= $end_datetime) {
            $error = 'End date must be after start date';
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $pdo->prepare("
                        INSERT INTO elections (title, description, start_date, end_date, status) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    // Set initial status based on current time
                    $initial_status = getElectionStatus($start_date_db, $end_date_db);
                    $stmt->execute([$title, $description, $start_date_db, $end_date_db, $initial_status]);
                    $success = 'Election created successfully';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE elections 
                        SET title = ?, description = ?, start_date = ?, end_date = ?, status = ?
                        WHERE id = ?
                    ");
                    // Update status based on current time
                    $updated_status = getElectionStatus($start_date_db, $end_date_db);
                    $stmt->execute([$title, $description, $start_date_db, $end_date_db, $updated_status, $election_id]);
                    $success = 'Election updated successfully';
                }
                
                // Redirect to list view after successful save
                header('Location: elections.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM elections WHERE id = ?");
            $stmt->execute([$election_id]);
            $success = 'Election deleted successfully';
            
            // Redirect to list view after successful deletion
            header('Location: elections.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Function to update all election statuses
function updateAllElectionStatuses() {
    global $pdo;
    
    // Get current Nairobi time in UTC format for database comparison
    $now = (new DateTime('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d H:i:s');
    
    // Update upcoming elections that have started
    $pdo->exec("
        UPDATE elections 
        SET status = 'active' 
        WHERE status = 'upcoming' AND start_date <= '{$now}'
    ");
    
    // Update active elections that have ended
    $pdo->exec("
        UPDATE elections 
        SET status = 'completed' 
        WHERE status = 'active' AND end_date <= '{$now}'
    ");
}

// Update all election statuses before processing
updateAllElectionStatuses();

// Get election data for edit
if ($action === 'edit' && $election_id) {
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch();
    
    if (!$election) {
        header('Location: elections');
        exit;
    }
    
    // Convert stored UTC dates to Nairobi time for display
    $start_datetime = new DateTime($election['start_date'], new DateTimeZone('UTC'));
    $start_datetime->setTimezone(new DateTimeZone('Africa/Nairobi'));
    $election['start_date'] = $start_datetime->format('Y-m-d\TH:i');
    
    $end_datetime = new DateTime($election['end_date'], new DateTimeZone('UTC'));
    $end_datetime->setTimezone(new DateTimeZone('Africa/Nairobi'));
    $election['end_date'] = $end_datetime->format('Y-m-d\TH:i');
}

// Get all elections for list view
$elections = [];
$stmt = $pdo->query("SELECT * FROM elections ORDER BY start_date DESC");
$elections = $stmt->fetchAll();

// Convert dates to Nairobi time for display
foreach ($elections as &$election) {
    $start_datetime = new DateTime($election['start_date'], new DateTimeZone('UTC'));
    $start_datetime->setTimezone(new DateTimeZone('Africa/Nairobi'));
    $election['start_date_display'] = $start_datetime->format('M j, Y H:i');
    
    $end_datetime = new DateTime($election['end_date'], new DateTimeZone('UTC'));
    $end_datetime->setTimezone(new DateTimeZone('Africa/Nairobi'));
    $election['end_date_display'] = $end_datetime->format('M j, Y H:i');
    
    // Get current status from database (already updated)
    $election['status'] = $election['status'];
}
unset($election); // Break the reference
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($action) ?> Election - Admin Dashboard</title>
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
                    <h1 class="h2"><?= ucfirst($action) ?> Election</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="elections.php" class="btn btn-sm btn-outline-secondary">
                            Back to List
                        </a>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <?php if ($action === 'list'): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Start Date (Nairobi)</th>
                                    <th>End Date (Nairobi)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($elections as $election): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($election['title']) ?></td>
                                        <td><?= htmlspecialchars($election['description']) ?></td>
                                        <td><?= $election['start_date_display'] ?></td>
                                        <td><?= $election['end_date_display'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $election['status'] === 'active' ? 'success' : 
                                                ($election['status'] === 'upcoming' ? 'warning' : 'secondary') 
                                            ?>">
                                                <?= ucfirst($election['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="elections.php?action=edit&id=<?= $election['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="positions.php?election_id=<?= $election['id'] ?>" class="btn btn-sm btn-info">Positions</a>
                                            <a href="results.php?election_id=<?= $election['id'] ?>" class="btn btn-sm btn-success">Results</a>
                                            <button class="btn btn-sm btn-danger delete-election" data-id="<?= $election['id'] ?>">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <a href="elections.php?action=create" class="btn btn-primary">Create New Election</a>
                <?php else: ?>
                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($election['title'] ?? '') ?>" required>
                            <div class="invalid-feedback">
                                Please provide a title for the election.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= 
                                htmlspecialchars($election['description'] ?? '') 
                            ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date (Nairobi Time)</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                                       value="<?= $election['start_date'] ?? '' ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a start date for the election.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date (Nairobi Time)</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" 
                                       value="<?= $election['end_date'] ?? '' ?>" required>
                                <div class="invalid-feedback">
                                    Please provide an end date for the election.
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Election</button>
                        <a href="elections.php" class="btn btn-secondary">Cancel</a>
                    </form>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <script>
    $(document).ready(function () {
        // Delete election with confirmation
        $(document).on('click', '.delete-election', function (e) {
            e.preventDefault();
            const electionId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../api/delete_election.php',
                        type: 'POST',
                        dataType: 'json',
                        data: { id: electionId },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function (xhr, status, error) {
                            Swal.fire(
                                'Error!',
                                'An error occurred while deleting the election.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        // Form validation
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
        })();
    });
    </script>
</body>
</html>