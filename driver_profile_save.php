<?php

session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$vehicle = $_POST['vehicle_type'];
$license = $_POST['license_number'];
$status  = $_POST['availability'];

$check = $conn->prepare("SELECT driver_id FROM driver WHERE user_id=?");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $stmt = $conn->prepare("
        UPDATE driver
        SET vehicle_type=?, license_number=?, availability_status=?
        WHERE user_id=?
    ");
    $stmt->bind_param("sssi", $vehicle, $license, $status, $user_id);
} else {
    $stmt = $conn->prepare("
        INSERT INTO driver (user_id, vehicle_type, license_number, availability_status)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $user_id, $vehicle, $license, $status);
}

if ($stmt->execute()) {
    header("Location: dashboard.php");
    exit;
} else {
    echo "Error: " . $conn->error;
}
?>
