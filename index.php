
<?php
// index.php - Home page
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Attendance System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .nav {
            background: #34495e;
            padding: 10px;
            margin-bottom: 20px;
        }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            margin: 0 5px;
            border-radius: 4px;
        }
        .nav a:hover {
            background: #1abc9c;
        }
        .welcome {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .welcome h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 50px auto;
        }
        .login-box h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-primary {
            background: #1abc9c;
        }
        .btn-primary:hover {
            background: #16a085;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            color: #7f8c8d;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Attendance System</h1>
    </div>
    
    <div class="container">
        <?php if (!is_logged_in()): ?>
        <div class="login-box">
            <h3>Login to System</h3>
            <?php echo display_message(); ?>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>Username or Email</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            <p style="text-align:center; margin-top:15px;">
                <a href="#" style="color:#3498db;">Forgot Password?</a>
            </p>
        </div>
        <?php else: ?>
        <div class="nav">
            <a href="index.php">Home</a>
            <a href="dashboard.php">Dashboard</a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="admin.php">Admin Panel</a>
            <?php elseif ($_SESSION['role'] == 'teacher'): ?>
                <a href="teacher.php">Teacher Panel</a>
            <?php elseif ($_SESSION['role'] == 'student'): ?>
                <a href="student.php">Student Panel</a>
            <?php endif; ?>
            <a href="logout.php" style="float:right;">Logout</a>
        </div>
        
        <div class="welcome">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
            <p>Role: <?php echo ucfirst($_SESSION['role']); ?></p>
            <p>You are logged into the Student Attendance System.</p>
            
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <p>As an administrator, you can manage users, courses, and view system reports.</p>
            <?php elseif ($_SESSION['role'] == 'teacher'): ?>
                <p>As a teacher, you can mark attendance, view student records, and manage your courses.</p>
            <?php elseif ($_SESSION['role'] == 'student'): ?>
                <p>As a student, you can view your attendance records and registered courses.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>© 2025 Student Attendance System | Rwanda Polytechnic</p>
    </div>
</body>
</html>