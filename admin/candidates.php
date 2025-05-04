<?php
require_once '../includes/connection.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login');
    exit;
}

$election_id = $_GET['election_id'] ?? 0;
$position_id = $_GET['position_id'] ?? 0;
$action = $_GET['action'] ?? 'list';
$candidate_id = $_GET['id'] ?? 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $name = trim($_POST['name']);
        $bio = trim($_POST['bio']);
        $photo = $_FILES['photo'] ?? null;
        
        // Validate
        if (empty($name)) {
            $error = 'Name is required';
        } else {
            try {
                $photo_path = null;
                
                // Handle file upload
                if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../assets/images/candidates/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid('candidate_') . '.' . $file_ext;
                    $target_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($photo['tmp_name'], $target_path)) {
                        $photo_path = 'assets/images/candidates/' . $file_name;
                        
                        // Delete old photo if editing
                        if ($action === 'edit' && !empty($candidate['photo'])) {
                            @unlink('../' . $candidate['photo']);
                        }
                    }
                } elseif ($action === 'edit') {
                    // Keep existing photo if not uploading new one
                    $photo_path = $candidate['photo'] ?? null;
                }
                
                if ($action === 'create') {
                    $stmt = $pdo->prepare("
                        INSERT INTO candidates (position_id, name, bio, photo) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$position_id, $name, $bio, $photo_path]);
                    $success = 'Candidate created successfully';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE candidates 
                        SET name = ?, bio = ?, photo = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $bio, $photo_path, $candidate_id]);
                    $success = 'Candidate updated successfully';
                }
                
                // Redirect to list view after successful save
                header("Location: candidates.php?position_id=$position_id&election_id=$election_id");
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        try {
            // Get candidate to delete photo
            $stmt = $pdo->prepare("SELECT photo FROM candidates WHERE id = ?");
            $stmt->execute([$candidate_id]);
            $candidate = $stmt->fetch();
            
            if ($candidate && $candidate['photo']) {
                @unlink('../' . $candidate['photo']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
            $stmt->execute([$candidate_id]);
            $success = 'Candidate deleted successfully';
            
            // Redirect to list view after successful deletion
            header("Location: candidates.php?position_id=$position_id&election_id=$election_id");
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get candidate data for edit
if ($action === 'edit' && $candidate_id) {
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        header("Location: candidates.php?position_id=$position_id&election_id=$election_id");
        exit;
    }
    
    $position_id = $candidate['position_id'];
}

// Get position and election details
$stmt = $pdo->prepare("
    SELECT p.*, e.title as election_title 
    FROM positions p 
    JOIN elections e ON e.id = p.election_id 
    WHERE p.id = ?
");
$stmt->execute([$position_id]);
$position = $stmt->fetch();

if (!$position) {
    header('Location: positions.php');
    exit;
}

$election_id = $position['election_id'];

// Get candidates for this position
$stmt = $pdo->prepare("SELECT * FROM candidates WHERE position_id = ? ORDER BY name");
$stmt->execute([$position_id]);
$candidates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($action) ?> Candidate - Admin Dashboard</title>
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
                    <h1 class="h2"><?= ucfirst($action) ?> Candidate</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="elections.php" class="btn btn-sm btn-outline-secondary">
                                Back to Elections
                            </a>
                            <a href="positions.php?election_id=<?= $election_id ?>" class="btn btn-sm btn-outline-secondary">
                                Back to Positions
                            </a>
                            <a href="candidates.php?position_id=<?= $position_id ?>&election_id=<?= $election_id ?>" 
                               class="btn btn-sm btn-outline-secondary">
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
                
                <h4>Election: <?= htmlspecialchars($position['election_title']) ?></h4>
                <h5>Position: <?= htmlspecialchars($position['title']) ?></h5>
                
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
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Bio</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr>
                                        <td>
                                            <?php if ($candidate['photo']): ?>
                                                <img src="../<?= htmlspecialchars($candidate['photo']) ?>" 
                                                     class="img-thumbnail" width="50" alt="<?= htmlspecialchars($candidate['name']) ?>">
                                            <?php else: ?>
                                                <div class="no-photo">
                                                    <i class="fas fa-user-circle fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($candidate['name']) ?></td>
                                        <td><?= htmlspecialchars($candidate['bio']) ?></td>
                                        <td>
                                            <a href="candidates.php?action=edit&id=<?= $candidate['id'] ?>&position_id=<?= $position_id ?>&election_id=<?= $election_id ?>" 
                                               class="btn btn-sm btn-warning">Edit</a>
                                               <button class="btn btn-sm btn-danger delete-candidate" 
        data-id="<?= $candidate['id'] ?>" 
        data-position-id="<?= $position_id ?>" 
        data-election-id="<?= $election_id ?>">Delete</button>
        <!-- JavaScript Code -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


                                                    <script>
$(document).ready(function () {
    $(document).on('click', '.delete-candidate', function (e) {
        e.preventDefault();

        const candidateId = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete the candidate and their photo.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../api/delete_candidate.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { id: candidateId },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function (xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'Something went wrong.', 'error');
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
                    
                    <a href="candidates.php?action=create&position_id=<?= $position_id ?>&election_id=<?= $election_id ?>" 
                       class="btn btn-primary">
                        Add New Candidate
                    </a>
                <?php else: ?>
                    <form method="post" class="needs-validation" novalidate enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($candidate['name'] ?? '') ?>" required>
                            <div class="invalid-feedback">
                                Please provide the candidate's name.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio/Description</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?= 
                                htmlspecialchars($candidate['bio'] ?? '') 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="photo" class="form-label">Photo</label>
                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                            <?php if ($action === 'edit' && !empty($candidate['photo'])): ?>
                                <div class="mt-2">
                                    <img src="../<?= htmlspecialchars($candidate['photo']) ?>" 
                                         class="img-thumbnail" width="100" alt="Current photo">
                                    <p class="text-muted small">Current photo</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Candidate</button>
                        <a href="candidates.php?position_id=<?= $position_id ?>&election_id=<?= $election_id ?>" 
                           class="btn btn-secondary">Cancel</a>
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