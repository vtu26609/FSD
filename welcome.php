<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .box { background: white; border-radius: 16px; padding: 50px 40px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-width: 400px; width: 100%; }
        h1 { font-size: 28px; color: #2d3748; margin: 15px 0 10px; }
        p { color: #718096; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 5px; }
        .btn-red { background: linear-gradient(135deg, #fc8181, #e53e3e); }
    </style>
</head>
<body>
<div class="box">
    <div style="font-size:64px;">🎉</div>
    <h1>Welcome, <?= htmlspecialchars($_SESSION['user']) ?>!</h1>
    <p>You have successfully logged in.</p>
    <a href="dashboard.php" class="btn">Go to Dashboard</a>
    <a href="logout.php" class="btn btn-red">Logout</a>
</div>
</body>
</html>
