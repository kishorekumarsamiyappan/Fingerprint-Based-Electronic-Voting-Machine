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
    
    // Check for recent heartbeat (within last 2 minutes)
    $heartbeatQuery = "SELECT wifi_status, sensor_status, template_count, ip_address, last_heartbeat 
                      FROM device_heartbeats 
                      WHERE place = :place 
                      AND last_heartbeat >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                      ORDER BY last_heartbeat DESC 
                      LIMIT 1";
    $heartbeatStmt = $db->prepare($heartbeatQuery);
    $heartbeatStmt->bindParam(':place', $place);
    $heartbeatStmt->execute();
    
    if($heartbeatStmt->rowCount() > 0) {
        $heartbeat = $heartbeatStmt->fetch(PDO::FETCH_ASSOC);
        $wifiStatus = $heartbeat['wifi_status'] ?? 'Unknown';
        $sensorStatus = $heartbeat['sensor_status'] ?? 'Unknown';
        $esp32Active = true;
    } else {
        $wifiStatus = "Disconnected";
        $sensorStatus = "Disconnected";
        $esp32Active = false;
    }
    
    // Get quick stats
    $votersQuery = "SELECT COUNT(*) as total FROM voters WHERE place = :place AND status = 'completed'";
    $votersStmt = $db->prepare($votersQuery);
    $votersStmt->bindParam(':place', $place);
    $votersStmt->execute();
    $totalVoters = $votersStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $votesQuery = "SELECT COUNT(*) as total FROM votes WHERE place = :place";
    $votesStmt = $db->prepare($votesQuery);
    $votesStmt->bindParam(':place', $place);
    $votesStmt->execute();
    $votesCast = $votesStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'status' => 'success',
        'wifi_status' => $wifiStatus,
        'sensor_status' => $sensorStatus,
        'esp32_active' => $esp32Active,
        'total_voters' => (int)$totalVoters,
        'votes_cast' => (int)$votesCast
    ]);
    
} catch(PDOException $exception) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $exception->getMessage(),
        'wifi_status' => 'Error',
        'sensor_status' => 'Error',
        'esp32_active' => false,
        'total_voters' => 0,
        'votes_cast' => 0
    ]);
}
?>