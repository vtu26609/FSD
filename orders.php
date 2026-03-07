<?php
$conn = mysqli_connect("localhost", "root", "chandu123", "myapp", 3307);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

// =====================
// JOIN: Full order history with customer + product info
// =====================
$order_history = mysqli_query($conn, "
    SELECT 
        o.id AS order_id,
        c.name AS customer_name,
        c.email AS customer_email,
        p.name AS product_name,
        p.price,
        o.quantity,
        (p.price * o.quantity) AS total_amount,
        o.order_date
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN products p ON o.product_id = p.id
    ORDER BY o.order_date DESC
");

// =====================
// SUBQUERY: Highest value order
// =====================
$highest_order = mysqli_query($conn, "
    SELECT 
        c.name AS customer_name,
        p.name AS product_name,
        (p.price * o.quantity) AS total_amount,
        o.order_date
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN products p ON o.product_id = p.id
    WHERE (p.price * o.quantity) = (
        SELECT MAX(p2.price * o2.quantity)
        FROM orders o2
        JOIN products p2 ON o2.product_id = p2.id
    )
    LIMIT 1
");
$highest = mysqli_fetch_assoc($highest_order);

// =====================
// SUBQUERY: Most active customer (most orders)
// =====================
$most_active = mysqli_query($conn, "
    SELECT 
        c.name AS customer_name,
        c.email,
        COUNT(o.id) AS total_orders,
        SUM(p.price * o.quantity) AS total_spent
    FROM customers c
    JOIN orders o ON c.id = o.customer_id
    JOIN products p ON o.product_id = p.id
    GROUP BY c.id
    HAVING COUNT(o.id) = (
        SELECT MAX(order_count) FROM (
            SELECT COUNT(id) AS order_count FROM orders GROUP BY customer_id
        ) AS counts
    )
    LIMIT 1
");
$active = mysqli_fetch_assoc($most_active);

// =====================
// Summary per customer
// =====================
$customer_summary = mysqli_query($conn, "
    SELECT 
        c.name,
        COUNT(o.id) AS total_orders,
        SUM(p.price * o.quantity) AS total_spent
    FROM customers c
    JOIN orders o ON c.id = o.customer_id
    JOIN products p ON o.product_id = p.id
    GROUP BY c.id
    ORDER BY total_spent DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
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
            margin-bottom: 8px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #718096;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .container { max-width: 1100px; margin: 0 auto; }

        /* Highlight Cards */
        .highlight-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .highlight-card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 5px solid #667eea;
        }

        .highlight-card.gold { border-left-color: #f6ad55; }
        .highlight-card.green { border-left-color: #68d391; }

        .highlight-card h3 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #a0aec0;
            margin-bottom: 12px;
        }

        .highlight-card .big-name {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 6px;
        }

        .highlight-card .detail {
            font-size: 13px;
            color: #718096;
            margin-top: 4px;
        }

        .highlight-card .amount {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            margin-top: 8px;
        }

        .highlight-card.gold .amount { color: #d97706; }
        .highlight-card.green .amount { color: #38a169; }

        /* Customer Summary */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .summary-card .cname {
            font-weight: 700;
            color: #2d3748;
            font-size: 15px;
            margin-bottom: 8px;
        }

        .summary-card .orders-count {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .summary-card .orders-label {
            font-size: 12px;
            color: #a0aec0;
        }

        .summary-card .spent {
            margin-top: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #38a169;
        }

        /* Table */
        .card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h2 { color: #2d3748; font-size: 18px; }

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

        .product-tag {
            background: #fefcbf;
            color: #744210;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .amount-cell {
            font-weight: 700;
            color: #38a169;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #4a5568;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }

        .nav-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        @media (max-width: 650px) {
            .highlight-grid { grid-template-columns: 1fr; }
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🛒 Order Management</h1>
    <p class="subtitle">Customer Order History using SQL JOINs & Subqueries</p>

    <a href="dashboard.php" class="nav-link">← Back to Dashboard</a>

    <!-- Highlight Cards -->
    <div class="highlight-grid">
        <!-- Highest Value Order -->
        <div class="highlight-card gold">
            <h3>🏆 Highest Value Order</h3>
            <?php if ($highest): ?>
                <div class="big-name"><?= htmlspecialchars($highest['customer_name']) ?></div>
                <div class="detail">Product: <?= htmlspecialchars($highest['product_name']) ?></div>
                <div class="detail">Date: <?= $highest['order_date'] ?></div>
                <div class="amount">₹<?= number_format($highest['total_amount'], 2) ?></div>
            <?php else: ?>
                <div class="detail">No orders yet.</div>
            <?php endif; ?>
        </div>

        <!-- Most Active Customer -->
        <div class="highlight-card green">
            <h3>⭐ Most Active Customer</h3>
            <?php if ($active): ?>
                <div class="big-name"><?= htmlspecialchars($active['customer_name']) ?></div>
                <div class="detail">Email: <?= htmlspecialchars($active['email']) ?></div>
                <div class="detail"><?= $active['total_orders'] ?> Orders Placed</div>
                <div class="amount">₹<?= number_format($active['total_spent'], 2) ?> Total Spent</div>
            <?php else: ?>
                <div class="detail">No orders yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer Summary -->
    <div class="card">
        <div class="section-title">📊 Customer Summary</div>
        <div class="summary-grid">
            <?php while ($s = mysqli_fetch_assoc($customer_summary)): ?>
            <div class="summary-card">
                <div class="cname"><?= htmlspecialchars($s['name']) ?></div>
                <div class="orders-count"><?= $s['total_orders'] ?></div>
                <div class="orders-label">Orders</div>
                <div class="spent">₹<?= number_format($s['total_spent'], 2) ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Full Order History -->
    <div class="card">
        <div class="card-header">
            <h2>📋 Full Order History (JOIN)</h2>
            <span class="badge"><?= mysqli_num_rows($order_history) ?> Orders</span>
        </div>
        <?php if (mysqli_num_rows($order_history) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($order_history)): ?>
                <tr>
                    <td>#<?= $row['order_id'] ?></td>
                    <td><strong><?= htmlspecialchars($row['customer_name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['customer_email']) ?></td>
                    <td><span class="product-tag"><?= htmlspecialchars($row['product_name']) ?></span></td>
                    <td>₹<?= number_format($row['price'], 2) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td class="amount-cell">₹<?= number_format($row['total_amount'], 2) ?></td>
                    <td><?= $row['order_date'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center; color:#a0aec0; padding:30px; font-style:italic;">No orders found. Insert sample data first.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
<?php mysqli_close($conn); ?>
