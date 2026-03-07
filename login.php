<?php
session_start();
$conn = mysqli_connect("localhost", "root", "chandu123", "myapp", 3307);

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql    = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['name'];
            header("Location: welcome.php");
            exit();
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "No account found with that email!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            background: white;
            border-radius: 16px;
            padding: 40px 35px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 26px;
            color: #2d3748;
            margin-top: 10px;
        }

        .logo p {
            color: #a0aec0;
            font-size: 14px;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 7px;
        }

        input {
            width: 100%;
            padding: 11px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s;
        }

        input:focus { border-color: #667eea; }

        input.error-input { border-color: #fc8181; }

        .error-msg {
            color: #e53e3e;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .server-error {
            background: #fff5f5;
            color: #c53030;
            border: 1px solid #fc8181;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 18px;
            text-align: center;
        }

        .btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 5px;
            transition: opacity 0.2s;
        }

        .btn:hover { opacity: 0.9; }

        .footer-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #718096;
        }

        .footer-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #a0aec0;
            user-select: none;
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="logo">
        <div style="font-size:48px;">🔐</div>
        <h1>Welcome Back</h1>
        <p>Sign in to your account</p>
    </div>

    <?php if ($error): ?>
        <div class="server-error">❌ <?= $error ?></div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="" novalidate>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your email">
            <span class="error-msg" id="emailError">Please enter a valid email address.</span>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="Enter your password">
                <span class="toggle-pw" onclick="togglePassword()">👁️</span>
            </div>
            <span class="error-msg" id="passwordError">Password must be at least 4 characters.</span>
        </div>

        <button type="submit" class="btn">Login</button>
    </form>

    <div class="footer-link">
        Don't have an account? <a href="signup.php">Sign Up</a>
    </div>
</div>

<script>
    // JavaScript Validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        let valid = true;

        const email    = document.getElementById('email');
        const password = document.getElementById('password');
        const emailErr = document.getElementById('emailError');
        const passErr  = document.getElementById('passwordError');

        // Reset
        email.classList.remove('error-input');
        password.classList.remove('error-input');
        emailErr.style.display = 'none';
        passErr.style.display  = 'none';

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.value.trim() || !emailRegex.test(email.value)) {
            email.classList.add('error-input');
            emailErr.style.display = 'block';
            valid = false;
        }

        // Password validation
        if (password.value.trim().length < 4) {
            password.classList.add('error-input');
            passErr.style.display = 'block';
            valid = false;
        }

        if (!valid) e.preventDefault();
    });

    // Show/hide password
    function togglePassword() {
        const pw = document.getElementById('password');
        pw.type = pw.type === 'password' ? 'text' : 'password';
    }

    // Real-time validation
    document.getElementById('email').addEventListener('input', function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailRegex.test(this.value)) {
            this.classList.remove('error-input');
            document.getElementById('emailError').style.display = 'none';
        }
    });

    document.getElementById('password').addEventListener('input', function() {
        if (this.value.length >= 4) {
            this.classList.remove('error-input');
            document.getElementById('passwordError').style.display = 'none';
        }
    });
</script>

</body>
</html>
<?php mysqli_close($conn); ?>
