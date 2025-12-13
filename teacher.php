<?php
// teacher.php - Teacher Panel
require_once 'config.php';
check_role(['teacher']);

$conn = db_connect();
$teacher_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Panel - Student Attendance System</title>
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
            background: #3498db;
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
            background: #2980b9;
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
            border-bottom: 2px solid #3498db;
        }
        .teacher-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .teacher-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .teacher-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .teacher-card h3:before {
            content: "📚";
            margin-right: 10px;
            font-size: 20px;
        }
        .today-attendance {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 14px;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-primary {
            background: #2ecc71;
        }
        .btn-primary:hover {
            background: #27ae60;
        }
        .btn-mark {
            background: #e74c3c;
            padding: 10px 20px;
            font-weight: bold;
        }
        .btn-mark:hover {
            background: #c0392b;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th, .table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background: #3498db;
            color: white;
        }
        .status-present { color: #27ae60; font-weight: bold; }
        .status-absent { color: #e74c3c; font-weight: bold; }
        .status-late { color: #f39c12; font-weight: bold; }
        .status-excused { color: #9b59b6; font-weight: bold; }
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
        <h1>Teacher Panel</h1>
        <div>
            Teacher: <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        </div>
    </div>
    
    <div class="nav">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="teacher.php" style="background:#2980b9;">Teacher Panel</a>
        <a href="mark_attendance.php">Mark Attendance</a>
        <a href="view_attendance.php">View Attendance</a>
        <a href="my_courses.php">My Courses</a>
        <a href="logout.php" style="float:right;">Logout</a>
    </div>
    
    <div class="container">
        <h2 class="page-title">Teacher Dashboard</h2>
        <?php echo display_message(); ?>
        
        <div class="teacher-cards">
            <?php
            // Get teacher's courses
            $sql = "SELECT * FROM courses WHERE teacher_id = :teacher_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->execute();
            $courses = $stmt->fetchAll();
            
            foreach ($courses as $course) {
                // Count students for this course
                $student_count = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
                
                // Get today's attendance for this course
                $today_sql = "SELECT COUNT(*) as total, 
                             SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                             SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                             FROM attendance 
                             WHERE course_id = :course_id AND attendance_date = CURDATE()";
                $today_stmt = $conn->prepare($today_sql);
                $today_stmt->bindParam(':course_id', $course['course_id']);
                $today_stmt->execute();
                $today_stats = $today_stmt->fetch();
                ?>
                
                <div class="teacher-card">
                    <h3><?php echo htmlspecialchars($course['course_code']); ?></h3>
                    <p><strong><?php echo htmlspecialchars($course['course_name']); ?></strong></p>
                    <p>Schedule: <?php echo htmlspecialchars($course['schedule_day'] . ' at ' . $course['schedule_time']); ?></p>
                    <p>Total Students: <?php echo $student_count; ?></p>
                    
                    <?php if ($today_stats['total'] > 0): ?>
                        <p>Today: <?php echo $today_stats['present']; ?> present, <?php echo $today_stats['absent']; ?> absent</p>
                        <a href="view_attendance.php?course_id=<?php echo $course['course_id']; ?>&date=<?php echo date('Y-m-d'); ?>" class="btn">View Today</a>
                    <?php else: ?>
                        <p>No attendance marked today</p>
                        <a href="mark_attendance.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-mark">Mark Attendance</a>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
        
        <div class="today-attendance">
            <h3>Today's Schedule</h3>
            <?php
            $today = date('l');
            $sql = "SELECT * FROM courses WHERE teacher_id = :teacher_id AND schedule_day = :today ORDER BY schedule_time";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $today_courses = $stmt->fetchAll();
            
            if (count($today_courses) > 0):
            ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($today_courses as $course): 
                            // Check if attendance marked today
                            $check_sql = "SELECT COUNT(*) FROM attendance WHERE course_id = :course_id AND attendance_date = CURDATE()";
                            $check_stmt = $conn->prepare($check_sql);
                            $check_stmt->bindParam(':course_id', $course['course_id']);
                            $check_stmt->execute();
                            $marked = $check_stmt->fetchColumn() > 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['schedule_time']); ?></td>
                            <td>
                                <?php if ($marked): ?>
                                    <span class="status-present">Attendance Marked</span>
                                <?php else: ?>
                                    <span class="status-absent">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($marked): ?>
                                    <a href="view_attendance.php?course_id=<?php echo $course['course_id']; ?>&date=<?php echo date('Y-m-d'); ?>" class="btn">View</a>
                                <?php else: ?>
                                    <a href="mark_attendance.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-mark">Mark Now</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No classes scheduled for today (<?php echo $today; ?>).</p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: white; border-radius: 5px;">
            <h3>Quick Actions</h3>
            <div style="margin-top: 15px;">
                <a href="mark_attendance.php" class="btn btn-mark">Mark Attendance</a>
                <a href="view_attendance.php" class="btn">View All Records</a>
                <a href="my_courses.php" class="btn">My Courses</a>
                <a href="reports.php" class="btn">Generate Reports</a>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>© 2025 Student Attendance System | Teacher Panel</p>
    </div>
</body>
</html>