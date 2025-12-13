<?php
// profile.php - User Profile
require_once 'config.php';
require_login();

$conn = db_connect();
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Get user data
$sql = "SELECT * FROM users WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch();

// Get student data if student
$student_data = [];
if ($_SESSION['role'] == 'student') {
    $student_sql = "SELECT * FROM students WHERE user_id = :user_id";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bindParam(':user_id', $user_id);
    $student_stmt->execute();
    $student_data = $student_stmt->fetch();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    // For students only
    $department = $_POST['department'] ?? '';
    $year_of_study = $_POST['year_of_study'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if (empty($full_name) || empty($email)) {
        $error = "Full name and email are required";
    } else {
        try {
            $conn->beginTransaction();
            
            // Update user table
            $user_sql = "UPDATE users SET full_name = :full_name, email = :email WHERE id = :id";
            $user_stmt = $conn->prepare($user_sql);
            $user_stmt->bindParam(':full_name', $full_name);
            $user_stmt->bindParam(':email', $email);
            $user_stmt->bindParam(':id', $user_id);
            $user_stmt->execute();
            
            // Update session
            $_SESSION['full_name'] = $full_name;
            
            // Update student table if student
            if ($_SESSION['role'] == 'student' && !empty($student_data)) {
                $student_sql = "UPDATE students SET 
                               department = :department,
                               year_of_study = :year_of_study,
                               phone = :phone
                               WHERE user_id = :user_id";
                $student_stmt = $conn->prepare($student_sql);
                $student_stmt->bindParam(':department', $department);
                $student_stmt->bindParam(':year_of_study', $year_of_study);
                $student_stmt->bindParam(':phone', $phone);
                $student_stmt->bindParam(':user_id', $user_id);
                $student_stmt->execute();
            }
            
            $conn->commit();
            $success = "Profile updated successfully!";
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters";
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = :password WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $success = "Password changed successfully!";
                }
            } catch (PDOException $e) {
                $error = "Error changing password: " . $e->getMessage();
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Student Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; }
        .nav { background: #34495e; padding: 10px; }
        .nav a { color: white; text-decoration: none; padding: 10px 15px; margin: 0 5px; border-radius: 4px; }
        .nav a:hover { background: #1abc9c; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .profile-card { background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1abc9c; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .btn { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb; }
        .profile-info { margin-bottom: 30px; }
        .info-row { display: flex; margin-bottom: 15px; }
        .info-label { width: 150px; color: #666; }
        .info-value { flex: 1; color: #333; font-weight: bold; }
        .tab-buttons { display: flex; margin-bottom: 20px; border-bottom: 1px solid #ddd; }
        .tab-button { padding: 10px 20px; background: none; border: none; cursor: pointer; border-bottom: 3px solid transparent; }
        .tab-button.active { border-bottom-color: #3498db; color: #3498db; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="header">
        <h1>My Profile</h1>
    </div>
    
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
        <a href="profile.php" style="background:#1abc9c;">My Profile</a>
        <a href="logout.php" style="float:right;">Logout</a>
    </div>
    
    <div class="container">
        <h2>Profile Settings</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="profile-info">
            <div class="info-row">
                <div class="info-label">Username:</div>
                <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Role:</div>
                <div class="info-value"><?php echo ucfirst($_SESSION['role']); ?></div>
            </div>
            <?php if ($_SESSION['role'] == 'student' && !empty($student_data)): ?>
                <div class="info-row">
                    <div class="info-label">Student Code:</div>
                    <div class="info-value"><?php echo htmlspecialchars($student_data['student_code']); ?></div>
                </div>
            <?php endif; ?>
            <div class="info-row">
                <div class="info-label">Account Created:</div>
                <div class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
            </div>
        </div>
        
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showTab('personal')">Personal Info</button>
            <button class="tab-button" onclick="showTab('password')">Change Password</button>
        </div>
        
        <!-- Personal Info Tab -->
        <div id="personal-tab" class="tab-content active">
            <div class="profile-card">
                <h3>Update Personal Information</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <?php if ($_SESSION['role'] == 'student'): ?>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <input type="text" id="department" name="department" 
                                   value="<?php echo htmlspecialchars($student_data['department'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="year_of_study">Year of Study</label>
                            <select id="year_of_study" name="year_of_study">
                                <option value="">-- Select Year --</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" 
                                        <?php echo ($student_data['year_of_study'] ?? '') == $i ? 'selected' : ''; ?>>
                                        Year <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($student_data['phone'] ?? ''); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" name="update_profile" class="btn btn-success">Update Profile</button>
                </form>
            </div>
        </div>
        
        <!-- Change Password Tab -->
        <div id="password-tab" class="tab-content">
            <div class="profile-card">
                <h3>Change Password</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <small style="color: #666;">Must be at least 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-success">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activate selected button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>