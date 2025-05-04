<?php
require_once 'connection.php';

// Set default timezone to Nairobi
date_default_timezone_set('Africa/Nairobi');

function getActiveElections() {
    global $pdo;
    $now = (new DateTime())->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE start_date <= ? AND end_date >= ? AND status = 'active'");
    $stmt->execute([$now, $now]);
    return $stmt->fetchAll();
}

function getUpcomingElections() {
    global $pdo;
    $now = (new DateTime())->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE start_date > ? AND status = 'upcoming'");
    $stmt->execute([$now]);
    return $stmt->fetchAll();
}

function getCompletedElections() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE status = 'completed' ORDER BY end_date DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getElectionPositions($election_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE election_id = ?");
    $stmt->execute([$election_id]);
    return $stmt->fetchAll();
}

function getPositionCandidates($position_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE position_id = ?");
    $stmt->execute([$position_id]);
    return $stmt->fetchAll();
}

function hasVoted($election_id, $ip) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM voter_restrictions WHERE election_id = ? AND voter_ip = ?");
    $stmt->execute([$election_id, $ip]);
    return $stmt->fetch() ? true : false;
}

function getVoteResults($election_id) {
    global $pdo;
    $results = [];
    
    $positions = getElectionPositions($election_id);
    
    foreach ($positions as $position) {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, COUNT(v.id) as vote_count 
            FROM candidates c 
            LEFT JOIN votes v ON v.candidate_id = c.id 
            WHERE c.position_id = ? 
            GROUP BY c.id 
            ORDER BY vote_count DESC
        ");
        $stmt->execute([$position['id']]);
        $candidates = $stmt->fetchAll();
        
        $total_votes = array_sum(array_column($candidates, 'vote_count'));
        
        foreach ($candidates as &$candidate) {
            $candidate['percentage'] = $total_votes > 0 ? round(($candidate['vote_count'] / $total_votes) * 100, 2) : 0;
        }
        
        $results[] = [
            'position' => $position,
            'candidates' => $candidates,
            'total_votes' => $total_votes
        ];
    }
    
    return $results;
}

function getTotalVotesForPosition($election_id, $position_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_votes 
        FROM votes 
        WHERE election_id = ? AND position_id = ?
    ");
    $stmt->execute([$election_id, $position_id]);
    $result = $stmt->fetch();
    return $result ? $result['total_votes'] : 0;
}

function getVotesForCandidate($election_id, $position_id, $candidate_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as vote_count 
        FROM votes 
        WHERE election_id = ? AND position_id = ? AND candidate_id = ?
    ");
    $stmt->execute([$election_id, $position_id, $candidate_id]);
    $result = $stmt->fetch();
    return $result ? $result['vote_count'] : 0;
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function getElectionStatus($start_date, $end_date) {
    $now = new DateTime('now', new DateTimeZone('Africa/Nairobi'));
    $start = new DateTime($start_date, new DateTimeZone('UTC'));
    $end = new DateTime($end_date, new DateTimeZone('UTC'));
    
    // Convert UTC times to Nairobi time for comparison
    $start->setTimezone(new DateTimeZone('Africa/Nairobi'));
    $end->setTimezone(new DateTimeZone('Africa/Nairobi'));
    
    if ($now < $start) {
        return 'upcoming';
    } elseif ($now > $end) {
        return 'completed';
    } else {
        return 'active';
    }
}

function updateElectionStatuses() {
    global $pdo;
    
    // Get current Nairobi time in UTC format for database comparison
    $now = (new DateTime('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d H:i:s');
    
    $pdo->exec("
        UPDATE elections 
        SET status = 'active' 
        WHERE status = 'upcoming' AND start_date <= '{$now}'
    ");
    
    $pdo->exec("
        UPDATE elections 
        SET status = 'completed' 
        WHERE status = 'active' AND end_date <= '{$now}'
    ");
}

// Update election statuses on each request
updateElectionStatuses();

function getCandidateById($candidate_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
    $stmt->execute([$candidate_id]);
    return $stmt->fetch();
}

function getPositionById($position_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE id = ?");
    $stmt->execute([$position_id]);
    return $stmt->fetch();
}

function getElectionById($election_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
    $stmt->execute([$election_id]);
    return $stmt->fetch();
}

function getElectionProgress($start_date, $end_date) {
    $start = new DateTime($start_date, new DateTimeZone('UTC'));
    $end = new DateTime($end_date, new DateTimeZone('UTC'));
    $now = new DateTime('now', new DateTimeZone('Africa/Nairobi'));
    
    // Convert UTC times to Nairobi time for comparison
    $start->setTimezone(new DateTimeZone('Africa/Nairobi'));
    $end->setTimezone(new DateTimeZone('Africa/Nairobi'));
    
    $start_ts = $start->getTimestamp();
    $end_ts = $end->getTimestamp();
    $now_ts = $now->getTimestamp();
    
    if ($now_ts < $start_ts) return 0;
    if ($now_ts > $end_ts) return 100;
    
    $total = $end_ts - $start_ts;
    $progress = $now_ts - $start_ts;
    
    return round(($progress / $total) * 100);
}

/**
 * Helper function to format a database datetime for display in Nairobi time
 */
function formatForDisplay($db_datetime) {
    $date = new DateTime($db_datetime, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Africa/Nairobi'));
    return $date->format('M j, Y H:i');
}

/**
 * Helper function to convert Nairobi time input to UTC for database storage
 */
function convertToUTC($nairobi_datetime) {
    $date = new DateTime($nairobi_datetime, new DateTimeZone('Africa/Nairobi'));
    $date->setTimezone(new DateTimeZone('UTC'));
    return $date->format('Y-m-d H:i:s');
}