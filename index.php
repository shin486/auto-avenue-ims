<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Avenue IMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary:rgb(11, 161, 66);
            --primary-light:rgb(46, 176, 77);
            --primary-dark:rgb(2, 163, 115);
            --primary-extra-light: #eff6ff;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --bg-color: #f8fafc;
            --text-color: #1e293b;
            --text-light: #64748b;
            --light-gray: #f1f5f9;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            min-height: 100vh;
            margin: 0;
            color: var(--text-color);
            background-color: var(--bg-color);
            line-height: 1.5;
        }
        
        .logo-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            background: linear-gradient(135deg, var(--primary-extra-light), white);
            position: relative;
            min-height: 100vh;
            width: 50%;
            overflow: hidden;
        }
        
        .logo-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            z-index: 0;
        }
        
        .logo {
            width: 30%;
            max-width: 400px;
            max-height: 70vh;
            object-fit: contain;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
            transition: transform 0.5s ease;
        }
        
        .logo:hover {
            transform: scale(1.03);
        }
        
        .logo-section h1 {
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-top: 1rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .logo-section p {
            color: var(--text-light);
            max-width: 80%;
            text-align: center;
            margin-top: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .login-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            width: 50%;
            position: relative;
        }
        
        .login-container {
            background: white;
            padding: 3rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 450px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            border: 1px solid var(--border-color);
        }
        
        .login-container:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: translateY(-2px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
            color: var(--primary-dark);
        }
        
        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .login-header p {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.3s;
            background-color: white;
            color: var(--text-color);
        }
        
        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
        
        .form-group i {
            position: absolute;
            left: 1rem;
            top: 65%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus + i {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .btn-login:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        .register-link {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.95rem;
        }
        
        .register-link p {
            color: var(--text-light);
        }
        
        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            margin-left: 0.25rem;
        }
        
        .register-link a:hover {
            background-color: rgba(37, 99, 235, 0.1);
            text-decoration: underline;
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.1);
            opacity: 0.8;
        }
        
        .shape:nth-child(1) {
            width: 200px;
            height: 200px;
            top: -50px;
            right: -50px;
        }
        
        .shape:nth-child(2) {
            width: 150px;
            height: 150px;
            bottom: 20%;
            left: -50px;
        }
        
        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            top: 30%;
            right: 20%;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 1;
            }
            20% {
                transform: scale(25, 25);
                opacity: 1;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }
        
        @media (max-width: 1024px) {
            .logo-section h1 {
                font-size: 2rem;
            }
            
            .login-container {
                padding: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .logo-section, .login-section {
                width: 100%;
                min-height: auto;
                padding: 2rem 1.5rem;
            }
            
            .logo-section {
                padding-top: 3rem;
                padding-bottom: 2rem;
            }
            
            .logo {
                width: 60%;
                max-height: 40vh;
                max-width: 300px;
            }
            
            .logo-section h1 {
                font-size: 1.8rem;
            }
            
            .login-container {
                padding: 1.75rem;
                max-width: 500px;
            }
            
            .login-header h1 {
                font-size: 1.75rem;
            }
            
            .logo-section::before {
                top: -30%;
                right: -30%;
                width: 150%;
                height: 150%;
            }
        }
        
        @media (max-width: 480px) {
            .logo {
                width: 70%;
            }
            
            .login-container {
                padding: 1.5rem;
            }
            
            .form-group input {
                padding: 0.875rem 0.875rem 0.875rem 2.75rem;
            }
            
            .form-group i {
                left: 0.875rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="logo-section">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        <img src="images/logo2.png" alt="Auto Avenue Logo" class="logo">
        <h1>Auto Avenue IMS</h1>
        <p>Inventory Management System</p>
    </div>
    
    <div class="login-section">
        <div class="login-container">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Sign in to access your dashboard</p>
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
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> LOGIN
                </button>
            </form>
            
            <div class="register-link">
                <p>Don't have an account?<a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Enhanced input field interactions
        document.querySelectorAll('.form-group input').forEach(input => {
            // Add focus class to parent when input is focused
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
            
            // Add filled class when input has value
            input.addEventListener('input', function() {
                if (this.value) {
                    this.parentElement.classList.add('filled');
                } else {
                    this.parentElement.classList.remove('filled');
                }
            });
        });
        
        // Add floating shapes animation
        document.addEventListener('DOMContentLoaded', function() {
            const shapes = document.querySelectorAll('.shape');
            
            shapes.forEach((shape, index) => {
                // Randomize initial position and size slightly
                const size = Math.random() * 50 + 100;
                shape.style.width = `${size}px`;
                shape.style.height = `${size}px`;
                
                // Animate shapes
                if (index % 2 === 0) {
                    animateShape(shape, 15);
                } else {
                    animateShape(shape, 20);
                }
            });
            
            function animateShape(element, duration) {
                let start = null;
                const initialY = Math.random() * 20 - 10;
                const initialX = Math.random() * 20 - 10;
                
                function step(timestamp) {
                    if (!start) start = timestamp;
                    const progress = (timestamp - start) / 1000;
                    
                    const y = initialY + Math.sin(progress) * 10;
                    const x = initialX + Math.cos(progress) * 10;
                    
                    element.style.transform = `translate(${x}px, ${y}px)`;
                    
                    if (progress < duration) {
                        window.requestAnimationFrame(step);
                    } else {
                        start = null;
                        window.requestAnimationFrame(step);
                    }
                }
                
                window.requestAnimationFrame(step);
            }
        });
    </script>
</body>
</html>