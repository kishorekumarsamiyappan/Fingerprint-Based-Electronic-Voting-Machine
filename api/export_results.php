<?php
session_start();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=results_export_' . date('Y-m-d') . '.csv');

// Database configuration
$host = "localhost";
$dbname = "voting_system";
$username = "root";
$password = "";

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $place = $_SESSION['admin_place'];
    
    // Get results
    $query = "
        SELECT 
            c.id as candidate_id,
            c.name as candidate_name,
            COUNT(v.id) as vote_count
        FROM candidates c
        LEFT JOIN votes v ON c.id = v.candidate_id AND v.place = :place
        GROUP BY c.id, c.name
        ORDER BY c.id
    ";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':place', $place);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total votes
    $totalVotesQuery = "SELECT COUNT(*) as total FROM votes WHERE place = :place";
    $totalVotesStmt = $db->prepare($totalVotesQuery);
    $totalVotesStmt->bindParam(':place', $place);
    $totalVotesStmt->execute();
    $totalVotes = (int)$totalVotesStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Output the column headings
    fputcsv($output, array('Candidate ID', 'Candidate Name', 'Vote Count', 'Percentage'));
    
    // Output the data
    foreach($results as $row) {
        $percentage = $totalVotes > 0 ? round(($row['vote_count'] / $totalVotes) * 100, 2) : 0;
        fputcsv($output, [
            $row['candidate_id'],
            $row['candidate_name'],
            $row['vote_count'],
            $percentage . '%'
        ]);
    }
    
    // Add summary row
    fputcsv($output, array('', '', '', ''));
    fputcsv($output, array('Total Votes:', '', $totalVotes, '100%'));
    
    fclose($output);
    
} catch(PDOException $exception) {
    echo "Database error: " . $exception->getMessage();
    error_log("Export results error: " . $exception->getMessage());
}
?>