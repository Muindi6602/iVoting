<?php
require_once '../includes/connection.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$election_id = $_GET['election_id'] ?? 0;
$action = $_GET['action'] ?? 'list';
$position_id = $_GET['id'] ?? 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $max_votes = $_POST['max_votes'] ?? 1;
        
        // Validate
        if (empty($title)) {
            $error = 'Title is required';
        } elseif (!is_numeric($max_votes) || $max_votes < 1) {
            $error = 'Max votes must be at least 1';
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $pdo->prepare("
                        INSERT INTO positions (election_id, title, description, max_votes) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$election_id, $title, $description, $max_votes]);
                    $success = 'Position created successfully';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE positions 
                        SET title = ?, description = ?, max_votes = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $description, $max_votes, $position_id]);
                    $success = 'Position updated successfully';
                }
                
                // Redirect to list view after successful save
                header("Location: positions.php?election_id=$election_id");
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM positions WHERE id = ?");
            $stmt->execute([$position_id]);
            $success = 'Position deleted successfully';
            
            // Redirect to list view after successful deletion
            header("Location: positions.php?election_id=$election_id");
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get position data for edit
if ($action === 'edit' && $position_id) {
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE id = ?");
    $stmt->execute([$position_id]);
    $position = $stmt->fetch();
    
    if (!$position) {
        header("Location: positions.php?election_id=$election_id");
        exit;
    }
    
    $election_id = $position['election_id'];
}

// Get election details
$stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
$stmt->execute([$election_id]);
$election = $stmt->fetch();

if (!$election) {
    header('Location: elections.php');
    exit;
}

// Get positions for this election
$stmt = $pdo->prepare("SELECT * FROM positions WHERE election_id = ? ORDER BY title");
$stmt->execute([$election_id]);
$positions = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($action) ?> Position - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= ucfirst($action) ?> Position</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="elections.php" class="btn btn-sm btn-outline-secondary">
                                Back to Elections
                            </a>
                            <a href="positions.php?election_id=<?= $election_id ?>" class="btn btn-sm btn-outline-secondary">
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
                
                <h4>Election: <?= htmlspecialchars($election['title']) ?></h4>
                
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
                                    <th>Max Votes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($positions as $position): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($position['title']) ?></td>
                                        <td><?= htmlspecialchars($position['description']) ?></td>
                                        <td><?= $position['max_votes'] ?></td>
                                        <td>
                                            <a href="positions.php?action=edit&id=<?= $position['id'] ?>&election_id=<?= $election_id ?>" 
                                               class="btn btn-sm btn-warning">Edit</a>
                                            <a href="candidates.php?position_id=<?= $position['id'] ?>&election_id=<?= $election_id ?>" 
                                               class="btn btn-sm btn-info">Candidates</a>
                                               <button class="btn btn-sm btn-danger delete-position" 
        data-id="<?= $position['id'] ?>" 
        data-election-id="<?= $election_id ?>">Delete</button>

                                                    <script>
$(document).ready(function () {
    $(document).on('click', '.delete-position', function (e) {
        e.preventDefault();
        const positionId = $(this).data('id');
        const electionId = $(this).data('election-id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete the position and its candidates.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../api/delete_position.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { id: positionId },
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
                        console.error(xhr.responseText);
                        Swal.fire(
                            'Error!',
                            'Something went wrong while deleting the position.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <a href="positions.php?action=create&election_id=<?= $election_id ?>" class="btn btn-primary">
                        Add New Position
                    </a>
                <?php else: ?>
                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($position['title'] ?? '') ?>" required>
                            <div class="invalid-feedback">
                                Please provide a title for the position.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= 
                                htmlspecialchars($position['description'] ?? '') 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="max_votes" class="form-label">Max Votes Allowed</label>
                            <input type="number" class="form-control" id="max_votes" name="max_votes" 
                                   min="1" value="<?= $position['max_votes'] ?? 1 ?>" required>
                            <div class="invalid-feedback">
                                Please provide a valid number (minimum 1).
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Position</button>
                        <a href="positions.php?election_id=<?= $election_id ?>" class="btn btn-secondary">Cancel</a>
                    </form>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>    
    <script src="../assets/js/admin.js"></script>
</body>
</html>