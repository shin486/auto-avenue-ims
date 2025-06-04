<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle order receipt and stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $notes = $_POST['receiving_notes'] ?? '';
    
    // Debug info
    error_log("Processing receipt for order #$order_id");
    error_log("Received quantities: " . json_encode($_POST['received_qty']));
    
    $conn->begin_transaction();
    
    try {
        // Process each item
        foreach ($_POST['received_qty'] as $item_id => $received_qty) {
            $item_id = (int)$item_id;
            $received_qty = (int)$received_qty;
            
            // Get current received quantity and ordered quantity
            $current_stmt = $conn->prepare("SELECT received_qty, quantity FROM purchase_order_items WHERE item_id = ?");
            $current_stmt->bind_param("i", $item_id);
            $current_stmt->execute();
            $current_result = $current_stmt->get_result();
            $current_row = $current_result->fetch_assoc();
            $current_received = (int)($current_row['received_qty'] ?? 0);
            $ordered_qty = (int)($current_row['quantity'] ?? 0);
            
            // Calculate new total received
            $new_received_total = $current_received + $received_qty;
            
            // Debug info
            error_log("Item #$item_id: Ordered: $ordered_qty, Already received: $current_received, Receiving now: $received_qty, New total: $new_received_total");
            
            // Update received quantity
            $stmt = $conn->prepare("UPDATE purchase_order_items SET received_qty = ? WHERE item_id = ?");
            $stmt->bind_param("ii", $new_received_total, $item_id);
            $stmt->execute();
            
            // Update stock if received qty > 0
            if ($received_qty > 0) {
                $update_stmt = $conn->prepare("UPDATE products p JOIN purchase_order_items poi ON p.product_id = poi.product_id SET p.quantity = p.quantity + ? WHERE poi.item_id = ?");
                $update_stmt->bind_param("ii", $received_qty, $item_id);
                $update_stmt->execute();
            }
        }
        
        // Calculate remaining quantity
        $check_stmt = $conn->prepare("SELECT SUM(quantity - IFNULL(received_qty, 0)) AS remaining 
                                     FROM purchase_order_items 
                                     WHERE order_id = ?");
        $check_stmt->bind_param("i", $order_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        $remaining_quantity = (int)$row['remaining'];
        
        // Debug info
        error_log("Remaining quantity for order #$order_id: $remaining_quantity");
        
        // Set status based on remaining items
        $status = ($remaining_quantity > 0) ? 'short_quantity' : 'received';
        
        // Debug info
        error_log("Setting order #$order_id status to: $status");
        
        // Update order status and remaining quantity
        $stmt = $conn->prepare("UPDATE purchase_orders SET order_status = ?, receiving_notes = ?, remaining_quantity = ? WHERE order_id = ?");
        $stmt->bind_param("ssii", $status, $notes, $remaining_quantity, $order_id);
        $stmt->execute();
        
        // Log transaction
        $remarks = ($remaining_quantity > 0) ? 
            "Order #$order_id received with shortages ($remaining_quantity items remaining)" : 
            "Order #$order_id fully received";
        $trans_stmt = $conn->prepare("INSERT INTO transactions (action_type, reference_id, reference_table, performed_by, timestamp, remarks) VALUES ('stock_received', ?, 'purchase_orders', ?, NOW(), ?)");
        $trans_stmt->bind_param("iis", $order_id, $_SESSION['user_id'], $remarks);
        $trans_stmt->execute();
        
        $conn->commit();
        header("Location: " . $_SERVER['PHP_SELF'] . "?updated=" . time());
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error processing receipt: " . $e->getMessage());
        die("Error processing receipt: " . $e->getMessage());
    }
}

// Handle order confirmation and rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order_id']) && $_SESSION['role'] === 'manager') {
    $order_id = (int)$_POST['confirm_order_id'];
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE purchase_orders SET order_status = 'confirmed' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Log transaction
        $trans_stmt = $conn->prepare("INSERT INTO transactions (action_type, reference_id, reference_table, performed_by, timestamp, remarks) VALUES ('order_confirmed', ?, 'purchase_orders', ?, NOW(), ?)");
        $remarks = "Order #$order_id confirmed";
        $trans_stmt->bind_param("iis", $order_id, $_SESSION['user_id'], $remarks);
        $trans_stmt->execute();
        
        $conn->commit();
        header("Location: " . $_SERVER['PHP_SELF'] . "?updated=" . time());
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error confirming order: " . $e->getMessage());
        die("Error confirming order: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_order_id']) && $_SESSION['role'] === 'manager') {
    $order_id = (int)$_POST['reject_order_id'];
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE purchase_orders SET order_status = 'rejected' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Log transaction
        $trans_stmt = $conn->prepare("INSERT INTO transactions (action_type, reference_id, reference_table, performed_by, timestamp, remarks) VALUES ('order_rejected', ?, 'purchase_orders', ?, NOW(), ?)");
        $remarks = "Order #$order_id rejected";
        $trans_stmt->bind_param("iis", $order_id, $_SESSION['user_id'], $remarks);
        $trans_stmt->execute();
        
        $conn->commit();
        header("Location: " . $_SERVER['PHP_SELF'] . "?updated=" . time());
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error rejecting order: " . $e->getMessage());
        die("Error rejecting order: " . $e->getMessage());
    }
}

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
    $order_id = (int)$_POST['delete_order_id'];
    
    // Check if user has permission to delete this order
    $check_stmt = $conn->prepare("SELECT user_id FROM purchase_orders WHERE order_id = ?");
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $order_data = $result->fetch_assoc();
    
    $can_delete = $_SESSION['role'] === 'manager' || 
                 ($_SESSION['role'] === 'staff' && $_SESSION['user_id'] == $order_data['user_id']);
    
    if ($can_delete) {
        $conn->begin_transaction();
        try {
            // First delete order items
            $delete_items = $conn->prepare("DELETE FROM purchase_order_items WHERE order_id = ?");
            $delete_items->bind_param("i", $order_id);
            $delete_items->execute();
            
            // Then delete the order
            $delete_order = $conn->prepare("DELETE FROM purchase_orders WHERE order_id = ?");
            $delete_order->bind_param("i", $order_id);
            $delete_order->execute();
            
            // Log transaction
            $trans_stmt = $conn->prepare("INSERT INTO transactions (action_type, reference_id, reference_table, performed_by, timestamp, remarks) VALUES ('order_deleted', ?, 'purchase_orders', ?, NOW(), ?)");
            $remarks = "Order #$order_id deleted";
            $trans_stmt->bind_param("iis", $order_id, $_SESSION['user_id'], $remarks);
            $trans_stmt->execute();
            
            $conn->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?updated=" . time());
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error deleting order: " . $e->getMessage());
            die("Error deleting order: " . $e->getMessage());
        }
    } else {
        die("You don't have permission to delete this order.");
    }
}

// Add this handler for removing individual items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item_id'])) {
    $item_id = (int)$_POST['remove_item_id'];
    
    // Get order information for this item
    $check_stmt = $conn->prepare("
        SELECT poi.order_id, po.user_id, po.order_status 
        FROM purchase_order_items poi
        JOIN purchase_orders po ON poi.order_id = po.order_id
        WHERE poi.item_id = ?
    ");
    $check_stmt->bind_param("i", $item_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $item_data = $result->fetch_assoc();
    
    // Check if user has permission to remove this item
    $can_remove = $_SESSION['role'] === 'manager' || 
                 ($_SESSION['role'] === 'staff' && $_SESSION['user_id'] == $item_data['user_id']);
    
    // Only allow removal if order is not received and user has permission
    if ($can_remove && $item_data['order_status'] !== 'received') {
        $conn->begin_transaction();
        try {
            // Delete the item
            $delete_item = $conn->prepare("DELETE FROM purchase_order_items WHERE item_id = ?");
            $delete_item->bind_param("i", $item_id);
            $delete_item->execute();
            
            // Check if this was the last item in the order
            $count_stmt = $conn->prepare("SELECT COUNT(*) as item_count FROM purchase_order_items WHERE order_id = ?");
            $count_stmt->bind_param("i", $item_data['order_id']);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_data = $count_result->fetch_assoc();
            
            // If no items left, delete the entire order
            if ((int)$count_data['item_count'] === 0) {
                $delete_order = $conn->prepare("DELETE FROM purchase_orders WHERE order_id = ?");
                $delete_order->bind_param("i", $item_data['order_id']);
                $delete_order->execute();
                
                // Log transaction for order deletion
                $trans_stmt = $conn->prepare("INSERT INTO transactions (action_type, reference_id, reference_table, performed_by, timestamp, remarks) VALUES ('order_deleted', ?, 'purchase_orders', ?, NOW(), ?)");
                $remarks = "Order #{$item_data['order_id']} deleted (last item removed)";
                $trans_stmt->bind_param("iis", $item_data['order_id'], $_SESSION['user_id'], $remarks);
                $trans_stmt->execute();
            } else {
                // Log transaction for item removal
                $trans_stmt = $conn->prepare("INSERT INTO transactions (action_type, reference_id, reference_table, performed_by, timestamp, remarks) VALUES ('item_removed', ?, 'purchase_order_items', ?, NOW(), ?)");
                $remarks = "Item #$item_id removed from order #{$item_data['order_id']}";
                $trans_stmt->bind_param("iis", $item_id, $_SESSION['user_id'], $remarks);
                $trans_stmt->execute();
                
                // Update remaining quantity for the order
                $update_stmt = $conn->prepare("
                    UPDATE purchase_orders 
                    SET remaining_quantity = (
                        SELECT SUM(quantity - IFNULL(received_qty, 0)) 
                        FROM purchase_order_items 
                        WHERE order_id = ?
                    )
                    WHERE order_id = ?
                ");
                $update_stmt->bind_param("ii", $item_data['order_id'], $item_data['order_id']);
                $update_stmt->execute();
            }
            
            $conn->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?updated=" . time());
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error removing item: " . $e->getMessage());
            die("Error removing item: " . $e->getMessage());
        }
    } else {
        die("You don't have permission to remove this item or the order has already been received.");
    }
}

// Fetch all orders for both managers and staff
$order_sql = "SELECT po.*, u.username, 
             (SELECT SUM(poi.quantity - IFNULL(poi.received_qty, 0)) 
              FROM purchase_order_items poi 
              WHERE poi.order_id = po.order_id) AS remaining_quantity
             FROM purchase_orders po 
             JOIN users u ON po.user_id = u.user_id 
             ORDER BY po.order_date DESC";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

$orders = [];
while ($order = $order_result->fetch_assoc()) {
    $order_id = $order['order_id'];
    $orders[$order_id] = [
        'user_id' => $order['user_id'],
        'username' => $order['username'] ?? '',
        'order_number' => $order['order_number'], 
        'expected_arrival' => $order['expected_arrival'],
        'payment_method' => $order['payment_method'],
        'payment_terms' => $order['payment_terms'],
        'notes' => $order['notes'],
        'order_date' => $order['order_date'],
        'order_status' => $order['order_status'] ?? 'pending',
        'remaining_quantity' => (int)($order['remaining_quantity'] ?? 0),
        'items' => []
    ];
    
    // Fetch items for this order
    $items_sql = "SELECT poi.*, p.name as product_name, p.category, p.supplier
                 FROM purchase_order_items poi 
                 LEFT JOIN products p ON poi.product_id = p.product_id 
                 WHERE poi.order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        $orders[$order_id]['items'][] = $item;
    }
}

// Add debug info if requested
if (isset($_GET['debug']) && $_SESSION['role'] === 'manager') {
    echo "<pre style='background:#f5f5f5;padding:15px;margin:15px 0;border:1px solid #ddd;'>";
    echo "DEBUG INFO:\n\n";
    
    // Show order statuses
    echo "ORDER STATUSES:\n";
    foreach ($orders as $order_id => $order) {
        echo "Order #$order_id: Status = " . ($order['order_status'] ?? 'undefined') . 
             ", Remaining = " . ($order['remaining_quantity'] ?? 'undefined') . "\n";
             
        // Show item details
        echo "  Items:\n";
        foreach ($order['items'] as $item) {
            echo "    - " . ($item['product_name'] ?? 'Unknown') . 
                 ": Ordered = " . ($item['quantity'] ?? 0) . 
                 ", Received = " . ($item['received_qty'] ?? 0) . "\n";
        }
        echo "\n";
    }
    
    echo "</pre>";
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

    <!-- Order details -->
    <p><strong>Transaction Number:</strong> <?= htmlspecialchars($order_id) ?></p>
    <p><strong>Purchase Order Number:</strong> <?= htmlspecialchars($order['order_number'] ?? '-') ?></p>
    <p><strong>Date Arrival:</strong> <?= !empty($order['expected_arrival']) ? date('F j, Y', strtotime($order['expected_arrival'])) : '-' ?></p>
    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method'] ?? '-') ?></p>
    <?php if (isset($order['payment_method']) && strtolower($order['payment_method']) === 'installment'): ?>
        <p><strong>Payment Terms:</strong> <?= htmlspecialchars($order['payment_terms'] ?? '-') ?></p>
    <?php endif; ?>

    <!-- Items table -->
    <table>
      <thead>
        <tr>
          <th>ID</th>
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
          <td><?= htmlspecialchars($item['product_id'] ?? '') ?></td>
          <td><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($item['supplier'] ?? '') ?></td>
          <td><?= (int)$item['quantity'] ?></td>
          <td><?= htmlspecialchars($item['category'] ?? '') ?></td>
          <td>
            <?php 
            // Only show remove button if order is not received and user has permission
            $can_remove = $_SESSION['role'] === 'manager' || $_SESSION['user_id'] == $order['user_id'];
            if ($can_remove && $order['order_status'] !== 'received'): 
            ?>
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

    <!-- Status and action buttons -->
    <div style="margin-top: 15px;">
      <?php 
      // Force display status based on remaining_quantity
      $remaining = isset($order['remaining_quantity']) ? (int)$order['remaining_quantity'] : 0;
      $has_received_any = false;
      
      foreach ($order['items'] as $item) {
        if (isset($item['received_qty']) && (int)$item['received_qty'] > 0) {
          $has_received_any = true;
          break;
        }
      }
      ?>
      
      <!-- Status Badge -->
      <div style="margin-bottom: 10px;">
        <?php if ($order['order_status'] === 'received' || $remaining === 0): ?>
          <span class="user-badge" style="background-color: #28a745; color: white; padding: 5px 10px; border-radius: 4px;">
            <i class="fas fa-check-double"></i> Complete Delivery
          </span>
        <?php elseif ($order['order_status'] === 'short_quantity' || ($has_received_any && $remaining > 0)): ?>
          <span class="user-badge" style="background-color: #ffc107; color: #000; padding: 5px 10px; border-radius: 4px;">
            <i class="fas fa-exclamation-triangle"></i> Partially Delivered
          </span>
        <?php elseif ($order['order_status'] === 'confirmed'): ?>
          <span class="user-badge" style="background-color: #007bff; color: white; padding: 5px 10px; border-radius: 4px;">
            <i class="fas fa-thumbs-up"></i> Confirmed
          </span>
        <?php elseif ($order['order_status'] === 'pending'): ?>
          <span class="user-badge" style="background-color: #6c757d; color: white; padding: 5px 10px; border-radius: 4px;">
            <i class="fas fa-clock"></i> Pending
          </span>
        <?php elseif ($order['order_status'] === 'rejected'): ?>
          <span class="user-badge" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 4px;">
            <i class="fas fa-times"></i> Rejected
          </span>
        <?php else: ?>
          <span class="user-badge" style="background-color: #6c757d; color: white; padding: 5px 10px; border-radius: 4px;">
            <i class="fas fa-info-circle"></i> <?= ucfirst(htmlspecialchars($order['order_status'] ?? 'Unknown')) ?>
          </span>
        <?php endif; ?>
      </div>
      
      <!-- Receive Order Button -->
      <?php 
      // Debug info to see what's happening
      error_log("Order #$order_id - Status: {$order['order_status']}, Remaining: {$order['remaining_quantity']}");

      // PERMISSION RULES:
      // 1. Manager can receive items for any order with remaining items (except rejected)
      // 2. Staff can only receive items for confirmed or short_quantity orders they created
      $can_receive = false;
      
      if ($_SESSION['role'] === 'manager') {
        // Managers can receive any non-rejected order with remaining items
        $can_receive = isset($order['remaining_quantity']) 
                      && (int)$order['remaining_quantity'] > 0
                      && $order['order_status'] !== 'rejected';
      } else {
        // Staff can only receive confirmed or short_quantity orders they created
        $can_receive = isset($order['remaining_quantity']) 
                      && (int)$order['remaining_quantity'] > 0
                      && ($_SESSION['user_id'] == $order['user_id'])
                      && ($order['order_status'] === 'confirmed' || $order['order_status'] === 'short_quantity')
                      && $order['order_status'] !== 'rejected';
      }
      
      if ($can_receive): 
      ?>
        <div style="margin-bottom: 15px;">
          <button type="button" class="btn" style="background-color: #17a2b8;" onclick="openReceiveModal(<?= $order_id ?>)">
            <i class="fas fa-check-circle"></i> Receive Items (<?= (int)$order['remaining_quantity'] ?>)
          </button>
          
          <span style="margin-left: 10px; color: #856404; font-size: 0.9rem;">
            <i class="fas fa-info-circle"></i> 
            <?= (int)$order['remaining_quantity'] ?> items remaining to be received
          </span>
        </div>
      <?php endif; ?>
      
      <!-- Manager actions -->
      <div style="display: flex; flex-wrap: wrap; gap: 10px;">
        <?php if ($_SESSION['role'] === 'manager' && $order['order_status'] === 'pending'): ?>
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
        <?php endif; ?>

        <?php
          $can_delete = $_SESSION['role'] === 'manager' || 
                      ($_SESSION['role'] === 'staff' && $_SESSION['user_id'] == $order['user_id']);
          // Allow deletion for any status except 'received'
          if ($can_delete && $order['order_status'] !== 'received'):
        ?>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="delete_order_id" value="<?= $order_id ?>">
            <button type="submit" class="btn-remove" onclick="return confirm('Are you sure you want to delete this order?')">
              <i class="fas fa-trash"></i> Delete Order
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

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

 <!-- Receive Order Modal -->
<div id="receiveModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; padding-top:50px; overflow-y:auto;">
    <div style="background:white; width:90%; max-width:800px; margin:auto; padding:25px; border-radius:8px; position:relative; margin-bottom:50px; box-shadow:0 5px 15px rgba(0,0,0,0.3);">
        <h2 style="color:#0b8126; margin-top:0; margin-bottom:20px; border-bottom:2px solid #0b8126; padding-bottom:10px;">
            <i class="fas fa-clipboard-check"></i> Receive Order Items
        </h2>
        <form method="POST" id="receiveForm">
            <input type="hidden" name="order_id" id="receive_order_id">
            <table style="width:100%; margin-bottom:25px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="background-color:#0b8126; color:white; padding:12px 15px; text-align:left; border:1px solid #ddd;">Product</th>
                        <th style="background-color:#0b8126; color:white; padding:12px 15px; text-align:center; border:1px solid #ddd;">Ordered</th>
                        <th style="background-color:#0b8126; color:white; padding:12px 15px; text-align:center; border:1px solid #ddd;">Already Received</th>
                        <th style="background-color:#0b8126; color:white; padding:12px 15px; text-align:center; border:1px solid #ddd;">Receive Now</th>
                        <th style="background-color:#0b8126; color:white; padding:12px 15px; text-align:center; border:1px solid #ddd;">Remaining</th>
                    </tr>
                </thead>
                <tbody id="receiveItemsBody">
                    <!-- Filled by JS -->
                </tbody>
            </table>
            <div style="margin-top:20px;">
                <label for="receiving_notes" style="display:block; margin-bottom:8px; font-weight:bold;">Notes:</label>
                <textarea name="receiving_notes" id="receiving_notes" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; min-height:100px; font-family:inherit;" rows="4" placeholder="Enter any notes about this receipt..."></textarea>
            </div>
            <div style="margin-top:25px; text-align:right; display:flex; justify-content:flex-end; gap:15px;">
                <button type="button" class="btn-remove" style="padding:12px 25px;" onclick="closeReceiveModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn" style="padding:12px 25px;">
                    <i class="fas fa-save"></i> Submit Receipt
                </button>
            </div>
        </form>
        <button onclick="closeReceiveModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:24px; cursor:pointer; color:#666;">&times;</button>
    </div>
</div>


<script>
function openReceiveModal(orderId) {
    // Find the order's items from the PHP data rendered on the page
    const orders = <?= json_encode($orders) ?>;
    const order = orders[orderId];

    if (!order) {
        alert("Order not found.");
        return;
    }

    const tbody = document.getElementById('receiveItemsBody');
    tbody.innerHTML = ''; // Clear existing rows
    
    let hasRemainingItems = false;

    order.items.forEach(item => {
        // Calculate remaining quantity to receive
        const orderedQty = parseInt(item.quantity) || 0;
        const alreadyReceivedQty = parseInt(item.received_qty) || 0;
        const remainingToReceive = orderedQty - alreadyReceivedQty;
        
        // Only show items that have remaining quantities
        if (remainingToReceive <= 0) return;
        
        hasRemainingItems = true;
        
        // Create row elements with inputs
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #ddd';

        // Product Name
        const tdName = document.createElement('td');
        tdName.textContent = item.product_name;
        tdName.style.padding = '12px 15px';
        tdName.style.borderLeft = '1px solid #ddd';
        tdName.style.borderRight = '1px solid #ddd';
        tr.appendChild(tdName);

        // Ordered Qty
        const tdOrdered = document.createElement('td');
        tdOrdered.textContent = orderedQty;
        tdOrdered.style.padding = '12px 15px';
        tdOrdered.style.textAlign = 'center';
        tdOrdered.style.borderRight = '1px solid #ddd';
        tr.appendChild(tdOrdered);

        // Already Received Qty
        const tdAlreadyReceived = document.createElement('td');
        tdAlreadyReceived.textContent = alreadyReceivedQty;
        tdAlreadyReceived.style.padding = '12px 15px';
        tdAlreadyReceived.style.textAlign = 'center';
        tdAlreadyReceived.style.borderRight = '1px solid #ddd';
        tr.appendChild(tdAlreadyReceived);

        // Receive Now Qty input
        const tdReceiveNow = document.createElement('td');
        tdReceiveNow.style.padding = '12px 15px';
        tdReceiveNow.style.textAlign = 'center';
        tdReceiveNow.style.borderRight = '1px solid #ddd';
        
        const inputReceiveNow = document.createElement('input');
        inputReceiveNow.type = 'number';
        inputReceiveNow.min = 0;
        inputReceiveNow.max = remainingToReceive;
        inputReceiveNow.name = `received_qty[${item.item_id}]`;
        inputReceiveNow.value = remainingToReceive; // Default to receiving all remaining
        inputReceiveNow.required = true;
        inputReceiveNow.style.width = '80px';
        inputReceiveNow.style.padding = '8px';
        inputReceiveNow.style.textAlign = 'center';
        inputReceiveNow.style.border = '1px solid #ddd';
        inputReceiveNow.style.borderRadius = '4px';
        inputReceiveNow.dataset.remaining = remainingToReceive;
        tdReceiveNow.appendChild(inputReceiveNow);
        tr.appendChild(tdReceiveNow);

        // Remaining after this receipt
        const tdRemaining = document.createElement('td');
        tdRemaining.className = 'remaining-cell';
        tdRemaining.textContent = '0'; // Default to 0 remaining
        tdRemaining.style.padding = '12px 15px';
        tdRemaining.style.textAlign = 'center';
        tdRemaining.style.borderRight = '1px solid #ddd';
        tr.appendChild(tdRemaining);

        // Update remaining dynamically on input change
        inputReceiveNow.addEventListener('input', function() {
            const receiveNow = parseInt(this.value) || 0;
            const remaining = parseInt(this.dataset.remaining) || 0;
            const willRemain = Math.max(0, remaining - receiveNow);
            this.closest('tr').querySelector('.remaining-cell').textContent = willRemain;
        });

        tbody.appendChild(tr);
    });

    if (!hasRemainingItems) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 5;
        td.textContent = 'All items have been received.';
        td.style.textAlign = 'center';
        td.style.padding = '20px';
        td.style.color = '#666';
        td.style.fontStyle = 'italic';
        td.style.border = '1px solid #ddd';
        tr.appendChild(td);
        tbody.appendChild(tr);
    }

    // Show modal and set hidden order_id
    document.getElementById('receive_order_id').value = orderId;
    document.getElementById('receiveModal').style.display = 'block';
    
    // Clear any previous notes
    document.getElementById('receiving_notes').value = '';
}

function closeReceiveModal() {
    document.getElementById('receiveModal').style.display = 'none';
}

// Close modal when clicking outside modal content
window.onclick = function(event) {
    const modal = document.getElementById('receiveModal');
    if (event.target === modal) {
        closeReceiveModal();
    }
}
</script>

  </body>
  </html>
