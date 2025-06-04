<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

function generatePONumber($conn) {
    $prefix = 'PO-' . date('Ymd') . '-';
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_orders WHERE order_number LIKE ?");
    $likePrefix = $prefix . '%';
    $stmt->bind_param("s", $likePrefix);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] ?? 0;
    return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

// Get distinct suppliers (categories) from products table
$suppliers = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit') {
    $conn->begin_transaction();

    try {
        $user_id = $_SESSION['user_id'];
        $notes = trim($_POST['notes'] ?? '');
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $payment_method = trim($_POST['payment_method'] ?? '');
        $payment_terms = trim($_POST['payment_terms'] ?? '');
        $expected_arrival = trim($_POST['expected_arrival'] ?? '');

        if (!$supplier_name || !$payment_method || !$expected_arrival) {
            throw new Exception("Please fill in all required fields.");
        }

        // Insert into purchase_orders, storing supplier as category (supplier_name)
        $order_number = generatePONumber($conn);
        $stmt = $conn->prepare("INSERT INTO purchase_orders 
                               (order_number, user_id, category, payment_method, payment_terms, expected_arrival, notes) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssss", $order_number, $user_id, $supplier_name, $payment_method, $payment_terms, $expected_arrival, $notes);
        $stmt->execute();
        $order_id = $stmt->insert_id;

        // Insert order items
        if (!empty($_POST['products'])) {
            foreach ($_POST['products'] as $product) {
                $product_id = (int)($product['product_id'] ?? 0);
                $quantity = (int)($product['quantity'] ?? 0);

                if ($product_id > 0 && $quantity > 0) {
                    // Get product details
                    $prod_stmt = $conn->prepare("SELECT name, category FROM products WHERE product_id = ?");
                    $prod_stmt->bind_param("i", $product_id);
                    $prod_stmt->execute();
                    $prod_result = $prod_stmt->get_result();

                    if ($prod = $prod_result->fetch_assoc()) {
                        $item_stmt = $conn->prepare("INSERT INTO purchase_order_items 
                                                   (order_id, product_id, product_name, category, quantity) 
                                                   VALUES (?, ?, ?, ?, ?)");
                        $item_stmt->bind_param("iissi", $order_id, $product_id, $prod['name'], $prod['category'], $quantity);
                        $item_stmt->execute();
                        $hasValidItems = false;
        if (!empty($_POST['products'])) {
            foreach ($_POST['products'] as $product) {
                $quantity = (int)($product['quantity'] ?? 0);
                if ($quantity > 0) {
                    $hasValidItems = true;
                    break;
                }
            }
        }
        
        if (!$hasValidItems) {
            throw new Exception("Please order at least one item with quantity greater than 0");
        }

                    }
                }
            }
        }

        // Log transaction
        $trans_stmt = $conn->prepare("INSERT INTO transactions 
    (action_type, reference_id, reference_table, performed_by, timestamp, remarks) 
    VALUES (?, ?, ?, ?, NOW(), ?)");
    
$trans_type = 'order_created';
$reference_id = $order_id;
$reference_table = 'purchase_orders';
$performed_by = $user_id;
$remarks = json_encode([
    'order_number' => $order_number,
    'items_count' => count($_POST['products'] ?? [])
]);

$trans_stmt->bind_param("sisss", $trans_type, $reference_id, $reference_table, $performed_by, $remarks);
$trans_stmt->execute();


        $conn->commit();

        header("Location: purchase_order_summary.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Error processing order: " . $e->getMessage());
    }
}

// Get products for selected supplier (category)
$products = [];
if (isset($_GET['supplier_name']) && $_GET['supplier_name'] !== '') {
    $supplier_name = $_GET['supplier_name'];
    $products_query = $conn->prepare("SELECT product_id, name, category, quantity, min_stock_level 
                                     FROM products 
                                     WHERE category = ?");
    $products_query->bind_param("s", $supplier_name);
    $products_query->execute();
    $products = $products_query->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $supplier_name = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Purchase Order - Auto Avenue IMS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    /* Your CSS here (same as previous) */
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
    /* Form layout and alignment */
    .form-group {
      margin-bottom: 15px;
      display: flex;
      flex-direction: column;
    }
    
    .form-group label {
      margin-bottom: 8px;
      font-weight: 500;
      color: #555;
    }
    
    .form-group select,
    .form-group input {
      height: 38px;
      padding: 0 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      width: 100%;
    }
    
    .form-group small {
      display: block;
      margin-top: 5px;
      color: #666;
      font-size: 0.85rem;
      line-height: 1.4;
    }
    
    .form-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      align-items: start;
    }
    
    .supplier-section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    
    /* Ensure consistent heights for inputs */
    select, input[type="date"], input[type="text"] {
      box-sizing: border-box;
      height: 38px !important;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1><i class="fas fa-file-invoice-dollar"></i> Create Purchase Order</h1>

    <form method="POST" action="purchase_order.php">
      <input type="hidden" name="action" value="submit" />
      
      <!-- Supplier Selection -->
      <div class="supplier-section">
        <h2><i class="fas fa-truck"></i> Supplier Details</h2>
        <div class="form-row">
          <div class="form-group">
            <label for="supplier-select"><strong>Supplier:</strong></label>
            <select name="supplier_name" id="supplier-select" required onchange="window.location.href='purchase_order.php?supplier_name='+encodeURIComponent(this.value)">
              <option value="">-- Select Supplier --</option>
              <?php while ($row = $suppliers->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($row['category']) ?>"
                  <?= ($supplier_name === $row['category']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($row['category']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="payment_method"><strong>Payment Method:</strong></label>
            <select name="payment_method" id="payment_method" required onchange="togglePaymentTerms()">
              <option value="">-- Select Payment Method --</option>
              <option value="Cash on Delivery">Cash on Delivery</option>
              <option value="Installment">Installment</option>
            </select>
          </div>

          <div class="form-group" id="payment_terms_container" style="display: none;">
            <label for="payment_terms"><strong>Payment Terms:</strong></label>
            <input type="text" name="payment_terms" id="payment_terms" placeholder="e.g., 3 months" />
          </div>

          <div class="form-group">
            <label for="expected_arrival"><strong>Expected Arrival:</strong></label>
            <input type="date" name="expected_arrival" id="expected_arrival" required 
                   min="<?= date('Y-m-d') ?>" 
                   value="<?= date('Y-m-d') ?>" />
            <small>
              <i class="fas fa-info-circle"></i> Date must be today or in the future
            </small>
          </div>
        </div>
      </div>

      <!-- Products Table -->
      <?php if (!empty($products)): ?>
        <div class="form-section">
          <h2><i class="fas fa-boxes"></i> Products from <?= htmlspecialchars($supplier_name) ?></h2>
          <p class="instruction-text">Enter quantities for the products you wish to order. Minimum stock levels are shown for reference.</p>
          
          <table>
            <thead>
              <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Supplier</th>
                <th>Current Stock</th>
                <th>Min Stock Level</th>
                <th>Order Quantity</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $product): ?>
                <tr class="<?= ((int)$product['quantity'] <= (int)$product['min_stock_level']) ? 'low-stock' : '' ?>">
                  <td><?= htmlspecialchars($product['product_id']) ?></td>
                  <td><?= htmlspecialchars($product['name']) ?></td>
                  <td><?= htmlspecialchars($product['category']) ?></td>
                  <td>
                    <?= htmlspecialchars($product['quantity']) ?>
                    <?php if ((int)$product['quantity'] <= (int)$product['min_stock_level']): ?>
                      <span class="stock-warning" title="Stock below minimum level">
                        <i class="fas fa-exclamation-triangle"></i>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($product['min_stock_level']) ?></td>
                  <td>
                    <input type="number" min="0" name="products[<?= (int)$product['product_id'] ?>][quantity]" value="<?= ((int)$product['quantity'] <= (int)$product['min_stock_level']) ? max(1, (int)$product['min_stock_level'] - (int)$product['quantity']) : 0 ?>" />
                    <input type="hidden" name="products[<?= (int)$product['product_id'] ?>][product_id]" value="<?= (int)$product['product_id'] ?>" />
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="no-items">Select a supplier to show their products here.</p>
      <?php endif; ?>

      <div class="form-section">
        <h2><i class="fas fa-sticky-note"></i> Notes</h2>
        <textarea name="notes" rows="3" placeholder="Additional notes or instructions"></textarea>
      </div>

      <div class="form-actions">
        <button type="submit" class="submit-btn">
          <i class="fas fa-paper-plane"></i> Submit Purchase Order
        </button>

        <button type="button" onclick="window.location.href='dashboard.php'" class="submit-btn secondary-btn">
          <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
      </div>
    </form>
  </div>

  <style>
    /* Additional styles */
    .form-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 15px;
    }
    
    .form-row > div {
      display: flex;
      flex-direction: column;
      margin-bottom: 10px;
    }
    
    label {
      margin-bottom: 8px;
      color: #555;
    }
    
    input[type="date"] {
      height: 38px;
      padding: 0 10px;
    }
    
    .instruction-text {
      color: #666;
      margin-bottom: 15px;
      font-style: italic;
    }
    
    .low-stock {
      background-color: #fff8e1;
    }
    
    .stock-warning {
      color: #f57c00;
      margin-left: 5px;
    }
    
    .form-actions {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }
    
    .secondary-btn {
      background-color: #6c757d;
    }
    
    .secondary-btn:hover {
      background-color: #5a6268;
    }
  </style>

  <script>
  function togglePaymentTerms() {
    const method = document.getElementById('payment_method').value;
    const termsContainer = document.getElementById('payment_terms_container');
    const terms = document.getElementById('payment_terms');
    
    if (method === 'Installment') {
      termsContainer.style.display = 'flex';
      terms.required = true;
    } else {
      termsContainer.style.display = 'none';
      terms.required = false;
      terms.value = '';
    }
  }

  // Validate expected arrival date
  function validateExpectedArrival() {
    const expectedArrival = document.getElementById('expected_arrival');
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time to start of day
    
    const selectedDate = new Date(expectedArrival.value);
    selectedDate.setHours(0, 0, 0, 0); // Reset time to start of day
    
    if (selectedDate < today) {
      expectedArrival.setCustomValidity('Expected arrival date cannot be in the past');
      return false;
    } else {
      expectedArrival.setCustomValidity('');
      return true;
    }
  }

  // Trigger once on load in case of browser remembering form values
  window.addEventListener('DOMContentLoaded', function() {
    togglePaymentTerms();
    
    // Set current date as default
    const expectedArrival = document.getElementById('expected_arrival');
    if (!expectedArrival.value) {
      const today = new Date();
      expectedArrival.valueAsDate = today;
    }
    
    // Add validation event listeners
    expectedArrival.addEventListener('change', validateExpectedArrival);
    validateExpectedArrival(); // Validate initial value
  });

  // Form validation
  document.querySelector('form').addEventListener('submit', function(e) {
    // Validate expected arrival date
    if (!validateExpectedArrival()) {
      e.preventDefault();
      alert('Expected arrival date cannot be in the past');
      return false;
    }
    
    let hasValidQuantity = false;
    const quantityInputs = document.querySelectorAll('input[name^="products["][name$="[quantity]"]');
    
    // Check if at least one product has quantity > 0
    quantityInputs.forEach(input => {
      if (parseInt(input.value) > 0) {
        hasValidQuantity = true;
      }
    });
    
    if (!hasValidQuantity) {
      e.preventDefault();
      alert('Please enter a quantity greater than 0 for at least one product');
      return false;
    }
    
    // Additional check for individual products
    let invalidProducts = [];
    quantityInputs.forEach(input => {
      if (parseInt(input.value) < 0) {
        invalidProducts.push(input.closest('tr').querySelector('td:first-child').textContent);
      }
    });
    
    if (invalidProducts.length > 0) {
      e.preventDefault();
      alert('Invalid quantities for:\n' + invalidProducts.join('\n') + '\n\nQuantity cannot be negative');
      return false;
    }
  });
  </script>

</body>
</html>
