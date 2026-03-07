<?php
session_start();
$conn = mysqli_connect("localhost", "root", "chandu123", "myapp", 3307);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$message = "";
$msg_type = "";

// Handle Payment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sender_id   = (int)$_POST['sender_id'];
    $receiver_id = (int)$_POST['receiver_id'];
    $amount      = (float)$_POST['amount'];

    if ($amount <= 0) {
        $message  = "Amount must be greater than 0!";
        $msg_type = "error";
    } elseif ($sender_id == $receiver_id) {
        $message  = "Sender and receiver cannot be the same!";
        $msg_type = "error";
    } else {
        // Start Transaction
        mysqli_begin_transaction($conn);

        try {
            // Check sender balance
            $check = mysqli_query($conn, "SELECT * FROM accounts WHERE id = $sender_id FOR UPDATE");
            $sender = mysqli_fetch_assoc($check);

            if (!$sender) {
                throw new Exception("Sender account not found!");
            }

            if ($sender['balance'] < $amount) {
                throw new Exception("Insufficient balance! Available: ₹" . number_format($sender['balance'], 2));
            }

            // Check receiver exists
            $check2   = mysqli_query($conn, "SELECT * FROM accounts WHERE id = $receiver_id");
            $receiver = mysqli_fetch_assoc($check2);

            if (!$receiver) {
                throw new Exception("Receiver account not found!");
            }

            // Deduct from sender
            $deduct = mysqli_query($conn, "UPDATE accounts SET balance = balance - $amount WHERE id = $sender_id");
            if (!$deduct) throw new Exception("Failed to deduct from sender!");

            // Add to receiver
            $add = mysqli_query($conn, "UPDATE accounts SET balance = balance + $amount WHERE id = $receiver_id");
            if (!$add) throw new Exception("Failed to add to receiver!");

            // Log transaction
            $note = mysqli_real_escape_string($conn, "Payment from {$sender['account_name']} to {$receiver['account_name']}");
            mysqli_query($conn, "INSERT INTO transactions (sender_id, receiver_id, amount, status, note)
                                 VALUES ($sender_id, $receiver_id, $amount, 'SUCCESS', '$note')");

            // COMMIT
            mysqli_commit($conn);
            $message  = "✅ Payment of ₹" . number_format($amount, 2) . " from <strong>{$sender['account_name']}</strong> to <strong>{$receiver['account_name']}</strong> was successful!";
            $msg_type = "success";

        } catch (Exception $e) {
            // ROLLBACK
            mysqli_rollback($conn);

            // Log failed transaction
            if (isset($sender)) {
                $err_note = mysqli_real_escape_string($conn, $e->getMessage());
                mysqli_query($conn, "INSERT INTO transactions (sender_id, receiver_id, amount, status, note)
                                     VALUES ($sender_id, $receiver_id, $amount, 'FAILED', '$err_note')");
            }

            $message  = "❌ Transaction Failed: " . $e->getMessage() . " — ROLLBACK executed.";
            $msg_type = "error";
        }
    }
}

// Fetch accounts
$accounts = mysqli_query($conn, "SELECT * FROM accounts ORDER BY id");

// Fetch transaction history
$history = mysqli_query($conn, "
    SELECT 
        t.id,
        s.account_name AS sender,
        r.account_name AS receiver,
        t.amount,
        t.status,
        t.note,
        t.created_at
    FROM transactions t
    JOIN accounts s ON t.sender_id = s.id
    JOIN accounts r ON t.receiver_id = r.id
    ORDER BY t.created_at DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Simulation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f4f8;
            padding: 30px 20px;
        }

        h1 { text-align: center; color: #2d3748; margin-bottom: 6px; font-size: 28px; }
        .subtitle { text-align: center; color: #718096; font-size: 14px; margin-bottom: 30px; }

        .container { max-width: 1000px; margin: 0 auto; }

        .nav-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; font-weight: 600; font-size: 14px; }

        /* Account Cards */
        .accounts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .account-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-top: 4px solid #667eea;
            text-align: center;
        }

        .account-card .acc-id { font-size: 11px; color: #a0aec0; text-transform: uppercase; letter-spacing: 1px; }
        .account-card .acc-name { font-size: 16px; font-weight: 700; color: #2d3748; margin: 6px 0; }
        .account-card .acc-balance { font-size: 24px; font-weight: 700; color: #38a169; }
        .account-card .acc-label { font-size: 11px; color: #a0aec0; }

        /* Payment Form */
        .card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .card h2 { color: #2d3748; font-size: 18px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; align-items: end; }

        .form-group { display: flex; flex-direction: column; gap: 6px; }

        label { font-size: 13px; font-weight: 600; color: #4a5568; }

        select, input[type="number"] {
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: white;
        }

        select:focus, input:focus { border-color: #667eea; }

        .btn {
            padding: 11px 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 18px;
        }

        .btn:hover { opacity: 0.9; }

        /* Messages */
        .msg-success {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            color: #276749;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 15px;
        }

        .msg-error {
            background: #fff5f5;
            border: 1px solid #fc8181;
            color: #c53030;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 15px;
        }

        /* Validation */
        .js-error { color: #e53e3e; font-size: 12px; margin-top: 4px; display: none; }
        .input-error { border-color: #fc8181 !important; }

        /* Transaction Table */
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        th { padding: 13px 15px; text-align: left; font-weight: 600; }
        td { padding: 12px 15px; color: #4a5568; border-bottom: 1px solid #f0f4f8; }
        tbody tr:hover { background: #f7fafc; }

        .status-success {
            background: #c6f6d5;
            color: #276749;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-failed {
            background: #fed7d7;
            color: #9b2c2c;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .badge { background: #ebf4ff; color: #667eea; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>💳 Payment Simulation</h1>
    <p class="subtitle">Transaction with COMMIT & ROLLBACK</p>

    <a href="dashboard.php" class="nav-link">← Back to Dashboard</a>

    <!-- Account Balances -->
    <div class="card">
        <h2>🏦 Account Balances</h2>
        <div class="accounts-grid">
            <?php
            $colors = ['#667eea','#38a169','#d97706','#e53e3e'];
            $i = 0;
            mysqli_data_seek($accounts, 0);
            while ($acc = mysqli_fetch_assoc($accounts)):
                $color = $colors[$i % count($colors)];
            ?>
            <div class="account-card" style="border-top-color: <?= $color ?>">
                <div class="acc-id">Account #<?= $acc['id'] ?></div>
                <div class="acc-name"><?= htmlspecialchars($acc['account_name']) ?></div>
                <div class="acc-balance" style="color: <?= $color ?>">₹<?= number_format($acc['balance'], 2) ?></div>
                <div class="acc-label">Available Balance</div>
            </div>
            <?php $i++; endwhile; ?>
        </div>
    </div>

    <!-- Payment Form -->
    <div class="card">
        <h2>💸 Make a Payment</h2>

        <?php if ($message): ?>
            <div class="msg-<?= $msg_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <form id="payForm" method="POST" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label>From (Sender)</label>
                    <select name="sender_id" id="sender_id">
                        <?php
                        mysqli_data_seek($accounts, 0);
                        while ($acc = mysqli_fetch_assoc($accounts)):
                        ?>
                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?> — ₹<?= number_format($acc['balance'], 2) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <span class="js-error" id="senderError">Please select a sender.</span>
                </div>

                <div class="form-group">
                    <label>To (Receiver)</label>
                    <select name="receiver_id" id="receiver_id">
                        <?php
                        mysqli_data_seek($accounts, 0);
                        while ($acc = mysqli_fetch_assoc($accounts)):
                        ?>
                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <span class="js-error" id="receiverError">Sender and receiver must be different.</span>
                </div>

                <div class="form-group">
                    <label>Amount (₹)</label>
                    <input type="number" name="amount" id="amount" placeholder="Enter amount" min="1" step="0.01">
                    <span class="js-error" id="amountError">Enter a valid amount greater than 0.</span>
                </div>
            </div>

            <button type="submit" class="btn">💳 Send Payment</button>
        </form>
    </div>

    <!-- Transaction History -->
    <div class="card">
        <div class="card-header">
            <h2>📜 Transaction History</h2>
            <span class="badge"><?= mysqli_num_rows($history) ?> Records</span>
        </div>

        <?php if (mysqli_num_rows($history) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Note</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($history)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><strong><?= htmlspecialchars($row['sender']) ?></strong></td>
                    <td><?= htmlspecialchars($row['receiver']) ?></td>
                    <td><strong>₹<?= number_format($row['amount'], 2) ?></strong></td>
                    <td>
                        <span class="status-<?= strtolower($row['status']) ?>">
                            <?= $row['status'] == 'SUCCESS' ? '✅' : '❌' ?> <?= $row['status'] ?>
                        </span>
                    </td>
                    <td style="font-size:12px; color:#718096;"><?= htmlspecialchars($row['note']) ?></td>
                    <td style="font-size:12px;"><?= $row['created_at'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center; color:#a0aec0; padding:30px; font-style:italic;">No transactions yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    document.getElementById('payForm').addEventListener('submit', function(e) {
        let valid = true;

        const sender   = document.getElementById('sender_id');
        const receiver = document.getElementById('receiver_id');
        const amount   = document.getElementById('amount');

        // Reset errors
        document.getElementById('senderError').style.display   = 'none';
        document.getElementById('receiverError').style.display = 'none';
        document.getElementById('amountError').style.display   = 'none';
        sender.classList.remove('input-error');
        receiver.classList.remove('input-error');
        amount.classList.remove('input-error');

        // Same sender/receiver check
        if (sender.value === receiver.value) {
            receiver.classList.add('input-error');
            document.getElementById('receiverError').style.display = 'block';
            valid = false;
        }

        // Amount check
        if (!amount.value || parseFloat(amount.value) <= 0) {
            amount.classList.add('input-error');
            document.getElementById('amountError').style.display = 'block';
            valid = false;
        }

        if (!valid) e.preventDefault();
    });
</script>

</body>
</html>
<?php mysqli_close($conn); ?>
