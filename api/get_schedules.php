<?php
require_once "../config/database.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get schedules from database
$query = "SELECT s.*, u.username as driver_name 
          FROM schedules s 
          LEFT JOIN users u ON s.driver_id = u.id 
          ORDER BY s.area, s.pickup_time";
$result = $conn->query($query);

if ($result) {
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = [
            'id' => $row['id'],
            'area' => $row['area'],
            'pickup_time' => $row['pickup_time'],
            'pickup_end_time' => $row['pickup_end_time'],
            'pickup_date' => $row['pickup_date'],
            'driver_name' => $row['driver_name'] ?? 'Not assigned'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching schedules'
    ]);
}

$conn->close();
?> 