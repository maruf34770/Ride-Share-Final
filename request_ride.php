<?php

include 'config.php';
header('Content-Type: application/json');

$rider_id = $_POST['passenger_id'] ?? $_POST['rider_id'] ?? '';
$pickup   = $_POST['pickup_location'] ?? '';
$dropoff  = $_POST['dropoff_location'] ?? $_POST['drop_location'] ?? '';
$distance = $_POST['distance'] ?? 0;
$vehicle  = $_POST['vehicle_type'] ?? 'car';

if ($rider_id && $pickup && $dropoff && $distance) {
    $distance = floatval($distance);

    // Fare rates matching fare_calculator.php
    $rates = ['car' => ['base' => 50, 'km' => 15], 'bike' => ['base' => 30, 'km' => 8], 'cng' => ['base' => 40, 'km' => 12]];
    $r    = $rates[$vehicle] ?? $rates['car'];
    $fare = $r['base'] + ($distance * $r['km']);

    $rider_id = $conn->real_escape_string($rider_id);
    $pickup   = $conn->real_escape_string($pickup);
    $dropoff  = $conn->real_escape_string($dropoff);
    $vehicle  = $conn->real_escape_string($vehicle);

    $sql = "INSERT INTO rides
            (rider_id, pickup_location, drop_location, distance, fare, vehicle_type, status)
            VALUES ('$rider_id','$pickup','$dropoff','$distance','$fare','$vehicle','Requested')";

    if ($conn->query($sql)) {
        $ride_id = $conn->insert_id;
        echo json_encode([
            'success'  => true,
            'message'  => 'Ride Requested Successfully',
            'ride_id'  => $ride_id,
            'fare'     => $fare,
            'distance' => $distance
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Please fill all fields']);
}
?>
