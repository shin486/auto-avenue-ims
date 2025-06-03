<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'auto_avenue_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$result = null;
$error_message = null;

try {
    // Check if column exists (for backward compatibility)
    $column_check = $conn->query("SHOW COLUMNS FROM products LIKE 'is_active'");
    if ($column_check && $column_check->num_rows == 0) {
        // Add the column if it doesn't exist
        if (!$conn->query("ALTER TABLE products ADD COLUMN is_active BOOLEAN DEFAULT TRUE")) {
            throw new Exception("Failed to add is_active column: " . $conn->error);
        }
    }

    // Handle product quantity updates
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
        $product_id = $conn->real_escape_string($_POST['product_id']);
        $action = $_POST['action'];
        
        // Get current quantity
        $sql = "SELECT quantity, min_stock_level FROM products WHERE product_id = $product_id";
        $res = $conn->query($sql);
        
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $current_quantity = $row['quantity'];
            
            // Update quantity
            $new_quantity = ($action == 'add') ? $current_quantity + 1 : max(0, $current_quantity - 1);
            $update_sql = "UPDATE products SET quantity = $new_quantity WHERE product_id = $product_id";
            if (!$conn->query($update_sql)) {
                throw new Exception("Failed to update quantity: " . $conn->error);
            }
            
            // Check for low stock alert
            if ($new_quantity <= $row['min_stock_level']) {
                $message = "Low stock alert for product ID $product_id (Current: $new_quantity)";
                $alert_sql = "INSERT INTO alerts (product_id, message) VALUES ($product_id, '".$conn->real_escape_string($message)."')";
                if (!$conn->query($alert_sql)) {
                    throw new Exception("Failed to create alert: " . $conn->error);
                }
            }
        }
        
        header("Location: products.php");
        exit();
    }

    // Handle search query
    $search = "";
    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
        $search = $conn->real_escape_string(trim($_GET['search']));
        $sql = "SELECT * FROM products WHERE is_active = TRUE AND (name LIKE '%$search%' OR category LIKE '%$search%') ORDER BY name ASC";
    } else {
        // Fetch all active products
        $sql = "SELECT * FROM products WHERE is_active = TRUE ORDER BY name ASC";
    }

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Error fetching products: " . $conn->error);
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Auto Avenue - Products</title>
  <style>
    :root {
      --primary: rgb(0, 0, 0);
      --primary-light: rgb(66, 169, 111);
      --secondary: #ff6b6b;
      --text-color: #333;
      --text-light: #666;
      --bg-color: #f4f6f8;
      --border-color: #e0e0e0;
      --warning: #ff9800;
      --danger: #f44336;
      --info: #2196F3;
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
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      padding: 25px;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid var(--border-color);
    }

    .page-header h2 {
      color: var(--primary);
    }

    .add-product-btn {
      background-color: var(--primary);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-weight: 500;
      transition: background-color 0.3s ease;
    }

    .add-product-btn:hover {
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

    tr:hover {
      background-color: #f1f1f1;
    }

    .action-btn {
      border: none;
      padding: 5px 10px;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.2s ease;
      margin: 2px;
    }

    .add-btn {
      background-color: #4CAF50;
      color: white;
    }

    .add-btn:hover {
      background-color: #45a049;
    }

    .remove-btn {
      background-color: var(--secondary);
      color: white;
    }

    .remove-btn:hover {
      background-color: #e05555;
    }

    .edit-btn {
      background-color: var(--warning);
      color: white;
    }

    .edit-btn:hover {
      background-color: #e68a00;
    }

    .delete-btn {
      background-color: var(--danger);
      color: white;
    }

    .delete-btn:hover {
      background-color: #d32f2f;
    }

    .warranty-btn {
      background-color: var(--info);
      color: white;
    }

    .warranty-btn:hover {
      background-color: #0b7dda;
    }

    .view-btn {
      background-color: var(--primary-light);
      color: white;
    }

    .view-btn:hover {
      background-color: #1e7e34;
    }

    .low-stock {
      color: var(--danger);
      font-weight: bold;
    }

    .action-form {
      display: inline-block;
    }

    .empty-state {
      text-align: center;
      padding: 40px 0;
      color: var(--text-light);
    }

    /* Product Detail Modal Styles */
    .product-modal {
      display: none;
      position: fixed;
      z-index: 1001;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.7);
    }
    
    .product-modal-content {
      background-color: #fefefe;
      margin: 5% auto;
      padding: 25px;
      border-radius: 8px;
      width: 80%;
      max-width: 700px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      animation: modalFadeIn 0.3s ease-out;
    }
    
    .product-detail-row {
      display: flex;
      margin-bottom: 12px;
    }
    
    .product-detail-label {
      font-weight: 600;
      width: 150px;
      color: var(--primary);
    }
    
    .product-detail-value {
      flex: 1;
    }
    
    .vin-code {
      font-family: monospace;
      letter-spacing: 1px;
      background-color: #f5f5f5;
      padding: 3px 6px;
      border-radius: 4px;
    }

    .close-modal {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close-modal:hover {
      color: var(--danger);
    }

    @keyframes modalFadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
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
      
      .page-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .add-product-btn {
        margin-top: 15px;
        width: 100%;
      }

      .product-modal-content {
        width: 95%;
        margin: 10% auto;
      }

      .product-detail-row {
        flex-direction: column;
      }

      .product-detail-label {
        width: 100%;
        margin-bottom: 5px;
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

      <?php if (isset($_SESSION['role'])): ?>
        <div style="background-color: rgba(255, 255, 255, 0.15); padding: 6px 12px; border-radius: 8px; font-size: 0.9rem;">
          Logged in as: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>
        </div>
      <?php endif; ?>
      <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php"class="active">Products</a>
        <a href="reports.php">Reports</a>
        <a href="sales.php">Sales</a>
        <a href="logout.php">Logout</a>
      </nav>
    </div>
  </header>

  <main class="main-content">
    <div class="page-header">
      <h2>Product Inventory</h2>
      <div>
        <button class="add-product-btn" onclick="location.href='add_product.php'">Add New Product</button>
      </div>
    </div>
    
    <?php if (isset($error_message)): ?>
      <div class="error" style="background-color: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
        <?= htmlspecialchars($error_message) ?>
      </div>
    <?php endif; ?>

    <form method="GET" action="products.php" style="margin-bottom: 20px;">
  <input type="text" name="search" placeholder="Search products by name or category" 
         value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
         style="padding: 8px; width: 250px; border-radius: 4px; border: 1px solid #ccc;">
  <button type="submit" class="add-product-btn" style="padding: 8px 12px; margin-left: 5px;">Search</button>
  <?php if (!empty($_GET['search'])): ?>
    <button type="button" onclick="window.location='products.php'" 
            style="padding: 8px 12px; margin-left: 5px; background-color: var(--danger);">
      Clear
    </button>
  <?php endif; ?>
</form>

    
    <?php if ($result && $result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Supplier</th>
            <th>Price (₱)</th>
            <th>Stock</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['product_id'] ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['supplier']) ?></td>
              <td><?= htmlspecialchars($row['category']) ?></td>
              <td><?= number_format($row['price'], 2) ?></td>
              <td class="<?= $row['quantity'] <= $row['min_stock_level'] ? 'low-stock' : '' ?>">
                <?= $row['quantity'] ?>
              </td>
              <td>
                <div class="action-buttons">
                  <form class="action-form" action="products.php" method="POST">
                      <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">
                      <input type="hidden" name="action" value="add">
                      <button class="action-btn add-btn" type="submit">+</button>
                  </form>
                  <form class="action-form" action="products.php" method="POST">
                      <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">
                      <input type="hidden" name="action" value="remove">
                      <button class="action-btn remove-btn" type="submit">−</button>
                  </form>
                  <form class="action-form" action="edit_product.php" method="GET">
                      <input type="hidden" name="id" value="<?= $row['product_id'] ?>">
                      <button class="action-btn edit-btn" type="submit">Edit</button>
                  </form>
                  <form class="action-form" action="delete_product.php" method="POST" onsubmit="return confirm('Are you sure?')">
                      <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">
                      <button class="action-btn delete-btn" type="submit">Delete</button>
                  </form>
                  <button class="action-btn view-btn" 
                          onclick="showProductDetails(
                            <?= htmlspecialchars(json_encode([
                              'name' => $row['name'],
                              'category' => $row['category'],
                              'supplier' => $row['supplier'] ?? 'N/A',
                              'price' => number_format($row['price'], 2),
                              'quantity' => $row['quantity'],
                              'min_stock' => $row['min_stock_level'],
                              'warranty_period' => $row['warranty_period'] ?? 'None',
                              'warranty_terms' => $row['warranty_terms'] ?? 'None specified',
                              'vin_code' => $row['vin_code'] ?? 'Not provided'
                            ]), ENT_QUOTES, 'UTF-8') ?>
                          )">
                    View
                  </button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty-state">
        <p>No products found. Would you like to <a href="add_product.php">add a new product</a>?</p>
      </div>
    <?php endif; ?>
  </main>

  <!-- Product Details Modal -->
  <div id="productModal" class="product-modal">
    <div class="product-modal-content">
      <span class="close-modal" onclick="closeProductModal()">&times;</span>
      <h2 style="color: var(--primary); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Product Details</h2>
      
      <div class="product-detail-row">
        <div class="product-detail-label">Product Name:</div>
        <div class="product-detail-value" id="detail-name"></div>
      </div>
      
      <div class="product-detail-row">
        <div class="product-detail-label">Category:</div>
        <div class="product-detail-value" id="detail-category"></div>
      </div>
      
      <div class="product-detail-row">
        <div class="product-detail-label">Supplier:</div>
        <div class="product-detail-value" id="detail-supplier"></div>
      </div>
      
      <div class="product-detail-row">
        <div class="product-detail-label">Price:</div>
        <div class="product-detail-value">₱<span id="detail-price"></span></div>
      </div>
      
      <div class="product-detail-row">
        <div class="product-detail-label">Current Stock:</div>
        <div class="product-detail-value" id="detail-quantity"></div>
      </div>
      
      <div class="product-detail-row">
        <div class="product-detail-label">Min Stock Level:</div>
        <div class="product-detail-value" id="detail-min-stock"></div>
      </div>
      
      <div class="product-detail-row">
        <div class="product-detail-label">Warranty Period:</div>
        <div class="product-detail-value" id="detail-warranty-period"></div>
      </div>
      
      <div class="product-detail-row">
        <div class="product-detail-label">Warranty Terms:</div>
        <div class="product-detail-value" id="detail-warranty-terms"></div>
      </div>
      
      <div class="product-detail-row">
        <div class="product-detail-label">VIN Code:</div>
        <div class="product-detail-value">
          <span id="detail-vin" class="vin-code"></span>
        </div>
      </div>
    </div>
  </div>

  <script>
    function showProductDetails(product) {
      document.getElementById('detail-name').textContent = product.name;
      document.getElementById('detail-category').textContent = product.category;
      document.getElementById('detail-supplier').textContent = product.supplier;
      document.getElementById('detail-price').textContent = product.price;
      document.getElementById('detail-quantity').textContent = product.quantity;
      document.getElementById('detail-min-stock').textContent = product.min_stock;
      document.getElementById('detail-warranty-period').textContent = product.warranty_period;
      document.getElementById('detail-warranty-terms').textContent = product.warranty_terms;
      document.getElementById('detail-vin').textContent = product.vin_code;
      
      document.getElementById('productModal').style.display = 'block';
    }
    
    function closeProductModal() {
      document.getElementById('productModal').style.display = 'none';
    }
    
    // Close modal when clicking outside content
    window.onclick = function(event) {
      const modal = document.getElementById('productModal');
      if (event.target == modal) {
        closeProductModal();
      }
    }
  </script>

  <?php
  // Close connection only if it exists
  if (isset($conn)) {
      $conn->close();
  }
  ?>
</body>
</html>