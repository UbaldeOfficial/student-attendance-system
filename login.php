<?php
// login.php - Login processing
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        try {
            $conn = db_connect();
            
            // FIXED: Use two different parameter names
            $sql = "SELECT * FROM users WHERE username = :username OR email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $username); // Same value for both
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // Redirect based on role
                    if ($user['role'] == 'admin') {
                        header('Location: admin.php');
                    } elseif ($user['role'] == 'teacher') {
                        header('Location: teacher.php');
                    } elseif ($user['role'] == 'student') {
                        header('Location: student.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid password';
                }
            } else {
                $error = 'User not found';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Student Attendance System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #2c3e50;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 5px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn-login {
            background: #1abc9c;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            font-weight: bold;
        }
        .btn-login:hover {
            background: #16a085;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #3498db;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .demo-accounts {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        .demo-title {
            color: #2c3e50;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        .demo-item {
            margin-bottom: 8px;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid #3498db;
        }
        .demo-username {
            font-weight: bold;
            color: #2c3e50;
        }
        .demo-password {
            color: #27ae60;
            font-weight: bold;
        }
        .demo-role {
            font-size: 12px;
            color: #7f8c8d;
            float: right;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Student Attendance System</h2>
            <p>Login to your account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       placeholder="Enter username or email">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required 
                       placeholder="Enter password">
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Back to Home</a>
        </div>
        
        <div class="demo-accounts">
            <div class="demo-title">Demo Accounts (Password: 123456)</div>
            
            <div class="demo-item">
                <span class="demo-username">admin</span> 
                <span class="demo-role">(Admin)</span><br>
                <small>Email: admin@school.rw</small>
            </div>
            
            <div class="demo-item">
                <span class="demo-username">teacher1</span>
                <span class="demo-role">(Teacher)</span><br>
                <small>Email: teacher@school.rw</small>
            </div>
            
            <div class="demo-item">
                <span class="demo-username">student1</span>
                <span class="demo-role">(Student)</span><br>
                <small>Email: student@school.rw</small>
            </div>
            
            <div class="demo-item">
                <span class="demo-username">ubaldeofficial</span>
                <span class="demo-role">(Admin)</span><br>
                <small>Email: ubaldeofficial@gmail.com</small>
            </div>
            
            <div style="text-align: center; margin-top: 10px; font-size: 12px; color: #666;">
                All accounts use password: <span class="demo-password">123456</span>
            </div>
        </div>
    </div>
</body>
</html>