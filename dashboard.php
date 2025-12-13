
<?php
// dashboard.php - Main dashboard
require_once 'config.php';
require_login();

$conn = db_connect();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Student Attendance System</title>
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
            background: #1abc9c;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1abc9c;
        }
        .stat {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
            text-align: center;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
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
        .recent-activity {
            margin-top: 30px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background: #34495e;
            color: white;
        }
        .table tr:hover {
            background: #f9f9f9;
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
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
            <span style="color: #1abc9c;">(<?php echo ucfirst($role); ?>)</span>
        </div>
    </div>
    
    <div class="nav">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <?php if ($role == 'admin'): ?>
            <a href="admin.php">Admin Panel</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_courses.php">Manage Courses</a>
        <?php elseif ($role == 'teacher'): ?>
            <a href="teacher.php">Teacher Panel</a>
            <a href="mark_attendance.php">Mark Attendance</a>
            <a href="view_attendance.php">View Attendance</a>
        <?php elseif ($role == 'student'): ?>
            <a href="student.php">Student Panel</a>
            <a href="my_attendance.php">My Attendance</a>
            <a href="my_courses.php">My Courses</a>
        <?php endif; ?>
        <a href="profile.php">Profile</a>
        <a href="logout.php" style="float:right;">Logout</a>
    </div>
    
    <div class="container">
        <h2>Dashboard</h2>
        <?php echo display_message(); ?>
        
        <div class="dashboard-cards">
            <?php if ($role == 'admin'): ?>
                <?php
                $users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
                $courses_count = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch()['count'];
                $students_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch()['count'];
                $attendance_count = $conn->query("SELECT COUNT(*) as count FROM attendance")->fetch()['count'];
                ?>
                <div class="card">
                    <h3>Total Users</h3>
                    <div class="stat"><?php echo $users_count; ?></div>
                    <a href="manage_users.php" class="btn">View Users</a>
                </div>
                <div class="card">
                    <h3>Total Courses</h3>
                    <div class="stat"><?php echo $courses_count; ?></div>
                    <a href="manage_courses.php" class="btn">View Courses</a>
                </div>
                <div class="card">
                    <h3>Total Students</h3>
                    <div class="stat"><?php echo $students_count; ?></div>
                </div>
                <div class="card">
                    <h3>Attendance Records</h3>
                    <div class="stat"><?php echo $attendance_count; ?></div>
                </div>
                
            <?php elseif ($role == 'teacher'): ?>
                <?php
                $my_courses = $conn->query("SELECT COUNT(*) as count FROM courses WHERE teacher_id = $user_id")->fetch()['count'];
                $total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch()['count'];
                ?>
                <div class="card">
                    <h3>My Courses</h3>
                    <div class="stat"><?php echo $my_courses; ?></div>
                    <a href="teacher.php" class="btn btn-primary">View Courses</a>
                </div>
                <div class="card">
                    <h3>Total Students</h3>
                    <div class="stat"><?php echo $total_students; ?></div>
                </div>
                
            <?php elseif ($role == 'student'): ?>
                <?php
                $student_id = $conn->query("SELECT student_id FROM students WHERE user_id = $user_id")->fetch()['student_id'];
                $my_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE student_id = $student_id")->fetch()['count'];
                ?>
                <div class="card">
                    <h3>My Attendance</h3>
                    <div class="stat"><?php echo $my_attendance; ?></div>
                    <a href="my_attendance.php" class="btn btn-primary">View Attendance</a>
                </div>
                <div class="card">
                    <h3>My Courses</h3>
                    <div class="stat">2</div>
                    <a href="my_courses.php" class="btn btn-primary">View Courses</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="recent-activity">
            <h3>Recent Activity</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Activity</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Display recent attendance records
                    if ($role == 'teacher') {
                        $sql = "SELECT a.attendance_date, s.student_code, u.full_name, a.status 
                               FROM attendance a 
                               JOIN students s ON a.student_id = s.student_id 
                               JOIN users u ON s.user_id = u.id 
                               WHERE a.marked_by = :user_id 
                               ORDER BY a.marked_at DESC 
                               LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        $activities = $stmt->fetchAll();
                        
                        foreach ($activities as $activity) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($activity['attendance_date']) . "</td>";
                            echo "<td>Attendance marked</td>";
                            echo "<td>" . htmlspecialchars($activity['full_name']) . " (" . $activity['student_code'] . ") - " . ucfirst($activity['status']) . "</td>";
                            echo "</tr>";
                        }
                    } elseif ($role == 'student') {
                        $sql = "SELECT a.attendance_date, c.course_name, a.status 
                               FROM attendance a 
                               JOIN courses c ON a.course_id = c.course_id 
                               WHERE a.student_id = :student_id 
                               ORDER BY a.attendance_date DESC 
                               LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':student_id', $student_id);
                        $stmt->execute();
                        $activities = $stmt->fetchAll();
                        
                        foreach ($activities as $activity) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($activity['attendance_date']) . "</td>";
                            echo "<td>Attendance recorded</td>";
                            echo "<td>" . htmlspecialchars($activity['course_name']) . " - " . ucfirst($activity['status']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No recent activity</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="footer">
        <p>© 2025 Student Attendance System | Last login: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
</body>
</html>