<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header("Location: purchase_order_summary.php");
    exit();
}

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, u.username, s.supplier_name
    FROM purchase_orders o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN suppliers s ON o.supplier_id = s.supplier_id
    WHERE o.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: purchase_order_summary.php");
    exit();
}

// Fetch defective items
$item_stmt = $conn->prepare("
    SELECT poi.*, p.name as product_name, p.category
    FROM purchase_order_items poi
    JOIN products p ON poi.product_id = p.product_id
    WHERE poi.order_id = ? AND poi.defective_qty > 0
");
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items_result = $item_stmt->get_result();
$defective_items = [];
while ($item = $items_result->fetch_assoc()) {
    $defective_items[] = $item;
}

// Calculate totals
$total_ordered = 0;
$total_defective = 0;
foreach ($defective_items as $item) {
    $total_ordered += $item['quantity'];
    $total_defective += $item['defective_qty'];
}
$defect_rate = $total_ordered > 0 ? round(($total_defective / $total_ordered) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Defective Items Report - Order #<?= $order_id ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1 { color: #e65100; border-bottom: 2px solid #ff9800; padding-bottom: 10px; }
        .report-header { background-color: #fff3e0; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .report-meta { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .meta-item { flex: 1; margin-right: 20px; }
        .meta-item:last-child { margin-right: 0; }
        .meta-label { font-weight: bold; color: #555; }
        .stats-container { display: flex; margin-bottom: 20px; }
        .stat-box { flex: 1; text-align: center; padding: 15px; background-color: #ffecb3; border-radius: 5px; margin-right: 15px; }
        .stat-box:last-child { margin-right: 0; }
        .stat-value { font-size: 28px; font-weight: bold; color: #e65100; }
        .stat-label { margin-top: 5px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th, td { border: 1px solid #ddd; padding: 12px 15px; text-align: left; }
        th { background-color: #ff9800; color: white; }
        tr:nth-child(even) { background-color: #fff3e0; }
        .notes-box { background: #fff; border-left: 5px solid #ff9800; padding: 15px; margin-bottom: 25px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #ff9800; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }
        .btn:hover { background-color: #e65100; }
        .print-btn { background-color: #4caf50; }
        .back-btn { background-color: #2196f3; }
        @media print {
            .no-print { display: none; }
            body { font-size: 12pt; }
            .container { width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print" style="text-align: right; margin-bottom: 20px;">
            <button onclick="window.print()" class="btn print-btn"><i class="fas fa-print"></i> Print Report</button>
            <a href="purchase_order_summary.php" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        </div>
        
        <h1><i class="fas fa-exclamation-triangle"></i> Defective Items Report</h1>
        
        <div class="report-header">
            <div class="report-meta">
                <div class="meta-item">
                    <p><span class="meta-label">Order ID:</span> <?= $order_id ?></p>
                    <p><span class="meta-label">Order Number:</span> <?= htmlspecialchars($order['order_number'] ?? '-') ?></p>
                    <p><span class="meta-label">Order Date:</span> <?= date('F j, Y', strtotime($order['order_date'])) ?></p>
                </div>
                <div class="meta-item">
                    <p><span class="meta-label">Supplier:</span> <?= htmlspecialchars($order['supplier_name'] ?? '-') ?></p>
                    <p><span class="meta-label">Received By:</span> <?= htmlspecialchars($order['username']) ?></p>
                    <p><span class="meta-label">Status:</span> <?= ucfirst(htmlspecialchars($order['order_status'])) ?></p>
                </div>
            </div>
            
            <div class="stats-container">
                <div class="stat-box">
                    <div class="stat-value"><?= count($defective_items) ?></div>
                    <div class="stat-label">Products with Defects</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $total_defective ?></div>
                    <div class="stat-label">Total Defective Units</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $defect_rate ?>%</div>
                    <div class="stat-label">Overall Defect Rate</div>
                </div>
            </div>
        </div>
        
        <h2>Defective Items Details</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Ordered Qty</th>
                    <th>Received Qty</th>
                    <th>Defective Qty</th>
                    <th>Defect Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($defective_items as $item): 
                    $item_defect_rate = round(($item['defective_qty'] / $item['quantity']) * 100, 1);
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= htmlspecialchars($item['category']) ?></td>
                    <td><?= (int)$item['quantity'] ?></td>
                    <td><?= (int)$item['received_qty'] ?></td>
                    <td><?= (int)$item['defective_qty'] ?></td>
                    <td>
                        <div style="background-color: <?= $item_defect_rate > 20 ? '#f44336' : ($item_defect_rate > 10 ? '#ff9800' : '#4caf50') ?>; 
                                    color: white; 
                                    padding: 3px 8px; 
                                    border-radius: 10px; 
                                    display: inline-block;">
                            <?= $item_defect_rate ?>%
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (!empty($order['receiving_notes'])): ?>
        <div class="notes-box">
            <h3><i class="fas fa-clipboard-list"></i> Notes on Defects</h3>
            <p><?= nl2br(htmlspecialchars($order['receiving_notes'])) ?></p>
        </div>
        <?php endif; ?>
        
        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="window.print()" class="btn print-btn"><i class="fas fa-print"></i> Print Report</button>
            <a href="purchase_order_summary.php" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        </div>
    </div>
</body>
</html>