<?php
$conn = mysqli_connect("localhost", "root", "chandu123", "myapp", 3307);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name       = mysqli_real_escape_string($conn, $_POST['name']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $dob        = mysqli_real_escape_string($conn, $_POST['dob']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);

    $sql = "INSERT INTO students (name, email, dob, department, phone)
            VALUES ('$name', '$email', '$dob', '$department', '$phone')";

    if (mysqli_query($conn, $sql)) {
        $success = "Student registered successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

$result = mysqli_query($conn, "SELECT * FROM students ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; padding: 30px 20px; }
        h1 { text-align: center; color: #2d3748; margin-bottom: 30px; font-size: 28px; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card h2 { color: #4a5568; margin-bottom: 20px; font-size: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #4a5568; font-size: 14px; }
        input, select { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px; outline: none; }
        input:focus, select:focus { border-color: #667eea; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        button:hover { opacity: 0.9; }
        .success { background: #c6f6d5; color: #276749; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 500; }
        .error { background: #fed7d7; color: #9b2c2c; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 500; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        th, td { padding: 12px 15px; text-align: left; }
        tbody tr:nth-child(even) { background: #f7fafc; }
        tbody tr:hover { background: #ebf4ff; }
        td { color: #4a5568; border-bottom: 1px solid #e2e8f0; }
        .no-data { text-align: center; color: #a0aec0; padding: 20px; font-style: italic; }
    </style>
</head>
<body>
<div class="container">
    <h1>🎓 Student Registration System</h1>

    <div class="card">
        <h2>Register New Student</h2>
        <?php if ($success): ?>
            <div class="success">✅ <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error">❌ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Enter full name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter email" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" placeholder="Enter phone number" required>
                </div>
            </div>
            <div class="form-group">
                <label>Department</label>
                <select name="department" required>
                    <option value="">-- Select Department --</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Information Technology">Information Technology</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Mechanical">Mechanical</option>
                    <option value="Civil">Civil</option>
                    <option value="Business Administration">Business Administration</option>
                </select>
            </div>
            <button type="submit">Register Student</button>
        </form>
    </div>

    <div class="card">
        <h2>Registered Students</h2>
        <?php if (mysqli_num_rows($result) > 0): ?>
        <table>
            <thead>
                <tr><th>#</th><th>Name</th><th>Email</th><th>DOB</th><th>Department</th><th>Phone</th></tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= $row['dob'] ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="no-data">No students registered yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
