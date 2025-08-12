<?php
// admin/customers.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('admin');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_customer'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $email = $conn->real_escape_string($_POST['email']);
        $address = $conn->real_escape_string($_POST['address']);
        
        $sql = "INSERT INTO customers (name, phone, email, address) 
                VALUES ('$name', '$phone', '$email', '$address')";
        $conn->query($sql);
    }
}

// Fetch all customers
$customers = $conn->query("SELECT * FROM customers ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management | Remkon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
            margin-bottom: 30px;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
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
            font-size: 1.5rem;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Form styling */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--secondary), #2980b9);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, #2980b9, var(--secondary));
            transform: translateY(-2px);
        }
        
        /* Table styling */
        .customer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .customer-table th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 15px;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            color: var(--dark);
        }
        
        .customer-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .customer-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .btn-action {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .btn-view {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-view:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-edit {
            background-color: var(--success);
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #219653;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background-color: var(--accent);
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }
        
        .action-group {
            display: flex;
            gap: 8px;
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
            
            .customer-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-group {
                flex-direction: column;
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
                        <p>Customer Management</p>
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
        
        <div class="dashboard-card">
            <div class="card-header">
                <i class="fas fa-user-plus card-icon"></i>
                <div class="card-title">Add New Customer</div>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" id="email" name="email" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                            <textarea id="address" name="address" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_customer" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Customer
                    </button>
                </form>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <i class="fas fa-list card-icon"></i>
                <div class="card-title">Customer Directory</div>
            </div>
            <div class="card-body">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Transactions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($customer = $customers->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($customer['name']) ?></td>
                            <td><?= htmlspecialchars($customer['phone']) ?></td>
                            <td><?= htmlspecialchars($customer['email']) ?></td>
                            <td>
                                <a href="customer_transactions.php?id=<?= $customer['id'] ?>" class="btn-action btn-view">
                                    <i class="fas fa-receipt"></i> View History
                                </a>
                            </td>
                            <td>
                                <div class="action-group">
                                    <a href="#" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="#" class="btn-action btn-delete">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>