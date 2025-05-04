<?php
require_once '../includes/connection.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidate_id = $_POST['id'] ?? 0;

    if (!$candidate_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid candidate ID']);
        exit;
    }

    try {
        // Get photo path
        $stmt = $pdo->prepare("SELECT photo FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);
        $candidate = $stmt->fetch();

        if (!$candidate) {
            echo json_encode(['success' => false, 'message' => 'Candidate not found']);
            exit;
        }

        // Delete photo file
        if (!empty($candidate['photo']) && file_exists('../' . $candidate['photo'])) {
            @unlink('../' . $candidate['photo']);
        }

        // Delete candidate record
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);

        echo json_encode(['success' => true, 'message' => 'Candidate deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
