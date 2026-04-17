document.addEventListener('DOMContentLoaded', async () => {
    // 1. Initialize Database
    const success = await initDatabase();
    const statusEl = document.getElementById('db-status');
    
    if (success) {
        statusEl.textContent = 'Active (SQL.js)';
        statusEl.parentElement.classList.add('active');
        refreshDashboard();
    } else {
        statusEl.textContent = 'Failed';
        statusEl.style.color = '#ef4444';
    }

    // 2. Navigation Logic
    const navItems = document.querySelectorAll('.nav-item');
    const views = document.querySelectorAll('.view');

    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const viewId = item.dataset.view;
            
            // UI Toggle
            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            // View Toggle
            if (viewId === 'dashboard') {
                showDashboard();
            } else {
                showGenericView(viewId);
            }
        });
    });

    // 3. Search Logic
    const searchInput = document.querySelector('.search-bar input');
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        filterCurrentView(query);
    });
});

/**
 * Dashboard Functions
 */

function refreshDashboard() {
    updateStats();
    populateOrderHistory();
}

function updateStats() {
    // Highest Value Order
    const highOrder = executeQuery(QUERIES.GET_HIGHEST_ORDER)[0];
    if (highOrder) {
        document.getElementById('highest-order-val').textContent = formatCurrency(highOrder.total_value);
        document.getElementById('highest-order-desc').textContent = `${highOrder.name} (#${highOrder.id})`;
    }

    // Most Active Customer
    const activeCust = executeQuery(QUERIES.GET_MOST_ACTIVE_CUSTOMER)[0];
    if (activeCust) {
        document.getElementById('active-customer-name').textContent = activeCust.name;
        document.getElementById('active-customer-desc').textContent = `${activeCust.order_count} Orders placed`;
    }

    // Total Revenue
    const revenue = executeQuery(QUERIES.GET_TOTAL_REVENUE)[0];
    if (revenue) {
        document.getElementById('total-revenue').textContent = formatCurrency(revenue.total_revenue);
    }
}

function populateOrderHistory() {
    const orders = executeQuery(QUERIES.GET_ORDER_HISTORY);
    const tbody = document.querySelector('#order-history-table tbody');
    tbody.innerHTML = '';

    orders.forEach(order => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="id-text">#${order.id}</span></td>
            <td>${order.customer_name}</td>
            <td>${order.product_name}</td>
            <td>${order.quantity}</td>
            <td><span class="total-pill">${formatCurrency(order.total_value)}</span></td>
            <td style="color: var(--text-secondary); font-size: 0.8rem;">${order.order_date}</td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Generic View Logic (Customers, Products, etc.)
 */

function showDashboard() {
    document.getElementById('dashboard-view').style.display = 'block';
    document.getElementById('other-view').style.display = 'none';
    refreshDashboard();
}

function showGenericView(type) {
    document.getElementById('dashboard-view').style.display = 'none';
    const otherView = document.getElementById('other-view');
    otherView.style.display = 'block';

    const titleEl = document.getElementById('view-title');
    const subtitleEl = document.getElementById('view-subtitle');
    const headRow = document.getElementById('table-head-row');
    const body = document.getElementById('table-body');

    let query = '';
    let title = '';
    let subtitle = '';

    switch(type) {
        case 'customers':
            query = QUERIES.GET_ALL_CUSTOMERS;
            title = 'Customers Directory';
            subtitle = 'Full list of registered clients';
            break;
        case 'products':
            query = QUERIES.GET_ALL_PRODUCTS;
            title = 'Product Inventory';
            subtitle = 'Hardware and software assets';
            break;
        case 'orders':
            query = QUERIES.GET_ORDER_HISTORY;
            title = 'All Orders';
            subtitle = 'Historical transaction logs';
            break;
    }

    titleEl.textContent = title;
    subtitleEl.textContent = subtitle;

    const data = executeQuery(query);
    if (data.length > 0) {
        // Headers
        const cols = Object.keys(data[0]);
        headRow.innerHTML = cols.map(c => `<th>${c.replace('_', ' ')}</th>`).join('');
        
        // Body
        body.innerHTML = data.map(row => `
            <tr>
                ${cols.map((c, i) => {
                    let val = row[c];
                    if (c === 'price' || c === 'total_value') val = formatCurrency(val);
                    if (c === 'id') return `<td><span class="id-text">#${val}</span></td>`;
                    return `<td>${val}</td>`;
                }).join('')}
            </tr>
        `).join('');
    }
}

/**
 * Helpers
 */

function formatCurrency(val) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(val);
}

function filterCurrentView(query) {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}
