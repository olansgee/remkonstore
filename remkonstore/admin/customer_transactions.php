<?php
// admin/customer_transactions.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('admin');

$customer_id = (int)$_GET['id'];
$customer = $conn->query("SELECT * FROM customers WHERE id = $customer_id")->fetch_assoc();
$transactions = $conn->query("
    SELECT s.*, st.store_name 
    FROM sales s
    JOIN stores st ON s.store_id = st.id
    WHERE customer_id = $customer_id
    ORDER BY sale_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Consistent admin styling -->
</head>
<body>
    <div class="container">
        <header>
            <h1>
                <i class="fas fa-receipt"></i> 
                Transaction History: <?= htmlspecialchars($customer['name']) ?>
            </h1>
        </header>

        <div class="card">
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Store</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Cashier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($tx = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('M d, Y h:i A', strtotime($tx['sale_date'])) ?></td>
                            <td><?= htmlspecialchars($tx['store_name']) ?></td>
                            <td>₦<?= number_format($tx['total_amount'], 2) ?></td>
                            <td><?= ucfirst($tx['payment_method']) ?></td>
                            <td><?= htmlspecialchars($tx['cashier_name']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>