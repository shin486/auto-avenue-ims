<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch low stock items
$low_stock_query = "SELECT * FROM products WHERE quantity < min_stock_level";
$low_stock_result = $conn->query($low_stock_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Purchase Order - Auto Avenue IMS</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6f8;
      padding: 20px;
    }
    h1 {
      color: #0b8126;
      margin-bottom: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    th, td {
      padding: 12px 15px;
      border-bottom: 1px solid #e0e0e0;
      text-align: left;
    }
    th {
      background-color: #0b8126;
      color: white;
    }
    .submit-btn {
      margin-top: 20px;
      background-color: #0b8126;
      color: white;
      border: none;
      padding: 10px 18px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
    }
    .submit-btn:hover {
      background-color: #1d883f;
    }
    .new-product-container {
      background: white;
      padding: 15px;
      border-radius: 8px;
      margin-top: 30px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .new-product-item {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 15px;
      margin-bottom: 10px;
    }
    .new-product-item input {
      padding: 8px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    .add-btn {
      background-color: #007bff;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 10px;
    }
    .add-btn:hover {
      background-color: #0056b3;
    }
  </style>
</head>
<body>

  <h1>Purchase Order Form</h1>

  <form method="POST" action="purchase_order_summary.php">
    
    <!-- New Product Order -->
    <div class="new-product-container">
      <h2>Order New Products</h2>
      <div id="new-products-section">
        <div class="new-product-item">
          <input type="text" name="new_products[0][name]" placeholder="Product Name" required>
          <input type="text" name="new_products[0][category]" placeholder="Category" required>
          <input type="number" name="new_products[0][quantity]" placeholder="Quantity" min="1" required>
          <input type="text" name="new_products[0][supplier]" placeholder="Supplier" required>
        </div>
      </div>
      <button type="button" class="add-btn" onclick="addNewProduct()">+ Add More</button>
    </div>

    <!-- Low Stock Items -->
    <?php if ($low_stock_result->num_rows > 0): ?>
      <h2 style="margin-top: 40px;">Low Stock Items</h2>
      <table>
        <thead>
          <tr>
            <th>Product Name</th>
            <th>Current Stock</th>
            <th>Min Level</th>
            <th>Category</th>
            <th>Order Quantity</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $low_stock_result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= $row['quantity'] ?></td>
              <td><?= $row['min_stock_level'] ?></td>
              <td><?= htmlspecialchars($row['category']) ?></td>
              <td>
                <input type="number" name="low_stock_order[<?= htmlspecialchars($row['name']) ?>]" min="1" placeholder="Qty">
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No low stock items to order.</p>
    <?php endif; ?>

    <button type="submit" class="submit-btn">Submit Purchase Order</button>
  </form>

  <script>
    let productCount = 1;
    function addNewProduct() {
      const section = document.getElementById('new-products-section');
      const div = document.createElement('div');
      div.className = 'new-product-item';
      div.innerHTML = `
        <input type="text" name="new_products[${productCount}][name]" placeholder="Product Name" required>
        <input type="text" name="new_products[${productCount}][category]" placeholder="Category" required>
        <input type="number" name="new_products[${productCount}][quantity]" placeholder="Quantity" min="1" required>
        <input type="text" name="new_products[${productCount}][supplier]" placeholder="Supplier" required>
      `;
      section.appendChild(div);
      productCount++;
    }
  </script>

</body>
</html>
