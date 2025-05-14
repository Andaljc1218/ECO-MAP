<?php
require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Manila'); // Set to your local timezone

// Get current datetime
$now = date('Y-m-d H:i:s');

// Delete schedules where the end datetime is in the past
// If pickup_end_time is null, use pickup_time as the end time
$sql = "DELETE FROM schedules WHERE 
    (
        pickup_date IS NOT NULL AND 
        (
            (
                pickup_end_time IS NOT NULL AND CONCAT(pickup_date, ' ', pickup_end_time) < ?
            )
            OR
            (
                pickup_end_time IS NULL AND CONCAT(pickup_date, ' ', pickup_time) < ?
            )
        )
    )";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $now, $now);
$stmt->execute();
$stmt->close();

// Only close the connection if this script is run directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $conn->close();
}
?> 