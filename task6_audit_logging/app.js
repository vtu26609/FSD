let db;
let config = {
    locateFile: file => `https://cdnjs.cloudflare.com/ajax/libs/sql.js/1.8.0/${file}`
};

// DOM Elements
const employeeForm = document.getElementById('employee-form');
const employeeTable = document.getElementById('employee-table').getElementsByTagName('tbody')[0];
const auditStream = document.getElementById('audit-stream');
const dailyReport = document.getElementById('daily-report');
const submitBtn = document.getElementById('submit-btn');
const cancelBtn = document.getElementById('cancel-btn');
const employeeIdInput = document.getElementById('employee-id');

const SCHEMA_SQL = `-- Schema for Task 6: Automated Logging
CREATE TABLE IF NOT EXISTS Employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    department TEXT NOT NULL,
    salary REAL NOT NULL,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS AuditLogs (
    log_id INTEGER PRIMARY KEY AUTOINCREMENT,
    target_id INTEGER,
    action_type TEXT,
    old_data TEXT,
    new_data TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER IF NOT EXISTS log_employee_insert
AFTER INSERT ON Employees
BEGIN
    INSERT INTO AuditLogs (target_id, action_type, new_data)
    VALUES (new.id, 'INSERT', 'Name: ' || new.name || ', Dept: ' || new.department || ', Salary: ' || new.salary);
END;

CREATE TRIGGER IF NOT EXISTS log_employee_update
AFTER UPDATE ON Employees
BEGIN
    INSERT INTO AuditLogs (target_id, action_type, old_data, new_data)
    VALUES (
        new.id, 
        'UPDATE', 
        'Name: ' || old.name || ', Dept: ' || old.department || ', Salary: ' || old.salary,
        'Name: ' || new.name || ', Dept: ' || new.department || ', Salary: ' || new.salary
    );
    UPDATE Employees SET last_updated = CURRENT_TIMESTAMP WHERE id = new.id;
END;

CREATE VIEW IF NOT EXISTS DailyActivityReport AS
SELECT 
    DATE(timestamp) as activity_date,
    action_type,
    COUNT(*) as total_actions
FROM AuditLogs
GROUP BY activity_date, action_type
ORDER BY activity_date DESC;

INSERT INTO Employees (name, department, salary) VALUES ('Alice Johnson', 'Engineering', 85000);
INSERT INTO Employees (name, department, salary) VALUES ('Bob Smith', 'Marketing', 65000);
INSERT INTO Employees (name, department, salary) VALUES ('Charlie Davis', 'Sales', 72000);
`;

async function initDB() {
    try {
        const SQL = await initSqlJs(config);
        db = new SQL.Database();
        
        // Execute embedded schema
        db.run(SCHEMA_SQL);
        
        console.log("Database initialized");
        document.getElementById('db-status').textContent = "Database Connected";
        updateUI();
    } catch (err) {
        console.error("Failed to initialize database:", err);
        document.getElementById('db-status').textContent = "Connection Failed";
        document.getElementById('db-status').style.borderColor = "#ef4444";
        document.getElementById('db-status').style.color = "#ef4444";
    }
}

function updateUI() {
    renderEmployees();
    renderAuditLogs();
    renderDailyReport();
}

function renderEmployees() {
    const res = db.exec("SELECT * FROM Employees ORDER BY id DESC");
    employeeTable.innerHTML = "";
    
    if (res.length > 0) {
        const rows = res[0].values;
        rows.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row[1]}</td>
                <td>${row[2]}</td>
                <td>$${row[3].toLocaleString()}</td>
                <td style="color: var(--text-muted); font-size: 0.7rem;">${row[4].split(' ')[1]}</td>
                <td>
                    <button class="badge" style="cursor:pointer; background:transparent;" onclick="editEmployee(${row[0]}, '${row[1]}', '${row[2]}', ${row[3]})">Edit</button>
                </td>
            `;
            employeeTable.appendChild(tr);
        });
    }
}

function renderAuditLogs() {
    const res = db.exec("SELECT * FROM AuditLogs ORDER BY timestamp DESC LIMIT 10");
    auditStream.innerHTML = "";
    
    if (res.length > 0) {
        const rows = res[0].values;
        rows.forEach(row => {
            const logItem = document.createElement('div');
            logItem.className = `log-item ${row[2]}`; // row[2] is action_type
            
            const time = row[5].split(' ')[1];
            const oldData = row[3] ? `<div style="color: #ef4444; text-decoration: line-through; opacity: 0.6;">FROM: ${row[3]}</div>` : '';
            const newData = row[4] ? `<div style="color: #10b981;">TO: ${row[4]}</div>` : '';

            logItem.innerHTML = `
                <div class="log-meta">
                    <span class="log-type">${row[2]} (ID: ${row[1]})</span>
                    <span>${time}</span>
                </div>
                <div class="log-content">
                    ${oldData}
                    ${newData}
                </div>
            `;
            auditStream.appendChild(logItem);
        });
    }
}

function renderDailyReport() {
    // Reading from the View: DailyActivityReport
    const res = db.exec("SELECT * FROM DailyActivityReport");
    dailyReport.innerHTML = "";
    
    if (res.length > 0) {
        const rows = res[0].values;
        rows.forEach(row => {
            const card = document.createElement('div');
            card.className = "report-card";
            card.innerHTML = `
                <div class="report-val">${row[2]}</div>
                <div class="report-label">${row[1]}s TODAY</div>
            `;
            dailyReport.appendChild(card);
        });
    } else {
        dailyReport.innerHTML = "<p style='color: var(--text-muted);'>No activity yet.</p>";
    }
}

// Event Handlers
employeeForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const id = employeeIdInput.value;
    const name = document.getElementById('employee-name').value;
    const dept = document.getElementById('employee-dept').value;
    const salary = document.getElementById('employee-salary').value;

    if (id) {
        // Update
        db.run("UPDATE Employees SET name = ?, department = ?, salary = ? WHERE id = ?", [name, dept, salary, id]);
        resetForm();
    } else {
        // Insert
        db.run("INSERT INTO Employees (name, department, salary) VALUES (?, ?, ?)", [name, dept, salary]);
    }

    employeeForm.reset();
    updateUI();
});

window.editEmployee = (id, name, dept, salary) => {
    employeeIdInput.value = id;
    document.getElementById('employee-name').value = name;
    document.getElementById('employee-dept').value = dept;
    document.getElementById('employee-salary').value = salary;
    
    submitBtn.textContent = "Save Changes";
    cancelBtn.style.display = "block";
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

cancelBtn.addEventListener('click', resetForm);

function resetForm() {
    employeeIdInput.value = "";
    employeeForm.reset();
    submitBtn.textContent = "Add Employee";
    cancelBtn.style.display = "none";
}

// Initialize
initDB();
