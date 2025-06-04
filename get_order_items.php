<?php
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    die(json_encode(['error' => 'Order ID required']));
}

$order_id = (int)$_GET['order_id'];
$stmt = $conn->prepare("SELECT * FROM purchase_order_items WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode($result->fetch_all(MYSQLI_ASSOC));