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

$events = [];
if ($result) {
    $today = new DateTime();
    $daysOfWeek = [
        'Sunday' => 0,
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6
    ];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['pickup_date'])) {
            // One-time event
            $start = $row['pickup_date'] . 'T' . $row['pickup_time'];
            $event = [
                'id' => $row['id'],
                'title' => $row['area'],
                'start' => $start,
                'extendedProps' => [
                    'pickup_end_time' => $row['pickup_end_time'],
                    'driver_name' => $row['driver_name'] ?? 'Not assigned',
                    'time' => $row['pickup_time']
                ]
            ];
            $events[] = $event;
        }
    }
}
echo json_encode($events);
$conn->close(); 