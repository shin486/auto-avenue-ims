<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get posted data
$new_product = $_POST['new_product'] ?? null;
$order_quantity = $_POST['order_quantity'] ?? [];

// For demo: just display saved data, but here you should insert into your DB tables

// Example pseudo-saving:
// 1) Insert a new purchase order record, get $purchase_order_id
// 2) Insert each ordered product (new or low stock) with quantity and purchase_order_id

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Purchase Order Confirmed - Auto Avenue IMS</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9; }
    h1 { color: #0b8126; }
    .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 6px; margin-bottom: 20px; }
    a.button { display: inline-block; background-color: #0b8126; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; }
    a.button:hover { background-color: #1d883f; }
  </style>
</head>
<body>

  <h1>Purchase Order Confirmed</h1>

  <div class="success">
    <p>Your purchase order has been successfully recorded.</p>

    <h2>Order Details</h2>

    <?php if ($new_product && !empty(trim($new_product['name'])) && !empty(trim($new_product['quantity'])) && (int)$new_product['quantity'] > 0): ?>
      <p><strong>New Product:</strong></p>
      <ul>
        <li>Name: <?= htmlspecialchars($new_product['name']) ?></li>
        <li>Category: <?= htmlspecialchars($new_product['category']) ?></li>
        <li>Quantity: <?= (int)$new_product['quantity'] ?></li>
      </ul>
    <?php else: ?>
      <p>No new product ordered.</p>
    <?php endif; ?>

    <h3>Low Stock Items Ordered</h3>
    <?php
    $hasOrder = false;
    foreach ($order_quantity as $product_id => $qty) {
        if ((int)$qty > 0) {
            $hasOrder = true;
            break;
        }
    }
    ?>

    <?php if ($hasOrder): ?>
      <ul>
        <?php foreach ($order_quantity as $product_id => $qty): 
            $qty = (int)$qty;
            if ($qty > 0):
        ?>
          <li>Product ID <?= htmlspecialchars($product_id) ?> — Quantity: <?= $qty ?></li>
        <?php 
            endif;
          endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No low stock items ordered.</p>
    <?php endif; ?>
  </div>

  <a href="purchase_order.php" class="button">Make Another Purchase Order</a>

</body>
</html>
