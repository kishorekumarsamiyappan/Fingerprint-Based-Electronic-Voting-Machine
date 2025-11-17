<?php
session_start();
header('Content-Type: application/json');

// Database configuration
$host = "localhost";
$dbname = "voting_system";
$username = "root";
$password = "";

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $place = $_SESSION['admin_place'];
    
    $query = "
        SELECT 
            v.fingerprint_id,
            vr.name,
            v.candidate_id,
            v.voted_at
        FROM votes v
        LEFT JOIN voters vr ON v.fingerprint_id = vr.fingerprint_id
        WHERE v.place = :place
        ORDER BY v.voted_at DESC
        LIMIT 10
    ";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':place', $place);
    $stmt->execute();
    
    $recentVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'recentVotes' => $recentVotes
    ]);
    
} catch(PDOException $exception) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $exception->getMessage()
    ]);
}
?>