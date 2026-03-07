<?php
$conn = mysqli_connect("localhost", "root", "chandu123", "myapp", 3307);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$success = "";
$error   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $rating   = (int)$_POST['rating'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $message  = mysqli_real_escape_string($conn, $_POST['message']);

    $sql = "INSERT INTO feedback (name, email, rating, category, message)
            VALUES ('$name', '$email', $rating, '$category', '$message')";

    if (mysqli_query($conn, $sql)) {
        $success = "Thank you, $name! Your feedback has been submitted.";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Fetch all feedback
$feedbacks = mysqli_query($conn, "SELECT * FROM feedback ORDER BY created_at DESC");
$total     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM feedback"))['c'];
$avg       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as a FROM feedback"))['a'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Form</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f4f8;
            padding: 30px 20px;
        }

        h1 { text-align: center; color: #2d3748; margin-bottom: 6px; font-size: 28px; }
        .subtitle { text-align: center; color: #718096; font-size: 14px; margin-bottom: 30px; }

        .container { max-width: 900px; margin: 0 auto; }
        .nav-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; font-weight: 600; font-size: 14px; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-top: 4px solid #667eea;
        }

        .stat-card.gold { border-top-color: #f6ad55; }
        .stat-card .number { font-size: 32px; font-weight: 700; color: #667eea; }
        .stat-card.gold .number { color: #d97706; }
        .stat-card .label { font-size: 12px; color: #a0aec0; margin-top: 4px; text-transform: uppercase; }

        /* Card */
        .card {
            background: white;
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .card h2 { color: #2d3748; font-size: 18px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }

        /* Form */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

        .form-group { margin-bottom: 18px; position: relative; }

        label { display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 7px; }

        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 11px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            font-family: inherit;
            transition: border-color 0.3s, box-shadow 0.3s, background 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
        }

        /* Hover highlight effect */
        input:hover, select:hover, textarea:hover {
            background: #f7f8ff;
            border-color: #b794f4;
        }

        .field-valid   { border-color: #68d391 !important; }
        .field-invalid { border-color: #fc8181 !important; }

        .validation-msg {
            font-size: 11px;
            margin-top: 5px;
            min-height: 16px;
            font-weight: 500;
        }

        .msg-ok  { color: #38a169; }
        .msg-err { color: #e53e3e; }

        /* Character counter */
        .char-counter { font-size: 11px; color: #a0aec0; text-align: right; margin-top: 4px; }
        .char-counter.warn { color: #d97706; }
        .char-counter.over { color: #e53e3e; }

        /* Star Rating */
        .star-group { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 5px; margin-top: 5px; }

        .star-group input { display: none; }

        .star-group label {
            font-size: 30px;
            color: #e2e8f0;
            cursor: pointer;
            transition: color 0.2s, transform 0.1s;
            margin: 0;
            padding: 0;
        }

        .star-group input:checked ~ label,
        .star-group label:hover,
        .star-group label:hover ~ label {
            color: #f6ad55;
            transform: scale(1.2);
        }

        /* Submit Button */
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
            transition: opacity 0.2s, transform 0.1s;
            margin-top: 5px;
        }

        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }

        /* Messages */
        .msg-success { background: #f0fff4; border: 1px solid #9ae6b4; color: #276749; padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-size: 15px; }
        .msg-error   { background: #fff5f5; border: 1px solid #fc8181; color: #c53030; padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-size: 14px; }

        /* Confirmation Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show { display: flex; }

        .modal {
            background: white;
            border-radius: 16px;
            padding: 35px 30px;
            max-width: 380px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: popIn 0.3s ease;
        }

        @keyframes popIn {
            from { transform: scale(0.8); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .modal h3 { font-size: 22px; color: #2d3748; margin: 10px 0; }
        .modal p  { color: #718096; font-size: 14px; margin-bottom: 25px; }

        .modal-btns { display: flex; gap: 10px; }

        .modal-btns button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-confirm { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-cancel  { background: #e2e8f0; color: #4a5568; }

        /* Feedback Table */
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        th { padding: 12px 15px; text-align: left; }
        td { padding: 11px 15px; color: #4a5568; border-bottom: 1px solid #f0f4f8; }
        tbody tr:hover { background: #f7fafc; }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header h2 { border: none; margin: 0; padding: 0; }
        .badge { background: #ebf4ff; color: #667eea; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }

        .stars-display { color: #f6ad55; font-size: 16px; letter-spacing: 1px; }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- Confirmation Modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal">
        <div style="font-size:50px;">📨</div>
        <h3>Submit Feedback?</h3>
        <p>You double-clicked submit! Are you sure you want to send your feedback?</p>
        <div class="modal-btns">
            <button class="btn-cancel"  onclick="closeModal()">Cancel</button>
            <button class="btn-confirm" onclick="submitForm()">Yes, Submit!</button>
        </div>
    </div>
</div>

<div class="container">
    <h1>💬 Feedback Form</h1>
    <p class="subtitle">Interactive form with real-time JS validation & events</p>

    <a href="dashboard.php" class="nav-link">← Back to Dashboard</a>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?= $total ?></div>
            <div class="label">Total Feedbacks</div>
        </div>
        <div class="stat-card gold">
            <div class="number"><?= $avg ? number_format($avg, 1) : '0' ?>⭐</div>
            <div class="label">Average Rating</div>
        </div>
    </div>

    <!-- Feedback Form -->
    <div class="card">
        <h2>📝 Share Your Feedback</h2>

        <?php if ($success): ?>
            <div class="msg-success">🎉 <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg-error">❌ <?= $error ?></div>
        <?php endif; ?>

        <form id="feedbackForm" method="POST" novalidate>

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your name"
                        onkeyup="validateName()"
                        onmouseenter="highlightField(this)"
                        onmouseleave="unhighlightField(this)">
                    <div class="validation-msg" id="nameMsg"></div>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email"
                        onkeyup="validateEmail()"
                        onmouseenter="highlightField(this)"
                        onmouseleave="unhighlightField(this)">
                    <div class="validation-msg" id="emailMsg"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select id="category" name="category"
                        onchange="validateCategory()"
                        onmouseenter="highlightField(this)"
                        onmouseleave="unhighlightField(this)">
                        <option value="">-- Select Category --</option>
                        <option value="Product">Product</option>
                        <option value="Service">Service</option>
                        <option value="Support">Support</option>
                        <option value="Website">Website</option>
                        <option value="Other">Other</option>
                    </select>
                    <div class="validation-msg" id="categoryMsg"></div>
                </div>

                <div class="form-group">
                    <label>Rating</label>
                    <div class="star-group" id="starGroup">
                        <input type="radio" id="s5" name="rating" value="5"><label for="s5" title="Excellent">★</label>
                        <input type="radio" id="s4" name="rating" value="4"><label for="s4" title="Good">★</label>
                        <input type="radio" id="s3" name="rating" value="3"><label for="s3" title="Average">★</label>
                        <input type="radio" id="s2" name="rating" value="2"><label for="s2" title="Poor">★</label>
                        <input type="radio" id="s1" name="rating" value="1"><label for="s1" title="Terrible">★</label>
                    </div>
                    <div class="validation-msg" id="ratingMsg"></div>
                </div>
            </div>

            <div class="form-group">
                <label>Your Message</label>
                <textarea id="message" name="message" rows="4" placeholder="Write your feedback here... (min 10, max 300 characters)"
                    onkeyup="validateMessage(); updateCounter()"
                    onmouseenter="highlightField(this)"
                    onmouseleave="unhighlightField(this)"></textarea>
                <div class="char-counter" id="charCounter">0 / 300</div>
                <div class="validation-msg" id="messageMsg"></div>
            </div>

            <!-- Double-click to submit -->
            <button type="button" class="btn" ondblclick="showModal()" onclick="singleClickHint()">
                Double-Click to Submit ✉️
            </button>
            <p style="text-align:center; font-size:12px; color:#a0aec0; margin-top:8px;" id="hintText">
                💡 Double-click the button to confirm and submit
            </p>
        </form>
    </div>

    <!-- Feedback Records -->
    <div class="card">
        <div class="card-header">
            <h2>📋 All Feedback</h2>
            <span class="badge"><?= $total ?> Records</span>
        </div>

        <?php if ($total > 0): ?>
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Category</th><th>Rating</th><th>Message</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($feedbacks)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><span style="background:#e9d8fd;color:#553c9a;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;"><?= $row['category'] ?></span></td>
                    <td><span class="stars-display"><?= str_repeat('★', $row['rating']) ?><span style="color:#e2e8f0"><?= str_repeat('★', 5 - $row['rating']) ?></span></span></td>
                    <td style="font-size:13px; max-width:200px;"><?= htmlspecialchars($row['message']) ?></td>
                    <td style="font-size:12px;"><?= $row['created_at'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center;color:#a0aec0;padding:30px;font-style:italic;">No feedback yet. Be the first!</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // =====================
    // REUSABLE VALIDATION FUNCTIONS
    // =====================

    function setValid(inputId, msgId, msg) {
        const el = document.getElementById(inputId);
        const msgEl = document.getElementById(msgId);
        el.classList.remove('field-invalid');
        el.classList.add('field-valid');
        msgEl.innerHTML = '✅ ' + msg;
        msgEl.className = 'validation-msg msg-ok';
        return true;
    }

    function setInvalid(inputId, msgId, msg) {
        const el = document.getElementById(inputId);
        const msgEl = document.getElementById(msgId);
        el.classList.remove('field-valid');
        el.classList.add('field-invalid');
        msgEl.innerHTML = '❌ ' + msg;
        msgEl.className = 'validation-msg msg-err';
        return false;
    }

    function clearState(inputId, msgId) {
        const el = document.getElementById(inputId);
        document.getElementById(msgId).innerHTML = '';
        el.classList.remove('field-valid', 'field-invalid');
    }

    // =====================
    // INDIVIDUAL VALIDATORS (keypress events)
    // =====================

    function validateName() {
        const val = document.getElementById('name').value.trim();
        if (val.length === 0)   return clearState('name', 'nameMsg');
        if (val.length < 3)     return setInvalid('name', 'nameMsg', 'Name must be at least 3 characters');
        if (!/^[a-zA-Z\s.]+$/.test(val)) return setInvalid('name', 'nameMsg', 'Name can only contain letters');
        return setValid('name', 'nameMsg', 'Looks good!');
    }

    function validateEmail() {
        const val = document.getElementById('email').value.trim();
        if (val.length === 0) return clearState('email', 'emailMsg');
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regex.test(val)) return setInvalid('email', 'emailMsg', 'Enter a valid email address');
        return setValid('email', 'emailMsg', 'Valid email!');
    }

    function validateCategory() {
        const val = document.getElementById('category').value;
        if (!val) return setInvalid('category', 'categoryMsg', 'Please select a category');
        return setValid('category', 'categoryMsg', 'Category selected!');
    }

    function validateRating() {
        const selected = document.querySelector('input[name="rating"]:checked');
        const msgEl = document.getElementById('ratingMsg');
        if (!selected) {
            msgEl.innerHTML = '❌ Please select a rating';
            msgEl.className = 'validation-msg msg-err';
            return false;
        }
        msgEl.innerHTML = '✅ Rating selected!';
        msgEl.className = 'validation-msg msg-ok';
        return true;
    }

    function validateMessage() {
        const val = document.getElementById('message').value.trim();
        if (val.length === 0)   return clearState('message', 'messageMsg');
        if (val.length < 10)    return setInvalid('message', 'messageMsg', 'Message must be at least 10 characters');
        if (val.length > 300)   return setInvalid('message', 'messageMsg', 'Message cannot exceed 300 characters');
        return setValid('message', 'messageMsg', 'Message looks good!');
    }

    // =====================
    // CHARACTER COUNTER
    // =====================
    function updateCounter() {
        const len     = document.getElementById('message').value.length;
        const counter = document.getElementById('charCounter');
        counter.textContent = len + ' / 300';
        counter.className = 'char-counter';
        if (len > 250) counter.classList.add('warn');
        if (len > 300) counter.classList.add('over');
    }

    // =====================
    // HOVER EFFECTS
    // =====================
    function highlightField(el) {
        if (!el.classList.contains('field-valid') && !el.classList.contains('field-invalid')) {
            el.style.background = '#f7f8ff';
        }
    }

    function unhighlightField(el) {
        if (!el.classList.contains('field-valid') && !el.classList.contains('field-invalid')) {
            el.style.background = '';
        }
    }

    // =====================
    // SINGLE CLICK HINT
    // =====================
    function singleClickHint() {
        const hint = document.getElementById('hintText');
        hint.textContent = '☝️ One more click! Double-click to submit.';
        hint.style.color = '#d97706';
        setTimeout(() => {
            hint.textContent = '💡 Double-click the button to confirm and submit';
            hint.style.color = '#a0aec0';
        }, 2000);
    }

    // =====================
    // MODAL - Double click submit
    // =====================
    function showModal() {
        // Validate all before showing modal
        const n = validateName();
        const e = validateEmail();
        const c = validateCategory();
        const r = validateRating();
        const m = validateMessage();

        if (n && e && c && r && m) {
            document.getElementById('confirmModal').classList.add('show');
        }
    }

    function closeModal() {
        document.getElementById('confirmModal').classList.remove('show');
    }

    function submitForm() {
        document.getElementById('feedbackForm').submit();
    }

    // Close modal on outside click
    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>

</body>
</html>
<?php mysqli_close($conn); ?>
