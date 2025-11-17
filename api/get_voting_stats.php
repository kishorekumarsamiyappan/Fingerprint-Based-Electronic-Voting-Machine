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
    
    // Total voters (completed)
    $votersQuery = "SELECT COUNT(*) as total FROM voters WHERE place = :place AND status = 'completed'";
    $votersStmt = $db->prepare($votersQuery);
    $votersStmt->bindParam(':place', $place);
    $votersStmt->execute();
    $totalVoters = $votersStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Votes cast
    $votesQuery = "SELECT COUNT(*) as total FROM votes WHERE place = :place";
    $votesStmt = $db->prepare($votesQuery);
    $votesStmt->bindParam(':place', $place);
    $votesStmt->execute();
    $votedCount = $votesStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $notVotedCount = $totalVoters - $votedCount;
    
    echo json_encode([
        'status' => 'success',
        'stats' => [
            'totalVoters' => $totalVoters,
            'votedCount' => $votedCount,
            'notVotedCount' => $notVotedCount
        ]
    ]);
    
} catch(PDOException $exception) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $exception->getMessage()
    ]);
}
?>