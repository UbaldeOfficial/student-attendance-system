<?php
// manage_users.php - Manage Users (Admin only)
require_once 'config.php';
check_role(['admin']);

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? 0;

$error = '';
$success = '';

// Handle different actions
switch ($action) {
    case 'add':
    case 'edit':
        // Form processing for add/edit
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $full_name = trim($_POST['full_name']);
            $role = $_POST['role'];
            
            // Validation
            if (empty($username) || empty($email) || empty($full_name) || empty($role)) {
                $error = "All fields are required";
            } else {
                try {
                    if ($action == 'add') {
                        // Check if username or email exists
                        $check_sql = "SELECT id FROM users WHERE username = :username OR email = :email";
                        $check_stmt = $conn->prepare($check_sql);
                        $check_stmt->bindParam(':username', $username);
                        $check_stmt->bindParam(':email', $email);
                        $check_stmt->execute();
                        
                        if ($check_stmt->rowCount() > 0) {
                            $error = "Username or email already exists";
                        } else {
                            // Hash password if provided
                            $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : password_hash('password123', PASSWORD_DEFAULT);
                            
                            $sql = "INSERT INTO users (username, email, password, full_name, role) 
                                   VALUES (:username, :email, :password, :full_name, :role)";
                            $stmt = $conn->prepare($sql);
                        }
                    } else {
                        // Edit existing user
                        $sql = "UPDATE users SET 
                               username = :username,
                               email = :email,
                               full_name = :full_name,
                               role = :role";
                        
                        // Add password update if provided
                        if (!empty($password)) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $sql .= ", password = :password";
                        }
                        
                        $sql .= " WHERE id = :id";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':id', $user_id);
                    }
                    
                    if (empty($error)) {
                        $stmt->bindParam(':username', $username);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':full_name', $full_name);
                        $stmt->bindParam(':role', $role);
                        
                        if ($action == 'add' || !empty($password)) {
                            $stmt->bindParam(':password', $hashed_password);
                        }
                        
                        if ($stmt->execute()) {
                            $success = $action == 'add' ? "User added successfully!" : "User updated successfully!";
                            
                            // If adding a student, create student record
                            if ($action == 'add' && $role == 'student') {
                                $new_user_id = $conn->lastInsertId();
                                $student_code = 'STU' . str_pad($new_user_id, 4, '0', STR_PAD_LEFT);
                                
                                $student_sql = "INSERT INTO students (user_id, student_code) VALUES (:user_id, :student_code)";
                                $student_stmt = $conn->prepare($student_sql);
                                $student_stmt->bindParam(':user_id', $new_user_id);
                                $student_stmt->bindParam(':student_code', $student_code);
                                $student_stmt->execute();
                            }
                            
                            if ($action == 'edit') {
                                // Redirect to list after edit
                                header('Location: manage_users.php?message=' . urlencode($success));
                                exit();
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
        
        // Get user data for editing
        $user_data = [];
        if ($action == 'edit' && $user_id > 0) {
            try {
                $sql = "SELECT * FROM users WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $user_data = $stmt->fetch();
            } catch (PDOException $e) {
                $error = "Error loading user data: " . $e->getMessage();
            }
        }
        
        // Display add/edit form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php echo $action == 'add' ? 'Add User' : 'Edit User'; ?> - Student Attendance System</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; background: #f5f5f5; }
                .header { background: #2c3e50; color: white; padding: 20px; }
                .container { max-width: 800px; margin: 0 auto; padding: 20px; }
                .card { background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                h2 { color: #2c3e50; margin-bottom: 20px; }
                .form-group { margin-bottom: 20px; }
                label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
                input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
                .btn { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
                .btn:hover { background: #2980b9; }
                .btn-success { background: #2ecc71; }
                .btn-success:hover { background: #27ae60; }
                .btn-danger { background: #e74c3c; }
                .btn-danger:hover { background: #c0392b; }
                .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
                .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
                .back-link { margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo $action == 'add' ? 'Add New User' : 'Edit User'; ?></h1>
            </div>
            
            <div class="container">
                <div class="back-link">
                    <a href="manage_users.php" class="btn">← Back to Users List</a>
                </div>
                
                <div class="card">
                    <?php if ($error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ($user_data['username'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ($user_data['email'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ($user_data['full_name'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role *</label>
                            <select id="role" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') || (isset($user_data['role']) && $user_data['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] == 'teacher') || (isset($user_data['role']) && $user_data['role'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') || (isset($user_data['role']) && $user_data['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">
                                <?php echo $action == 'add' ? 'Password *' : 'Password (Leave blank to keep current)'; ?>
                            </label>
                            <input type="password" id="password" name="password" 
                                   <?php echo $action == 'add' ? 'required' : ''; ?>>
                            <?php if ($action == 'add'): ?>
                                <small style="color: #666;">Default password will be 'password123' if not specified</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">
                                <?php echo $action == 'add' ? 'Add User' : 'Update User'; ?>
                            </button>
                            <a href="manage_users.php" class="btn btn-danger">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
        break;
        
    case 'delete':
        // Delete user
        if ($user_id > 0 && $user_id != $_SESSION['user_id']) {
            try {
                $sql = "DELETE FROM users WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $success = "User deleted successfully!";
                }
            } catch (PDOException $e) {
                $error = "Error deleting user: " . $e->getMessage();
            }
        } elseif ($user_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account!";
        }
        // Fall through to list view
        
    default:
    case 'list':
        // Display users list
        try {
            $sql = "SELECT * FROM users ORDER BY role, username";
            $stmt = $conn->query($sql);
            $users = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error = "Error loading users: " . $e->getMessage();
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Manage Users - Student Attendance System</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; background: #f5f5f5; }
                .header { background: #2c3e50; color: white; padding: 20px; }
                .nav { background: #34495e; padding: 10px; }
                .nav a { color: white; text-decoration: none; padding: 10px 15px; margin: 0 5px; border-radius: 4px; }
                .nav a:hover { background: #e74c3c; }
                .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
                .page-title { color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e74c3c; }
                .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
                .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
                .table { width: 100%; border-collapse: collapse; background: white; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .table th, .table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
                .table th { background: #34495e; color: white; }
                .table tr:hover { background: #f9f9f9; }
                .btn { background: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-block; }
                .btn:hover { background: #2980b9; }
                .btn-success { background: #2ecc71; }
                .btn-success:hover { background: #27ae60; }
                .btn-danger { background: #e74c3c; }
                .btn-danger:hover { background: #c0392b; }
                .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
                .badge-admin { background: #e74c3c; color: white; }
                .badge-teacher { background: #3498db; color: white; }
                .badge-student { background: #2ecc71; color: white; }
                .action-buttons { display: flex; gap: 5px; }
                .footer { text-align: center; padding: 20px; margin-top: 30px; color: #7f8c8d; border-top: 1px solid #eee; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Manage Users</h1>
            </div>
            
            <div class="nav">
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="admin.php">Admin Panel</a>
                <a href="manage_users.php" style="background:#e74c3c;">Manage Users</a>
                <a href="manage_courses.php">Manage Courses</a>
                <a href="logout.php" style="float:right;">Logout</a>
            </div>
            
            <div class="container">
                <h2 class="page-title">User Management</h2>
                
                <?php if (isset($_GET['message'])): ?>
                    <div class="success"><?php echo htmlspecialchars($_GET['message']); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <div style="margin-bottom: 20px;">
                    <a href="manage_users.php?action=add" class="btn btn-success">+ Add New User</a>
                    <span style="float:right; color:#666;">Total Users: <?php echo count($users); ?></span>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $role_class = "badge-" . $user['role'];
                        ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><span class="badge <?php echo $role_class; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td class="action-buttons">
                                <a href="manage_users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn">Edit</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="manage_users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">Delete</a>
                                <?php else: ?>
                                    <span class="btn" style="background:#95a5a6; cursor:not-allowed;">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 30px; background: white; padding: 20px; border-radius: 5px;">
                    <h3>User Roles Information</h3>
                    <p><strong>Admin:</strong> Full system access, can manage users, courses, and view all reports.</p>
                    <p><strong>Teacher:</strong> Can mark attendance, view student records, and manage assigned courses.</p>
                    <p><strong>Student:</strong> Can view their own attendance records and registered courses.</p>
                </div>
            </div>
            
            <div class="footer">
                <p>© 2025 Student Attendance System | User Management</p>
            </div>
        </body>
        </html>
        <?php
        break;
}
?>