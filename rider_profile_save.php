<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: index.php");
    exit;
}

$user_id            = $_SESSION['user_id'];
$home_address       = $_POST['home_address'];
$work_address       = $_POST['work_address'];
$preferred_vehicle  = $_POST['preferred_vehicle'];
$payment_preference = $_POST['payment_preference'];

$check = $conn->prepare("SELECT rider_id FROM rider_profile WHERE user_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $stmt = $conn->prepare("
        UPDATE rider_profile
        SET home_address=?, work_address=?, preferred_vehicle=?, payment_preference=?
        WHERE user_id=?
    ");
    $stmt->bind_param("ssssi", $home_address, $work_address, $preferred_vehicle, $payment_preference, $user_id);
} else {
    $stmt = $conn->prepare("
        INSERT INTO rider_profile (user_id, home_address, work_address, preferred_vehicle, payment_preference)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $user_id, $home_address, $work_address, $preferred_vehicle, $payment_preference);
}

if ($stmt->execute()) {
    header("Location: dashboard.php");
    exit;
} else {
    echo "Error: " . $conn->error;
}
?>
