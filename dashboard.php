  <?php
  // Start session and check authentication
  require_once __DIR__ . '/includes/config.php';

  if (!isset($_SESSION['user_id'])) {
      header("Location: login.php");
      exit();
  }

  // Database connection
  require_once __DIR__ . '/includes/config.php';

  // Get low stock items
  $low_stock_query = "SELECT * FROM products WHERE quantity < min_stock_level";
  $low_stock_result = $conn->query($low_stock_query);

  // Get total products count
  $total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];

  // Get total sales count
  $total_sales = $conn->query("SELECT COUNT(*) as count FROM sales")->fetch_assoc()['count'];

  // Get total inventory value
  $inventory_value = $conn->query("SELECT SUM(quantity * price) as value FROM products")->fetch_assoc()['value'];

  // Pagination setup
$items_per_page = 5;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total sales count for pagination
$total_sales_count = $conn->query("SELECT COUNT(*) as count FROM sales")->fetch_assoc()['count'];
$total_pages = ceil($total_sales_count / $items_per_page);
 
// Fetch paginated recent sales
$recent_sales = $conn->query("SELECT s.sale_id, p.name, s.quantity_sold, p.price, s.sale_date 
                             FROM sales s JOIN products p ON s.product_id = p.product_id 
                             ORDER BY s.sale_date DESC 
                             LIMIT $items_per_page OFFSET $offset");

                              
  ?>

  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Avenue - Dashboard</title>
    
    <style>
      :root {
        --primary: rgb(0, 0, 0);
        --primary-light: rgb(66, 169, 111);
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
        padding: 10px 0;
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
        max-width: 1200px;
        margin: 30px auto;
      }

      .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
      }

      .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        padding: 20px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }

      .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      }

      .card h3 {
        color: var(--primary);
        margin-bottom: 10px;
        font-size: 1.1rem;
      }

      .card .value {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 5px;
      }

      .card .description {
        color: var(--text-light);
        font-size: 0.9rem;
      }

      .total-products .value { color: var(--primary); }
      .total-sales .value { color: var(--success); }
      .inventory-value .value { color: var(--warning); }
      .low-stock .value { color: var(--danger); }

      .section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        padding: 20px;
        margin-bottom: 20px;
      }

      .section h2 {
        color: var(--primary);
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
      }

      table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
      }

      th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
      }

      th {
        background-color: var(--primary);
        color: white;
        font-weight: 500;
      }

      tr:nth-child(even) {
        background-color: #f9f9f9;
      }

      tr:hover {
        background-color: #f1f1f1;
      }

      .alert-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
        background-color: var(--danger);
        color: white;
      }

      .low-stock-item {
        color: var(--danger);
        font-weight: bold;
      }

      @media (max-width: 768px) {
        .container {
          flex-direction: column;
          align-items: flex-start;
        }
        
        nav {
          margin-top: 15px;
          width: 100%;
        }
        
        nav a {
          margin: 0 10px 0 0;
          display: inline-block;
          padding: 8px 0;
        }
        
        table {
          display: block;
          overflow-x: auto;
        }
        .logo {
        position: absolute;
        top: 10px;
        left: 10px;
      }
      .brand {
    display: flex;
    align-items: center;
    gap: 15px;
  }

      }
    </style>
  </head>
  <body>
    <header>
      <div class="container">
        <div style="display: flex; align-items: center; gap: 15px;">
    <img src="autoavelogo.svg" alt="Auto Avenue Logo" class="logo" width="150">
    <h1>Auto Avenue IMS</h1>
  </div>

  <?php if (isset($_SESSION['role'])): ?>
    <div style="background-color: rgba(255, 255, 255, 0.15); padding: 6px 12px; border-radius: 8px; font-size: 0.9rem;">
      Logged in as: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>
    </div>
  <?php endif; ?>
        <nav>
          <a href="dashboard.php" class="active">Dashboard</a>
          <a href="products.php">Products</a>
          <a href="reports.php">Reports</a>
          <a href="sales.php">Sales</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="main-content">
      <div class="dashboard-grid">
        <div class="card total-products">
          <h3>Total Products</h3>
          <div class="value"><?= number_format($total_products) ?></div>
          <div class="description">All products in inventory</div>
        </div>
        
        <div class="card total-sales">
          <h3>Total Sales</h3>
          <div class="value"><?= number_format($total_sales) ?></div>
          <div class="description">All-time sales transactions</div>
        </div>
        
        <div class="card inventory-value">
          <h3>Inventory Value</h3>
          <div class="value">₱<?= number_format($inventory_value, 2) ?></div>
          <div class="description">Total value of inventory</div>
        </div>
        
        <div class="card low-stock">
          <h3>Low Stock Items</h3>
          <div class="value"><?= $low_stock_result->num_rows ?></div>
          <div class="description">Products below minimum level</div>
        </div>
      </div>

      <div class="section">
    <h2>Low Stock Alerts <span class="alert-badge"><?= $low_stock_result->num_rows ?></span></h2>
    <?php if ($low_stock_result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Current Stock</th>
            <th>Minimum Level</th>
            <th>Category</th>
            <th>Supplier</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $low_stock_result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['product_id'] ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td class="low-stock-item"><?= $row['quantity'] ?></td>
              <td><?= $row['min_stock_level'] ?></td>
              <td><?= htmlspecialchars($row['supplier']) ?></td>
              <td><?= htmlspecialchars($row['category']) ?></td>
              
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <form action="purchase_order.php" method="get" style="margin-top: 15px;">
        <button type="submit" style="
          background-color: var(--primary);
          color: white;
          border: none;
          padding: 10px 18px;
          border-radius: 6px;
          font-size: 1rem;
          cursor: pointer;
          transition: background-color 0.3s ease;
        " 
        onmouseover="this.style.backgroundColor='var(--primary-light)';" 
        onmouseout="this.style.backgroundColor='var(--primary)';">
          Generate Purchase Order
        </button>
      </form>

    <?php else: ?>
      <p>No low stock items at this time.</p>

      <form action="purchase_order.php" method="get" style="margin-top: 15px;">
        <button type="submit" style="
          background-color: var(--primary);
          color: white;
          border: none;
          padding: 10px 18px;
          border-radius: 6px;
          font-size: 1rem;
          cursor: pointer;
          transition: background-color 0.3s ease;
        " 
        onmouseover="this.style.backgroundColor='var(--primary-light)';" 
        onmouseout="this.style.backgroundColor='var(--primary)';">
          Generate Purchase Order
        </button>
      </form>

    <?php endif; ?>
  </div>


      <div class="section">
  <h2>Recent Sales</h2>
  <?php if ($recent_sales->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Sale ID</th>
          <th>Product</th>
          <th>Quantity</th>
          <th>Unit Price</th>
          <th>Total</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($sale = $recent_sales->fetch_assoc()):
          $total_sale = $sale['quantity_sold'] * $sale['price'];
          $highlight_class = ($total_sale > 10000) ? 'highlight-high-sale' : '';
        ?>
          <tr class="<?= $highlight_class ?>">
            <td><?= $sale['sale_id'] ?></td>
            <td><?= htmlspecialchars($sale['name']) ?></td>
            <td><?= $sale['quantity_sold'] ?></td>
            <td>₱<?= number_format($sale['price'], 2) ?></td>
            <td>₱<?= number_format($total_sale, 2) ?></td>
            <td><?= date('M j, Y h:i A', strtotime($sale['sale_date'])) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <!-- Pagination Links -->
    <div style="margin-top: 15px; text-align: center;">
      <?php if ($total_pages > 1): ?>
        <?php if ($current_page > 1): ?>
          <a href="?page=<?= $current_page - 1 ?>" style="margin-right: 10px;">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($page = 1; $page <= $total_pages; $page++): ?>
          <?php if ($page == $current_page): ?>
            <strong style="margin: 0 5px;"><?= $page ?></strong>
          <?php else: ?>
            <a href="?page=<?= $page ?>" style="margin: 0 5px;"><?= $page ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
          <a href="?page=<?= $current_page + 1 ?>" style="margin-left: 10px;">Next &raquo;</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <p>No recent sales found.</p>
  <?php endif; ?>
</div>

    </main>
  </body>
  </html>