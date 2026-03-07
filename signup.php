<?php
session_start();
$conn = mysqli_connect("localhost", "root", "chandu123", "myapp", 3307);

$success = "";
$error   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email exists
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email already registered!";
    } else {
        $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
        if (mysqli_query($conn, $sql)) {
            $success = "Account created! <a href='login.php'>Login now</a>";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .box { background: white; border-radius: 16px; padding: 40px 35px; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo h1 { font-size: 24px; color: #2d3748; margin-top: 10px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 7px; }
        input { width: 100%; padding: 11px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px; outline: none; }
        input:focus { border-color: #667eea; }
        input.error-input { border-color: #fc8181; }
        .error-msg { color: #e53e3e; font-size: 12px; margin-top: 5px; display: none; }
        .server-error { background: #fff5f5; color: #c53030; border: 1px solid #fc8181; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 15px; text-align: center; }
        .server-success { background: #f0fff4; color: #276749; border: 1px solid #9ae6b4; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 15px; text-align: center; }
        .btn { width: 100%; padding: 13px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 5px; }
        .btn:hover { opacity: 0.9; }
        .footer-link { text-align: center; margin-top: 20px; font-size: 13px; color: #718096; }
        .footer-link a { color: #667eea; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="box">
    <div class="logo">
        <div style="font-size:48px;">📝</div>
        <h1>Create Account</h1>
    </div>

    <?php if ($error): ?>
        <div class="server-error">❌ <?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="server-success">✅ <?= $success ?></div>
    <?php endif; ?>

    <form id="signupForm" method="POST" novalidate>
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" id="name" name="name" placeholder="Enter your name">
            <span class="error-msg" id="nameError">Name is required.</span>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your email">
            <span class="error-msg" id="emailError">Please enter a valid email.</span>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" id="password" name="password" placeholder="Min 6 characters">
            <span class="error-msg" id="passwordError">Password must be at least 6 characters.</span>
        </div>
        <button type="submit" class="btn">Create Account</button>
    </form>

    <div class="footer-link">Already have an account? <a href="login.php">Login</a></div>
</div>

<script>
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        let valid = true;

        const name     = document.getElementById('name');
        const email    = document.getElementById('email');
        const password = document.getElementById('password');

        [name, email, password].forEach(el => el.classList.remove('error-input'));
        ['nameError','emailError','passwordError'].forEach(id => document.getElementById(id).style.display = 'none');

        if (!name.value.trim()) {
            name.classList.add('error-input');
            document.getElementById('nameError').style.display = 'block';
            valid = false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value)) {
            email.classList.add('error-input');
            document.getElementById('emailError').style.display = 'block';
            valid = false;
        }

        if (password.value.length < 6) {
            password.classList.add('error-input');
            document.getElementById('passwordError').style.display = 'block';
            valid = false;
        }

        if (!valid) e.preventDefault();
    });
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
