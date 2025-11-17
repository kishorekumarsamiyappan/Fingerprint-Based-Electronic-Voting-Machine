<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Only handle POST requests
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get the raw POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log received data for debugging
    file_put_contents('vote_debug.log', print_r($input, true) . "\n", FILE_APPEND);
    
    $fingerprint_id = isset($input['fingerprint_id']) ? intval($input['fingerprint_id']) : null;
    $candidate_id = isset($input['candidate_id']) ? intval($input['candidate_id']) : null;
    $place = isset($input['place']) ? trim($input['place']) : '';
    
    // Validate input
    if(!$fingerprint_id || !$candidate_id || empty($place)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields: fingerprint_id, candidate_id, place',
            'received' => $input
        ]);
        exit;
    }
    
    // Database configuration
    $host = "localhost";
    $dbname = "voting_system";
    $username = "root";
    $password = "";
    
    try {
        // Create database connection
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 1. Check if voter exists
        $voter_query = "SELECT id, name FROM voters WHERE fingerprint_id = :fingerprint_id";
        $voter_stmt = $db->prepare($voter_query);
        $voter_stmt->bindParam(':fingerprint_id', $fingerprint_id, PDO::PARAM_INT);
        $voter_stmt->execute();
        
        if($voter_stmt->rowCount() == 0) {
            // Create a temporary voter record if not exists
            $create_voter = "INSERT INTO voters (fingerprint_id, name, place) VALUES (:fingerprint_id, 'Auto-Registered Voter', :place)";
            $create_stmt = $db->prepare($create_voter);
            $create_stmt->bindParam(':fingerprint_id', $fingerprint_id, PDO::PARAM_INT);
            $create_stmt->bindParam(':place', $place);
            $create_stmt->execute();
        }
        
        // 2. Check if already voted
        $check_vote_query = "SELECT id FROM votes WHERE fingerprint_id = :fingerprint_id";
        $check_vote_stmt = $db->prepare($check_vote_query);
        $check_vote_stmt->bindParam(':fingerprint_id', $fingerprint_id, PDO::PARAM_INT);
        $check_vote_stmt->execute();
        
        if($check_vote_stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Voter has already cast their vote'
            ]);
            exit;
        }
        
        // 3. Record the vote
        $vote_query = "INSERT INTO votes (fingerprint_id, candidate_id, place) VALUES (:fingerprint_id, :candidate_id, :place)";
        $vote_stmt = $db->prepare($vote_query);
        $vote_stmt->bindParam(':fingerprint_id', $fingerprint_id, PDO::PARAM_INT);
        $vote_stmt->bindParam(':candidate_id', $candidate_id, PDO::PARAM_INT);
        $vote_stmt->bindParam(':place', $place);
        
        if($vote_stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Vote recorded successfully',
                'data' => [
                    'fingerprint_id' => $fingerprint_id,
                    'candidate_id' => $candidate_id,
                    'place' => $place
                ]
            ]);
        } else {
            throw new Exception('Failed to execute vote query');
        }
        
    } catch(PDOException $exception) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $exception->getMessage()
        ]);
    } catch(Exception $exception) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error: ' . $exception->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method is allowed'
    ]);
}
?>