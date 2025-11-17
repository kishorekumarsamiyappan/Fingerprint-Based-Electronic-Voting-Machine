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
    
    // Get pending enrollments for current admin's place
    $query = "SELECT fingerprint_id, place, created_at FROM voters 
              WHERE status = 'pending' AND place = :place 
              ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':place', $place);
    $stmt->execute();
    
    $pending_enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $pending_enrollments
    ]);
    
} catch(PDOException $exception) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $exception->getMessage()
    ]);
}
?>