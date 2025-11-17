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
    
    $admin_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if($admin_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid admin ID']);
        exit;
    }
    
    // Prevent deletion of current logged-in admin
    if($admin_id == $_SESSION['admin_id']) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete your own account']);
        exit;
    }
    
    // Delete admin
    $deleteQuery = "DELETE FROM admin WHERE id = :id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
    
    if($deleteStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Admin deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete admin']);
    }
    
} catch(PDOException $exception) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $exception->getMessage()
    ]);
}
?>