<?php

include 'config.php';
header('Content-Type: application/json');

$ride_id = $_POST['ride_id'] ?? '';

if (!$ride_id) {
    echo json_encode(['success' => false, 'message' => 'Ride ID is required']);
    exit;
}

$ride_id = $conn->real_escape_string($ride_id);

// Check ride exists
$check = $conn->query("SELECT ride_id, status, driver_id FROM rides WHERE ride_id='$ride_id'");
if (!$check || $check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Ride not found. Check the Ride ID.']);
    exit;
}
$ride = $check->fetch_assoc();

if ($ride['status'] !== 'Requested') {
    echo json_encode([
        'success' => false,
        'message' => 'This ride is "' . $ride['status'] . '" — only Requested rides can be assigned a driver.'
    ]);
    exit;
}


$sql = "SELECT d.driver_id,
               COALESCE(u.name, CONCAT('Driver #', d.driver_id)) AS driver_name,
               d.vehicle_type,
               d.license_number
        FROM driver d
        LEFT JOIN users u ON d.user_id = u.id
        WHERE d.availability_status = 'online'
          AND d.driver_id NOT IN (
              SELECT COALESCE(driver_id, 0) FROM rides
              WHERE status IN ('Accepted','Ongoing')
              AND driver_id IS NOT NULL
          )
        LIMIT 1";

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    $total  = $conn->query("SELECT COUNT(*) c FROM driver")->fetch_assoc()['c'] ?? 0;
    $online = $conn->query("SELECT COUNT(*) c FROM driver WHERE availability_status='online'")->fetch_assoc()['c'] ?? 0;

    if ($total == 0) {
        $msg = 'No drivers in the system yet.';
    } elseif ($online == 0) {
        $msg = 'No drivers are online. Set a driver availability_status to "online" in your database.';
    } else {
        $msg = 'All ' . $online . ' online driver(s) are currently on active rides.';
    }
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$driver    = $result->fetch_assoc();
$driver_id = $driver['driver_id'];

$update = "UPDATE rides SET driver_id='$driver_id', status='Accepted' WHERE ride_id='$ride_id'";

if ($conn->query($update)) {
    echo json_encode([
        'success'      => true,
        'message'      => 'Driver assigned successfully',
        'driver_id'    => $driver_id,
        'driver_name'  => $driver['driver_name'],
        'vehicle_type' => $driver['vehicle_type'] ?? 'N/A',
        'license'      => $driver['license_number'] ?? 'N/A'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
}
?>
