<?php
// cashier/pos.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || !has_role('cashier')) {
    header("Location: ../index.php");
    exit();
}

// Get store ID from session
$store_id = $_SESSION['store_id'] ?? 1;

// Get store information
$store_info = [];
$store_query = $conn->query("SELECT * FROM stores WHERE id = $store_id");
if ($store_query && $store_query->num_rows > 0) {
    $store_info = $store_query->fetch_assoc();
    // Map database fields to expected keys
    $store_info['address'] = $store_info['store_location'] ?? 'Unknown address';
    $store_info['phone'] = $store_info['store_head'] ? 'Contact: ' . $store_info['store_head'] : 'No contact';
} else {
    $store_info = [
        'store_name' => 'Remkon Store',
        'address' => '123 Main Street',
        'phone' => '(555) 123-4567'
    ];
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart = &$_SESSION['cart'];
$cart_total = 0;
$success = '';
$error = '';
$show_receipt = false;

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Get product details
    $product_query = $conn->query("SELECT * FROM products WHERE id = $product_id");
    if ($product_query && $product_query->num_rows > 0) {
        $product = $product_query->fetch_assoc();
        
        // Check if product already in cart
        $found = false;
        foreach ($cart as &$item) {
            if ($item['id'] === $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $cart[] = [
                'id' => $product_id,
                'name' => $product['product_name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
        
        // Redirect to prevent duplicate adds
        header("Location: pos.php");
        exit();
    }
}

// Handle remove from cart
if (isset($_GET['remove_from_cart'])) {
    $index = (int)$_GET['remove_from_cart'];
    if (isset($cart[$index])) {
        array_splice($cart, $index, 1);
    }
    header("Location: pos.php");
    exit();
}

// Handle clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = [];
    $cart = [];
    header("Location: pos.php");
    exit();
}

// Process sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_sale'])) {
    if (empty($cart)) {
        $error = "Cannot process an empty sale!";
    } else {
        $payment_method = $conn->real_escape_string($_POST['payment_method']);
        $cashier_name = $conn->real_escape_string($_POST['cashier_name']);
        $customer_phone = $conn->real_escape_string($_POST['customer_phone'] ?? '');
        $total_amount = (float)$_POST['total_amount'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create sale record
            $sale_sql = "INSERT INTO sales (sale_date, total_amount, payment_method, cashier_name, store_id)
                         VALUES (NOW(), $total_amount, '$payment_method', '$cashier_name', $store_id)";
            
            if ($conn->query($sale_sql)) {
                $sale_id = $conn->insert_id;
                
                // Add sale items
                foreach ($cart as $item) {
                    $product_id = (int)$item['id'];
                    $quantity = (int)$item['quantity'];
                    $unit_price = (float)$item['price'];
                    $subtotal = $quantity * $unit_price;
                    
                    // Insert sale item
                    $item_sql = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal)
                                 VALUES ($sale_id, $product_id, $quantity, $unit_price, $subtotal)";
                    $conn->query($item_sql);
                    
                    // Update inventory
                    $update_sql = "UPDATE store_inventory 
                                   SET quantity = quantity - $quantity 
                                   WHERE store_id = $store_id AND product_id = $product_id";
                    $conn->query($update_sql);
                }
                
                // Store receipt data in session
                $_SESSION['last_receipt'] = [
                    'id' => $sale_id,
                    'date' => date('Y-m-d H:i:s'),
                    'store_name' => $store_info['store_name'],
                    'cashier' => $cashier_name,
                    'payment_method' => $payment_method,
                    'total' => $total_amount,
                    'items' => $cart
                ];
                
                // Clear cart
                $_SESSION['cart'] = [];
                $cart = [];
                
                $conn->commit();
                $success = "Sale processed successfully! Receipt #$sale_id";
                $show_receipt = true;
            } else {
                throw new Exception("Error creating sale record: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Get products for POS with inventory
$products = $conn->query("
    SELECT p.*, COALESCE(si.quantity, 0) AS stock 
    FROM products p
    LEFT JOIN store_inventory si ON p.id = si.product_id AND si.store_id = $store_id
");

// Calculate cart total
foreach ($cart as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remkon Store - POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #34495e;
            --gray: #95a5a6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, var(--secondary), var(--dark));
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo i {
            font-size: 2.8rem;
            color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 50%;
        }
        
        .logo-text h1 {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .logo-text p {
            font-size: 1.1rem;
            opacity: 0.9;
            color: #e0e0e0;
        }
        
        .store-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px 25px;
            border-radius: 12px;
            text-align: right;
            min-width: 300px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .store-info div:first-child {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 8px;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .notification {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .notification i {
            font-size: 1.8rem;
        }
        
        .notification.success {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.9), rgba(39, 174, 96, 0.9));
            border: 1px solid rgba(39, 174, 96, 0.5);
            color: white;
        }
        
        .notification.error {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.9), rgba(192, 57, 43, 0.9));
            border: 1px solid rgba(192, 57, 43, 0.5);
            color: white;
        }
        
        .layout {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 30px;
        }
        
        .products-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .products-section h2 {
            color: var(--secondary);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.8rem;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .product-card {
            border: 1px solid #e0e7ff;
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            background: white;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
            border-color: var(--primary);
        }
        
        .product-name {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .product-price {
            font-weight: 800;
            font-size: 1.6rem;
            color: var(--primary);
            margin: 5px 0;
        }
        
        .product-stock {
            font-size: 1rem;
            padding: 6px 15px;
            border-radius: 20px;
            display: inline-block;
            width: fit-content;
            font-weight: 600;
        }
        
        .stock-low {
            background: linear-gradient(135deg, #f9c851, #f39c12);
            color: white;
        }
        
        .stock-out {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .product-form {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }
        
        .quantity-input {
            width: 80px;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1.1rem;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .quantity-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .add-to-cart-btn {
            background: linear-gradient(135deg, var(--primary), #1a5276);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            cursor: pointer;
            font-size: 1.3rem;
            transition: all 0.3s;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .add-to-cart-btn:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transform: translateX(-100%);
            transition: all 0.3s;
        }
        
        .add-to-cart-btn:hover:after {
            transform: translateX(0);
        }
        
        .add-to-cart-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }
        
        .add-to-cart-btn:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .add-to-cart-btn:disabled:after {
            display: none;
        }
        
        .cart-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            height: fit-content;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .cart-header {
            background: linear-gradient(135deg, var(--primary), #1a5276);
            color: white;
            padding: 25px;
        }
        
        .cart-header h2 {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.8rem;
            margin: 0;
        }
        
        .cart-body {
            padding: 20px;
            flex-grow: 1;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .empty-cart {
            text-align: center;
            padding: 50px 20px;
            color: var(--gray);
        }
        
        .empty-cart i {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.2;
        }
        
        .empty-cart h3 {
            font-size: 1.6rem;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .empty-cart p {
            font-size: 1.1rem;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            gap: 20px;
            align-items: center;
            transition: all 0.3s;
        }
        
        .cart-item:hover {
            background-color: #f9f9f9;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark);
            font-size: 1.2rem;
        }
        
        .cart-item-price {
            font-size: 1.1rem;
            color: var(--gray);
            font-weight: 600;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 700;
            color: var(--secondary);
            min-width: 150px;
            justify-content: flex-end;
            font-size: 1.2rem;
        }
        
        .remove-btn {
            color: var(--danger);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .remove-btn:hover {
            background-color: rgba(231, 76, 60, 0.1);
            transform: scale(1.1);
        }
        
        .cart-footer {
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-top: 1px solid #eee;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 30px;
            padding: 15px 0;
            color: var(--dark);
            border-bottom: 2px dashed #ccc;
        }
        
        .payment-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .form-group label {
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }
        
        .form-control {
            padding: 16px 20px;
            border: 2px solid #ddd;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2);
        }
        
        .btn-group {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 16px 25px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-align: center;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .btn:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.15);
            transform: translateX(-100%);
            transition: all 0.3s;
        }
        
        .btn:hover:after {
            transform: translateX(0);
        }
        
        .btn-clear {
            background: linear-gradient(135deg, #e0e0e0, #bdbdbd);
            color: var(--dark);
            flex: 1;
        }
        
        .btn-clear:hover {
            background: linear-gradient(135deg, #d5dbdb, #aebfbe);
            transform: translateY(-3px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--success), #27ae60);
            color: white;
            flex: 2;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.4);
        }
        
        .btn-primary:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Receipt Modal */
        .receipt-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeInModal 0.4s ease;
        }
        
        @keyframes fadeInModal {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .receipt-content {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            transform: scale(0.95);
            animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        
        @keyframes popIn {
            0% { transform: scale(0.95); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }
        
        .receipt-header {
            background: linear-gradient(135deg, var(--secondary), var(--dark));
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        
        .receipt-header h2 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        
        .receipt-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .receipt-body {
            padding: 30px;
            color: #333;
            background: #fff;
        }
        
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px dashed #eee;
            font-size: 1.1rem;
        }
        
        .receipt-row.total-row {
            font-weight: 800;
            font-size: 1.4rem;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .receipt-footer {
            padding: 25px;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-top: 1px solid #eee;
        }
        
        .receipt-footer p {
            font-style: italic;
            color: var(--gray);
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .close-receipt {
            background: linear-gradient(135deg, var(--primary), #1a5276);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-receipt:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        /* Thermal Printer Styles */
        .thermal-print {
            display: none;
            font-family: monospace;
            max-width: 80mm;
            margin: 0 auto;
            padding: 10px;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .thermal-header {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .thermal-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .thermal-details {
            margin-bottom: 10px;
        }
        
        .thermal-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .thermal-total {
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #000;
        }
        
        .thermal-footer {
            text-align: center;
            margin-top: 15px;
            font-style: italic;
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #1a5276;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .thermal-print, .thermal-print * {
                visibility: visible;
            }
            .thermal-print {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-cash-register"></i>
                    <div class="logo-text">
                        <h1><?= htmlspecialchars($store_info['store_name']) ?></h1>
                        <p>Point of Sale System</p>
                    </div>
                </div>
                <div class="store-info">
                    <div>Store #<?= $store_id ?> - <?= htmlspecialchars($store_info['store_name']) ?></div>
                    <div><?= htmlspecialchars($store_info['address']) ?> | <?= htmlspecialchars($store_info['phone']) ?></div>
                    <div>Today: <?= date('F j, Y, h:i A') ?></div>
                </div>
            </div>
        </header>
        
        <!-- Notification area -->
        <?php if ($success): ?>
            <div class="notification success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="notification error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <div class="layout">
            <!-- Products Section -->
            <div class="products-section">
                <h2><i class="fas fa-wine-bottle"></i> Available Products</h2>
                <div class="products-grid">
                    <?php if ($products && $products->num_rows > 0): ?>
                        <?php while ($product = $products->fetch_assoc()): 
                            $stock_class = '';
                            if ($product['stock'] <= 0) $stock_class = 'stock-out';
                            elseif ($product['stock'] < 10) $stock_class = 'stock-low';
                        ?>
                            <div class="product-card">
                                <div class="product-name">
                                    <i class="fas fa-wine-bottle"></i>
                                    <?= htmlspecialchars($product['product_name']) ?>
                                </div>
                                <div class="product-price">
                                    ₦<?= number_format($product['price'], 2) ?>
                                </div>
                                <div class="product-stock <?= $stock_class ?>">
                                    <?= $product['stock'] ?> in stock
                                </div>
                                <form method="POST" class="product-form">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="number" name="quantity" class="quantity-input" value="1" min="1" 
                                           max="<?= $product['stock'] ?>" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <button type="submit" name="add_to_cart" class="add-to-cart-btn" 
                                            title="Add to Cart" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No products available. Please add products to inventory.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Shopping Cart Section -->
            <div class="cart-container">
                <div class="cart-header">
                    <h2><i class="fas fa-shopping-cart"></i> Current Sale</h2>
                </div>
                <div class="cart-body">
                    <?php if (!empty($cart)): ?>
                        <?php foreach ($cart as $index => $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-info">
                                    <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="cart-item-price">₦<?= number_format($item['price'], 2) ?> each</div>
                                </div>
                                <div class="cart-item-quantity">
                                    <span><?= $item['quantity'] ?></span>
                                    <span>×</span>
                                    <span>₦<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                </div>
                                <a href="pos.php?remove_from_cart=<?= $index ?>" class="remove-btn" title="Remove Item">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>Your cart is empty</h3>
                            <p>Add products to start a sale</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="cart-footer">
                    <div class="cart-total">
                        <span>Total:</span>
                        <span>₦<?= number_format($cart_total, 2) ?></span>
                    </div>
                    
                    <form method="POST" action="pos.php">
                        <input type="hidden" name="total_amount" value="<?= $cart_total ?>">
                        
                        <div class="payment-form">
                            <div class="form-group">
                                <label for="payment_method"><i class="fas fa-credit-card"></i> Payment Method</label>
                                <select name="payment_method" id="payment_method" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="debit_card">Debit Card</option>
                                    <option value="mobile_payment">Mobile Payment</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="cashier_name"><i class="fas fa-user-tie"></i> Cashier Name</label>
                                <input type="text" name="cashier_name" id="cashier_name" class="form-control" 
                                       value="<?= htmlspecialchars($_SESSION['username']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="customer_phone"><i class="fas fa-user"></i> Customer Phone</label>
                                <input type="text" name="customer_phone" id="customer_phone" class="form-control" 
                                       placeholder="Enter phone number for receipt">
                            </div>
                            
                            <div class="btn-group">
                                <a href="pos.php?clear_cart=true" class="btn btn-clear">
                                    <i class="fas fa-trash"></i> Clear Cart
                                </a>
                                
                                <button type="submit" name="process_sale" class="btn btn-primary" <?= empty($cart) ? 'disabled' : '' ?>>
                                    <i class="fas fa-check-circle"></i> Process Sale (₦<?= number_format($cart_total, 2) ?>)
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Receipt Modal -->
    <div class="receipt-modal" id="receiptModal">
        <div class="receipt-content">
            <div class="receipt-header">
                <h2><?= htmlspecialchars($store_info['store_name']) ?></h2>
                <p>Point of Sale Receipt</p>
            </div>
            <div class="receipt-body">
                <div class="receipt-details">
                    <div class="receipt-row">
                        <span>Receipt #:</span>
                        <span id="receiptNumber">
                            <?= $_SESSION['last_receipt']['id'] ?? '1001' ?>
                        </span>
                    </div>
                    <div class="receipt-row">
                        <span>Date:</span>
                        <span>
                            <?= isset($_SESSION['last_receipt']['date']) 
                                ? date('M j, Y h:i A', strtotime($_SESSION['last_receipt']['date'])) 
                                : date('M j, Y h:i A') ?>
                        </span>
                    </div>
                    <div class="receipt-row">
                        <span>Store:</span>
                        <span id="receiptStore">
                            <?= htmlspecialchars($_SESSION['last_receipt']['store_name'] ?? $store_info['store_name']) ?>
                        </span>
                    </div>
                    <div class="receipt-row">
                        <span>Cashier:</span>
                        <span id="receiptCashier">
                            <?= htmlspecialchars($_SESSION['last_receipt']['cashier'] ?? $_SESSION['username']) ?>
                        </span>
                    </div>
                    <div class="receipt-row">
                        <span>Payment Method:</span>
                        <span id="receiptPayment">
                            <?= isset($_SESSION['last_receipt']['payment_method']) 
                                ? htmlspecialchars(ucfirst($_SESSION['last_receipt']['payment_method'])) 
                                : 'Cash' ?>
                        </span>
                    </div>
                </div>
                
                <div style="margin: 25px 0; border-top: 2px dashed #ddd;"></div>
                
                <div id="receiptItems">
                    <?php if (isset($_SESSION['last_receipt']['items'])): ?>
                        <?php foreach ($_SESSION['last_receipt']['items'] as $item): ?>
                            <div class="receipt-row">
                                <div>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($item['name']) ?></div>
                                    <div style="font-size: 0.95rem; color: #777;">
                                        <?= $item['quantity'] ?> × ₦<?= number_format($item['price'], 2) ?>
                                    </div>
                                </div>
                                <div style="font-weight: 600;">₦<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="receipt-row">No items in receipt</div>
                    <?php endif; ?>
                </div>
                
                <div style="margin: 25px 0; border-top: 2px dashed #ddd;"></div>
                
                <div class="receipt-row total-row">
                    <span>Total Amount:</span>
                    <span>
                        ₦<?= isset($_SESSION['last_receipt']['total']) 
                            ? number_format($_SESSION['last_receipt']['total'], 2) 
                            : '0.00' ?>
                    </span>
                </div>
            </div>
            <div class="receipt-footer">
                <p>Thank you for shopping with us!</p>
                <button class="close-receipt" onclick="closeReceipt()">
                    <i class="fas fa-times"></i> Close Receipt
                </button>
                <button class="close-receipt" onclick="printThermal()" style="margin-top: 10px;">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
    
    <!-- Thermal Printer Format -->
    <div class="thermal-print" id="thermalPrint">
        <div class="thermal-header">
            <div class="thermal-title"><?= htmlspecialchars($store_info['store_name']) ?></div>
            <div>Point of Sale Receipt</div>
        </div>
        
        <div class="thermal-details">
            <div class="thermal-row">
                <span>Receipt #:</span>
                <span><?= $_SESSION['last_receipt']['id'] ?? '1001' ?></span>
            </div>
            <div class="thermal-row">
                <span>Date:</span>
                <span>
                    <?= isset($_SESSION['last_receipt']['date']) 
                        ? date('M j, Y h:i A', strtotime($_SESSION['last_receipt']['date'])) 
                        : date('M j, Y h:i A') ?>
                </span>
            </div>
            <div class="thermal-row">
                <span>Store:</span>
                <span><?= htmlspecialchars($_SESSION['last_receipt']['store_name'] ?? $store_info['store_name']) ?></span>
            </div>
            <div class="thermal-row">
                <span>Cashier:</span>
                <span><?= htmlspecialchars($_SESSION['last_receipt']['cashier'] ?? $_SESSION['username']) ?></span>
            </div>
            <div class="thermal-row">
                <span>Payment:</span>
                <span>
                    <?= isset($_SESSION['last_receipt']['payment_method']) 
                        ? htmlspecialchars(ucfirst($_SESSION['last_receipt']['payment_method'])) 
                        : 'Cash' ?>
                </span>
            </div>
        </div>
        
        <div style="text-align: center; margin: 10px 0;">--------------------------------</div>
        
        <div class="thermal-items">
            <?php if (isset($_SESSION['last_receipt']['items'])): ?>
                <?php foreach ($_SESSION['last_receipt']['items'] as $item): ?>
                    <div class="thermal-row">
                        <div>
                            <div><?= htmlspecialchars($item['name']) ?></div>
                            <div><?= $item['quantity'] ?> × ₦<?= number_format($item['price'], 2) ?></div>
                        </div>
                        <div>₦<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="thermal-row">No items in receipt</div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin: 10px 0;">--------------------------------</div>
        
        <div class="thermal-total">
            <div class="thermal-row">
                <span>Total:</span>
                <span>₦<?= isset($_SESSION['last_receipt']['total']) 
                            ? number_format($_SESSION['last_receipt']['total'], 2) 
                            : '0.00' ?></span>
            </div>
        </div>
        
        <div class="thermal-footer">
            <div>Thank you for shopping with us!</div>
            <div><?= date('M j, Y h:i A') ?></div>
        </div>
    </div>

    <script>
        function closeReceipt() {
            document.getElementById('receiptModal').style.display = 'none';
        }
        
        function printThermal() {
            // Show the thermal print version
            document.getElementById('thermalPrint').style.display = 'block';
            
            // Print the thermal version
            window.print();
            
            // Hide again after printing
            setTimeout(() => {
                document.getElementById('thermalPrint').style.display = 'none';
            }, 1000);
        }
        
        <?php if ($show_receipt): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('receiptModal').style.display = 'flex';
            });
        <?php endif; ?>
    </script>
</body>
</html>