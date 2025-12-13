
<?php
// student.php - Student Panel
require_once 'config.php';
check_role(['student']);

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// Get student info
$sql = "SELECT s.* FROM students s WHERE s.user_id = :user_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$student = $stmt->fetch();

$student_id = $student['student_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Panel - Student Attendance System</title>
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
            background: #2ecc71;
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
            background: #27ae60;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .student-info {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .info-value {
            color: #555;
        }
        .attendance-summary {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .summary-card {
            text-align: center;
            padding: 15px;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .summary-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .present { color: #27ae60; }
        .absent { color: #e74c3c; }
        .late { color: #f39c12; }
        .total { color: #3498db; }
        .recent-attendance {
            background: white;
            padding: 20px;
            border-radius: 5px;
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
            background: #2ecc71;
            color: white;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-excused { background: #e2d9f3; color: #4a235a; }
        .btn {
            display: inline-block;
            background: #2ecc71;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover {
            background: #27ae60;
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
        <h1>Student Panel</h1>
        <div>
            Student: <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        </div>
    </div>
    
    <div class="nav">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="student.php" style="background:#27ae60;">Student Panel</a>
        <a href="my_attendance.php">My Attendance</a>
        <a href="my_courses.php">My Courses</a>
        <a href="profile.php">My Profile</a>
        <a href="logout.php" style="float:right;">Logout</a>
    </div>
    
    <div class="container">
        <h2>Student Dashboard</h2>
        <?php echo display_message(); ?>
        
        <div class="student-info">
            <div class="info-item">
                <div class="info-label">Student Code</div>
                <div class="info-value"><?php echo htmlspecialchars($student['student_code']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Department</div>
                <div class="info-value"><?php echo htmlspecialchars($student['department'] ?? 'Not specified'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Year of Study</div>
                <div class="info-value">Year <?php echo htmlspecialchars($student['year_of_study'] ?? '1'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Phone</div>
                <div class="info-value"><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></div>
            </div>
        </div>
        
        <div class="attendance-summary">
            <h3>Attendance Summary</h3>
            <?php
            // Get attendance summary
            $summary_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
                FROM attendance 
                WHERE student_id = :student_id";
            $summary_stmt = $conn->prepare($summary_sql);
            $summary_stmt->bindParam(':student_id', $student_id);
            $summary_stmt->execute();
            $summary = $summary_stmt->fetch();
            
            $attendance_rate = $summary['total'] > 0 ? round(($summary['present'] / $summary['total']) * 100, 2) : 0;
            ?>
            
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-number total"><?php echo $summary['total']; ?></div>
                    <div>Total Classes</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number present"><?php echo $summary['present']; ?></div>
                    <div>Present</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number absent"><?php echo $summary['absent']; ?></div>
                    <div>Absent</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number"><?php echo $attendance_rate; ?>%</div>
                    <div>Attendance Rate</div>
                </div>
            </div>
        </div>
        
        <div class="recent-attendance">
            <h3>Recent Attendance Records</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Marked By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent_sql = "SELECT a.*, c.course_name, u.full_name as teacher_name 
                                 FROM attendance a 
                                 JOIN courses c ON a.course_id = c.course_id 
                                 JOIN users u ON a.marked_by = u.id 
                                 WHERE a.student_id = :student_id 
                                 ORDER BY a.attendance_date DESC 
                                 LIMIT 10";
                    $recent_stmt = $conn->prepare($recent_sql);
                    $recent_stmt->bindParam(':student_id', $student_id);
                    $recent_stmt->execute();
                    
                    while ($record = $recent_stmt->fetch()) {
                        $status_class = 'status-' . $record['status'];
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($record['attendance_date']) . "</td>";
                        echo "<td>" . htmlspecialchars($record['course_name']) . "</td>";
                        echo "<td><span class='status $status_class'>" . ucfirst($record['status']) . "</span></td>";
                        echo "<td>" . htmlspecialchars($record['teacher_name']) . "</td>";
                        echo "</tr>";
                    }
                    
                    if ($recent_stmt->rowCount() == 0) {
                        echo "<tr><td colspan='4'>No attendance records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <a href="view_attendance.php" class="btn">View All Attendance →</a>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: white; border-radius: 5px;">
            <h3>Today's Classes</h3>
            <?php
            $today = date('Y-m-d');
            $today_sql = "SELECT c.* FROM courses c 
                         WHERE c.course_id IN (
                             SELECT DISTINCT course_id FROM attendance 
                             WHERE student_id = :student_id AND attendance_date = :today
                         )";
            $today_stmt = $conn->prepare($today_sql);
            $today_stmt->bindParam(':student_id', $student_id);
            $today_stmt->bindParam(':today', $today);
            $today_stmt->execute();
            $today_courses = $today_stmt->fetchAll();
            
            if (count($today_courses) > 0):
            ?>
                <table class="table" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Schedule</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($today_courses as $course): 
                            // Get today's attendance status
                            $status_sql = "SELECT status FROM attendance 
                                         WHERE student_id = :student_id 
                                         AND course_id = :course_id 
                                         AND attendance_date = :today";
                            $status_stmt = $conn->prepare($status_sql);
                            $status_stmt->bindParam(':student_id', $student_id);
                            $status_stmt->bindParam(':course_id', $course['course_id']);
                            $status_stmt->bindParam(':today', $today);
                            $status_stmt->execute();
                            $status = $status_stmt->fetchColumn();
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['schedule_day'] . ' ' . $course['schedule_time']); ?></td>
                            <td><span class="status status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No classes attended today.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="footer">
        <p>© 2025 Student Attendance System | Student ID: <?php echo htmlspecialchars($student['student_code']); ?></p>
    </div>
</body>
</html>