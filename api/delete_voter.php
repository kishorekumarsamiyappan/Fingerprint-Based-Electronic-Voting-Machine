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
    
    $fingerprint_id = isset($_GET['fingerprint_id']) ? intval($_GET['fingerprint_id']) : 0;
    
    if($fingerprint_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid fingerprint ID']);
        exit;
    }
    
    // Delete voter
    $deleteQuery = "DELETE FROM voters WHERE fingerprint_id = :fingerprint_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':fingerprint_id', $fingerprint_id, PDO::PARAM_INT);
    
    if($deleteStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Voter deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete voter']);
    }
    
} catch(PDOException $exception) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $exception->getMessage()
    ]);
}
?>