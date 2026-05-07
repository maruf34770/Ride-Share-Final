<?php
include 'config.php';
header('Content-Type: application/json');

$ride_id = $_POST['ride_id'] ?? '';
$status  = $_POST['status']  ?? '';
$allowed = ['Accepted', 'Ongoing', 'Completed', 'Cancelled', 'completed', 'cancelled'];

if (!$ride_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Ride ID and status are required']);
    exit;
}
if (!in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

$ride_id = $conn->real_escape_string($ride_id);
$status  = $conn->real_escape_string($status);

$transitions = [
    'Requested'  => ['Accepted', 'Cancelled', 'cancelled'],
    'Accepted'   => ['Ongoing',  'Cancelled', 'cancelled'],
    'Ongoing'    => ['Completed','Cancelled', 'completed', 'cancelled'],
    'Completed'  => [], 'completed' => [],
    'Cancelled'  => [], 'cancelled' => [],
];

$check = $conn->query("SELECT status FROM rides WHERE ride_id='$ride_id'");
if (!$check || $check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Ride not found.']);
    exit;
}
$current = $check->fetch_assoc()['status'];

if ($current === $status) {
    echo json_encode(['success' => true, 'message' => 'Ride is already "' . $status . '"', 'ride_id' => $ride_id, 'new_status' => $status]);
    exit;
}

if (!in_array($status, $transitions[$current] ?? [])) {
    $allowed_next = $transitions[$current] ?? [];
    $hint = count($allowed_next)
        ? 'Current status is "' . $current . '". Next allowed: ' . implode(' or ', $allowed_next) . '.'
        : 'Ride is "' . $current . '" — no further changes allowed.';
    echo json_encode(['success' => false, 'message' => $hint]);
    exit;
}

$extra = '';
if (in_array($status, ['Completed', 'completed'])) {
    $now   = date("Y-m-d H:i:s");
    $extra = ", completed_time='$now'";
}

// Normalize to lowercase for T3 compatibility (rides table uses lowercase status)
$dbStatus = strtolower($status) === 'completed' ? 'completed' :
            (strtolower($status) === 'cancelled' ? 'cancelled' : $status);

$sql = "UPDATE rides SET status='$dbStatus'$extra WHERE ride_id='$ride_id'";

if ($conn->query($sql)) {
    echo json_encode([
        'success'    => true,
        'message'    => 'Status updated: ' . $current . ' → ' . $dbStatus,
        'ride_id'    => $ride_id,
        'old_status' => $current,
        'new_status' => $dbStatus
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>
