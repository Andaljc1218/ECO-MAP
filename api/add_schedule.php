<?php
session_start();
require_once "../config/database.php";

header('Content-Type: application/json');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'driver'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$area = $_POST['area'] ?? '';
$pickupDate = $_POST['pickup_date'] ?? null;
$pickupTime = $_POST['pickupTime'] ?? '';
$pickupEndTime = $_POST['pickupEndTime'] ?? null;

// Convert to 24-hour format for MySQL
if ($pickupTime) {
    $pickupTime = date('H:i:s', strtotime($pickupTime));
}
if ($pickupEndTime) {
    $pickupEndTime = date('H:i:s', strtotime($pickupEndTime));
}

// Validate input
if (empty($area) || empty($pickupTime) || empty($pickupDate)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Prepare SQL statement
$stmt = $conn->prepare("INSERT INTO schedules (area, pickup_date, pickup_time, pickup_end_time, driver_id) VALUES (?, ?, ?, ?, ?)");
$driverId = $_SESSION['role'] === 'driver' ? $_SESSION['user_id'] : null;
$stmt->bind_param("ssssi", $area, $pickupDate, $pickupTime, $pickupEndTime, $driverId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Schedule added successfully',
        'schedule' => [
            'id' => $stmt->insert_id,
            'area' => $area,
            'pickup_time' => $pickupTime,
            'pickup_date' => $pickupDate,
            'driver_id' => $driverId
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error adding schedule'
    ]);
}

$stmt->close();
$conn->close();
?> 