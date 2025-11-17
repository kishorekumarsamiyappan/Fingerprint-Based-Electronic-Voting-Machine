<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $place = isset($input['place']) ? $input['place'] : '';
    $wifi_status = isset($input['wifi_status']) ? $input['wifi_status'] : '';
    $sensor_status = isset($input['sensor_status']) ? $input['sensor_status'] : '';
    $template_count = isset($input['template_count']) ? intval($input['template_count']) : 0;
    $ip_address = isset($input['ip_address']) ? $input['ip_address'] : '';
    $last_enroll_id = isset($input['last_enroll_id']) ? intval($input['last_enroll_id']) : 0;
    $votes_total = isset($input['votes_total']) ? intval($input['votes_total']) : 0;
    
    // Database configuration
    $host = "localhost";
    $dbname = "voting_system";
    $username = "root";
    $password = "";
    
    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Insert heartbeat data
        $query = "INSERT INTO device_heartbeats (place, wifi_status, sensor_status, template_count, ip_address, last_enroll_id, votes_total) 
                  VALUES (:place, :wifi_status, :sensor_status, :template_count, :ip_address, :last_enroll_id, :votes_total)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':place', $place);
        $stmt->bindParam(':wifi_status', $wifi_status);
        $stmt->bindParam(':sensor_status', $sensor_status);
        $stmt->bindParam(':template_count', $template_count);
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->bindParam(':last_enroll_id', $last_enroll_id);
        $stmt->bindParam(':votes_total', $votes_total);
        
        if($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Heartbeat received']);
        } else {
            throw new Exception('Failed to store heartbeat');
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