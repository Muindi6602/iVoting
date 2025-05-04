<?php
// This must be the VERY first line, no whitespace before!
header('Content-Type: application/json');

// Enable error reporting (remove in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Set Nairobi timezone
date_default_timezone_set('Africa/Nairobi');

require_once '../includes/connection.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$response = ['success' => false, 'html' => ''];

try {
    if (!isAdmin()) {
        throw new Exception('Unauthorized access');
    }

    // First update all election statuses
    updateAllElectionStatuses();

    // Get recent votes (last 10)
    $stmt = $pdo->query("
        SELECT v.*, e.title as election_title, p.title as position_title, c.name as candidate_name
        FROM votes v
        JOIN elections e ON e.id = v.election_id
        JOIN positions p ON p.id = v.position_id
        JOIN candidates c ON c.id = v.candidate_id
        ORDER BY v.created_at DESC
        LIMIT 10
    ");
    $recentVotes = $stmt->fetchAll();

    // Get recent elections activity
    $stmt = $pdo->query("
        SELECT *, 
            (SELECT COUNT(*) FROM positions WHERE election_id = e.id) as position_count,
            (SELECT COUNT(*) FROM votes WHERE election_id = e.id) as vote_count
        FROM elections e
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $recentElections = $stmt->fetchAll();

    // Generate HTML for recent activity
    $html = '<div class="activity-list">';
    
    // Recent votes
    $html .= '<h6>Recent Votes</h6>';
    if (empty($recentVotes)) {
        $html .= '<p class="text-muted small">No votes recorded yet</p>';
    } else {
        $html .= '<ul class="list-unstyled">';
        foreach ($recentVotes as $vote) {
            // Database already stores Nairobi time, no conversion needed
            $createdAt = new DateTime($vote['created_at'], new DateTimeZone('Africa/Nairobi'));
            $formattedTime = $createdAt->format('M j, Y H:i');
            
            $timeAgo = time_elapsed_string($vote['created_at']);
            $html .= '<li class="mb-2 small">';
            $html .= '<strong>'.htmlspecialchars($vote['candidate_name']).'</strong> voted for ';
            $html .= htmlspecialchars($vote['position_title']).' in ';
            $html .= htmlspecialchars($vote['election_title']).'<br>';
            $html .= '<span class="text-muted">'.$timeAgo.' ('.$formattedTime.') from '.htmlspecialchars($vote['voter_ip']).'</span>';
            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    // Recent elections
    $html .= '<h6 class="mt-3">Recent Elections</h6>';
    if (empty($recentElections)) {
        $html .= '<p class="text-muted small">No elections found</p>';
    } else {
        $html .= '<ul class="list-unstyled">';
        foreach ($recentElections as $election) {
            // Database already stores Nairobi time, no conversion needed
            $startDate = new DateTime($election['start_date'], new DateTimeZone('Africa/Nairobi'));
            $endDate = new DateTime($election['end_date'], new DateTimeZone('Africa/Nairobi'));
            
            // Use the status from database (already updated)
            $status = $election['status'];
            $html .= '<li class="mb-2 small">';
            $html .= '<strong>'.htmlspecialchars($election['title']).'</strong> (';
            $html .= '<span class="badge bg-'.($status === 'active' ? 'success' : ($status === 'upcoming' ? 'warning' : 'secondary')).'">';
            $html .= ucfirst($status).'</span>)<br>';
            $html .= $startDate->format('M j, Y').' to '.$endDate->format('M j, Y').'<br>';
            $html .= $election['position_count'].' positions • '.$election['vote_count'].' votes';
            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '</div>';

    $response = [
        'success' => true,
        'html' => $html
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Ensure no output has been sent
if (!headers_sent()) {
    echo json_encode($response);
} else {
    error_log('Headers already sent when trying to output JSON');
    die(json_encode(['success' => false, 'message' => 'Server error']));
}
exit;

// Helper function to show time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime('now', new DateTimeZone('Africa/Nairobi'));
    $ago = new DateTime($datetime, new DateTimeZone('Africa/Nairobi'));
    
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Function to update all election statuses
function updateAllElectionStatuses() {
    global $pdo;
    
    // Get current Nairobi time
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