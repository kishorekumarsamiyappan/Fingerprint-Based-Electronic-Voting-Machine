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

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get the raw POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log received data for debugging
    file_put_contents('enroll_debug.log', date('Y-m-d H:i:s') . " - " . print_r($input, true) . "\n", FILE_APPEND);
    
    $fingerprint_id = isset($input['fingerprint_id']) ? intval($input['fingerprint_id']) : null;
    $place = isset($input['place']) ? trim($input['place']) : '';
    
    if(!$fingerprint_id || empty($place)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields: fingerprint_id and place',
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
        
        // Check if fingerprint already exists and is pending
        $check_query = "SELECT id, name, status FROM voters WHERE fingerprint_id = :fingerprint_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':fingerprint_id', $fingerprint_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
            if($existing['status'] == 'pending') {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Fingerprint already registered and pending completion',
                    'existing_voter' => $existing
                ]);
            } else {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Fingerprint already registered and completed',
                    'existing_voter' => $existing
                ]);
            }
        } else {
            // Insert new voter with pending status
            $insert_query = "INSERT INTO voters (fingerprint_id, name, place, status, created_at) 
                            VALUES (:fingerprint_id, 'Pending Registration', :place, 'pending', NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':fingerprint_id', $fingerprint_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':place', $place);
            
            if($insert_stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Fingerprint registered successfully. Please complete registration on web dashboard.',
                    'fingerprint_id' => $fingerprint_id,
                    'place' => $place
                ]);
            } else {
                throw new Exception('Failed to insert voter record');
            }
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