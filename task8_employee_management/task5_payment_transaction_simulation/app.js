let db;
let config = {
    locateFile: filename => `https://cdnjs.cloudflare.com/ajax/libs/sql.js/1.8.0/sql-wasm.wasm`
};

// UI Elements
const customerBalanceEl = document.getElementById('customer-balance');
const merchantBalanceEl = document.getElementById('merchant-balance');
const transferAmountInput = document.getElementById('transfer-amount');
const payBtn = document.getElementById('pay-btn');
const failSwitch = document.getElementById('fail-switch');
const terminalEl = document.getElementById('terminal-log');
const dbStatusEl = document.getElementById('db-status');
const notificationEl = document.getElementById('notification');

// Initialize Database
async function initDB() {
    try {
        const SQL = await initSqlJs(config);
        db = new SQL.Database();
        
        logToTerminal('-- Initializing Database Schema --', 'system');
        
        // Create tables
        db.run(`
            CREATE TABLE accounts (
                id INTEGER PRIMARY KEY,
                name TEXT,
                balance REAL
            );
            
            CREATE TABLE transaction_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                amount REAL,
                status TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        `);
        logToTerminal('CREATE TABLE accounts (...);', 'sql');
        logToTerminal('CREATE TABLE transaction_history (...);', 'sql');

        // Seed data
        db.run("INSERT INTO accounts (id, name, balance) VALUES (1, 'Customer', 1000.00);");
        db.run("INSERT INTO accounts (id, name, balance) VALUES (2, 'Merchant', 5000.00);");
        logToTerminal("INSERT INTO accounts VALUES (1, 'Customer', 1000.00);", 'sql');
        logToTerminal("INSERT INTO accounts VALUES (2, 'Merchant', 5000.00);", 'sql');

        updateUI();
        dbStatusEl.textContent = 'Database Ready';
        dbStatusEl.style.color = 'var(--success-color)';
        payBtn.disabled = false;
        
    } catch (err) {
        console.error(err);
        dbStatusEl.textContent = 'DB Initialization Failed';
        dbStatusEl.style.color = 'var(--error-neon)';
        logToTerminal('Critical Error: Failed to initialize WASM.', 'error');
    }
}

function logToTerminal(message, type = '') {
    const line = document.createElement('div');
    line.className = `terminal-line ${type}`;
    line.textContent = message;
    terminalEl.appendChild(line);
    terminalEl.scrollTop = terminalEl.scrollHeight;
}

function updateUI() {
    const customer = db.exec("SELECT balance FROM accounts WHERE id = 1")[0].values[0][0];
    const merchant = db.exec("SELECT balance FROM accounts WHERE id = 2")[0].values[0][0];
    
    animateValue(customerBalanceEl, parseFloat(customerBalanceEl.textContent), customer, 500);
    animateValue(merchantBalanceEl, parseFloat(merchantBalanceEl.textContent), merchant, 500);
}

function animateValue(obj, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = (progress * (end - start) + start).toFixed(2);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

async function handlePayment() {
    const amount = parseFloat(transferAmountInput.value);
    const shouldFail = failSwitch.checked;

    if (isNaN(amount) || amount <= 0) {
        showNotification('Please enter a valid amount.', 'error');
        return;
    }

    // Check balance first
    const currentBalance = db.exec("SELECT balance FROM accounts WHERE id = 1")[0].values[0][0];
    if (amount > currentBalance) {
        showNotification('Insufficient funds.', 'error');
        logToTerminal(`-- Transaction Aborted: Insufficient funds ($${amount} > $${currentBalance}) --`, 'error');
        return;
    }

    payBtn.disabled = true;
    logToTerminal('\n-- NEW TRANSACTION STARTED --', 'system');
    
    try {
        // --- START TRANSACTION ---
        logToTerminal('BEGIN TRANSACTION;', 'sql');
        db.run('BEGIN TRANSACTION;');

        // Step 1: Deduct from customer
        logToTerminal(`UPDATE accounts SET balance = balance - ${amount} WHERE id = 1;`, 'sql');
        db.run('UPDATE accounts SET balance = balance - ? WHERE id = 1', [amount]);

        // Simulated Network/DB Delay
        await new Promise(r => setTimeout(r, 800));

        // Step 2: Artificial Failure point
        if (shouldFail) {
            logToTerminal('-- SIMULATED SYSTEM FAILURE DETECTED --', 'error');
            throw new Error('Database connection lost during write operation.');
        }

        // Step 3: Add to merchant
        logToTerminal(`UPDATE accounts SET balance = balance + ${amount} WHERE id = 2;`, 'sql');
        db.run('UPDATE accounts SET balance = balance + ? WHERE id = 2', [amount]);

        // Step 4: Record history
        logToTerminal(`INSERT INTO transaction_history (amount, status) VALUES (${amount}, 'SUCCESS');`, 'sql');
        db.run("INSERT INTO transaction_history (amount, status) VALUES (?, 'SUCCESS')", [amount]);

        // --- COMMIT ---
        logToTerminal('COMMIT;', 'sql');
        db.run('COMMIT;');
        
        showNotification(`Payment of $${amount.toFixed(2)} successful!`, 'success');
        logToTerminal('-- TRANSACTION COMMITTED SUCCESSFULLY --', 'success');

    } catch (err) {
        // --- ROLLBACK ---
        logToTerminal(`-- ERROR: ${err.message} --`, 'error');
        logToTerminal('ROLLBACK;', 'sql');
        db.run('ROLLBACK;');
        
        showNotification('Transaction failed. Funds rolled back.', 'error');
        logToTerminal('-- DATA INTEGRITY PRESERVED VIA ROLLBACK --', 'system');
    }

    updateUI();
    transferAmountInput.value = '';
    payBtn.disabled = false;
}

function showNotification(message, type) {
    notificationEl.textContent = message;
    notificationEl.className = `notification ${type}`;
    setTimeout(() => {
        notificationEl.classList.add('hidden');
    }, 3000);
}

// Event Listeners
payBtn.addEventListener('click', handlePayment);
document.getElementById('clear-log').addEventListener('click', () => {
    terminalEl.innerHTML = '<div class="terminal-line system">-- Log Cleared --</div>';
});

// Initialization
initDB();
