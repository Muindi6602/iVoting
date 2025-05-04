<?php
header('Content-Type: application/json');
require_once '../includes/connection.php';
require_once '../includes/functions.php';

$response = ['success' => false, 'data' => []];

try {
    $election_id = filter_input(INPUT_GET, 'election_id', FILTER_VALIDATE_INT);
    
    if (!$election_id) {
        throw new Exception('Invalid election ID');
    }
    
    $results = getVoteResults($election_id);
    
    $response['success'] = true;
    $response['data'] = $results;
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>