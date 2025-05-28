<?php
// Database connection and processing logic at the top
include 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Validate passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords don't match";
    } else {
        // Check if username exists
        $check_sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert new user
            $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sss", $username, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
                // Clear form
                $_POST = array();
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Auto Avenue - Register</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    /* Fallback styles if CSS file isn't loading */
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 100%;
      max-width: 400px;
      margin: 50px auto;
      padding: 20px;
    }
    .card {
      background: white;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-control {
      width: 100%;
      padding: 8px;
      box-sizing: border-box;
      margin-top: 5px;
    }
    .btn-primary {
      background: #007bff;
      color: white;
      border: none;
      padding: 10px;
      width: 100%;
      cursor: pointer;
      margin-top: 10px;
    }
    .error { color: red; text-align: center; margin: 10px 0; }
    .success { color: green; text-align: center; margin: 10px 0; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2 style="text-align: center; color: #007bff;">Create Account</h2>
      
      <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" class="form-control" 
                 value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" class="form-control" required>
            <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
            <option value="manager" <?php echo (isset($_POST['role']) && $_POST['role'] === 'manager') ? 'selected' : ''; ?>>Manager</option>
          </select>
        </div>
        <button type="submit" class="btn-primary">Register</button>
      </form>
      <div style="text-align: center; margin-top: 15px;">
        <p>Already have an account? <a href="index.php">Login here</a></p>
      </div>
    </div>
  </div>
</body>
</html>