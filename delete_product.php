<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
     
        
        
        // Option 2: HARD DELETE (only use if you want to permanently remove)
        // First delete related alerts
        $delete_alerts = $conn->prepare("DELETE FROM alerts WHERE product_id = ?");
        $delete_alerts->bind_param("i", $product_id);
        $delete_alerts->execute();
        
        // Set product_id to NULL in sales (to maintain sales records)
        $update_sales = $conn->prepare("UPDATE sales SET product_id = NULL WHERE product_id = ?");
        $update_sales->bind_param("i", $product_id);
        $update_sales->execute();
        
        // Then delete the product
        $delete_product = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $delete_product->bind_param("i", $product_id);
        $delete_product->execute();
        
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success_message'] = 'Product deactivated successfully';
    } catch (Exception $e) {
        // Rollback if any error occurs
        $conn->rollback();
        $_SESSION['error_message'] = 'Error processing request: ' . $e->getMessage();
        error_log("Delete Product Error: " . $e->getMessage());
    } finally {
        // Close statements
        if (isset($stmt)) $stmt->close();
        if (isset($resolve_alerts)) $resolve_alerts->close();
    }
}

header("Location: products.php");
exit();
?>