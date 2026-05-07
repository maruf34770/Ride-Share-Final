<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include 'config.php';

// Support lookup by ride_id OR rider_id
$ride_id  = isset($_GET['ride_id'])  ? (int)$_GET['ride_id']  : 0;
$rider_id = isset($_GET['rider_id']) ? (int)$_GET['rider_id'] : 0;

if (!$ride_id && !$rider_id) {
    echo json_encode(["error" => "ride_id or rider_id required"]);
    exit();
}

if ($ride_id) {
    $where = "p.ride_id = $ride_id";
} else {
    $where = "r.rider_id = $rider_id";
}

// JOIN rides + payments for full payment page display
$sql = "SELECT 
            p.payment_id,
            p.ride_id,
            p.amount,
            p.payment_method,
            p.payment_status,
            p.created_at        AS payment_date,
            r.pickup_location,
            r.drop_location,
            r.fare              AS ride_fare,
            r.status            AS ride_status,
            r.rider_id,
            r.driver_id
        FROM payments p
        JOIN rides r ON p.ride_id = r.ride_id
        WHERE $where
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => $conn->error]);
    exit();
}

$payments = [];
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}

echo json_encode([
    "success"  => true,
    "total"    => count($payments),
    "payments" => $payments
]);
?>