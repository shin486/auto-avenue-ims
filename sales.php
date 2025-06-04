<?php
require_once __DIR__ . '/includes/config.php';

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$errors = [];
$success = false;

// Handle new sale submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
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
        if (!$product_check) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $product_check->bind_param("i", $product_id);
            $product_check->execute();
            $product = $product_check->get_result()->fetch_assoc();

            if ($product && $product['quantity'] >= $quantity) {
                // Start transaction
                $conn->begin_transaction();

                try {
                    $sale_date = date('Y-m-d H:i:s');

                    // Record the sale
                    $insert_sale = $conn->prepare("INSERT INTO sales (product_id, quantity_sold, sale_date) VALUES (?, ?, ?)");
                    if (!$insert_sale) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $insert_sale->bind_param("iis", $product_id, $quantity, $sale_date);
                    $insert_sale->execute();

                    // Update product quantity
                    $update_product = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
                    if (!$update_product) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $update_product->bind_param("ii", $quantity, $product_id);
                    $update_product->execute();

                    // Check for low stock
                    $new_quantity = $product['quantity'] - $quantity;
                    $min_stock_stmt = $conn->prepare("SELECT min_stock_level FROM products WHERE product_id = ?");
                    if (!$min_stock_stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $min_stock_stmt->bind_param("i", $product_id);
                    $min_stock_stmt->execute();
                    $min_stock_result = $min_stock_stmt->get_result()->fetch_assoc();

                    if ($min_stock_result && $new_quantity <= $min_stock_result['min_stock_level']) {
                        $message = "Low stock alert for product ID $product_id (Current: $new_quantity)";
                        $alert_insert = $conn->prepare("INSERT INTO alerts (product_id, message) VALUES (?, ?)");
                        if (!$alert_insert) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        $alert_insert->bind_param("is", $product_id, $message);
                        $alert_insert->execute();
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
}

// Handle product search with strict filtering (show only matched products)
$products_result = null;
$search = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = trim($_GET['search']);
    $sql = "
        SELECT product_id, name, category, quantity, price, is_active 
        FROM products
        WHERE is_active = TRUE AND (name LIKE ? OR category LIKE ?)
        ORDER BY 
            CASE 
                WHEN name LIKE ? THEN 1
                WHEN category LIKE ? THEN 2
                ELSE 3
            END,
            name ASC
    ";
    $search_param = "%$search%";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $products_result = $stmt->get_result();
} else {
    // Show all active products ordered by name
    $products_result = $conn->query("SELECT product_id, name, category, quantity, price, is_active FROM products WHERE is_active = TRUE ORDER BY name ASC");
    if (!$products_result) {
        die("Query failed: " . $conn->error);
    }
}

// Get the sale_date filter from GET (for filtering sales by date)
$sale_date_filter = $_GET['sale_date'] ?? '';

// Initialize where clause and parameters for prepared statement
$where_clause = "";
$params = [];
$param_types = "";

// Validate sale date filter
if ($sale_date_filter) {
    $d = DateTime::createFromFormat('Y-m-d', $sale_date_filter);
    // Ensure exact date match to prevent invalid date
    if ($d && $d->format('Y-m-d') === $sale_date_filter) {
        $start_date = $d->format('Y-m-d 00:00:00');
        $end_date = $d->format('Y-m-d 23:59:59');
        $where_clause = "WHERE s.sale_date BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
        $param_types = "ss";
    } else {
        $errors[] = "Invalid date format for sale_date filter.";
    }
}

// Prepare and execute the query to get sales data
if ($where_clause) {
    $stmt = $conn->prepare("
        SELECT s.sale_id, p.name, s.quantity_sold, p.price, (s.quantity_sold * p.price) as total, s.sale_date 
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.product_id
        $where_clause
        ORDER BY s.sale_date DESC
        LIMIT 50
    ");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $sales_result = $stmt->get_result();
} else {
    $sales_result = $conn->query("
        SELECT s.sale_id, p.name, s.quantity_sold, p.price, (s.quantity_sold * p.price) as total, s.sale_date 
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.product_id
        ORDER BY s.sale_date DESC
        LIMIT 50
    ");
    if (!$sales_result) {
        die("Query failed: " . $conn->error);
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Auto Avenue - Sales</title>
    <style>
        :root {
            --primary: rgb(0, 0, 0);
            --primary-light: rgb(66, 169, 111);
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
          .btn, .btn-clear {
    display: inline-block;
    width: 100px;           /* fixed width */
    padding: 8px 0;         /* same vertical padding, no horizontal padding for fixed width */
    font-size: 16px;
    text-align: center;     /* center text */
    border: 1px solid #ccc;
    background-color: #f5f5f5;
    color: #333;
    text-decoration: none;  /* removes underline for link */
    cursor: pointer;
    box-sizing: border-box; /* include padding and border in width */
    font-family: inherit;
    border-radius: 3px;
    user-select: none;
  }

  .btn:hover, .btn-clear:hover {
    background-color: #e0e0e0;
  }

  /* Make <button> and <a> behave the same visually */
  button.btn {
  border: none;
  background-color:rgba(22, 155, 22, 0.79);        /* bright green */
  color: white;                     /* white text */
  padding: 10px 20px;               /* comfortable padding */
  font-size: 16px;
  font-weight: 600;                 /* semi-bold */
  border-radius: 5px;               /* rounded corners */
  cursor: pointer;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* subtle shadow */
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

button.btn:hover, button.btn:focus {
  background-color: #149114;       /* darker green on hover/focus */
  box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15); /* stronger shadow */
  outline: none;
}

button.btn-record {
  border: 1px solid #ccc;
  background-color: rgb(18, 173, 18); /* same green as .btn */
  color: white;
  padding: 10px 20px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 5px;
  cursor: pointer;
  box-shadow: 0 4px 6px rgba(22, 189, 22, 0.4);
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

button.btn-record:hover, button.btn-record:focus {
  background-color: rgb(16, 136, 16); /* darker green on hover */
  box-shadow: 0 6px 8px rgba(16, 136, 16, 0.6);
  outline: none;
}






    </style>
</head>
<body>
    <header>
        <div class="container">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="autoavelogo.svg" alt="Auto Avenue Logo" class="logo" width="150" />
                <h1>Auto Avenue IMS</h1>
            </div>

            <div style="background-color: rgba(255, 255, 255, 0.15); padding: 6px 12px; border-radius: 8px; font-size: 0.9rem;">
                Logged in as: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>
            </div>

            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="products.php">Products</a>
                <a href="reports.php">Reports</a>
                <a href="sales.php" class="active">Sales</a>
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
                    <input type="number" id="quantity" name="quantity" min="1" required />
                </div>

                <button type="submit" class="btn-record">Record Sale</button>
            </form>
        </div>

        <div class="section">
            <div class="page-header">
    

<!-- Date filter form -->

<form method="GET" action="" style="margin-bottom: 20px;">
  <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
    <label for="sale_date" style="min-width: 150px; margin: 0;">Filter sales by date:</label>
    <input type="date" id="sale_date" name="sale_date" value="<?= htmlspecialchars($sale_date_filter) ?>" style="max-width: 200px; padding: 6px;" />
    <button type="submit" class="btn">Filter</button>
    <a href="sales.php" class="btn btn-clear">Clear</a>
  </div>
</form>

<form method="GET" action="sales.php" style="margin-bottom: 20px;">
  <input type="text" name="search" placeholder="Search products by name or category" 
         value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
         style="padding: 8px; width: 250px; border-radius: 4px; border: 1px solid #ccc;">
  <button type="submit" class="add-product-btn" style="padding: 8px 12px; margin-left: 5px;">Search</button>
  <?php if (!empty($_GET['search'])): ?>
    <button type="button" onclick="window.location='sales.php'" 
            style="padding: 8px 12px; margin-left: 5px; background-color: var(--danger);">
      Clear
    </button>
  <?php endif; ?>
</form>





<!-- Sales Table -->
<table>
    <thead>
        <tr>
            <th>Sale ID</th>
            <th>Product Name</th>
            <th>Quantity Sold</th>
            <th>Price</th>
            <th>Total</th>
            <th>Sale Date & Time</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($sales_result && $sales_result->num_rows > 0): ?>
            <?php while($row = $sales_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['sale_id']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['quantity_sold']) ?></td>
                    <td><?= number_format($row['price'], 2) ?></td>
                    <td><?= number_format($row['total'], 2) ?></td>
                    <td><?= htmlspecialchars($row['sale_date']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center;">No sales found for this date.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>

<?php
$conn->close();
?>