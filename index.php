<?php
require_once 'includes/connection.php';
require_once 'includes/functions.php';

$active_elections = getActiveElections();
$completed_elections = getCompletedElections();
$ip_address = getClientIP();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MuindiCast - Modern eVoting Platform</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header/Navigation -->
<header class="app-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <span>MuindiCast</span>
            </div>
            <div style="font-size: 12px; font-family: Arial, sans-serif; color: #666; margin-left: auto; display: flex; align-items: center;">
                <span>Do you want to post your own voting/elections with us?</span>
                <a href="https://wa.me/254115783375?text=I'm%20interested%20in%20posting%20my%20voting/election%20on%20MuindiCast" 
                   style="margin-left: 8px; text-decoration: none;">
                    <i class="fab fa-whatsapp" style="color: #25D366; font-size: 20px;"></i>
                </a>
            </div>
        </div>
    </div>
</header>

    <!-- Main Content -->
    <main class="container main-content">
        <?php if (empty($active_elections)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Active Elections</h3>
                <p>There are currently no ongoing elections. Check back later or contact the administrator.</p>
            </div>
        <?php else: ?>
            <div class="election-grid">
                <!-- Sidebar with Live Results -->
                <aside class="election-sidebar">
                    <div class="sidebar-card live-results-card">
                        <h3><i class="fas fa-chart-line"></i> Live Results</h3>
                        <div id="resultsContainer">
                            <?php foreach ($active_elections as $election): ?>
                                <div class="election-result" id="electionResults-<?= $election['id'] ?>">
                                    <div class="election-result-header">
                                        <h4 class="election-title">
                                            <i class="fas fa-vote-yea"></i>
                                            <?= htmlspecialchars($election['title']) ?>
                                        </h4>
                                        <div class="election-meta">
                                            <span class="election-progress-text">
                                                <?= getElectionProgress($election['start_date'], $election['end_date']) ?>% complete
                                            </span>
                                            <div class="election-progress">
                                                <div class="progress-bar" style="width: <?= getElectionProgress($election['start_date'], $election['end_date']) ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="positions-container">
                                        <?php 
                                        $positions = getElectionPositions($election['id']);
                                        foreach ($positions as $position): 
                                            $candidates = getPositionCandidates($position['id']);
                                            $total_votes = getTotalVotesForPosition($election['id'], $position['id']);
                                        ?>
                                            <div class="position-result">
                                                <h5 class="position-title">
                                                    <i class="fas fa-chevron-right"></i>
                                                    <?= htmlspecialchars($position['title']) ?>
                                                    <span class="total-votes-badge"><?= $total_votes ?> votes</span>
                                                </h5>
                                                
                                                <div class="candidates-list">
                                                    <?php 
                                                    $max_votes = 0;
                                                    foreach ($candidates as $candidate) {
                                                        $votes = getVotesForCandidate($election['id'], $position['id'], $candidate['id']);
                                                        if ($votes > $max_votes) {
                                                            $max_votes = $votes;
                                                        }
                                                    }
                                                    
                                                    foreach ($candidates as $candidate): 
                                                        $votes = getVotesForCandidate($election['id'], $position['id'], $candidate['id']);
                                                        $percentage = $total_votes > 0 ? round(($votes / $total_votes) * 100, 1) : 0;
                                                        $is_winner = $votes == $max_votes && $max_votes > 0;
                                                    ?>
                                                        <div class="candidate-result <?= $is_winner ? 'leading' : '' ?>">
                                                            <div class="candidate-photo-sm">
                                                                <?php if (!empty($candidate['photo'])): ?>
                                                                    <img src="<?= htmlspecialchars($candidate['photo']) ?>" alt="<?= htmlspecialchars($candidate['name']) ?>">
                                                                <?php else: ?>
                                                                    <div class="photo-placeholder-sm">
                                                                        <i class="fas fa-user"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="candidate-details-sm">
                                                                <span class="candidate-name"><?= htmlspecialchars($candidate['name']) ?></span>
                                                                <div class="vote-progress-container">
                                                                    <div class="vote-progress-sm">
                                                                        <div class="progress-fill" style="width: <?= $percentage ?>%"></div>
                                                                    </div>
                                                                    <div class="vote-numbers-sm">
                                                                        <span class="vote-count"><?= $votes ?></span>
                                                                        <span class="vote-percent"><?= $percentage ?>%</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>

                <!-- Election Voting Content -->
                <div class="election-content">
                    <?php foreach ($active_elections as $election): ?>
                        <article class="election-card">
                            <header class="election-header">
                                <div class="election-meta">
                                    <span class="election-status active">Active</span>
                                    <span class="election-date">
                                        <i class="fas fa-clock"></i> Ends: <?= date('M j, Y H:i', strtotime($election['end_date'])) ?>
                                    </span>
                                </div>
                                <h2><?= htmlspecialchars($election['title']) ?></h2>
                                <p class="election-description"><?= htmlspecialchars($election['description']) ?></p>
                            </header>

                            <?php if (hasVoted($election['id'], $ip_address)): ?>
                                <div class="voted-notice">
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>You've already voted!</h4>
                                        <p>Thank you for participating in this election.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <form id="voteForm-<?= $election['id'] ?>" class="vote-form" data-election-id="<?= $election['id'] ?>">
                                    <?php 
                                    $positions = getElectionPositions($election['id']);
                                    foreach ($positions as $position): 
                                    ?>
                                        <section class="position-section">
                                            <h3 class="position-title">
                                                <i class="fas fa-chevron-right"></i>
                                                <?= htmlspecialchars($position['title']) ?>
                                            </h3>
                                            <p class="position-description"><?= htmlspecialchars($position['description']) ?></p>
                                            
                                            <div class="candidates-grid">
                                                <?php 
                                                $candidates = getPositionCandidates($position['id']);
                                                foreach ($candidates as $candidate): 
                                                ?>
                                                    <div class="candidate-card">
                                                        <label class="candidate-selector">
                                                            <input type="radio" name="position_<?= $position['id'] ?>" value="<?= $candidate['id'] ?>" required>
                                                            <span class="radio-indicator"></span>
                                                        </label>
                                                        
                                                        <div class="candidate-info">
                                                            <div class="candidate-photo">
                                                                <?php if (!empty($candidate['photo'])): ?>
                                                                    <img src="<?= htmlspecialchars($candidate['photo']) ?>" alt="<?= htmlspecialchars($candidate['name']) ?>">
                                                                <?php else: ?>
                                                                    <div class="photo-placeholder">
                                                                        <i class="fas fa-user"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <div class="candidate-details">
                                                                <h4><?= htmlspecialchars($candidate['name']) ?></h4>
                                                                <p class="candidate-bio"><?= htmlspecialchars($candidate['bio']) ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </section>
                                    <?php endforeach; ?>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-vote">
                                            <i class="fas fa-paper-plane"></i> Submit Your Vote
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
<br><br>
        <!-- Completed Elections -->
        <section class="completed-elections">
<h2 class="section-title" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: left; width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px; box-sizing: border-box;">
    <i class="fas fa-archive"></i> Final Results
</h2>            
            <?php if (empty($completed_elections)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Completed Elections</h3>
                    <p>Final results will appear here once elections are completed.</p>
                </div>
            <?php else: ?>
                <div class="results-accordion">
                    <?php foreach ($completed_elections as $election): ?>
                        <details class="result-item">
                            <summary>
                                <h3><?= htmlspecialchars($election['title']) ?></h3>
                                <span class="result-date">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?= date('M j, Y', strtotime($election['end_date'])) ?>
                                </span>
                                <i class="fas fa-chevron-down"></i>
                            </summary>
                            
                            <div class="result-content">
                                <?php 
                                $positions = getElectionPositions($election['id']);
                                foreach ($positions as $position): 
                                    $candidates = getPositionCandidates($position['id']);
                                    $total_votes = getTotalVotesForPosition($election['id'], $position['id']);
                                ?>
                                    <div class="position-result">
                                        <h4><?= htmlspecialchars($position['title']) ?></h4>
                                        <div class="vote-summary">
                                            <span class="total-votes"><?= $total_votes ?> total votes</span>
                                        </div>
                                        
                                        <?php 
                                        $winner_id = null;
                                        $max_votes = 0;
                                        $candidate_votes = [];
                                        
                                        foreach ($candidates as $candidate) {
                                            $votes = getVotesForCandidate($election['id'], $position['id'], $candidate['id']);
                                            $candidate_votes[$candidate['id']] = $votes;
                                            if ($votes > $max_votes) {
                                                $max_votes = $votes;
                                                $winner_id = $candidate['id'];
                                            }
                                        }
                                        
                                        $winners = [];
                                        foreach ($candidate_votes as $cid => $votes) {
                                            if ($votes == $max_votes && $max_votes > 0) {
                                                $winners[] = $cid;
                                            }
                                        }
                                        ?>
                                        
                                        <div class="candidate-results">
                                            <?php foreach ($candidates as $candidate): 
                                                $votes = $candidate_votes[$candidate['id']];
                                                $percentage = $total_votes > 0 ? round(($votes / $total_votes) * 100, 2) : 0;
                                                $is_winner = in_array($candidate['id'], $winners) && $max_votes > 0;
                                            ?>
                                                <div class="candidate-result <?= $is_winner ? 'winner' : '' ?>">
                                                    <div class="result-candidate-info">
                                                        <div class="result-photo">
                                                            <?php if (!empty($candidate['photo'])): ?>
                                                                <img src="<?= htmlspecialchars($candidate['photo']) ?>" alt="<?= htmlspecialchars($candidate['name']) ?>">
                                                            <?php else: ?>
                                                                <div class="photo-placeholder">
                                                                    <i class="fas fa-user"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="result-details">
                                                            <h5><?= htmlspecialchars($candidate['name']) ?></h5>
                                                            <?php if ($is_winner): ?>
                                                                <span class="winner-badge">
                                                                    <i class="fas fa-trophy"></i> Winner
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="result-stats">
                                                        <div class="vote-bar">
                                                            <div class="bar-container">
                                                                <div class="vote-progress" style="width: <?= $percentage ?>%"></div>
                                                            </div>
                                                            <div class="vote-numbers">
                                                                <span class="vote-count"><?= $votes ?> votes</span>
                                                                <span class="vote-percent"><?= $percentage ?>%</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer -->
    <footer class="app-footer">
    <div class="container">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> MuindiCast. All rights reserved.</p>
               <a href="https://josephmuindi.vercel.app/" target="_blank" rel="noopener"> By Muindi</a>
            </p>
        </div>
    </div>
</footer>


    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>