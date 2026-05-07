<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ride Sharing - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            height: 100vh;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            width: 360px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .logo {
            text-align: center;
            font-size: 36px;
            margin-bottom: 5px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 5px;
            font-size: 20px;
        }

        .subtitle {
            text-align: center;
            font-size: 12px;
            color: #888;
            margin-bottom: 25px;
        }

        .input-group {
            margin-bottom: 15px;
        }

        label {
            font-size: 13px;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 11px 14px;
            border-radius: 8px;
            border: 1px solid #ddd;
            outline: none;
            font-size: 14px;
            transition: 0.3s;
        }

        input:focus {
            border-color: #4facfe;
            box-shadow: 0 0 6px rgba(79,172,254,0.35);
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 5px;
        }

        .btn:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 18px rgba(0,0,0,0.15);
        }

        .error-msg {
            background: #ffe0e0;
            color: #c0392b;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 15px;
            text-align: center;
        }

        .register-link {
            text-align: center;
            margin-top: 18px;
            font-size: 13px;
            color: #666;
        }

        .register-link a {
            color: #4facfe;
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="logo">🚖</div>
    <h2>Welcome Back</h2>
    <p class="subtitle">Login to your ride sharing account</p>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-msg">❌ Invalid email or password. Try again.</div>
    <?php endif; ?>

    <form action="login.php" method="POST">

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter your email" required autofocus>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <button class="btn" type="submit">Login</button>
    </form>

    <div class="register-link">
        Don't have an account? <a href="register.html">Register here</a>
    </div>
</div>

</body>
</html>
