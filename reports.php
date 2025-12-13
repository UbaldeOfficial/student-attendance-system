<?php
// reports.php - Reports and Analytics
require_once 'config.php';
check_role(['admin', 'teacher']);

$conn = db_connect();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Default date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Initialize statistics
$stats = [];
$course_stats = [];
$student_stats = [];

try {
    // Base query condition
    $where_condition = "WHERE a.attendance_date BETWEEN :start_date AND :end_date";
    $params = [':start_date' => $start_date, ':end_date' => $end_date];
    
    // For teachers, only show their courses
    if ($role == 'teacher') {
        $where_condition .= " AND c.teacher_id = :teacher_id";
        $params[':teacher_id'] = $user_id;
    }
    
    // Overall statistics
    $stats_sql = "SELECT 
        COUNT(*) as total_attendance,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused
        FROM attendance a
        JOIN courses c ON a.course_id = c.course_id
        $where_condition";
    
    $stats_stmt = $conn->prepare($stats_sql);
    foreach ($params as $key => $value) {
        $stats_stmt->bindValue($key, $value);
    }
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch();
    
    // Course-wise statistics
    $course_sql = "SELECT 
        c.course_id, c.course_code, c.course_name,
        COUNT(*) as total,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent
        FROM attendance a
        JOIN courses c ON a.course_id = c.course_id
        $where_condition
        GROUP BY c.course_id, c.course_code, c.course_name
        ORDER BY total DESC";
    
    $course_stmt = $conn->prepare($course_sql);
    foreach ($params as $key => $value) {
        $course_stmt->bindValue($key, $value);
    }
    $course_stmt->execute();
    $course_stats = $course_stmt->fetchAll();
    
    // Student-wise statistics (top 10)
    $student_sql = "SELECT 
        s.student_id, s.student_code, u.full_name,
        COUNT(*) as total,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
        ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_rate
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        JOIN users u ON s.user_id = u.id
        JOIN courses c ON a.course_id = c.course_id
        $where_condition
        GROUP BY s.student_id, s.student_code, u.full_name
        HAVING total >= 5
        ORDER BY attendance_rate ASC, total DESC
        LIMIT 10";
    
    $student_stmt = $conn->prepare($student_sql);
    foreach ($params as $key => $value) {
        $student_stmt->bindValue($key, $value);
    }
    $student_stmt->execute();
    $student_stats = $student_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Error loading reports: " . $e->getMessage();
}

// Calculate percentages
if ($stats['total_attendance'] > 0) {
    $stats['present_percent'] = round(($stats['present'] / $stats['total_attendance']) * 100, 2);
    $stats['absent_percent'] = round(($stats['absent'] / $stats['total_attendance']) * 100, 2);
    $stats['late_percent'] = round(($stats['late'] / $stats['total_attendance']) * 100, 2);
    $stats['excused_percent'] = round(($stats['excused'] / $stats['total_attendance']) * 100, 2);
} else {
    $stats['present_percent'] = $stats['absent_percent'] = $stats['late_percent'] = $stats['excused_percent'] = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Reports - Student Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; }
        .nav { background: #34495e; padding: 10px; }
        .nav a { color: white; text-decoration: none; padding: 10px 15px; margin: 0 5px; border-radius: 4px; }
        .nav a:hover { background: #1abc9c; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .filter-section { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-number { font-size: 36px; font-weight: bold; margin-bottom: 10px; }
        .stat-label { color: #666; font-size: 14px; }
        .stat-bar { height: 10px; background: #ecf0f1; border-radius: 5px; margin-top: 10px; overflow: hidden; }
        .stat-fill { height: 100%; border-radius: 5px; }
        .present-fill { background: #2ecc71; }
        .absent-fill { background: #e74c3c; }
        .late-fill { background: #f39c12; }
        .excused-fill { background: #9b59b6; }
        .report-section { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #34495e; color: white; }
        .table tr:hover { background: #f9f9f9; }
        .btn { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .form-row { display: flex; gap: 20px; }
        .col { flex: 1; }
        .good-attendance { color: #27ae60; }
        .poor-attendance { color: #e74c3c; }
        .footer { text-align: center; padding: 20px; margin-top: 30px; color: #7f8c8d; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Reports & Analytics</h1>
        <p><?php echo ucfirst($role); ?>: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
    </div>
    
    <div class="nav">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <?php if ($role == 'admin'): ?>
            <a href="admin.php">Admin Panel</a>
        <?php elseif ($role == 'teacher'): ?>
            <a href="teacher.php">Teacher Panel</a>
        <?php endif; ?>
        <a href="reports.php" style="background:#1abc9c;">Reports</a>
        <a href="view_attendance.php">View Attendance</a>
        <a href="logout.php" style="float:right;">Logout</a>
    </div>
    
    <div class="container">
        <h2>Attendance Analytics</h2>
        
        <div class="filter-section">
            <h3>Select Date Range</h3>
            <form method="GET" action="">
                <div class="form-row">
                    <div class="col">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Generate Report</button>
                <a href="reports.php" class="btn">Reset to Current Month</a>
            </form>
            <p style="margin-top: 10px; color: #666;">
                Date Range: <?php echo date('F d, Y', strtotime($start_date)); ?> to <?php echo date('F d, Y', strtotime($end_date)); ?>
            </p>
        </div>
        
        <?php if (isset($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_attendance'] ?? 0; ?></div>
                <div class="stat-label">Total Attendance Records</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['present'] ?? 0; ?></div>
                <div class="stat-label">Present Records</div>
                <div class="stat-bar">
                    <div class="stat-fill present-fill" style="width: <?php echo $stats['present_percent'] ?? 0; ?>%"></div>
                </div>
                <div class="stat-label"><?php echo $stats['present_percent'] ?? 0; ?>%</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['absent'] ?? 0; ?></div>
                <div class="stat-label">Absent Records</div>
                <div class="stat-bar">
                    <div class="stat-fill absent-fill" style="width: <?php echo $stats['absent_percent'] ?? 0; ?>%"></div>
                </div>
                <div class="stat-label"><?php echo $stats['absent_percent'] ?? 0; ?>%</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['attendance_rate'] ?? 0; ?>%</div>
                <div class="stat-label">Overall Attendance Rate</div>
            </div>
        </div>
        
        <?php if (!empty($course_stats)): ?>
        <div class="report-section">
            <h3>Course-wise Attendance</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Total Records</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Attendance Rate</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($course_stats as $course): 
                        $attendance_rate = $course['total'] > 0 ? round(($course['present'] / $course['total']) * 100, 2) : 0;
                        $rate_class = $attendance_rate >= 80 ? 'good-attendance' : 'poor-attendance';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></td>
                        <td><?php echo $course['total']; ?></td>
                        <td><?php echo $course['present']; ?></td>
                        <td><?php echo $course['absent']; ?></td>
                        <td class="<?php echo $rate_class; ?>"><?php echo $attendance_rate; ?>%</td>
                        <td>
                            <a href="view_attendance.php?course_id=<?php echo $course['course_id']; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                               class="btn" style="padding: 5px 10px; font-size: 12px;">View Details</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($student_stats)): ?>
        <div class="report-section">
            <h3>Students with Lowest Attendance (Need Attention)</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student Code</th>
                        <th>Total Classes</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($student_stats as $student): 
                        $rate_class = $student['attendance_rate'] >= 80 ? 'good-attendance' : 'poor-attendance';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                        <td><?php echo $student['total']; ?></td>
                        <td><?php echo $student['present']; ?></td>
                        <td><?php echo $student['absent']; ?></td>
                        <td class="<?php echo $rate_class; ?>"><?php echo $student['attendance_rate']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="color: #666; margin-top: 10px;">
                Note: Showing students with at least 5 attendance records and lowest attendance rates.
            </p>
        </div>
        <?php endif; ?>
        
        <div class="report-section">
            <h3>Export Options</h3>
            <div style="margin-top: 15px;">
                <button onclick="printReport()" class="btn">Print Report</button>
                <a href="#" class="btn btn-success">Export to Excel</a>
                <a href="#" class="btn">Generate PDF</a>
            </div>
        </div>
        
        <div class="report-section">
            <h3>Report Summary</h3>
            <p><strong>Period:</strong> <?php echo date('F d, Y', strtotime($start_date)); ?> to <?php echo date('F d, Y', strtotime($end_date)); ?></p>
            <p><strong>Total Attendance Records:</strong> <?php echo $stats['total_attendance'] ?? 0; ?></p>
            <p><strong>Overall Attendance Rate:</strong> <?php echo $stats['attendance_rate'] ?? 0; ?>%</p>
            <p><strong>Generated on:</strong> <?php echo date('F d, Y H:i:s'); ?></p>
            <p><strong>Generated by:</strong> <?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo ucfirst($role); ?>)</p>
        </div>
    </div>
    
    <div class="footer">
        <p>© 2025 Student Attendance System | Reports & Analytics</p>
    </div>
    
    <script>
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>