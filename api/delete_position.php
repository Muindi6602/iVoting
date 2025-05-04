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
    $position_id = $_POST['id'] ?? 0;

    if (!$position_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid position ID']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Delete candidates under the position
        $deleteCandidates = $pdo->prepare("DELETE FROM candidates WHERE position_id = ?");
        $deleteCandidates->execute([$position_id]);

        // Delete the position
        $deletePosition = $pdo->prepare("DELETE FROM positions WHERE id = ?");
        $deletePosition->execute([$position_id]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Position deleted successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
