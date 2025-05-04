<?php
header('Content-Type: application/json');
require_once '../includes/connection.php';
require_once '../includes/functions.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $election_id = filter_input(INPUT_POST, 'election_id', FILTER_VALIDATE_INT);
    $votes = $_POST['votes'] ?? [];
    
    if (!$election_id) {
        throw new Exception('Invalid election ID');
    }
    
    if (empty($votes)) {
        throw new Exception('No votes submitted');
    }
    
    $ip_address = getClientIP();
    
    // Check if already voted
    if (hasVoted($election_id, $ip_address)) {
        throw new Exception('You have already voted in this election');
    }
    
    $pdo->beginTransaction();
    
    // Record that this IP has voted
    $stmt = $pdo->prepare("INSERT INTO voter_restrictions (election_id, voter_ip) VALUES (?, ?)");
    $stmt->execute([$election_id, $ip_address]);
    
    // Record each vote
    foreach ($votes as $position_id => $candidate_id) {
        $stmt = $pdo->prepare("
            INSERT INTO votes (election_id, position_id, candidate_id, voter_ip) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$election_id, $position_id, $candidate_id, $ip_address]);
    }
    
    $pdo->commit();
    
    $response['success'] = true;
    $response['message'] = 'Vote recorded successfully';
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>