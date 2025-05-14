<?php
require_once "../config/database.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get pickup points from database
$query = "SELECT id, name, latitude, longitude, schedule FROM pickup_points";
$result = $conn->query($query);

if ($result) {
    $points = [];
    while ($row = $result->fetch_assoc()) {
        $points[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
            'schedule' => $row['schedule']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'points' => $points
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching pickup points'
    ]);
}

$conn->close();
?> 