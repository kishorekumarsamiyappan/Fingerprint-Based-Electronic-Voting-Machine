<?php
session_start();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=voters_export_' . date('Y-m-d') . '.csv');

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
    
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Output the column headings
    fputcsv($output, array('Fingerprint ID', 'Name', 'Date of Birth', 'Aadhaar', 'Voter ID', 'Place', 'Status', 'Registered Date'));
    
    // Output the data
    foreach($voters as $voter) {
        fputcsv($output, [
            $voter['fingerprint_id'],
            $voter['name'] ?? 'N/A',
            $voter['dob'] ?? 'N/A',
            $voter['aadhaar'] ?? 'N/A',
            $voter['voter_id'] ?? 'N/A',
            $voter['place'],
            $voter['status'],
            $voter['created_at']
        ]);
    }
    
    fclose($output);
    
} catch(PDOException $exception) {
    // If there is an error, output a simple message
    echo "Database error: " . $exception->getMessage();
    error_log("Export voters error: " . $exception->getMessage());
}
?>