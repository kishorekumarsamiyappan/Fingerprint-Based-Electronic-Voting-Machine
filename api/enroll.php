<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

if($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $fingerprint_id = $_POST['fingerprint_id'];
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $aadhaar = $_POST['aadhaar'];
    $voter_id = $_POST['voter_id'];
    $place = $_POST['place'];
    
    try {
        // Check if fingerprint ID already exists
        $check_query = "SELECT id FROM voters WHERE fingerprint_id = :fingerprint_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':fingerprint_id', $fingerprint_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            echo json_encode(array(
                "status" => "error",
                "message" => "Fingerprint ID already exists!"
            ));
            exit;
        }
        
        // Check if Aadhaar already exists
        $check_query = "SELECT id FROM voters WHERE aadhaar = :aadhaar";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':aadhaar', $aadhaar);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            echo json_encode(array(
                "status" => "error",
                "message" => "Aadhaar number already registered!"
            ));
            exit;
        }
        
        // Insert voter
        $query = "INSERT INTO voters (fingerprint_id, name, dob, aadhaar, voter_id, place) 
                  VALUES (:fingerprint_id, :name, :dob, :aadhaar, :voter_id, :place)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':fingerprint_id', $fingerprint_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':aadhaar', $aadhaar);
        $stmt->bindParam(':voter_id', $voter_id);
        $stmt->bindParam(':place', $place);
        
        if($stmt->execute()) {
            echo json_encode(array(
                "status" => "success",
                "message" => "Voter enrolled successfully! Fingerprint ID: " . $fingerprint_id
            ));
        } else {
            echo json_encode(array(
                "status" => "error",
                "message" => "Failed to enroll voter!"
            ));
        }
        
    } catch(PDOException $exception) {
        echo json_encode(array(
            "status" => "error",
            "message" => "Database error: " . $exception->getMessage()
        ));
    }
}
?>