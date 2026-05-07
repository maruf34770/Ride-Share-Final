<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: index.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .box {
            background: #ffffff;
            padding: 35px;
            border-radius: 16px;
            width: 360px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .welcome {
            text-align: center;
            font-size: 13px;
            color: #888;
            margin-bottom: 4px;
        }

        .box h2 { text-align: center; margin-bottom: 20px; color: #333; }

        .input-group { margin-bottom: 15px; }

        label { font-size: 13px; color: #666; display: block; margin-bottom: 5px; }

        input, select {
            width: 100%;
            padding: 10px 14px;
            margin-top: 2px;
            border-radius: 8px;
            border: 1px solid #ddd;
            outline: none;
            font-size: 14px;
            transition: 0.3s;
            background: #fff;
        }

        input:focus, select:focus {
            border-color: #43cea2;
            box-shadow: 0 0 5px rgba(67,206,162,0.4);
        }

        input[readonly] {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .logout {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
        }

        .logout a { color: #e74c3c; text-decoration: none; font-weight: 500; }
        .logout a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="box">
    <p class="welcome">Welcome, <?= htmlspecialchars($user_name) ?> 👋</p>
    <h2>🚗 Driver Profile</h2>

    <form action="driver_profile_save.php" method="POST">

        <!-- Hidden: auto-filled from session -->
        <input type="hidden" name="user_id" value="<?= $user_id ?>">

        <div class="input-group">
            <label>Logged in as (User ID: <?= $user_id ?>)</label>
            <input type="text" value="<?= htmlspecialchars($user_name) ?>" readonly>
        </div>

        <div class="input-group">
            <label>Vehicle Type</label>
            <select name="vehicle_type" required>
                <option value="">Select Vehicle</option>
                <option value="car">Car</option>
                <option value="bike">Bike</option>
            </select>
        </div>

        <div class="input-group">
            <label>License Number</label>
            <input type="text" name="license_number" placeholder="Enter License Number" required>
        </div>

        <div class="input-group">
            <label>Availability</label>
            <select name="availability">
                <option value="online">Online</option>
                <option value="offline">Offline</option>
            </select>
        </div>

        <button type="submit">Save Profile</button>
    </form>

    <div class="logout"><a href="logout.php">Logout →</a></div>
</div>

</body>
</html>
