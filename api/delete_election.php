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
    $election_id = $_POST['id'] ?? 0;

    if (!$election_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid election ID']);
        exit;
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Delete related votes
        $stmt = $pdo->prepare("DELETE FROM votes WHERE election_id = ?");
        $stmt->execute([$election_id]);

        // Delete candidates under each position
        $positions = $pdo->prepare("SELECT id FROM positions WHERE election_id = ?");
        $positions->execute([$election_id]);

        while ($position = $positions->fetch()) {
            $deleteCandidates = $pdo->prepare("DELETE FROM candidates WHERE position_id = ?");
            $deleteCandidates->execute([$position['id']]);
        }

        // Delete positions
        $deletePositions = $pdo->prepare("DELETE FROM positions WHERE election_id = ?");
        $deletePositions->execute([$election_id]);

        // Delete the election itself
        $stmt = $pdo->prepare("DELETE FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Election deleted successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
