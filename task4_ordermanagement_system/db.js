const DB_CONFIG = {
    locateFile: filename => `https://cdn.jsdelivr.net/npm/sql.js@1.10.3/dist/${filename}`
};

let db = null;

async function initDatabase() {
    try {
        const SQL = await initSqlJs(DB_CONFIG);
        db = new SQL.Database();
        console.log("Database initialized");

        // 1. Create Tables
        db.run(`
            CREATE TABLE Customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE,
                joined_date DATE DEFAULT (date('now'))
            );

            CREATE TABLE Products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                price DECIMAL(10, 2),
                category TEXT
            );

            CREATE TABLE Orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER,
                product_id INTEGER,
                quantity INTEGER,
                order_date DATE DEFAULT (date('now')),
                FOREIGN KEY(customer_id) REFERENCES Customers(id),
                FOREIGN KEY(product_id) REFERENCES Products(id)
            );
        `);

        // 2. Seed Data
        seedData();

        return true;
    } catch (err) {
        console.error("Failed to initialize database:", err);
        return false;
    }
}

function seedData() {
    // Customers
    const customers = [
        ['Alex Rivers', 'alex@rivers.com'],
        ['Sarah Chen', 'sarah@chen.dev'],
        ['Marcus Thorne', 'marcus@thorne.io'],
        ['Elena Vance', 'elena@vance.net'],
        ['John Wick', 'john@wick.com']
    ];
    customers.forEach(c => {
        db.run("INSERT INTO Customers (name, email) VALUES (?, ?)", c);
    });

    // Products
    const products = [
        ['Neural Processor X1', 1200.00, 'Hardware'],
        ['OLED Quantum Display', 850.00, 'Display'],
        ['Cyberdeck Kit V2', 2500.00, 'Hardware'],
        ['SATA 10TB Drive', 150.00, 'Storage'],
        ['Mech Keyboard Pro', 220.00, 'Peripherals']
    ];
    products.forEach(p => {
        db.run("INSERT INTO Products (name, price, category) VALUES (?, ?, ?)", p);
    });

    // Orders
    const orders = [
        [1, 1, 2], // Alex: 2x Processors
        [1, 4, 1], // Alex: 1x Drive
        [2, 3, 1], // Sarah: 1x Cyberdeck
        [3, 2, 3], // Marcus: 3x Display
        [4, 5, 2], // Elena: 2x Keyboards
        [5, 3, 5], // John: 5x Cyberdecks (Whale!)
        [1, 5, 1], // Alex: 1x Keyboard
    ];
    orders.forEach(o => {
        db.run("INSERT INTO Orders (customer_id, product_id, quantity) VALUES (?, ?, ?)", o);
    });
}

/**
 * SQL Queries for the dashboard
 */

const QUERIES = {
    GET_ORDER_HISTORY: `
        SELECT 
            o.id, c.name as customer_name, p.name as product_name, 
            o.quantity, (o.quantity * p.price) as total_value, o.order_date
        FROM Orders o
        JOIN Customers c ON o.customer_id = c.id
        JOIN Products p ON o.product_id = p.id
        ORDER BY o.order_date DESC;
    `,

    GET_HIGHEST_ORDER: `
        SELECT o.id, c.name, (o.quantity * p.price) as total_value
        FROM Orders o
        JOIN Customers c ON o.customer_id = c.id
        JOIN Products p ON o.product_id = p.id
        WHERE (o.quantity * p.price) = (
            SELECT MAX(o2.quantity * p2.price) FROM Orders o2 JOIN Products p2 ON o2.product_id = p2.id
        )
        LIMIT 1;
    `,

    GET_MOST_ACTIVE_CUSTOMER: `
        SELECT c.name, COUNT(o.id) as order_count
        FROM Customers c
        JOIN Orders o ON c.id = o.customer_id
        GROUP BY c.id
        ORDER BY order_count DESC
        LIMIT 1;
    `,

    GET_TOTAL_REVENUE: `
        SELECT SUM(o.quantity * p.price) as total_revenue
        FROM Orders o
        JOIN Products p ON o.product_id = p.id;
    `,

    GET_ALL_CUSTOMERS: "SELECT id, name, email, joined_date FROM Customers",
    GET_ALL_PRODUCTS: "SELECT id, name, price, category FROM Products",
    GET_ALL_ORDERS: "SELECT * FROM Orders"
};

function executeQuery(query, params = []) {
    const res = db.exec(query, params);
    if (res.length === 0) return [];
    
    // Transform to array of objects
    const columns = res[0].columns;
    const values = res[0].values;
    return values.map(row => {
        const obj = {};
        columns.forEach((col, i) => obj[col] = row[i]);
        return obj;
    });
}
