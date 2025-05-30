<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

function clean_input($data) {
    return htmlspecialchars(trim($data));
}

// Initialize arrays
$validated_new_products = [];
$validated_low_stock_orders = [];
$notes = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_products = $_POST['new_products'] ?? [];
    $low_stock_order = $_POST['low_stock_order'] ?? [];
    $notes = clean_input(trim($_POST['notes'] ?? ''));

    // Validate new products
    foreach ($new_products as $prod) {
        $name = clean_input($prod['name'] ?? '');
        $category = clean_input($prod['category'] ?? '');
        $quantity = filter_var($prod['quantity'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $supplier_name = clean_input($prod['supplier_name'] ?? '');

        if ($name && $category && $quantity) {
            $validated_new_products[] = [
                'name' => $name,
                'category' => $category,
                'quantity' => $quantity,
                'supplier_name' => $supplier_name
            ];
        }
    }

    // Validate low stock orders
    foreach ($low_stock_order as $product_id => $qty) {
        $product_id = filter_var($product_id, FILTER_VALIDATE_INT);
        $qty = filter_var($qty, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if ($product_id && $qty) {
            $validated_low_stock_orders[$product_id] = $qty;
        }
    }

    // Save to DB
    $user_id = $_SESSION['user_id'];
    $order_date = date('Y-m-d H:i:s');

    // Insert into purchase_orders
    $stmt = $conn->prepare("INSERT INTO purchase_orders (user_id, order_date, notes) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $order_date, $notes);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert new product items
    foreach ($validated_new_products as $prod) {
        $stmt = $conn->prepare("INSERT INTO purchase_order_items (order_id, product_name, category, quantity, supplier_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $order_id, $prod['name'], $prod['category'], $prod['quantity'], $prod['supplier_name']);
        $stmt->execute();
        $stmt->close();
    }

    // Insert low stock items (fixed: product_id column used)
    foreach ($validated_low_stock_orders as $product_id => $qty) {
        $stmt = $conn->prepare("INSERT INTO purchase_order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $order_id, $product_id, $qty);
        $stmt->execute();
        $stmt->close();
    }

    // Fetch product details for low stock display
    $low_stock_products = [];
    if (!empty($validated_low_stock_orders)) {
        $ids = implode(',', array_keys($validated_low_stock_orders));
        $sql = "SELECT product_id, name, category FROM products WHERE product_id IN ($ids)";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $low_stock_products[$row['product_id']] = $row;
        }
    }

    // Save summary data to session for summary page
    $_SESSION['order_summary'] = [
        'new_products' => $validated_new_products,
        'low_stock_orders' => $validated_low_stock_orders,
        'low_stock_products' => $low_stock_products,
        'notes' => $notes
    ];

    header("Location: purchase_order_summary.php");
    exit();
} else {
    header("Location: purchase_order.php");
    exit();
}
