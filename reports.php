<?php
require_once __DIR__ . '/includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] === 'staff') {
    echo "<script>alert('Access denied: Staff accounts cannot view reports.'); window.location.href = 'dashboard.php';</script>";
    exit;
}

// Initialize variables
$start_date = date('Y-m-01'); // First day of current month
$end_date = date('Y-m-d');    // Today
$report_type = 'sales_summary';

// Handle form submission
date_default_timezone_set('Asia/Manila'); // or your local timezone
$end_datetime = $end_date . ' 23:59:59';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'] ?? $start_date;
    $end_date = $_POST['end_date'] ?? $end_date;
    $report_type = $_POST['report_type'] ?? $report_type;
}

// Generate reports based on type
$report_data = [];
$grand_total_sold = 0;
$grand_total_revenue = 0;
$total_value = 0;

try {
    switch ($report_type) {
        case 'sales_summary':
            $stmt = $conn->prepare("
                SELECT 
                    COALESCE(p.name, 'Deleted Product') as product_name,
                    COALESCE(p.product_id, 0) as product_id,
                    SUM(s.quantity_sold) as total_sold,
                    COALESCE(p.price, 0) as price,
                    SUM(s.quantity_sold * COALESCE(p.price, 0)) as total_revenue,
                    COUNT(s.sale_id) as transaction_count
                FROM sales s
                LEFT JOIN products p ON s.product_id = p.product_id
                WHERE s.sale_date BETWEEN ? AND ?
                GROUP BY p.product_id, p.name
                ORDER BY total_revenue DESC
            ");
            
            $stmt->bind_param("ss", $start_date, $end_datetime);
            $stmt->execute();
            $result = $stmt->get_result();
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
            
            // Calculate grand totals
            foreach ($report_data as $row) {
                $grand_total_sold += $row['total_sold'];
                $grand_total_revenue += $row['total_revenue'];
            }
            break;

        case 'inventory_status':
            $stmt = $conn->prepare("
                SELECT 
                    product_id,
                    name,
                    quantity,
                    min_stock_level,
                    price,
                    (quantity * price) as inventory_value,
                    CASE 
                        WHEN quantity = 0 THEN 'Out of Stock'
                        WHEN quantity <= min_stock_level THEN 'Low Stock'
                        ELSE 'In Stock'
                    END as status
                FROM products
                WHERE is_active = TRUE
                ORDER BY status, name
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
            
            // Calculate total inventory value
            foreach ($report_data as $row) {
                $total_value += $row['inventory_value'];
            }
            break;

        case 'alerts_log':
            $stmt = $conn->prepare("
                SELECT 
                    a.alert_id,
                    p.name as product_name,
                    a.message,
                    a.alert_date,
                    CASE 
                        WHEN a.is_resolved THEN 'Resolved'
                        ELSE 'Pending'
                    END as status
                FROM alerts a
                LEFT JOIN products p ON a.product_id = p.product_id
                WHERE a.alert_date BETWEEN ? AND ?
                ORDER BY a.alert_date DESC
            ");
            $end_datetime = $end_date . ' 23:59:59';
            $stmt->bind_param("ss", $start_date, $end_datetime);
            $stmt->execute();
            $result = $stmt->get_result();
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
            break;
    }
} catch (Exception $e) {
    error_log("Report generation error: " . $e->getMessage());
    $error_message = "An error occurred while generating the report.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Avenue - Reports</title>
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

        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
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

        .report-controls {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }

        .report-results {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
        }

        .page-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .page-header h2 {
            color: var(--primary);
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        select, input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: var(--primary-light);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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

        .status-out {
            color: var(--danger);
            font-weight: bold;
        }

        .status-low {
            color: var(--warning);
            font-weight: bold;
        }

        .status-in {
            color: var(--success);
            font-weight: bold;
        }

        .total-row {
            font-weight: bold;
            background-color: rgba(11, 129, 38, 0.1) !important;
        }

        .error-message {
            color: var(--danger);
            background-color: #ffebee;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
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
            
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }

          .btn-print {
            background-color: var(--primary);
            margin-left: 1000px;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        /* Additional styles for date validation */
        .date-validation-message {
            display: block;
            font-size: 0.8rem;
            margin-top: 5px;
            min-height: 1.2rem;
        }
        
        input[type="date"].form-input:invalid {
            border-color: #f44336;
        }
        
        .btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .form-group {
            position: relative;
        }
        
        /* Ensure consistent form element heights */
        select.form-select, 
        input.form-input {
            height: 38px;
            padding: 0 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="brand">
                <img src="autoavelogo.svg" alt="Auto Avenue Logo" class="logo" width="150">
                <h1>Auto Avenue IMS</h1>
            </div>

            <?php if (isset($_SESSION['role'])): ?>
                <div style="background-color: rgba(255, 255, 255, 0.15); padding: 6px 12px; border-radius: 8px; font-size: 0.9rem;">
                    Logged in as: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>
                </div>
            <?php endif; ?>
            
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="products.php">Products</a>
                <a href="reports.php" class="active">Reports</a>
                <a href="sales.php">Sales</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="report-controls">
            <div class="page-header">
                <h2>Generate Report</h2> 
            </div>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="report_type">Report Type</label>
                        <select id="report_type" name="report_type" class="form-select" onchange="updateReportType()">
                            <option value="sales_summary" <?= $report_type == 'sales_summary' ? 'selected' : '' ?>>Sales Summary</option>
                            <option value="inventory_status" <?= $report_type == 'inventory_status' ? 'selected' : '' ?>>Inventory Status</option>
                            <option value="alerts_log" <?= $report_type == 'alerts_log' ? 'selected' : '' ?>>Alerts Log</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" 
                               max="<?= date('Y-m-d') ?>" class="form-input" onchange="validateDateRange()">
                        <small class="date-validation-message"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" 
                               max="<?= date('Y-m-d') ?>" class="form-input" onchange="validateDateRange()">
                        <small class="date-validation-message"></small>
                    </div>
                </div>
                
                <button type="submit" class="btn" id="generate-report-btn">Generate Report</button>        
            </form>
        </div>

        <div class="report-results">
            <div class="page-header">
                <h2>
                    <?= match($report_type) {
                        'sales_summary' => 'Sales Summary Report',
                        'inventory_status' => 'Inventory Status Report',
                        'alerts_log' => 'Alerts Log Report',
                        default => 'Report Results'
                    } ?> 

                    <small>(<?= date('M j, Y', strtotime($start_date)) ?> to <?= date('M j, Y', strtotime($end_date)) ?>)
                    <button type="button" id="print-report-btn" class="btn-print">Print Report</button></small>
                </h2>
            </div>

            <script>
            // Wait for the document to be fully loaded
            document.addEventListener('DOMContentLoaded', function() {
                // Get references to form elements
                const reportTypeSelect = document.getElementById('report_type');
                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');
                const printButton = document.getElementById('print-report-btn');
                const generateButton = document.getElementById('generate-report-btn');
                const form = document.querySelector('form');
                
                // Validate date range function
                function validateDateRange() {
                    const startDate = new Date(startDateInput.value);
                    const endDate = new Date(endDateInput.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    // Reset validation messages
                    document.querySelectorAll('.date-validation-message').forEach(el => {
                        el.textContent = '';
                        el.style.color = '';
                    });
                    
                    let isValid = true;
                    
                    // Validate start date is not in the future
                    if (startDate > today) {
                        startDateInput.nextElementSibling.textContent = 'Start date cannot be in the future';
                        startDateInput.nextElementSibling.style.color = '#f44336';
                        isValid = false;
                    }
                    
                    // Validate end date is not in the future (but allow today)
                    const tomorrow = new Date(today);
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    if (endDate >= tomorrow) {
                        endDateInput.nextElementSibling.textContent = 'End date cannot be in the future';
                        endDateInput.nextElementSibling.style.color = '#f44336';
                        isValid = false;
                    }
                    
                    // Validate start date is not after end date
                    if (startDate > endDate) {
                        startDateInput.nextElementSibling.textContent = 'Start date cannot be after end date';
                        startDateInput.nextElementSibling.style.color = '#f44336';
                        endDateInput.nextElementSibling.textContent = 'End date cannot be before start date';
                        endDateInput.nextElementSibling.style.color = '#f44336';
                        isValid = false;
                    }
                    
                    // Enable/disable the generate button based on validation
                    generateButton.disabled = !isValid;
                    
                    return isValid;
                }
                
                // Add event listeners for date validation
                startDateInput.addEventListener('change', validateDateRange);
                endDateInput.addEventListener('change', validateDateRange);
                
                // Validate on form submission
                form.addEventListener('submit', function(e) {
                    if (!validateDateRange()) {
                        e.preventDefault();
                        alert('Please correct the date range issues before generating the report.');
                    }
                });
                
                // Initial validation
                validateDateRange();
                
                // Add click event listener to the print button
                if (printButton) {
                    printButton.addEventListener('click', function() {
                        // Get current values
                        const reportType = reportTypeSelect ? reportTypeSelect.value : '<?= $report_type ?>';
                        const startDate = startDateInput ? encodeURIComponent(startDateInput.value) : '<?= urlencode($start_date) ?>';
                        const endDate = endDateInput ? encodeURIComponent(endDateInput.value) : '<?= urlencode($end_date) ?>';
                        
                        // Construct URL based on report type
                        let url = 'tcpdf6/examples/';
                        
                        switch(reportType) {
                            case 'sales_summary':
                                url += 'aaarepsales_summary.php';
                                break;
                            case 'inventory_status':
                                url += 'aaarepinventory_status.php';
                                break;
                            case 'alerts_log':
                                url += 'aaarepalert_log.php';
                                break;
                            default:
                                url += 'aaarepsales_summary.php';
                        }
                        
                        // Add date parameters
                        url += '?start_date=' + startDate + '&end_date=' + endDate;
                        
                        // Debug output
                        console.log('Opening URL:', url);
                        
                        // Navigate to the report in a new tab
                        window.open(url, '_blank');
                    });
                }
            });

            // Function to update report type (can be expanded if needed)
            function updateReportType() {
                // This function can be used to show/hide fields based on report type
                console.log('Report type changed');
            }
            </script>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?= $error_message ?>
                </div>
            <?php elseif (!empty($report_data)): ?>
                <?php if ($report_type == 'sales_summary'): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Transactions</th>
                                <th>Units Sold</th>
                                <th>Unit Price</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                                    <td><?= htmlspecialchars($row['transaction_count']) ?></td>
                                    <td><?= htmlspecialchars($row['total_sold']) ?></td>
                                    <td>₱<?= number_format($row['price'], 2) ?></td>
                                    <td>₱<?= number_format($row['total_revenue'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="2">Grand Total</td>
                                <td><?= $grand_total_sold ?></td>
                                <td></td>
                                <td>₱<?= number_format($grand_total_revenue, 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                
                <?php elseif ($report_type == 'inventory_status'): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Current Stock</th>
                                <th>Min Stock Level</th>
                                <th>Unit Price</th>
                                <th>Inventory Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['quantity']) ?></td>
                                    <td><?= htmlspecialchars($row['min_stock_level']) ?></td>
                                    <td>₱<?= number_format($row['price'], 2) ?></td>
                                    <td>₱<?= number_format($row['inventory_value'], 2) ?></td>
                                    <td class="status-<?= strtolower(str_replace(' ', '_', $row['status'])) ?>">
                                        <?= $row['status'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="4">Total Inventory Value</td>
                                <td>₱<?= number_format($total_value, 2) ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                
                <?php elseif ($report_type == 'alerts_log'): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Message</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?= date('M j, Y h:i A', strtotime($row['alert_date'])) ?></td>
                                    <td><?= htmlspecialchars($row['product_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['message']) ?></td>
                                    <td><?= $row['status'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php else: ?>
                <p>No data found for the selected report criteria.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
