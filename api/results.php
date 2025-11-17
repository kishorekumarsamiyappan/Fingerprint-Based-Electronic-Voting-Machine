<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    $database = new Database();
    $db = $database->getConnection();
    
    $current_place = isset($_SESSION['admin_place']) ? $_SESSION['admin_place'] : '';
    
    try {
        // Get total voters count
        $voters_query = "SELECT COUNT(*) as total_voters FROM voters";
        if($current_place) {
            $voters_query .= " WHERE place = :place";
        }
        $voters_stmt = $db->prepare($voters_query);
        if($current_place) {
            $voters_stmt->bindParam(':place', $current_place);
        }
        $voters_stmt->execute();
        $total_voters = $voters_stmt->fetch(PDO::FETCH_ASSOC)['total_voters'];
        
        // Get votes by candidate
        $votes_query = "
            SELECT 
                c.id as candidate_id,
                c.name as candidate_name,
                c.symbol as candidate_symbol,
                COUNT(v.id) as vote_count
            FROM candidates c
            LEFT JOIN votes v ON c.id = v.candidate_id
        ";
        
        if($current_place) {
            $votes_query .= " WHERE v.place = :place OR v.place IS NULL";
        }
        
        $votes_query .= " GROUP BY c.id, c.name, c.symbol ORDER BY c.id";
        
        $votes_stmt = $db->prepare($votes_query);
        if($current_place) {
            $votes_stmt->bindParam(':place', $current_place);
        }
        $votes_stmt->execute();
        
        $candidates = [];
        $total_votes = 0;
        
        while($row = $votes_stmt->fetch(PDO::FETCH_ASSOC)) {
            $candidates[] = [
                'id' => $row['candidate_id'],
                'name' => $row['candidate_name'],
                'symbol' => $row['candidate_symbol'],
                'votes' => (int)$row['vote_count']
            ];
            $total_votes += (int)$row['vote_count'];
        }
        
        // Calculate percentages and find winner
        $winner = null;
        $max_votes = 0;
        
        foreach($candidates as &$candidate) {
            $candidate['percentage'] = $total_votes > 0 ? round(($candidate['votes'] / $total_votes) * 100, 2) : 0;
            
            if($candidate['votes'] > $max_votes) {
                $max_votes = $candidate['votes'];
                $winner = $candidate;
            }
        }
        
        // Get recent votes
        $recent_votes_query = "
            SELECT v.*, c.name as candidate_name, vr.name as voter_name
            FROM votes v
            LEFT JOIN candidates c ON v.candidate_id = c.id
            LEFT JOIN voters vr ON v.fingerprint_id = vr.fingerprint_id
            ORDER BY v.voted_at DESC
            LIMIT 10
        ";
        $recent_votes_stmt = $db->prepare($recent_votes_query);
        $recent_votes_stmt->execute();
        
        $recent_votes = [];
        while($row = $recent_votes_stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent_votes[] = [
                'voter_name' => $row['voter_name'] ?: 'Unknown Voter',
                'candidate_name' => $row['candidate_name'],
                'voted_at' => $row['voted_at']
            ];
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_voters' => (int)$total_voters,
                'total_votes' => $total_votes,
                'voter_turnout' => $total_voters > 0 ? round(($total_votes / $total_voters) * 100, 2) : 0,
                'candidates' => $candidates,
                'winner' => $winner,
                'recent_votes' => $recent_votes
            ]
        ]);
        
    } catch(PDOException $exception) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $exception->getMessage()
        ]);
    }
}
?>