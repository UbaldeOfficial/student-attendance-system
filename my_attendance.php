<?php
// my_attendance.php - Student's personal attendance view
require_once 'config.php';
check_role(['student']);

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// Get student info
$student_sql = "SELECT s.* FROM students s WHERE s.user_id = :user_id";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bindParam(':user_id', $user_id);
$student_stmt->execute();
$student = $student_stmt->fetch();
$student_id = $student['student_id'];

// Get filter parameters
$course_id = $_GET['course_id'] ?? '';
$month = $_GET['month'] ?? date('Y-m');
$status = $_GET['status'] ?? '';

// Get student's courses from attendance
$courses_sql = "SELECT DISTINCT c.* FROM courses c 
               JOIN attendance a ON c.course_id = a.course_id 
               WHERE a.student_id = :student_id 
               ORDER BY c.course_name";
$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bindParam(':student_id', $student_id);
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll();

// Build query for attendance records
$sql = "SELECT a.*, 
               c.course_code, c.course_name,
               u.full_name as teacher_name
        FROM attendance a
        JOIN courses c ON a.course_id = c.course_id
        JOIN users u ON a.marked_by = u.id
        WHERE a.student_id = :student_id";
        
$params = [':student_id' => $student_id];

// Add filters
if (!empty($course_id)) {
    $sql .= " AND a.course_id = :course_id";
    $params[':course_id'] = $course_id;
}

if (!empty($month)) {
    $sql .= " AND DATE_FORMAT(a.attendance_date, '%Y-%m') = :month";
    $params[':month'] = $month;
}

if (!empty($status)) {
    $sql .= " AND a.status = :status";
    $params[':status'] = $status;
}

$sql .= " ORDER BY a.attendance_date DESC, c.course_name";

// Execute query
try {
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $attendance_records = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading attendance records: " . $e->getMessage();
}

// Calculate statistics
$total_classes = count($attendance_records);
$present_count = 0;
$absent_count = 0;
$late_count = 0;
$excused_count = 0;

foreach ($attendance_records as $record) {
    switch ($record['status']) {
        case 'present': $present_count++; break;
        case 'absent': $absent_count++; break;
        case 'late': $late_count++; break;
        case 'excused': $excused_count++; break;
    }
}

$attendance_rate = $total_classes > 0 ? round(($present_count / $total_classes) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Attendance - Student Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; }
        .nav { background: #2ecc71; padding: 10px; }
        .nav a { color: white; text-decoration: none; padding: 10px 15px; margin: 0 5px; border-radius: 4px; }
        .nav a:hover { background: #27ae60; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .student-info { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .info-item { padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .info-label { font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
        .info-value { color: #555; }
        .filter-section { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .form-row { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 200px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .btn { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 5px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-number { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .present { color: #27ae60; }
        .absent { color: #e74c3c; }
        .late { color: #f39c12; }
        .excused { color: #9b59b6; }
        .total { color: #3498db; }
        .attendance-table { width: 100%; border-collapse: collapse; background: white; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .attendance-table th, .attendance-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .attendance-table th { background: #2ecc71; color: white; }
        .attendance-table tr:hover { background: #f9f9f9; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-excused { background: #e2d9f3; color: #4a235a; }
        .no-data { text-align: center; padding: 40px; background: white; border-radius: 5px; }
        .print-btn { margin-top: 20px; }
        .footer { text-align: center; padding: 20px; margin-top: 30px; color: #7f8c8d; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="header">
        <h1>My Attendance Records</h1>
    </div>
    
    <div class="nav">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="student.php">Student Panel</a>
        <a href="my_attendance.php" style="background:#27ae60;">My Attendance</a>
        <a href="my_courses.php">My Courses</a>
        <a href="profile.php">My Profile</a>
        <a href="logout.php" style="float:right;">Logout</a>
    </div>
    
    <div class="container">
        <div class="student-info">
            <h2>Student Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Student Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                </div>
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
            </div>
        </div>
        
        <div class="filter-section">
            <h3>Filter Attendance Records</h3>
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_id">Course</label>
                        <select id="course_id" name="course_id">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>"
                                    <?php echo $course_id == $course['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="month">Month</label>
                        <input type="month" id="month" name="month" value="<?php echo htmlspecialchars($month); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Status</option>
                            <option value="present" <?php echo $status == 'present' ? 'selected' : ''; ?>>Present</option>
                            <option value="absent" <?php echo $status == 'absent' ? 'selected' : ''; ?>>Absent</option>
                            <option value="late" <?php echo $status == 'late' ? 'selected' : ''; ?>>Late</option>
                            <option value="excused" <?php echo $status == 'excused' ? 'selected' : ''; ?>>Excused</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <button type="submit" class="btn btn-success">Apply Filters</button>
                    <a href="my_attendance.php" class="btn">Clear Filters</a>
                    <button type="button" onclick="window.print()" class="btn print-btn">Print Report</button>
                </div>
            </form>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number total"><?php echo $total_classes; ?></div>
                <div>Total Classes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number present"><?php echo $present_count; ?></div>
                <div>Present</div>
            </div>
            <div class="stat-card">
                <div class="stat-number absent"><?php echo $absent_count; ?></div>
                <div>Absent</div>
            </div>
            <div class="stat-card">
                <div class="stat-number late"><?php echo $late_count; ?></div>
                <div>Late</div>
            </div>
            <div class="stat-card">
                <div class="stat-number excused"><?php echo $excused_count; ?></div>
                <div>Excused</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $attendance_rate; ?>%</div>
                <div>Attendance Rate</div>
            </div>
        </div>
        
        <?php if (!empty($attendance_records)): ?>
            <div style="background: white; padding: 20px; border-radius: 5px;">
                <h3>Attendance Records (<?php echo count($attendance_records); ?> records found)</h3>
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Course Code</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Marked By</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): 
                            $status_class = 'status-' . $record['status'];
                        ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                            <td><?php echo htmlspecialchars($record['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['course_code']); ?></td>
                            <td><span class="status <?php echo $status_class; ?>"><?php echo ucfirst($record['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
                            <td><?php echo date('h:i A', strtotime($record['marked_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                    <p><strong>Summary:</strong> Showing <?php echo count($attendance_records); ?> attendance records. 
                    Attendance rate: <strong><?php echo $attendance_rate; ?>%</strong></p>
                    <?php if (!empty($course_id)): 
                        $course_name = '';
                        foreach ($courses as $c) {
                            if ($c['course_id'] == $course_id) {
                                $course_name = $c['course_name'];
                                break;
                            }
                        }
                    ?>
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($course_name); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($month)): ?>
                        <p><strong>Month:</strong> <?php echo date('F Y', strtotime($month . '-01')); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="no-data">
                <h3>No Attendance Records Found</h3>
                <p>You don't have any attendance records yet.</p>
                <p>Your teacher will mark attendance for your classes.</p>
                <div style="margin-top: 20px;">
                    <a href="student.php" class="btn">Go to Student Panel</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; background: white; padding: 20px; border-radius: 5px;">
            <h3>Attendance Information</h3>
            <p><strong>Present:</strong> You attended the class</p>
            <p><strong>Absent:</strong> You did not attend the class</p>
            <p><strong>Late:</strong> You arrived late to class</p>
            <p><strong>Excused:</strong> You had a valid excuse for missing class</p>
            <p><strong>Note:</strong> If you believe there is an error in your attendance, please contact your teacher.</p>
        </div>
    </div>
    
    <div class="footer">
        <p>© 2025 Student Attendance System | Student ID: <?php echo htmlspecialchars($student['student_code']); ?></p>
        <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
    </div>
</body>
</html>