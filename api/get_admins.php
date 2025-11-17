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
    
    // Check if admin table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'admin'");
    if($tableCheck->rowCount() == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Admin table does not exist. Please run the database setup script.',
            'admins' => []
        ]);
        exit;
    }
    
    // Get all admins
    $query = "SELECT id, username, place, created_at FROM admin ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'admins' => $admins
    ]);
    
} catch(PDOException $exception) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $exception->getMessage(),
        'admins' => []
    ]);
}
?>