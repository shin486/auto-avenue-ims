<?php
require_once __DIR__ . '/includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}



// Initialize variables
$errors = [];
$success = false;
$products = [];
$sales = [];

// Fetch active products for dropdown
$products_result = $conn->query("SELECT product_id, name, price, quantity FROM products WHERE is_active = TRUE AND quantity > 0 ORDER BY name");

// Handle new sale submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // Validate inputs
    if ($product_id <= 0) {
        $errors[] = "Invalid product selection";
    }
    if ($quantity <= 0) {
        $errors[] = "Quantity must be greater than 0";
    }
    
    if (empty($errors)) {
        // Check product availability
        $product_check = $conn->prepare("SELECT quantity, price FROM products WHERE product_id = ?");
        $product_check->bind_param("i", $product_id);
        $product_check->execute();
        $product = $product_check->get_result()->fetch_assoc();
        
        if ($product && $product['quantity'] >= $quantity) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Record the sale
                $insert_sale = $conn->prepare("INSERT INTO sales (product_id, quantity_sold) VALUES (?, ?)");
                $insert_sale->bind_param("ii", $product_id, $quantity);
                $insert_sale->execute();
                
                // Update product quantity
                $update_product = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
                $update_product->bind_param("ii", $quantity, $product_id);
                $update_product->execute();
                
                // Check for low stock
                $new_quantity = $product['quantity'] - $quantity;
                $min_stock = $conn->query("SELECT min_stock_level FROM products WHERE product_id = $product_id")->fetch_assoc();
                
                if ($new_quantity <= $min_stock['min_stock_level']) {
                    $message = "Low stock alert for product ID $product_id (Current: $new_quantity)";
                    $conn->query("INSERT INTO alerts (product_id, message) VALUES ($product_id, '$message')");
                }
                
                $conn->commit();
                $success = true;
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Error processing sale: " . $e->getMessage();
            }
        } else {
            $errors[] = "Insufficient stock available";
        }
    }
}

// Fetch recent sales
$sales_result = $conn->query("
    SELECT s.sale_id, p.name, s.quantity_sold, p.price, (s.quantity_sold * p.price) as total, s.sale_date 
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.product_id
    ORDER BY s.sale_date DESC LIMIT 50
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Avenue - Sales</title>
    <style>
        :root {
             --primary:rgb(11, 129, 38);
      --primary-light:rgb(29, 136, 63);
            --secondary: #ff6b6b;
            --success: #4CAF50;
            --warning: #ff9800;
            --danger: #f44336;
            --text-color: #333;
            --text-light: #666;
            --bg-color: #f4f6f8;
            --border-color: #e0e0e0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 4px;
        }

        nav a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        nav a.active {
            background-color: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }

        .main-content {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
        }

        .section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }

        .page-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .page-header h2 {
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        select, input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: var(--primary-light);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .error {
            color: var(--danger);
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .success {
            color: var(--success);
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            nav {
                margin-top: 15px;
                width: 100%;
            }
            
            nav a {
                margin: 0 10px 0 0;
                display: inline-block;
                padding: 8px 0;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
      <div style="display: flex; align-items: center; gap: 15px;">
  <img src="autoavelogo.svg" alt="Auto Avenue Logo" class="logo" width="150">
  <h1>Auto Avenue IMS</h1>
</div>

            <div style="background-color: rgba(255, 255, 255, 0.15); padding: 6px 12px; border-radius: 8px; font-size: 0.9rem;">
    Logged in as: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>
  </div>

            <nav>
               <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="reports.php">Reports</a>
        <a href="sales.php"class="active">Sales</a>
        <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="section">
            <div class="page-header">
                <h2>Record New Sale</h2>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success">
                    Sale recorded successfully!
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="product_id">Product</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">Select a product</option>
                        <?php while ($product = $products_result->fetch_assoc()): ?>
                            <option value="<?= $product['product_id'] ?>">
                                <?= htmlspecialchars($product['name']) ?> 
                                (₱<?= number_format($product['price'], 2) ?>) 
                                - Stock: <?= $product['quantity'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>

                <button type="submit" class="btn">Record Sale</button>
            </form>
        </div>

        <div class="section">
            <div class="page-header">
                <h2>Recent Sales</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($sale = $sales_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('M j, Y h:i A', strtotime($sale['sale_date'])) ?></td>
                            <td><?= htmlspecialchars($sale['name'] ?? 'Deleted Product') ?></td>
                            <td><?= $sale['quantity_sold'] ?></td>
                            <td>₱<?= number_format($sale['price'], 2) ?></td>
                            <td>₱<?= number_format($sale['total'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>