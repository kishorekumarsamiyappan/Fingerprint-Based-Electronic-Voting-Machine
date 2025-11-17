<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';
    $place = isset($input['place']) ? trim($input['place']) : '';
    
    if(empty($username) || empty($password) || empty($place)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required'
        ]);
        exit;
    }
    
    // Database configuration
    $host = "localhost";
    $dbname = "voting_system";
    $username_db = "root";
    $password_db = "";
    
    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if username already exists
        $checkQuery = "SELECT id FROM admin WHERE username = :username";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Username already exists'
            ]);
            exit;
        }
        
        // Insert new admin
        $insertQuery = "INSERT INTO admin (username, password, place) VALUES (:username, :password, :place)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':username', $username);
        $insertStmt->bindParam(':password', $password);
        $insertStmt->bindParam(':place', $place);
        
        if($insertStmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Admin added successfully'
            ]);
        } else {
            throw new Exception('Failed to insert admin record');
        }
        
    } catch(PDOException $exception) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $exception->getMessage()
        ]);
    } catch(Exception $exception) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error: ' . $exception->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method is allowed'
    ]);
}
?>