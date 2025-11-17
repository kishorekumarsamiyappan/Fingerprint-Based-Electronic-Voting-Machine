<?php
session_start();
header('Content-Type: application/json');

// Database configuration
$host = "localhost";
$dbname = "voting_system";
$username = "root";
$password = "";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_username = $_POST['username'] ?? '';
    $input_password = $_POST['password'] ?? '';
    $input_place = $_POST['place'] ?? '';
    
    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if admin table exists
        $tableCheck = $db->query("SHOW TABLES LIKE 'admin'");
        if($tableCheck->rowCount() == 0) {
            // Fallback to hardcoded credentials if table doesn't exist
            if($input_username === '23ECR117' && $input_password === 'Kumar@010506') {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $input_username;
                $_SESSION['admin_place'] = $input_place;
                $_SESSION['admin_id'] = 1;
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful!',
                    'place' => $input_place
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid credentials!'
                ]);
            }
            exit;
        }
        
        // Check admin credentials from database
        $query = "SELECT * FROM admin WHERE username = :username AND place = :place";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $input_username);
        $stmt->bindParam(':place', $input_place);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if($input_password === $admin['password']) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $input_username;
                $_SESSION['admin_place'] = $input_place;
                $_SESSION['admin_id'] = $admin['id'];
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful!',
                    'place' => $input_place
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid password!'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid username or place!'
            ]);
        }
        
    } catch(PDOException $exception) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $exception->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>