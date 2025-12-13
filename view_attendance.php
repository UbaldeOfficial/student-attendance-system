<?php
// view_attendance.php - View Attendance Records
require_once 'config.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$conn = db_connect();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get filter parameters
$course_id = $_GET['course_id'] ?? '';
$student_id = $_GET['student_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Initialize variables
$courses = [];
$students = [];
$attendance_records = [];
$summary = [];

// Get courses based on role
if ($role == 'teacher') {
    $courses_sql = "SELECT * FROM courses WHERE teacher_id = :teacher_id ORDER BY course_name";
    $courses_stmt = $conn->prepare($courses_sql);
    $courses_stmt->bindParam(':teacher_id', $user_id);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll();
} elseif ($role == 'admin') {
    $courses_sql = "SELECT * FROM courses ORDER BY course_name";
    $courses_stmt = $conn->query($courses_sql);
    $courses = $courses_stmt->fetchAll();
} elseif ($role == 'student') {
    // Get student's courses from attendance records
    $student_sql = "SELECT student_id FROM students WHERE user_id = :user_id";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bindParam(':user_id', $user_id);
    $student_stmt->execute();
    $student = $student_stmt->fetch();
    $student_id = $student['student_id'];
    
    $courses_sql = "SELECT DISTINCT c.* FROM courses c 
                   JOIN attendance a ON c.course_id = a.course_id 
                   WHERE a.student_id = :student_id 
                   ORDER BY c.course_name";
    $courses_stmt = $conn->prepare($courses_sql);
    $courses_stmt->bindParam(':student_id', $student_id);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll();
}

// Get all students (for admin/teacher)
if ($role == 'admin' || $role == 'teacher') {
    $students_sql = "SELECT s.*, u.full_name FROM students s JOIN users u ON s.user_id = u.id ORDER BY u.full_name";
    $students_stmt = $conn->query($students_sql);
    $students = $students_stmt->fetchAll();
}

// Build query based on role and filters
$sql = "SELECT a.*, 
               c.course_code, c.course_name,
               s.student_code,
               u_student.full_name as student_name,
               u_teacher.full_name as teacher_name
        FROM attendance a
        JOIN courses c ON a.course_id = c.course_id
        JOIN students s ON a.student_id = s.student_id
        JOIN users u_student ON s.user_id = u_student.id
        JOIN users u_teacher ON a.marked_by = u_teacher.id
        WHERE 1=1";
        
$params = [];

// Add filters
if (!empty($course_id)) {
    $sql .= " AND a.course_id = :course_id";
    $params[':course_id'] = $course_id;
}

if (!empty($student_id)) {
    $sql .= " AND a.student_id = :student_id";
    $params[':student_id'] = $student_id;
}

if (!empty($start_date)) {
    $sql .= " AND a.attendance_date >= :start_date";
    $params[':start_date'] = $start_date;
}

if (!empty($end_date)) {
    $sql .= " AND a.attendance_date <= :end_date";
    $params[':end_date'] = $end_date;
}

// For students, only show their own records
if ($role == 'student') {
    $sql .= " AND a.student_id = :student_id";
    $params[':student_id'] = $student_id;
}

// For teachers, only show their courses
if ($role == 'teacher') {
    $sql .= " AND c.teacher_id = :teacher_id";
    $params[':teacher_id'] = $user_id;
}

$sql .= " ORDER BY a.attendance_date DESC, c.course_name, u_student.full_name";

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

// Calculate summary
if (!empty($attendance_records)) {
    $total = count($attendance_records);
    $present = 0;
    $absent = 0;
    $late = 0;
    $excused = 0;
    
    foreach ($attendance_records as $record) {
        switch ($record['status']) {
            case 'present': $present++; break;
            case 'absent': $absent++; break;
            case 'late': $late++; break;
            case 'excused': $excused++; break;
        }
    }
    
    $summary = [
        'total' => $total,
        'present' => $present,
        'absent' => $absent,
        'late' => $late,
        'excused' => $excused,
        'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Attendance - Student Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; }
        .nav { background: #34495e; padding: 10px; }
        .nav a { color: white; text-decoration: none; padding: 10px 15px; margin: 0 5px; border-radius: 4px; }
        .nav a:hover { background: #1abc9c; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .filter-section { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-row { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 200px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .btn { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .summary-card { background: white; padding: 15px; border-radius: 5px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .summary-number { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .present { color: #27ae60; }
        .absent { color: #e74c3c; }
        .late { color: #f39c12; }
        .excused { color: #9b59b6; }
        .total { color: #3498db; }
        .table-container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #34495e; color: white; position: sticky; top: 0; }
        .table tr:hover { background: #f9f9f9; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-excused { background: #e2d9f3; color: #4a235a; }
        .export-buttons { margin-top: 20px; }
        .footer { text-align: center; padding: 20px; margin-top: 30px; color: #7f8c8d; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="header">
        <h1>View Attendance Records</h1>
        <p>Role: <?php echo ucfirst($role); ?> | User: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
    </div>
    
    <div class="nav">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <?php if ($role == 'admin'): ?>
            <a href="admin.php">Admin Panel</a>
        <?php elseif ($role == 'teacher'): ?>
            <a href="teacher.php">Teacher Panel</a>
            <a href="mark_attendance.php">Mark Attendance</a>
            <a href="view_attendance.php" style="background:#1abc9c;">View Attendance</a>
        <?php elseif ($role == 'student'): ?>
            <a href="student.php">Student Panel</a>
            <a href="view_attendance.php" style="background:#1abc9c;">My Attendance</a>
        <?php endif; ?>
        <a href="logout.php" style="float:right;">Logout</a>
    </div>
    
    <div class="container">
        <h2>Attendance Records</h2>
        
        <div class="filter-section">
            <h3>Filter Records</h3>
            <form method="GET" action="">
                <div class="form-row">
                    <?php if ($role == 'admin' || $role == 'teacher'): ?>
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
                    <?php endif; ?>
                    
                    <?php if ($role == 'admin'): ?>
                        <div class="form-group">
                            <label for="student_id">Student</label>
                            <select id="student_id" name="student_id">
                                <option value="">All Students</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['student_id']; ?>"
                                        <?php echo $student_id == $student['student_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['full_name'] . ' (' . $student['student_code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <button type="submit" class="btn btn-success">Apply Filters</button>
                    <a href="view_attendance.php" class="btn">Clear Filters</a>
                    <?php if (!empty($attendance_records)): ?>
                        <button type="button" onclick="printReport()" class="btn">Print Report</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <?php if (!empty($attendance_records)): ?>
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-number total"><?php echo $summary['total']; ?></div>
                    <div>Total Records</div>
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
                    <div class="summary-number late"><?php echo $summary['late']; ?></div>
                    <div>Late</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number"><?php echo $summary['attendance_rate']; ?>%</div>
                    <div>Attendance Rate</div>
                </div>
            </div>
            
            <div class="table-container">
                <h3>Attendance Records (<?php echo count($attendance_records); ?> records found)</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Student</th>
                            <th>Student Code</th>
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
                            <td><?php echo htmlspecialchars($record['course_code'] . ' - ' . $record['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_code']); ?></td>
                            <td><span class="status <?php echo $status_class; ?>"><?php echo ucfirst($record['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($record['remarks'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
                            <td><?php echo date('H:i', strtotime($record['marked_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="export-buttons">
                <p>Records shown: <?php echo count($attendance_records); ?> | 
                   Date range: <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
            </div>
            
        <?php else: ?>
            <div style="background: white; padding: 30px; border-radius: 5px; text-align: center;">
                <h3>No attendance records found</h3>
                <p>Try adjusting your filters or mark attendance for selected criteria.</p>
                <?php if ($role == 'teacher'): ?>
                    <a href="mark_attendance.php" class="btn btn-success">Mark Attendance Now</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>© 2025 Student Attendance System | Attendance Records</p>
    </div>
    
    <script>
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>