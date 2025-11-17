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
        echo json_encode(['status' => 'error', 'message' => 'Invalid fingerprint ID']);
        exit;
    }
    
    // Get voter details
    $query = "SELECT name, dob, aadhaar, voter_id FROM voters WHERE fingerprint_id = :fingerprint_id AND status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':fingerprint_id', $fingerprint_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $voter = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'status' => 'success',
            'name' => $voter['name'],
            'dob' => $voter['dob'],
            'aadhaar' => $voter['aadhaar'],
            'voter_id' => $voter['voter_id']
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Voter not found or not completed registration']);
    }
    
} catch(PDOException $exception) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $exception->getMessage()
    ]);
}
?>