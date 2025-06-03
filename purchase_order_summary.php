<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle item removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item_id'])) {
    $remove_item_id = (int)$_POST['remove_item_id'];
    $stmt = $conn->prepare("DELETE FROM purchase_order_items WHERE item_id = ?");
    $stmt->bind_param("i", $remove_item_id);
    $stmt->execute();
    $conn->query("DELETE FROM purchase_orders WHERE order_id NOT IN (SELECT DISTINCT order_id FROM purchase_order_items)");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle reject order request (manager only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_order_id']) && $_SESSION['role'] === 'manager') {
    $reject_order_id = (int)$_POST['reject_order_id'];
    $stmt = $conn->prepare("UPDATE purchase_orders SET order_status = 'rejected' WHERE order_id = ?");
    $stmt->bind_param("i", $reject_order_id);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle confirmation (manager only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order_id']) && $_SESSION['role'] === 'manager') {
    $confirm_order_id = (int)$_POST['confirm_order_id'];
    $stmt = $conn->prepare("UPDATE purchase_orders SET order_status = 'confirmed' WHERE order_id = ?");
    $stmt->bind_param("i", $confirm_order_id);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle full order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
    $delete_order_id = (int)$_POST['delete_order_id'];

    if ($_SESSION['role'] === 'manager') {
        $stmt = $conn->prepare("DELETE FROM purchase_order_items WHERE order_id = ?");
        $stmt->bind_param("i", $delete_order_id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM purchase_orders WHERE order_id = ?");
        $stmt->bind_param("i", $delete_order_id);
        $stmt->execute();
    } elseif ($_SESSION['role'] === 'staff') {
        $stmt = $conn->prepare("DELETE poi FROM purchase_order_items poi JOIN purchase_orders po ON poi.order_id = po.order_id WHERE po.order_id = ? AND po.user_id = ?");
        $stmt->bind_param("ii", $delete_order_id, $_SESSION['user_id']);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM purchase_orders WHERE order_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $delete_order_id, $_SESSION['user_id']);
        $stmt->execute();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch orders
// Fetch orders
if ($_SESSION['role'] === 'manager' || $_SESSION['role'] === 'staff') {
    $order_sql = "SELECT o.*, u.username FROM purchase_orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC";
    $order_stmt = $conn->prepare($order_sql);
} else {
    // Other roles if any
    $order_sql = "SELECT * FROM purchase_orders WHERE user_id = ? ORDER BY order_date DESC";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("i", $_SESSION['user_id']);
}
$order_stmt->execute();
$order_result = $order_stmt->get_result();


$orders = [];
while ($order = $order_result->fetch_assoc()) {
    $order_id = $order['order_id'];
    $orders[$order_id] = [
        'user_id' => $order['user_id'],
        'username' => $order['username'] ?? '',
        'notes' => $order['notes'],
        'order_date' => $order['order_date'],
        'order_status' => $order['order_status'] ?? 'pending',
        'items' => []
    ];

    $item_stmt = $conn->prepare("SELECT * FROM purchase_order_items WHERE order_id = ?");
    $item_stmt->bind_param("i", $order_id);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    while ($item = $item_result->fetch_assoc()) {
        $orders[$order_id]['items'][] = $item;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Purchase Order Summary - Auto Avenue IMS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f8; padding: 20px; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    h1 { color: #0b8126; margin-bottom: 20px; }
    h2 { color: #0b8126; margin-top: 30px; border-bottom: 2px solid #0b8126; padding-bottom: 5px; }
    .order-header { background-color: #eef7ee; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .order-date { font-weight: bold; color: #555; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
    th, td { border: 1px solid #ddd; padding: 10px 15px; text-align: left; }
    th { background-color: #0b8126; color: white; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .no-data { color: #666; font-style: italic; margin-bottom: 25px; }
    .notes-box { background: #eef7ee; border-left: 5px solid #0b8126; padding: 15px; margin-bottom: 25px; white-space: pre-wrap; }
    .btn, .btn-remove {
      padding: 12px 25px;
      font-size: 1rem;
      border-radius: 6px;
      cursor: pointer;
      display: inline-block;
      margin-right: 10px;
      transition: background-color 0.3s;
      border: none;
      color: white;
    }
    .btn {
      background-color: #0b8126;
    }
    .btn:hover:enabled {
      background-color: #1d883f;
    }
    .btn-remove {
      background-color: #d9534f;
    }
    .btn-remove:hover {
      background-color: #c9302c;
    }
    .btn:disabled {
      background-color: #999;
      cursor: not-allowed;
    }
    .action-buttons { margin-top: 20px; display: flex; gap: 10px; }
    .user-badge { background-color: #e1f5fe; padding: 3px 8px; border-radius: 15px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 5px; }
  </style>
</head>
<body>
  <div class="container">
    <h1><i class="fas fa-file-invoice-dollar"></i> Purchase Order Summary</h1>

    <?php foreach ($orders as $order_id => $order): ?>
    <div class="order-header">
      <?php if ($_SESSION['role'] === 'manager'): ?>
        <span class="user-badge"><i class="fas fa-user"></i> <?= htmlspecialchars($order['username']) ?></span>
      <?php endif; ?>
      <p class="order-date"><i class="far fa-calendar-alt"></i> <?= date('F j, Y H:i', strtotime($order['order_date'])) ?></p>
    </div>

    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>Quantity</th>
          <th>Supplier</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($order['items'] as $item): ?>
        <tr>
          <td><?= htmlspecialchars($item['product_name']) ?></td>
          <td><?= htmlspecialchars($item['category']) ?></td>
          <td><?= (int)$item['quantity'] ?></td>
          <td><?= htmlspecialchars($item['supplier_name'] ?: '-') ?></td>
          <td>
            <?php if ($_SESSION['role'] === 'manager' || $_SESSION['user_id'] == $order['user_id']): ?>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="remove_item_id" value="<?= $item['item_id'] ?>">
              <button type="submit" class="btn-remove" onclick="return confirm('Remove this item?')">
                <i class="fas fa-trash-alt"></i>
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if (!empty($order['notes'])): ?>
    <div class="notes-box">
      <h3><i class="fas fa-edit"></i> Notes</h3>
      <p><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
    </div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] === 'manager'): ?>
      <?php if ($order['order_status'] === 'pending'): ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="confirm_order_id" value="<?= $order_id ?>">
          <button type="submit" class="btn"><i class="fas fa-check"></i> Confirm</button>
        </form>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="reject_order_id" value="<?= $order_id ?>">
          <button type="submit" class="btn-remove" onclick="return confirm('Reject this order?')">
            <i class="fas fa-times"></i> Reject
          </button>
        </form>
      <?php else: ?>
        <span class="user-badge">Status: <?= ucfirst(htmlspecialchars($order['order_status'])) ?></span>
      <?php endif; ?>
    <?php else: ?>
      <span class="user-badge">Status: <?= ucfirst(htmlspecialchars($order['order_status'])) ?></span>
    <?php endif; ?>

    <?php
      $can_delete = $_SESSION['role'] === 'manager' ||
                   ($_SESSION['role'] === 'staff' && $_SESSION['user_id'] == $order['user_id']);
      if ($can_delete && in_array($order['order_status'], ['confirmed', 'rejected'])):
    ?>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="delete_order_id" value="<?= $order_id ?>">
        <button type="submit" class="btn-remove" onclick="return confirm('Are you sure you want to delete this order?')">
          <i class="fas fa-trash"></i> Delete Order
        </button>
      </form>
    <?php endif; ?>

    <hr style="margin: 40px 0;">
    <?php endforeach; ?>

    <?php if (empty($orders)): ?>
      <p class="no-data">No orders found.</p>
    <?php endif; ?>
        <div style="text-align: center; margin-top: 30px;">
      <a href="purchase_order.php" class="btn">
        <i class="fas fa-arrow-left"></i> Order Again
      </a>
    </div>

  </div>
</body>
</html>
