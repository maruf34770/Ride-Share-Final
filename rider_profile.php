<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
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
    <title>Rider Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            padding: 20px;
        }

        .card {
            width: 400px;
            background: #ffffff;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .welcome {
            text-align: center;
            font-size: 13px;
            color: #888;
            margin-bottom: 4px;
        }

        .title {
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 20px;
            color: #111827;
            text-align: center;
        }

        .input-group { margin-bottom: 14px; }

        label {
            font-size: 13px;
            color: #374151;
            margin-bottom: 5px;
            display: block;
        }

        input, select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            outline: none;
            transition: 0.2s;
            font-size: 14px;
            background: #fff;
        }

        input:focus, select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 5px rgba(79,70,229,0.3);
        }

        input[readonly] {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }

        button:hover { transform: scale(1.02); opacity: 0.95; }

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

<div class="card">
    <p class="welcome">Welcome, <?= htmlspecialchars($user_name) ?> 👋</p>
    <div class="title">🙋 Rider Profile</div>

    <form action="rider_profile_save.php" method="POST">

        <!-- Hidden: auto-filled from session -->
        <input type="hidden" name="user_id" value="<?= $user_id ?>">

        <div class="input-group">
            <label>Logged in as (User ID: <?= $user_id ?>)</label>
            <input type="text" value="<?= htmlspecialchars($user_name) ?>" readonly>
        </div>

        <div class="input-group">
            <label>Home Address</label>
            <input type="text" name="home_address" placeholder="e.g. Mirpur, Dhaka">
        </div>

        <div class="input-group">
            <label>Work Address</label>
            <input type="text" name="work_address" placeholder="e.g. Banani, Dhaka">
        </div>

        <div class="input-group">
            <label>Preferred Vehicle</label>
            <select name="preferred_vehicle">
                <option value="both">Both</option>
                <option value="car">Car</option>
                <option value="bike">Bike</option>
            </select>
        </div>

        <div class="input-group">
            <label>Payment Preference</label>
            <select name="payment_preference">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="mobile_banking">Mobile Banking</option>
            </select>
        </div>

        <button type="submit">Save Profile</button>
    </form>

    <div class="logout"><a href="logout.php">Logout →</a></div>
</div>

</body>
</html>
