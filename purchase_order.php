<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$low_stock_query = "SELECT p.product_id, p.name, p.quantity, p.min_stock_level, p.category, s.supplier_name
                    FROM products p
                    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                    WHERE p.quantity < p.min_stock_level";
$low_stock_result = $conn->query($low_stock_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'submit') {
    $user_id = $_SESSION['user_id'];
    $notes = trim($_POST['notes']) ?? '';

    // 1. Insert into purchase_orders
    $stmt = $conn->prepare("INSERT INTO purchase_orders (user_id, notes) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $notes);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // 2. Insert low stock ordered items
    if (!empty($_POST['low_stock_order'])) {
        foreach ($_POST['low_stock_order'] as $product_id => $quantity) {
            if ((int)$quantity > 0) {
                // Fetch product details for saving
                $prod_stmt = $conn->prepare("SELECT name, category, supplier_id FROM products WHERE product_id = ?");
                $prod_stmt->bind_param("i", $product_id);
                $prod_stmt->execute();
                $prod_result = $prod_stmt->get_result();
                if ($prod = $prod_result->fetch_assoc()) {
                    // Get supplier name if available
                    $supplier_name = '';
                    if (!empty($prod['supplier_id'])) {
                        $sup_stmt = $conn->prepare("SELECT supplier_name FROM suppliers WHERE supplier_id = ?");
                        $sup_stmt->bind_param("i", $prod['supplier_id']);
                        $sup_stmt->execute();
                        $sup_result = $sup_stmt->get_result();
                        if ($sup = $sup_result->fetch_assoc()) {
                            $supplier_name = $sup['supplier_name'];
                        }
                    }

                    $item_stmt = $conn->prepare("INSERT INTO purchase_order_items (order_id, product_name, category, quantity, supplier_name) VALUES (?, ?, ?, ?, ?)");
                    $item_stmt->bind_param("issis", $order_id, $prod['name'], $prod['category'], $quantity, $supplier_name);
                    $item_stmt->execute();
                }
            }
        }
    }

    // 3. Insert new products
    if (!empty($_POST['new_products'])) {
        foreach ($_POST['new_products'] as $new_product) {
            $name = trim($new_product['name']);
            $category = trim($new_product['category']);
            $quantity = (int)$new_product['quantity'];
            $supplier_name = trim($new_product['supplier_name']);

            if ($name && $category && $quantity > 0) {
                $item_stmt = $conn->prepare("INSERT INTO purchase_order_items (order_id, product_name, category, quantity, supplier_name) VALUES (?, ?, ?, ?, ?)");
                $item_stmt->bind_param("issis", $order_id, $name, $category, $quantity, $supplier_name);
                $item_stmt->execute();
            }
        }
    }

    // Optional: redirect or show confirmation
    header("Location: purchase_order_summary.php?action=view&success=1");
    exit();
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
  </style>
</head>
<body>
  <div class="container">
    <h1><i class="fas fa-file-invoice-dollar"></i> Purchase Order Form</h1>

    <form method="POST" action="purchase_order.php">
      
      <!-- New Product Order Section -->
      <div class="form-section">
        <h2><i class="fas fa-plus-circle"></i> Order New Products</h2>
        <div id="new-products-section">
          <div class="form-row">
            <input type="text" name="new_products[0][name]" placeholder="Product Name" required>
            <input type="text" name="new_products[0][category]" placeholder="Category" required>
            <input type="number" name="new_products[0][quantity]" placeholder="Quantity" min="1" required>
            <input type="text" name="new_products[0][supplier_name]" placeholder="Supplier Name (optional)">
          </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="addNewProduct()">
          <i class="fas fa-plus"></i> Add Product
        </button>
      </div>

     <!-- Low Stock Items Section -->
<div class="form-section">
  <h2><i class="fas fa-exclamation-triangle"></i> Low Stock Items</h2>
  <?php if ($low_stock_result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Product Name</th>
          <th>Current Stock</th>
          <th>Min Level</th>
          <th>Category</th>
          <th>Supplier</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $low_stock_result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= $row['min_stock_level'] ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><?= htmlspecialchars($row['supplier_name']) ?></td>
            <td>
              <button type="button" class="btn btn-primary" onclick="showQtyField(<?= $row['product_id'] ?>)">Order Item</button>
              <div id="qty-field-<?= $row['product_id'] ?>" style="display:none; margin-top:10px;">
                <input type="number" 
                       name="low_stock_order[<?= $row['product_id'] ?>]" 
                       min="1" 
                       placeholder="Qty"
                       value="<?= max(1, $row['min_stock_level'] - $row['quantity']) ?>"
                       style="margin-top:5px; width:100px;"
                       disabled>
                       
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="no-items">
      <p><i class="fas fa-check-circle"></i> No low stock items to order.</p>
    </div>
  <?php endif; ?>
</div>


      <!-- Additional Order Notes -->
      <div class="form-section">
        <h2><i class="fas fa-edit"></i> Additional Information</h2>
        <div class="form-row">
          <div style="grid-column: span 4;">
            <label for="notes">Order Notes / Instructions:</label><br />
            <textarea id="notes" name="notes" rows="3" placeholder="Add notes or instructions here..."></textarea>
          </div>
        </div>
      </div>

      <button type="submit" name="action" value="submit" class="submit-btn">
        <i class="fas fa-paper-plane"></i> Submit Purchase Order
      </button>
      <button type="button" class="submit-btn" onclick="window.location.href='dashboard.php'">
  <i class="fas fa-arrow-left"></i> Go Back to Dashboard
</button>

<button type="button" class="submit-btn" onclick="window.location.href='purchase_order_summary.php'">
  <i class="fas fa-arrow-right"></i> View Order
</button>

    </form>
  </div>

<script>
  let productCount = 1;

  function addNewProduct() {
    const section = document.getElementById('new-products-section');
    const div = document.createElement('div');
    div.className = 'form-row';
    div.innerHTML = `
      <input type="text" name="new_products[${productCount}][name]" placeholder="Product Name" required>
      <input type="text" name="new_products[${productCount}][category]" placeholder="Category" required>
      <input type="number" name="new_products[${productCount}][quantity]" placeholder="Quantity" min="1" required>
      <div style="display: flex; gap: 10px; align-items: center;">
        <input type="text" name="new_products[${productCount}][supplier_name]" placeholder="Supplier Name (optional)">
        <button type="button" class="remove-btn" onclick="this.parentElement.parentElement.remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;
    section.appendChild(div);
    productCount++;
  }

 function showQtyField(productId) {
  const qtyDiv = document.getElementById(`qty-field-${productId}`);
  if (qtyDiv) {
    qtyDiv.style.display = 'block';

    const input = qtyDiv.querySelector('input[type="number"]');
    if (input) {
      input.disabled = false;
    }
  }

  // Hide the Order Item button itself
  const btn = qtyDiv.previousElementSibling;
  if (btn) {
    btn.style.display = 'none';
  }
}

</script>

</body>
</html>
