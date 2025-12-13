<?php
// admin.php - Admin Panel
require_once 'config.php';
check_role(['admin']);

$conn = db_connect();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Student Attendance System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav {
            background: #34495e;
            padding: 10px;
        }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            margin: 0 5px;
            border-radius: 4px;
        }
        .nav a:hover {
            background: #e74c3c;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .page-title {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e74c3c;
        }
        .admin-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .admin-card {
            background: white;
            padding: 25px;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .admin-card:hover {
            transform: translateY(-5px);
        }
        .admin-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .admin-card h3:before {
            content: "✓";
            background: #2ecc71;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        .card-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .card-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
        }
        .btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
            font-weight: bold;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-success {
            background: #2ecc71;
        }
        .btn-success:hover {
            background: #27ae60;
        }
        .recent-users {
            margin-top: 40px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background: #34495e;
            color: white;
        }
        .table tr:hover {
            background: #f9f9f9;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-admin { background: #e74c3c; color: white; }
        .badge-teacher { background: #3498db; color: white; }
        .badge-student { background: #2ecc71; color: white; }
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            color: #7f8c8d;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Control Panel</h1>
        <div>
            Admin: <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        </div>
    </div>
    
    <div class="nav">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="admin.php" style="background:#e74c3c;">Admin Panel</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_courses.php">Manage Courses</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php" style="float:right;">Logout</a>
    </div>
    
    <div class="container">
        <h2 class="page-title">Administrator Dashboard</h2>
        <?php echo display_message(); ?>
        
        <div class="admin-cards">
            <div class="admin-card">
                <h3>User Management</h3>
                <p>Create, edit, and delete user accounts. Assign roles and manage permissions.</p>
                <?php
                $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
                $admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch()['count'];
                $teachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='teacher'")->fetch()['count'];
                $students = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='student'")->fetch()['count'];
                ?>
                <div class="card-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_users; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $admins; ?></div>
                        <div class="stat-label">Admins</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $teachers; ?></div>
                        <div class="stat-label">Teachers</div>
                    </div>
                </div>
                <a href="manage_users.php" class="btn btn-success">Manage Users →</a>
            </div>
            
            <div class="admin-card">
                <h3>Course Management</h3>
                <p>Add, edit, and remove courses. Assign teachers and manage schedules.</p>
                <?php
                $total_courses = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch()['count'];
                $active_courses = $conn->query("SELECT COUNT(DISTINCT course_id) as count FROM attendance WHERE attendance_date = CURDATE()")->fetch()['count'];
                ?>
                <div class="card-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_courses; ?></div>
                        <div class="stat-label">Total Courses</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $active_courses; ?></div>
                        <div class="stat-label">Active Today</div>
                    </div>
                </div>
                <a href="manage_courses.php" class="btn btn-success">Manage Courses →</a>
            </div>
            
            <div class="admin-card">
                <h3>Attendance Reports</h3>
                <p>View attendance statistics, generate reports, and export data.</p>
                <?php
                $today_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE attendance_date = CURDATE()")->fetch()['count'];
                $total_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance")->fetch()['count'];
                ?>
                <div class="card-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $today_attendance; ?></div>
                        <div class="stat-label">Today</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_attendance; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
                <a href="reports.php" class="btn btn-success">View Reports →</a>
            </div>
        </div>
        
        <div class="recent-users">
            <h3>Recent Users</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT 10";
                    $stmt = $conn->query($sql);
                    while ($user = $stmt->fetch()) {
                        $role_class = "badge-" . $user['role'];
                        echo "<tr>";
                        echo "<td>" . $user['id'] . "</td>";
                        echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                        echo "<td><span class='badge $role_class'>" . ucfirst($user['role']) . "</span></td>";
                        echo "<td>" . date('M d, Y', strtotime($user['created_at'])) . "</td>";
                        echo "<td>";
                        echo "<a href='edit_user.php?id=" . $user['id'] . "' class='btn' style='padding:5px 10px; font-size:12px;'>Edit</a> ";
                        if ($user['id'] != $_SESSION['user_id']) {
                            echo "<a href='delete_user.php?id=" . $user['id'] . "' class='btn btn-danger' style='padding:5px 10px; font-size:12px;' onclick='return confirm(\"Delete this user?\")'>Delete</a>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: white; border-radius: 5px;">
            <h3>Quick Actions</h3>
            <div style="margin-top: 15px;">
                <a href="register.php" class="btn">Add New User</a>
                <a href="add_course.php" class="btn">Add New Course</a>
                <a href="backup.php" class="btn btn-success">Backup Database</a>
                <a href="system_logs.php" class="btn">View System Logs</a>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>© 2025 Student Attendance System | Admin Panel | <?php echo date('l, F j, Y'); ?></p>
    </div>
</body>
</html>