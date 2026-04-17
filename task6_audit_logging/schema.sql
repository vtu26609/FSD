-- Schema for Task 6: Automated Logging
-- Demonstrates Triggers and Views for Audit Logging

-- 1. Main Table
CREATE TABLE IF NOT EXISTS Employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    department TEXT NOT NULL,
    salary REAL NOT NULL,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Audit Table
CREATE TABLE IF NOT EXISTS AuditLogs (
    log_id INTEGER PRIMARY KEY AUTOINCREMENT,
    target_id INTEGER,
    action_type TEXT, -- 'INSERT', 'UPDATE'
    old_data TEXT,
    new_data TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Trigger for INSERT
CREATE TRIGGER IF NOT EXISTS log_employee_insert
AFTER INSERT ON Employees
BEGIN
    INSERT INTO AuditLogs (target_id, action_type, new_data)
    VALUES (
        new.id, 
        'INSERT', 
        'Name: ' || new.name || ', Dept: ' || new.department || ', Salary: ' || new.salary
    );
END;

-- 4. Trigger for UPDATE
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
    
    -- Update the last_updated timestamp
    UPDATE Employees SET last_updated = CURRENT_TIMESTAMP WHERE id = new.id;
END;

-- 5. View for Daily Activity Report
-- Summarizes actions per day
CREATE VIEW IF NOT EXISTS DailyActivityReport AS
SELECT 
    DATE(timestamp) as activity_date,
    action_type,
    COUNT(*) as total_actions
FROM AuditLogs
GROUP BY activity_date, action_type
ORDER BY activity_date DESC;

-- Initial Data
INSERT INTO Employees (name, department, salary) VALUES ('Alice Johnson', 'Engineering', 85000);
INSERT INTO Employees (name, department, salary) VALUES ('Bob Smith', 'Marketing', 65000);
INSERT INTO Employees (name, department, salary) VALUES ('Charlie Davis', 'Sales', 72000);
