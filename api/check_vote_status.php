<?php
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
        echo json_encode(['has_voted' => false]);
        exit;
    }
    
    // Check if the voter has already voted
    $query = "SELECT COUNT(*) as count FROM votes WHERE fingerprint_id = :fingerprint_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':fingerprint_id', $fingerprint_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $has_voted = ($result['count'] > 0);
    
    echo json_encode(['has_voted' => $has_voted]);
    
} catch(PDOException $exception) {
    echo json_encode(['has_voted' => false, 'error' => $exception->getMessage()]);
}
?>