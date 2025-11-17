<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database configuration
$host = "localhost";
$dbname = "voting_system";
$username = "root";
$password = "";

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $place = $_SESSION['admin_place'];
    
    // Get total voters (completed only)
    $votersQuery = "SELECT COUNT(*) as total FROM voters WHERE place = :place AND status = 'completed'";
    $votersStmt = $db->prepare($votersQuery);
    $votersStmt->bindParam(':place', $place);
    $votersStmt->execute();
    $totalVoters = (int)$votersStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total votes
    $votesQuery = "SELECT COUNT(*) as total FROM votes WHERE place = :place";
    $votesStmt = $db->prepare($votesQuery);
    $votesStmt->bindParam(':place', $place);
    $votesStmt->execute();
    $totalVotes = (int)$votesStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate voter turnout
    $voterTurnout = ($totalVoters > 0) ? round(($totalVotes / $totalVoters) * 100, 2) : 0;
    
    // Get votes per candidate
    $candidateQuery = "SELECT candidate_id, COUNT(*) as vote_count FROM votes WHERE place = :place GROUP BY candidate_id";
    $candidateStmt = $db->prepare($candidateQuery);
    $candidateStmt->bindParam(':place', $place);
    $candidateStmt->execute();
    $candidateVotes = $candidateStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize votes for all candidates
    $votes = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
    foreach($candidateVotes as $cv) {
        if (isset($cv['candidate_id']) && isset($votes[$cv['candidate_id']])) {
            $votes[$cv['candidate_id']] = (int)$cv['vote_count'];
        }
    }
    
    // Find leading candidate
    $leadingVotes = max($votes);
    $leadingCandidateId = array_search($leadingVotes, $votes);
    $leadingCandidate = "Candidate " . $leadingCandidateId;
    $leadingPercentage = $totalVotes > 0 ? round(($leadingVotes / $totalVotes) * 100, 2) : 0;
    
    // Prepare data for response
    $candidateLabels = ['Candidate 1', 'Candidate 2', 'Candidate 3', 'Candidate 4'];
    $voteData = array_values($votes);
    $backgroundColors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'];
    $borderColors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'];
    
    $candidateResults = [];
    for($i = 1; $i <= 4; $i++) {
        $candidateResults[] = [
            'name' => 'Candidate ' . $i,
            'votes' => $votes[$i]
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'totalVoters' => $totalVoters,
        'totalVotes' => $totalVotes,
        'voterTurnout' => $voterTurnout,
        'leadingCandidate' => $leadingCandidate,
        'leadingVotes' => $leadingVotes,
        'leadingPercentage' => $leadingPercentage,
        'candidateLabels' => $candidateLabels,
        'voteData' => $voteData,
        'backgroundColors' => $backgroundColors,
        'borderColors' => $borderColors,
        'candidateResults' => $candidateResults
    ]);
    
} catch(PDOException $exception) {
    error_log("Database error in get_results.php: " . $exception->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $exception->getMessage(),
        'totalVoters' => 0,
        'totalVotes' => 0,
        'voterTurnout' => 0,
        'leadingCandidate' => 'None',
        'leadingVotes' => 0,
        'leadingPercentage' => 0,
        'candidateLabels' => ['Candidate 1', 'Candidate 2', 'Candidate 3', 'Candidate 4'],
        'voteData' => [0, 0, 0, 0],
        'backgroundColors' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
        'borderColors' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
        'candidateResults' => []
    ]);
}
?>