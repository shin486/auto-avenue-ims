<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Generate a unique PO number
function generatePONumber($conn) {
    $prefix = 'PO-' . date('Ymd') . '-';
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_orders WHERE order_number LIKE ?");
    $likePrefix = $prefix . '%';
    $stmt->bind_param("s", $likePrefix);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

// Get all suppliers
$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'submit') {
    $conn->begin_transaction();
    
    try {
        $user_id = $_SESSION['user_id'];
        $notes = trim($_POST['notes']) ?? '';
        $supplier_id = (int)$_POST['supplier_id'];
        $payment_method = trim($_POST['payment_method']);
        $payment_terms = trim($_POST['payment_terms']);
        $expected_arrival = trim($_POST['expected_arrival']);
        
        // 1. Insert into purchase_orders
        $order_number = generatePONumber($conn);
        $stmt = $conn->prepare("INSERT INTO purchase_orders 
                               (order_number, user_id, supplier_id, payment_method, payment_terms, expected_arrival, notes) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siissss", $order_number, $user_id, $supplier_id, $payment_method, $payment_terms, $expected_arrival, $notes);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        
        // 2. Insert order items
        if (!empty($_POST['products'])) {
            foreach ($_POST['products'] as $product) {
                $product_id = (int)$product['product_id'];
                $quantity = (int)$product['quantity'];
                
                if ($quantity > 0) {
                    // Get product details
                    $prod_stmt = $conn->prepare("SELECT product_code, name, category FROM products WHERE product_id = ?");
                    $prod_stmt->bind_param("i", $product_id);
                    $prod_stmt->execute();
                    $prod_result = $prod_stmt->get_result();
                    
                    if ($prod = $prod_result->fetch_assoc()) {
                        $item_stmt = $conn->prepare("INSERT INTO purchase_order_items 
                                                   (order_id, product_id, product_code, product_name, category, quantity, supplier_id) 
                                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $item_stmt->bind_param("iisssii", $order_id, $product_id, $prod['product_code'], $prod['name'], $prod['category'], $quantity, $supplier_id);
                        $item_stmt->execute();
                    }
                }
            }
        }
        
        // 3. Log transaction
        $trans_stmt = $conn->prepare("INSERT INTO transactions 
                                    (transaction_number, order_id, type, user_id, details) 
                                    VALUES (?, ?, ?, ?, ?)");
        $trans_number = 'TR-' . date('YmdHis') . '-' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
        $trans_type = 'order_created';
        $details = json_encode([
            'order_number' => $order_number,
            'items_count' => count($_POST['products'] ?? [])
        ]);
        $trans_stmt->bind_param("sisis", $trans_number, $order_id, $trans_type, $user_id, $details);
        $trans_stmt->execute();
        
        $conn->commit();
        
        header("Location: purchase_order_summary.php?order_id=" . $order_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        die("Error processing order: " . $e->getMessage());
    }
}

// Get products for selected supplier
$products = [];
if (isset($_GET['supplier_id'])) {
    $supplier_id = (int)$_GET['supplier_id'];
    $products_query = $conn->prepare("SELECT product_id, product_code, name, category, quantity, min_stock_level 
                                     FROM products 
                                     WHERE supplier_id = ?");
    $products_query->bind_param("i", $supplier_id);
    $products_query->execute();
    $products = $products_query->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Purchase Order - Auto Avenue IMS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Your existing CSS here */
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6f8;
      padding: 20px;
      line-height: 1.6;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
    }
    h1 {
      color: #0b8126;
      margin-bottom: 20px;
    }
    h2 {
      color: #333;
      margin: 25px 0 15px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      margin-bottom: 20px;
    }
    th, td {
      padding: 12px 15px;
      border-bottom: 1px solid #e0e0e0;
      text-align: left;
    }
    th {
      background-color: #0b8126;
      color: white;
      position: sticky;
      top: 0;
    }
    tr:hover {
      background-color: #f5f5f5;
    }
    .submit-btn {
      margin-top: 20px;
      background-color: #0b8126;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      transition: background-color 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .submit-btn:hover {
      background-color: #1d883f;
    }
    .form-section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin: 20px 0;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .form-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 15px;
      margin-bottom: 15px;
      align-items: center;
    }
    input, select, textarea {
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
      width: 100%;
      box-sizing: border-box;
    }
    .btn {
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .btn-primary {
      background-color: #007bff;
    }
    .btn-primary:hover {
      background-color: #0056b3;
    }
    .btn-danger {
      background-color: #dc3545;
    }
    .btn-danger:hover {
      background-color: #bb2d3b;
    }
    .no-items {
      background: white;
      padding: 15px;
      border-radius: 8px;
      text-align: center;
      color: #666;
    }
    .remove-btn {
      background: none;
      border: none;
      color: #dc3545;
      cursor: pointer;
      font-size: 1.2rem;
    }
  
    .supplier-section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .product-row {
      display: grid;
      grid-template-columns: 100px 1fr 1fr 100px 100px 80px;
      gap: 15px;
      align-items: center;
      margin-bottom: 10px;
      padding: 10px;
      background: #f9f9f9;
      border-radius: 5px;
    }
    .payment-terms {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 15px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1><i class="fas fa-file-invoice-dollar"></i> Create Purchase Order</h1>

    <form method="POST" action="purchase_order.php">
      <input type="hidden" name="action" value="submit">
      
      <!-- Supplier Selection -->
      <div class="supplier-section">
        <h2><i class="fas fa-truck"></i> Supplier Details</h2>
        <div class="form-row">
          <select name="supplier_id" id="supplier-select" required onchange="window.location.href='purchase_order.php?supplier_id='+this.value">
            <option value="">Select Supplier</option>
            <?php while ($supplier = $suppliers->fetch_assoc()): ?>
              <option value="<?= $supplier['supplier_id'] ?>" <?= isset($_GET['supplier_id']) && $_GET['supplier_id'] == $supplier['supplier_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($supplier['supplier_name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <!-- Payment Terms -->
        <div class="payment-terms">
          <div>
            <label>Payment Method</label>
            <select name="payment_method" required>
              <option value="cash">Cash</option>
              <option value="15_days_net">15 Days Net</option>
              <option value="30_days_net">30 Days Net</option>
              <option value="60_days_net">60 Days Net</option>
              <option value="credit_card">Credit Card</option>
            </select>
          </div>
          <div>
            <label>Payment Terms</label>
            <select name="payment_terms" required>
              <option value="immediate">Immediate</option>
              <option value="15_days">15 Days</option>
              <option value="30_days">30 Days</option>
              <option value="60_days">60 Days</option>
            </select>
          </div>
          <div>
            <label>Expected Arrival</label>
            <input type="date" name="expected_arrival" required min="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>

      <!-- Products List -->
      <div class="form-section">
        <h2><i class="fas fa-boxes"></i> Products</h2>
        <div id="products-list">
          <?php if (!empty($products)): ?>
            <?php foreach ($products as $index => $product): ?>
              <div class="product-row">
                <input type="hidden" name="products[<?= $index ?>][product_id]" value="<?= $product['product_id'] ?>">
                <span><?= htmlspecialchars($product['product_code']) ?></span>
                <span><?= htmlspecialchars($product['name']) ?></span>
                <span><?= htmlspecialchars($product['category']) ?></span>
                <span>Stock: <?= $product['quantity'] ?> / <?= $product['min_stock_level'] ?></span>
                <input type="number" name="products[<?= $index ?>][quantity]" 
                       min="1" value="<?= max(1, $product['min_stock_level'] - $product['quantity']) ?>">
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="no-items">Please select a supplier to view products</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Order Notes -->
      <div class="form-section">
        <h2><i class="fas fa-edit"></i> Additional Information</h2>
        <textarea name="notes" rows="3" placeholder="Order notes or special instructions..."></textarea>
      </div>

      <div class="form-actions">
        <button type="submit" class="submit-btn">
          <i class="fas fa-paper-plane"></i> Submit Purchase Order
        </button>
        <button type="button" class="submit-btn" onclick="window.location.href='dashboard.php'">
          <i class="fas fa-arrow-left"></i> Cancel
        </button>
      </div>
    </form>
  </div>

  <script>
    // Auto-set expected arrival date to 3 days from now
    document.querySelector('input[name="expected_arrival"]').valueAsDate = new Date(Date.now() + 3 * 24 * 60 * 60 * 1000);
  </script>
</body>
</html>