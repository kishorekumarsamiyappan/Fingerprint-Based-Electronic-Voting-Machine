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
    
    // Get voters for the current place
    $query = "SELECT fingerprint_id, name, dob, aadhaar, voter_id, place, status, created_at 
              FROM voters 
              WHERE place = :place 
              ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':place', $place);
    $stmt->execute();
    
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get stats
    $statsQuery = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                   FROM voters 
                   WHERE place = :place";
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->bindParam(':place', $place);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Ensure stats are integers
    $stats = [
        'total' => (int)($stats['total'] ?? 0),
        'completed' => (int)($stats['completed'] ?? 0),
        'pending' => (int)($stats['pending'] ?? 0)
    ];
    
    echo json_encode([
        'status' => 'success',
        'voters' => $voters,
        'stats' => $stats
    ]);
    
} catch(PDOException $exception) {
    error_log("Database error in get_voters.php: " . $exception->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $exception->getMessage(),
        'voters' => [],
        'stats' => ['total' => 0, 'completed' => 0, 'pending' => 0]
    ]);
}
?>