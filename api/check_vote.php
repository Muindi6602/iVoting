<?php
header('Content-Type: application/json');
require_once '../includes/connection.php';
require_once '../includes/functions.php';

$response = ['has_voted' => false];

try {
    $election_id = filter_input(INPUT_GET, 'election_id', FILTER_VALIDATE_INT);
    
    if (!$election_id) {
        throw new Exception('Invalid election ID');
    }
    
    $ip_address = getClientIP();
    $response['has_voted'] = hasVoted($election_id, $ip_address);
    $response['success'] = true;
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>