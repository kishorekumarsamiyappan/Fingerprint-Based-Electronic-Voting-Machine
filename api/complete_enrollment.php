<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fingerprint_id = isset($input['fingerprint_id']) ? intval($input['fingerprint_id']) : null;
    $name = isset($input['name']) ? trim($input['name']) : '';
    $dob = isset($input['dob']) ? trim($input['dob']) : '';
    $aadhaar = isset($input['aadhaar']) ? trim($input['aadhaar']) : '';
    $voter_id = isset($input['voter_id']) ? trim($input['voter_id']) : '';
    
    if(!$fingerprint_id || empty($name) || empty($dob) || empty($aadhaar) || empty($voter_id)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required'
        ]);
        exit;
    }
    
    // Database configuration
    $host = "localhost";
    $dbname = "voting_system";
    $username = "root";
    $password = "";
    
    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if aadhaar already exists for another voter
        $check_query = "SELECT id FROM voters WHERE aadhaar = :aadhaar AND fingerprint_id != :fingerprint_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':aadhaar', $aadhaar);
        $check_stmt->bindParam(':fingerprint_id', $fingerprint_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Aadhaar number already registered for another voter!'
            ]);
            exit;
        }
        
        // Update the pending enrollment with voter details
        $update_query = "UPDATE voters SET name = :name, dob = :dob, aadhaar = :aadhaar, 
                        voter_id = :voter_id, status = 'completed' 
                        WHERE fingerprint_id = :fingerprint_id AND status = 'pending'";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':name', $name);
        $update_stmt->bindParam(':dob', $dob);
        $update_stmt->bindParam(':aadhaar', $aadhaar);
        $update_stmt->bindParam(':voter_id', $voter_id);
        $update_stmt->bindParam(':fingerprint_id', $fingerprint_id);
        
        if($update_stmt->execute()) {
            if($update_stmt->rowCount() > 0) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Voter enrollment completed successfully!'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No pending enrollment found with this fingerprint ID'
                ]);
            }
        } else {
            throw new Exception('Failed to update voter record');
        }
        
    } catch(PDOException $exception) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $exception->getMessage()
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