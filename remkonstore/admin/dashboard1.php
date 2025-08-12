<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Remkon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #95a5a6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            background: linear-gradient(to right, var(--primary), var(--dark));
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo i {
            font-size: 2.5rem;
            color: #f1c40f;
        }
        
        .logo-text h1 {
            font-size: 2.2rem;
            margin-bottom: 5px;
        }
        
        .logo-text p {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-info p {
            margin-bottom: 5px;
        }
        
        .logout-btn {
            color: var(--warning);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: var(--primary);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .card-icon {
            font-size: 2rem;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .card-description {
            color: var(--gray);
            line-height: 1.6;
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
            text-align: center;
            flex: 1;
            min-width: 250px;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin: 15px 0;
        }
        
        .stat-label {
            font-size: 1.1rem;
            color: var(--gray);
        }

        /* Recent Transactions Table Styles */
        .recent-transactions {
            margin-top: 30px;
        }
        
        .recent-transactions .card-header {
            background: var(--dark);
        }
        
        .recent-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .recent-table th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 12px 15px;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .recent-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .recent-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .view-history-btn {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.85rem;
        }
        
        .view-history-btn:hover {
            background-color: #2980b9;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .user-info {
                text-align: center;
            }
            
            .recent-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
     <div class="container">
        <header>
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-store"></i>
                    <div class="logo-text">
                        <h1>Remkon Store</h1>
                        <p>Administrator Dashboard</p>
                    </div>
                </div>
                <div class="user-info">
                    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
                    <p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </header>
        
        <div class="stats">
            <?php
            // Fetch statistics
            $products = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc();
            $suppliers = $conn->query("SELECT COUNT(*) AS total FROM suppliers")->fetch_assoc();
            $stores = $conn->query("SELECT COUNT(*) AS total FROM stores")->fetch_assoc();
            $sales = $conn->query("SELECT COUNT(*) AS total FROM sales")->fetch_assoc();
            $customers = $conn->query("SELECT COUNT(*) AS total FROM customers")->fetch_assoc();
            $cashiers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'cashier'")->fetch_assoc();
            ?>
            <div class="stat-card">
                <i class="fas fa-wine-bottle fa-2x"></i>
                <div class="stat-value"><?php echo $products['total']; ?></div>
                <div class="stat-label">Products</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-truck fa-2x"></i>
                <div class="stat-value"><?php echo $suppliers['total']; ?></div>
                <div class="stat-label">Suppliers</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-store-alt fa-2x"></i>
                <div class="stat-value"><?php echo $stores['total']; ?></div>
                <div class="stat-label">Stores</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-receipt fa-2x"></i>
                <div class="stat-value"><?php echo $sales['total']; ?></div>
                <div class="stat-label">Sales</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users fa-2x"></i>
                <div class="stat-value"><?php echo $customers['total']; ?></div>
                <div class="stat-label">Customers</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-tie fa-2x"></i>
                <div class="stat-value"><?php echo $cashiers['total']; ?></div>
                <div class="stat-label">Cashiers</div>
            </div>
        </div>
            
        <div class="dashboard-grid">
            <a href="products.php" class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-wine-bottle card-icon"></i>
                    <div class="card-title">Product Management</div>
                </div>
                <div class="card-body">
                    <p class="card-description">Manage all products in inventory. Add, edit, or delete products and categories.</p>
                </div>
            </a>
            
            <a href="suppliers.php" class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-truck card-icon"></i>
                    <div class="card-title">Supplier Management</div>
                </div>
                <div class="card-body">
                    <p class="card-description">Manage supplier information and relationships with products.</p>
                </div>
            </a>
            
            <a href="product_orders.php" class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-clipboard-list card-icon"></i>
                    <div class="card-title">Product Orders</div>
                </div>
                <div class="card-body">
                    <p class="card-description">Manage product orders from suppliers and track delivery status.</p>
                </div>
            </a>
            
            <a href="store_transfer.php" class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-exchange-alt card-icon"></i>
                    <div class="card-title">Store Transfers</div>
                </div>
                <div class="card-body">
                    <p class="card-description">Transfer products between stores and manage inventory levels.</p>
                </div>
            </a>
            
            <a href="supply_manager.php" class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-handshake card-icon"></i>
                    <div class="card-title">Supply Manager</div>
                </div>
                <div class="card-body">
                    <p class="card-description">Manage supplier-product relationships and supply chains.</p>
                </div>
            </a>
            
            <a href="reports.php" class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-chart-bar card-icon"></i>
                    <div class="card-title">Reports & Analytics</div>
                </div>
                <div class="card-body">
                    <p class="card-description">View sales reports, inventory analytics, and business insights.</p>
                </div>
            </a>
            
            <!-- New Customer Management Card -->
            <a href="customers.php" class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-users card-icon"></i>
                    <div class="card-title">Customer Management</div>
                </div>
                <div class="card-body">
                    <p class="card-description">Manage customer information and view transaction history.</p>
                </div>
            </a>
        </div>
        
        <!-- Recent Transactions Section -->
        <div class="recent-transactions">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-history card-icon"></i>
                    <div class="card-title">Recent Customer Transactions</div>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch recent transactions (last 5)
                    $recent_transactions = $conn->query("
                        SELECT s.id, s.sale_date, s.total_amount, s.payment_method, 
                               c.name AS customer_name, c.id AS customer_id,
                               st.store_name
                        FROM sales s
                        JOIN customers c ON s.customer_id = c.id
                        JOIN stores st ON s.store_id = st.id
                        ORDER BY s.sale_date DESC
                        LIMIT 5
                    ");
                    
                    if ($recent_transactions->num_rows > 0):
                    ?>
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Store</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($tx = $recent_transactions->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($tx['sale_date'])) ?></td>
                                <td><?= htmlspecialchars($tx['customer_name']) ?></td>
                                <td><?= htmlspecialchars($tx['store_name']) ?></td>
                                <td>₦<?= number_format($tx['total_amount'], 2) ?></td>
                                <td><?= ucfirst($tx['payment_method']) ?></td>
                                <td>
                                    <a href="customer_transactions.php?id=<?= $tx['customer_id'] ?>" 
                                       class="view-history-btn">
                                        <i class="fas fa-receipt"></i> View History
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p>No recent transactions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>