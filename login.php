<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$email    = trim($_POST['email']);
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT id, name, role, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];

        // Both roles go to the unified dashboard (T2 dashboard)
        // Profile pages are optional extras reachable from there
        if ($user['role'] === 'driver') {
            header("Location: driver_profile.php");
        } else {
            header("Location: rider_profile.php");
        }
        exit;
    }
}

header("Location: index.php?error=1");
exit;
?>
