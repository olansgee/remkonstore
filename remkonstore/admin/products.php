<?php
// admin/products.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || !has_role('admin')) {
    header("Location: ../index.php");
    exit();
}

$errors = [];
$success = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ADD PRODUCT
    if (isset($_POST['add'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $desc = $conn->real_escape_string($_POST['description']);
        $category = $conn->real_escape_string($_POST['category']);
        $price = floatval($_POST['price']);
        
        $sql = "INSERT INTO products (product_name, product_description, category, price)
                VALUES ('$name', '$desc', '$category', $price)";
        if ($conn->query($sql)) {
            $success = "Product added successfully!";
        } else {
            $errors[] = "Error adding product: " . $conn->error;
        }
    }
    
    // UPDATE PRODUCT
    if (isset($_POST['update'])) {
        $id = (int)$_POST['id'];
        $name = $conn->real_escape_string($_POST['name']);
        $desc = $conn->real_escape_string($_POST['description']);
        $category = $conn->real_escape_string($_POST['category']);
        $price = floatval($_POST['price']);
        
        $sql = "UPDATE products SET 
                product_name = '$name',
                product_description = '$desc',
                category = '$category',
                price = $price
                WHERE id = $id";
        if ($conn->query($sql)) {
            $success = "Product updated successfully!";
        } else {
            $errors[] = "Error updating product: " . $conn->error;
        }
    }
}

// DELETE PRODUCT
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM products WHERE id = $id";
    if ($conn->query($sql)) {
        $success = "Product deleted successfully!";
    } else {
        $errors[] = "Error deleting product: " . $conn->error;
    }
}

// Fetch all products
$products = $conn->query("SELECT * FROM products");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Manager - Remkon Store</title>
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
            max-width: 1200px;
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
        
        header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        header p {
            opacity: 0.9;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: var(--primary);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px;
            border: 2px solid #e1e5eb;
            border-radius: 10px;
            font-size: 1rem;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn i {
            font-size: 1.1rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a252f;
            transform: translateY(-2px);
        }
        
        .btn-update {
            background: var(--success);
            color: white;
        }
        
        .btn-update:hover {
            background: #219653;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: var(--gray);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .table-container {
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e1e5eb;
        }
        
        th {
            background: var(--primary);
            color: white;
            position: sticky;
            top: 0;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #e9f7fe;
        }
        
        .action-cell {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2980b9;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        .notification {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-wine-bottle"></i> Product Manager</h1>
            <p>Manage your product inventory for Remkon Store</p>
        </header>
        
        <!-- Notifications -->
        <?php if (!empty($errors)): ?>
            <div class="notification error">
                <?php foreach ($errors as $error): ?>
                    <p><?= $error ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="notification success">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-edit"></i> Product Information</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-signature"></i> Product Name *</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category"><i class="fas fa-tags"></i> Category *</label>
                            <select id="category" name="category" class="form-control" required>
                                <option value="">-- Select Category --</option>
                                <option value="Gin">Gin</option>
                                <option value="Wine">Wine</option>
                                <option value="Soda">Soda</option>
                                <option value="Juice">Juice</option>
                                <option value="Spirit">Spirit</option>
                                <option value="Water">Water</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="price"><i class="fas fa-tag"></i> Price *</label>
                            <input type="number" id="price" name="price" class="form-control" 
                                   min="0.01" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description"><i class="fas fa-align-left"></i> Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" name="add" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add Product
                        </button>
                        <button type="submit" name="update" class="btn btn-update">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                        <button type="button" onclick="resetForm()" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Product List</h2>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $products->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td>$<?= number_format($row['price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['product_description']) ?></td>
                                <td class="action-cell">
                                    <button class="btn-action btn-edit" onclick="editProduct(
                                        <?= $row['id'] ?>, 
                                        '<?= addslashes($row['product_name']) ?>',
                                        '<?= $row['category'] ?>',
                                        <?= $row['price'] ?>,
                                        `<?= addslashes($row['product_description']) ?>`
                                    )">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?delete=<?= $row['id'] ?>" class="btn-action btn-delete" 
                                       onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editProduct(id, name, category, price, desc) {
            document.getElementById('edit_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('category').value = category;
            document.getElementById('price').value = price;
            document.getElementById('description').value = desc;
            
            // Scroll to form
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        }
        
        function resetForm() {
            document.getElementById('edit_id').value = '';
            document.getElementById('name').value = '';
            document.getElementById('category').selectedIndex = 0;
            document.getElementById('price').value = '';
            document.getElementById('description').value = '';
        }
    </script>
</body>
</html>