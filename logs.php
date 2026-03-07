<?php
$conn = mysqli_connect("localhost", "root", "chandu123", "myapp", 3307);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$message  = "";
$msg_type = "";

// Handle INSERT student
if (isset($_POST['action']) && $_POST['action'] == 'insert') {
    $name       = mysqli_real_escape_string($conn, $_POST['name']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);

    $sql = "INSERT INTO students (name, email, dob, department, phone)
            VALUES ('$name', '$email', '2000-01-01', '$department', '0000000000')";

    if (mysqli_query($conn, $sql)) {
        $message  = "✅ Student inserted! Trigger automatically logged this action.";
        $msg_type = "success";
    } else {
        $message  = "❌ Error: " . mysqli_error($conn);
        $msg_type = "error";
    }
}

// Handle UPDATE student
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $id         = (int)$_POST['student_id'];
    $department = mysqli_real_escape_string($conn, $_POST['new_department']);

    $sql = "UPDATE students SET department = '$department' WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        $message  = "✅ Student updated! Trigger automatically logged this action.";
        $msg_type = "success";
    } else {
        $message  = "❌ Error: " . mysqli_error($conn);
        $msg_type = "error";
    }
}

// Fetch audit logs
$logs = mysqli_query($conn, "SELECT * FROM audit_log ORDER BY action_time DESC LIMIT 30");

// Fetch daily activity view
$daily = mysqli_query($conn, "SELECT * FROM daily_activity_report ORDER BY action_date DESC LIMIT 14");

// Fetch students for dropdown
$students = mysqli_query($conn, "SELECT id, name FROM students ORDER BY name");

// Stats
$total_logs    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM audit_log"))['c'];
$insert_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM audit_log WHERE action_type='INSERT'"))['c'];
$update_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM audit_log WHERE action_type='UPDATE'"))['c'];
$today_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM audit_log WHERE DATE(action_time) = CURDATE()"))['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs & Triggers</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f4f8;
            padding: 30px 20px;
        }

        h1 { text-align: center; color: #2d3748; margin-bottom: 6px; font-size: 28px; }
        .subtitle { text-align: center; color: #718096; font-size: 14px; margin-bottom: 30px; }

        .container { max-width: 1100px; margin: 0 auto; }

        .nav-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; font-weight: 600; font-size: 14px; }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-top: 4px solid #667eea;
        }

        .stat-card.green  { border-top-color: #38a169; }
        .stat-card.orange { border-top-color: #d97706; }
        .stat-card.red    { border-top-color: #e53e3e; }

        .stat-card .number { font-size: 32px; font-weight: 700; color: #667eea; }
        .stat-card.green  .number { color: #38a169; }
        .stat-card.orange .number { color: #d97706; }
        .stat-card.red    .number { color: #e53e3e; }
        .stat-card .label { font-size: 12px; color: #a0aec0; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Cards */
        .card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .card h2 {
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        /* Two column form layout */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }

        .form-section { background: #f7fafc; border-radius: 10px; padding: 20px; }
        .form-section h3 { font-size: 15px; color: #4a5568; margin-bottom: 15px; }

        .form-group { margin-bottom: 14px; }

        label { display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 6px; }

        input[type="text"], input[type="email"], select {
            width: 100%;
            padding: 9px 13px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: white;
        }

        input:focus, select:focus { border-color: #667eea; }

        .btn {
            width: 100%;
            padding: 11px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 5px;
        }

        .btn.green-btn { background: linear-gradient(135deg, #38a169, #276749); }
        .btn:hover { opacity: 0.9; }

        .msg-success { background: #f0fff4; border: 1px solid #9ae6b4; color: #276749; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
        .msg-error   { background: #fff5f5; border: 1px solid #fc8181; color: #c53030; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        th { padding: 12px 15px; text-align: left; font-weight: 600; }
        td { padding: 11px 15px; color: #4a5568; border-bottom: 1px solid #f0f4f8; }
        tbody tr:hover { background: #f7fafc; }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header h2 { border: none; margin: 0; padding: 0; }
        .badge { background: #ebf4ff; color: #667eea; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }

        .tag-insert { background: #c6f6d5; color: #276749; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .tag-update { background: #fefcbf; color: #744210; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }

        .view-tag { background: #e9d8fd; color: #553c9a; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

        @media (max-width: 700px) {
            .stats-grid  { grid-template-columns: 1fr 1fr; }
            .form-grid   { grid-template-columns: 1fr; }
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Audit Logging System</h1>
    <p class="subtitle">Automated Logging using MySQL Triggers & Views</p>

    <a href="dashboard.php" class="nav-link">← Back to Dashboard</a>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?= $total_logs ?></div>
            <div class="label">Total Logs</div>
        </div>
        <div class="stat-card green">
            <div class="number"><?= $insert_count ?></div>
            <div class="label">INSERT Logs</div>
        </div>
        <div class="stat-card orange">
            <div class="number"><?= $update_count ?></div>
            <div class="label">UPDATE Logs</div>
        </div>
        <div class="stat-card red">
            <div class="number"><?= $today_count ?></div>
            <div class="label">Today's Activity</div>
        </div>
    </div>

    <!-- Trigger Actions -->
    <div class="card">
        <h2>⚡ Trigger Demo — Perform Actions</h2>

        <?php if ($message): ?>
            <div class="msg-<?= $msg_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="form-grid">
            <!-- INSERT -->
            <div class="form-section">
                <h3>➕ INSERT Student <span style="font-size:11px; background:#c6f6d5; color:#276749; padding:2px 8px; border-radius:10px;">Fires INSERT Trigger</span></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="insert">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" placeholder="Student name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Email address" required>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department">
                            <option value="Computer Science">Computer Science</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Mechanical">Mechanical</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Insert & Trigger Log</button>
                </form>
            </div>

            <!-- UPDATE -->
            <div class="form-section">
                <h3>✏️ UPDATE Student <span style="font-size:11px; background:#fefcbf; color:#744210; padding:2px 8px; border-radius:10px;">Fires UPDATE Trigger</span></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <div class="form-group">
                        <label>Select Student</label>
                        <select name="student_id">
                            <?php while ($s = mysqli_fetch_assoc($students)): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>New Department</label>
                        <select name="new_department">
                            <option value="Computer Science">Computer Science</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Mechanical">Mechanical</option>
                            <option value="Civil">Civil</option>
                        </select>
                    </div>
                    <button type="submit" class="btn green-btn" style="margin-top: 53px;">Update & Trigger Log</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Daily Activity Report VIEW -->
    <div class="card">
        <div class="card-header">
            <h2>📅 Daily Activity Report <span class="view-tag">MySQL VIEW</span></h2>
            <span class="badge"><?= mysqli_num_rows($daily) ?> Days</span>
        </div>
        <?php if (mysqli_num_rows($daily) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Total Actions</th>
                    <th>INSERT Count</th>
                    <th>UPDATE Count</th>
                    <th>Tables Affected</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($daily)): ?>
                <tr>
                    <td><strong><?= $row['action_date'] ?></strong></td>
                    <td><strong style="color:#667eea"><?= $row['total_actions'] ?></strong></td>
                    <td><span class="tag-insert"><?= $row['insert_count'] ?> INSERT</span></td>
                    <td><span class="tag-update"><?= $row['update_count'] ?> UPDATE</span></td>
                    <td style="font-size:12px; color:#718096;"><?= $row['tables_affected'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center; color:#a0aec0; padding:30px; font-style:italic;">No activity yet. Perform actions above to generate logs!</p>
        <?php endif; ?>
    </div>

    <!-- Full Audit Log -->
    <div class="card">
        <div class="card-header">
            <h2>📋 Full Audit Log <span style="font-size:11px; background:#ebf4ff; color:#667eea; padding:2px 8px; border-radius:10px; margin-left:8px;">AUTO by Triggers</span></h2>
            <span class="badge"><?= $total_logs ?> Records</span>
        </div>
        <?php if ($total_logs > 0):
            $all_logs = mysqli_query($conn, "SELECT * FROM audit_log ORDER BY action_time DESC LIMIT 30");
        ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Description</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = mysqli_fetch_assoc($all_logs)): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td>
                        <span class="tag-<?= strtolower($log['action_type']) ?>">
                            <?= $log['action_type'] == 'INSERT' ? '➕' : '✏️' ?> <?= $log['action_type'] ?>
                        </span>
                    </td>
                    <td><code style="background:#f0f4f8; padding:2px 6px; border-radius:4px;"><?= $log['table_name'] ?></code></td>
                    <td>#<?= $log['record_id'] ?></td>
                    <td style="font-size:12px; color:#718096;"><?= htmlspecialchars($log['description']) ?></td>
                    <td style="font-size:12px;"><?= $log['action_time'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center; color:#a0aec0; padding:30px; font-style:italic;">No logs yet. Perform INSERT or UPDATE above!</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
<?php mysqli_close($conn); ?>
