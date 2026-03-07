<?php
$conn = mysqli_connect("localhost", "root", "chandu123", "myapp", 3307);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get filter and sort values
$filter_dept = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';

// Validate sort
$allowed_sorts = ['name', 'dob'];
if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'name';

// Build query
$where = $filter_dept ? "WHERE department = '$filter_dept'" : "";
$query = "SELECT * FROM students $where ORDER BY $sort_by ASC";
$result = mysqli_query($conn, $query);

// Count per department
$dept_count_result = mysqli_query($conn, "SELECT department, COUNT(*) as total FROM students GROUP BY department ORDER BY total DESC");

// Get all departments for filter dropdown
$dept_result = mysqli_query($conn, "SELECT DISTINCT department FROM students ORDER BY department");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f4f8;
            padding: 30px 20px;
        }

        h1 {
            text-align: center;
            color: #2d3748;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .container { max-width: 1000px; margin: 0 auto; }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-top: 4px solid #667eea;
        }

        .stat-card .dept-name {
            font-size: 13px;
            color: #718096;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .count {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-card .label {
            font-size: 12px;
            color: #a0aec0;
            margin-top: 4px;
        }

        /* Filter & Sort Controls */
        .controls {
            background: white;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            min-width: 180px;
        }

        .control-group label {
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
        }

        .control-group select {
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: white;
            cursor: pointer;
        }

        .control-group select:focus { border-color: #667eea; }

        .btn {
            padding: 10px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover { opacity: 0.9; }

        .btn-reset {
            padding: 10px 24px;
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        /* Table */
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h2 {
            color: #2d3748;
            font-size: 18px;
        }

        .badge {
            background: #ebf4ff;
            color: #667eea;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }

        thead { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }

        th { padding: 13px 15px; text-align: left; font-weight: 600; }

        td { padding: 12px 15px; color: #4a5568; border-bottom: 1px solid #f0f4f8; }

        tbody tr:hover { background: #f7fafc; }

        .dept-tag {
            background: #ebf4ff;
            color: #667eea;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .no-data {
            text-align: center;
            color: #a0aec0;
            padding: 40px;
            font-style: italic;
        }

        .nav-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .nav-link:hover { text-decoration: underline; }

        @media (max-width: 600px) {
            .controls { flex-direction: column; }
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📊 Student Dashboard</h1>

    <a href="register.php" class="nav-link">← Back to Registration</a>

    <!-- Department Count Cards -->
    <div class="stats-grid">
        <?php
        mysqli_data_seek($dept_count_result, 0);
        $colors = ['#667eea','#764ba2','#f093fb','#4facfe','#43e97b','#fa709a'];
        $i = 0;
        while ($dept = mysqli_fetch_assoc($dept_count_result)):
            $color = $colors[$i % count($colors)];
        ?>
        <div class="stat-card" style="border-top-color: <?= $color ?>">
            <div class="dept-name"><?= htmlspecialchars($dept['department']) ?></div>
            <div class="count" style="color: <?= $color ?>"><?= $dept['total'] ?></div>
            <div class="label">Students</div>
        </div>
        <?php $i++; endwhile; ?>
    </div>

    <!-- Filter & Sort Controls -->
    <form method="GET" action="">
        <div class="controls">
            <div class="control-group">
                <label>Filter by Department</label>
                <select name="department">
                    <option value="">All Departments</option>
                    <?php
                    mysqli_data_seek($dept_result, 0);
                    while ($dept = mysqli_fetch_assoc($dept_result)):
                    ?>
                    <option value="<?= htmlspecialchars($dept['department']) ?>"
                        <?= $filter_dept == $dept['department'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['department']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="control-group">
                <label>Sort By</label>
                <select name="sort">
                    <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
                    <option value="dob" <?= $sort_by == 'dob' ? 'selected' : '' ?>>Date of Birth</option>
                </select>
            </div>

            <button type="submit" class="btn">Apply</button>
            <a href="dashboard.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <!-- Students Table -->
    <div class="card">
        <div class="card-header">
            <h2>Student Records</h2>
            <span class="badge"><?= mysqli_num_rows($result) ?> Students</span>
        </div>

        <?php if (mysqli_num_rows($result) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Date of Birth</th>
                    <th>Department</th>
                    <th>Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= $row['dob'] ?></td>
                    <td><span class="dept-tag"><?= htmlspecialchars($row['department']) ?></span></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="no-data">No students found.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

<?php mysqli_close($conn); ?>
