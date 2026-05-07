<?php
session_start();
include 'config.php';

$name     = trim($_POST['name']);
$phone    = trim($_POST['phone']);
$email    = trim($_POST['email']);
$role     = $_POST['role'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

$stmt = $conn->prepare(
    "INSERT INTO users (name, phone, email, password, role) VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssss", $name, $phone, $email, $password, $role);

if ($stmt->execute()) {
    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['name']    = $name;
    $_SESSION['role']    = $role;

    // Go to profile page first (to fill vehicle/address info), then dashboard
    if ($role === 'driver') {
        header("Location: driver_profile.php");
    } else {
        header("Location: rider_profile.php");
    }
    exit;
} else {
    $error = $conn->error;
    echo "<!DOCTYPE html><html><body style='font-family:sans-serif;text-align:center;padding:40px;'>";
    echo "<h3>⚠️ Registration failed</h3>";
    echo "<p style='color:#c0392b;'>$error</p>";
    echo "<a href='register.html'>← Go back</a>";
    echo "</body></html>";
}
?>
