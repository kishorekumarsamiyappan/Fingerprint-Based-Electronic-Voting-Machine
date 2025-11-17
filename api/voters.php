<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$database = new Database();
$db = $database->getConnection();

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get all voters
    $current_place = isset($_SESSION['admin_place']) ? $_SESSION['admin_place'] : '';
    
    $query = "
        SELECT 
            v.*,
            (SELECT COUNT(*) FROM votes WHERE fingerprint_id = v.fingerprint_id) as has_voted
        FROM voters v
    ";
    
    $params = [];
    
    // Add place filter if set
    if($current_place) {
        $query .= " WHERE v.place = :place";
        $params[':place'] = $current_place;
    }
    
    $query .= " ORDER BY v.created_at DESC";
    
    $stmt = $db->prepare($query);
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    $voters = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $voters[] = [
            'id' => $row['id'],
            'fingerprint_id' => $row['fingerprint_id'],
            'name' => $row['name'],
            'dob' => $row['dob'],
            'aadhaar' => $row['aadhaar'],
            'voter_id' => $row['voter_id'],
            'place' => $row['place'],
            'created_at' => $row['created_at'],
            'has_voted' => $row['has_voted'] > 0
        ];
    }
    
    // Get voting statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_voters,
            SUM(CASE WHEN votes.id IS NOT NULL THEN 1 ELSE 0 END) as voted_count
        FROM voters
        LEFT JOIN votes ON voters.fingerprint_id = votes.fingerprint_id
    ";
    
    if($current_place) {
        $stats_query .= " WHERE voters.place = :place";
    }
    
    $stats_stmt = $db->prepare($stats_query);
    if($current_place) {
        $stats_stmt->bindParam(':place', $current_place);
    }
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'voters' => $voters,
            'statistics' => [
                'total_voters' => (int)$stats['total_voters'],
                'voted_count' => (int)$stats['voted_count'],
                'not_voted_count' => (int)$stats['total_voters'] - (int)$stats['voted_count']
            ]
        ]
    ]);
    
} elseif($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    // Delete voter
    $input = json_decode(file_get_contents('php://input'), true);
    $fingerprint_id = isset($input['fingerprint_id']) ? intval($input['fingerprint_id']) : null;
    
    if(!$fingerprint_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Fingerprint ID is required'
        ]);
        exit;
    }
    
    try {
        // Check if voter exists
        $check_query = "SELECT id FROM voters WHERE fingerprint_id = :fingerprint_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':fingerprint_id', $fingerprint_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() == 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Voter not found'
            ]);
            exit;
        }
        
        // Check if voter has voted
        $vote_check_query = "SELECT id FROM votes WHERE fingerprint_id = :fingerprint_id";
        $vote_check_stmt = $db->prepare($vote_check_query);
        $vote_check_stmt->bindParam(':fingerprint_id', $fingerprint_id);
        $vote_check_stmt->execute();
        
        if($vote_check_stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cannot delete voter who has already voted'
            ]);
            exit;
        }
        
        // Delete voter
        $delete_query = "DELETE FROM voters WHERE fingerprint_id = :fingerprint_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':fingerprint_id', $fingerprint_id);
        
        if($delete_stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Voter deleted successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete voter'
            ]);
        }
        
    } catch(PDOException $exception) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $exception->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>