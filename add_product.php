<?php
require_once __DIR__ . '/includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = false;
$formData = [
    'name' => '',
    'category' => '',
    'supplier' => '',
    'quantity' => 0,
    'price' => 0.00,
    'min_stock_level' => 10,
    'warranty_period' => '',
    'warranty_terms' => '',
    'vin_code' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'supplier' => trim($_POST['supplier'] ?? ''),
        'quantity' => intval($_POST['quantity'] ?? 0),
        'price' => floatval($_POST['price'] ?? 0),
        'min_stock_level' => intval($_POST['min_stock_level'] ?? 10),
        'warranty_period' => trim($_POST['warranty_period'] ?? ''),
        'warranty_terms' => trim($_POST['warranty_terms'] ?? ''),
        'vin_code' => trim($_POST['vin_code'] ?? '')
    ];

    // Validation
    if (empty($formData['name'])) {
        $errors['name'] = 'Product name is required';
    }
    if ($formData['price'] <= 0) {
        $errors['price'] = 'Price must be greater than 0';
    }
    if ($formData['quantity'] < 0) {
        $errors['quantity'] = 'Quantity cannot be negative';
    }
    if ($formData['min_stock_level'] < 0) {
        $errors['min_stock_level'] = 'Minimum stock level cannot be negative';
    }
    // Optional VIN validation (17 characters standard)
    if (!empty($formData['vin_code']) && strlen($formData['vin_code']) != 17) {
        $errors['vin_code'] = 'VIN must be exactly 17 characters';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products 
                              (name, quantity, price, supplier, category, min_stock_level, 
                              warranty_period, warranty_terms, vin_code) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param(
            "sidssisss", 
            $formData['name'],
            $formData['quantity'],
            $formData['price'],
            $formData['category'],
            $formData['supplier'],
            $formData['min_stock_level'],
            $formData['warranty_period'],
            $formData['warranty_terms'],
            $formData['vin_code']
        );

        if ($stmt->execute()) {
            $success = true;
            // Reset form on success
            $formData = [
                'name' => '',
                'category' => '',
                'supplier' => '',
                'quantity' => 0,
                'price' => 0.00,
                'min_stock_level' => 10,
                'warranty_period' => '',
                'warranty_terms' => '',
                'vin_code' => ''
            ];
        } else {
            $errors['database'] = 'Error saving product: ' . $stmt->error;
            error_log("Database error: " . $stmt->error);
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Auto Avenue - Add Product</title>
  
  <style>
    :root {
     --primary:rgb(11, 129, 38);
      --primary-light:rgb(29, 136, 63);
      --secondary: #ff6b6b;
      --success: #4CAF50;
      --warning: #ff9800;
      --danger: #f44336;
      --text-color: #333;
      --text-light: #666;
      --bg-color: #f4f6f8;
      --border-color: #e0e0e0;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      line-height: 1.6;
    }

    header {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white;
      padding: 15px 0;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    nav a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-weight: 500;
      transition: all 0.3s ease;
      padding: 5px 10px;
      border-radius: 4px;
    }

    nav a:hover {
      background-color: rgba(255, 255, 255, 0.2);
    }

    nav a.active {
      background-color: rgba(255, 255, 255, 0.3);
      font-weight: 600;
    }

    .main-content {
      width: 90%;
      max-width: 800px;
      margin: 30px auto;
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      padding: 30px;
    }

    .page-header {
      margin-bottom: 25px;
      text-align: center;
    }

    .page-header h2 {
      color: var(--primary);
      font-size: 1.8rem;
    }

    /* Form Styles */
    .product-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-row {
      display: flex;
      gap: 20px;
    }

    .form-group {
      flex: 1;
      margin-bottom: 15px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text-color);
    }

    .required {
      color: var(--danger);
    }

    .form-input {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 16px;
      transition: all 0.3s;
    }

    .form-textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 16px;
      min-height: 100px;
      resize: vertical;
      transition: all 0.3s;
    }

    .form-input:focus,
    .form-textarea:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 2px rgba(0, 74, 173, 0.1);
    }

    .submit-btn {
      background-color: var(--primary);
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s;
      margin-top: 10px;
      width: 100%;
    }

    .submit-btn:hover {
      background-color: var(--primary-light);
    }

    .error {
      color: var(--danger);
      font-size: 0.9rem;
      margin-top: 5px;
    }

    .success-message {
      background-color: var(--success);
      color: white;
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
    }

    /* Warranty Section Styling */
    .warranty-section {
      background-color: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      border-left: 4px solid var(--primary);
      margin-bottom: 20px;
    }

    .warranty-section h3 {
      color: var(--primary);
      margin-bottom: 15px;
      font-size: 1.2rem;
    }

    @media (max-width: 768px) {
      .form-row {
        flex-direction: column;
        gap: 15px;
      }
      
      .main-content {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="container">
      <img src="autoavelogo.svg" alt="Auto Avenue Logo" class="logo" width="150">
      <h1>Auto Avenue IMS</h1>
      <?php if (isset($_SESSION['role'])): ?>
  <div style="background-color: rgba(255, 255, 255, 0.15); padding: 6px 12px; border-radius: 8px; font-size: 0.9rem;">
    Logged in as: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>
  </div>
<?php endif; ?>
      <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="sales.php">Sales</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
      </nav>
    </div>
  </header>

  <main class="main-content">
    <div class="page-header">
      <h2>Add New Product</h2>
    </div>

    <?php if ($success): ?>
      <div class="success-message">
        Product added successfully! <a href="products.php" style="color: white; text-decoration: underline;">View all products</a>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors['database'])): ?>
      <div class="error" style="background-color: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
        <?= $errors['database'] ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="product-form">
      <!-- Product Name -->
      <div class="form-group">
        <label for="name" class="form-label">Product Name <span class="required">*</span></label>
        <input type="text" id="name" name="name" class="form-input" 
               value="<?= htmlspecialchars($formData['name']) ?>" required>
        <?php if (!empty($errors['name'])): ?>
          <span class="error"><?= $errors['name'] ?></span>
        <?php endif; ?>
      </div>
      
      <!-- Category and Supplier -->
      <div class="form-row">
        <div class="form-group">
          <label for="category" class="form-label">Category</label>
          <input type="text" id="category" name="category" class="form-input"
                 value="<?= htmlspecialchars($formData['category']) ?>">
        </div>
        <div class="form-group">
          <label for="supplier" class="form-label">Supplier</label>
          <input type="text" id="supplier" name="supplier" class="form-input"
                 value="<?= htmlspecialchars($formData['supplier']) ?>">
        </div>
      </div>
      
      <!-- Price, Quantity, and Stock Level -->
      <div class="form-row">
        <div class="form-group">
          <label for="price" class="form-label">Price (₱) <span class="required">*</span></label>
          <input type="number" id="price" name="price" class="form-input" 
                 min="0" step="0.01" value="<?= htmlspecialchars($formData['price']) ?>" required>
          <?php if (!empty($errors['price'])): ?>
            <span class="error"><?= $errors['price'] ?></span>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label for="quantity" class="form-label">Quantity</label>
          <input type="number" id="quantity" name="quantity" class="form-input" 
                 min="0" value="<?= htmlspecialchars($formData['quantity']) ?>">
          <?php if (!empty($errors['quantity'])): ?>
            <span class="error"><?= $errors['quantity'] ?></span>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label for="min_stock_level" class="form-label">Min Stock Level</label>
          <input type="number" id="min_stock_level" name="min_stock_level" class="form-input" 
                 min="0" value="<?= htmlspecialchars($formData['min_stock_level']) ?>">
          <?php if (!empty($errors['min_stock_level'])): ?>
            <span class="error"><?= $errors['min_stock_level'] ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Warranty Section -->
      <div class="warranty-section">
        <h3>Warranty Information</h3>
        
        <div class="form-group">
          <label for="warranty_period" class="form-label">Warranty Period</label>
          <input type="text" id="warranty_period" name="warranty_period" class="form-input"
                 value="<?= htmlspecialchars($formData['warranty_period']) ?>"
                 placeholder="e.g., '1 year', '6 months'">
        </div>
        
        <div class="form-group">
          <label for="warranty_terms" class="form-label">Warranty Terms</label>
          <textarea id="warranty_terms" name="warranty_terms" class="form-textarea"
                    placeholder="Detailed warranty conditions..."><?= htmlspecialchars($formData['warranty_terms']) ?></textarea>
        </div>
      </div>
      
      
 <!-- Vehicle Information Section -->
      <div class="warranty-section">
        <h3>Vehicle Information</h3>
        
        <div class="form-group">
          <label for="vin_code" class="form-label">VIN Code</label>
          <input type="text" id="vin_code" name="vin_code" class="form-input"
                 value="<?= htmlspecialchars($formData['vin_code']) ?>"
                 placeholder="17-character vehicle identification number"
                 maxlength="17">
          <?php if (!empty($errors['vin_code'])): ?>
            <span class="error"><?= $errors['vin_code'] ?></span>
          <?php endif; ?>
          <small class="text-muted">Optional - Only for vehicle products</small>
        </div>
      </div>
      
      <button type="submit" class="submit-btn">Add Product</button>
    </form>
  </main>
</body>
</html>