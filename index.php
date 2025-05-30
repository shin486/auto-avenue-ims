<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Avenue IMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            display: flex;
            min-height: 100vh;
            margin: 0;
            color: var(--text-color);
            background-color: var(--bg-color);
        }
        
        .logo-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background: linear-gradient(rgba(0,74,173,0.1), rgba(0,74,173,0.1));
            position: relative;
            min-height: 100vh;
            width: 50%;
        }
        
        .logo {
            width: 80%;
            max-height: 70vh;
            object-fit: contain;
            margin-bottom: 2rem;
        }
        
        .logo-section h1 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-top: 1rem;
        }
        
        .login-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            width: 50%;
        }
        
        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            transition: all 0.3s ease;
        }
        
        .login-container:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
        }
        
        .login-header h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: var(--light-gray);
            padding-left: 3rem;
        }
        
        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 74, 173, 0.2);
            background-color: white;
        }
        
        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.2rem;
            transition: all 0.3s;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
            text-transform: uppercase;
        }
        
        .btn-login:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        

        
        .register-link {
            margin-top: 1.5rem;
            text-align: center;
        }
        
        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        
        .register-link a:hover {
            background-color: rgba(0, 74, 173, 0.1);
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .logo-section, .login-section {
                width: 100%;
                min-height: auto;
            }
            
            .logo {
                width: 60%;
                max-height: 40vh;
            }
            
            .logo-section h1 {
                font-size: 2rem;
            }
            
            .login-container {
                padding: 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="logo-section">
        <img src="autoavelogo.svg" alt="Auto Avenue Logo" class="logo">
        <h1>Auto Avenue IMS</h1>
    </div>
    
    <div class="login-section">
        <div class="login-container">
            <div class="login-header">
                <h1>Welcome</h1>
                <p>Sign in to your account</p>
            </div>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                
                <button type="submit" class="btn-login">LOGIN</button>
            </form>
            
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Input field focus effects
        document.querySelectorAll('.form-group input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('i').style.color = 'var(--primary-dark)';
                this.parentElement.querySelector('i').style.transform = 'translateY(-50%) scale(1.2)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('i').style.color = 'var(--primary)';
                this.parentElement.querySelector('i').style.transform = 'translateY(-50%) scale(1)';
            });
        });
        
        // Button ripple effect
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function(e) {
                const x = e.clientX - e.target.getBoundingClientRect().left;
                const y = e.clientY - e.target.getBoundingClientRect().top;
                
                const ripple = document.createElement('span');
                ripple.style.left = ${x}px;
                ripple.style.top = ${y}px;
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 1000);
            });
        });
    </script>
</body>
</html>