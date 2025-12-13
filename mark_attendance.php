<?php
// mark_attendance.php - Mark attendance (Teacher only)
require_once 'config.php';
check_role(['teacher']);

$conn = db_connect();
$teacher_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Get teacher's courses
$courses_sql = "SELECT * FROM courses WHERE teacher_id = :teacher_id ORDER BY course_name";
$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bindParam(':teacher_id', $teacher_id);
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    $course_id = $_POST['course_id'];
    $attendance_date = $_POST['attendance_date'];
    $students = $_POST['students'] ?? [];
    
    if (empty($course_id) || empty($attendance_date)) {
        $error = "Please select course and date";
    } elseif (empty($students)) {
        $error = "No students to mark attendance for";
    } else {
        try {
            $conn->beginTransaction();
            
            foreach ($students as $student_id => $data) {
                $status = $data['status'];
                $remarks = trim($data['remarks'] ?? '');
                
                // Check if attendance already exists
                $check_sql = "SELECT attendance_id FROM attendance 
                             WHERE student_id = :student_id 
                             AND course_id = :course_id 
                             AND attendance_date = :attendance_date";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bindParam(':student_id', $student_id);
                $check_stmt->bindParam(':course_id', $course_id);
                $check_stmt->bindParam(':attendance_date', $attendance_date);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    // Update existing
                    $update_sql = "UPDATE attendance SET 
                                  status = :status, 
                                  remarks = :remarks, 
                                  marked_by = :marked_by,
                                  marked_at = NOW()
                                  WHERE student_id = :student_id 
                                  AND course_id = :course_id 
                                  AND attendance_date = :attendance_date";
                    $stmt = $conn->prepare($update_sql);
                } else {
                    // Insert new
                    $insert_sql = "INSERT INTO attendance 
                                  (student_id, course_id, attendance_date, status, remarks, marked_by) 
                                  VALUES (:student_id, :course_id, :attendance_date, :status, :remarks, :marked_by)";
                    $stmt = $conn->prepare($insert_sql);
                }
                
                $stmt->bindParam(':student_id', $student_id);
                $stmt->bindParam(':course_id', $course_id);
                $stmt->bindParam(':attendance_date', $attendance_date);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':remarks', $remarks);
                $stmt->bindParam(':marked_by', $teacher_id);
                $stmt->execute();
            }
            
            $conn->commit();
            $success = "Attendance marked successfully for " . count($students) . " students.";
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get selected course and date
$selected_course_id = $_GET['course_id'] ?? ($_POST['course_id'] ?? '');
$selected_date = $_GET['date'] ?? ($_POST['attendance_date'] ?? date('Y-m-d'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance - Student Attendance System</title>
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
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        .form-control {
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
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-success {
            background: #2ecc71;
        }
        .btn-success:hover {
            background: #27ae60;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .attendance-table th, .attendance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .attendance-table th {
            background: #34495e;
            color: white;
        }
        .attendance-table tr:hover {
            background: #f9f9f9;
        }
        .status-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .status-present { border-color: #27ae60; }
        .status-absent { border-color: #e74c3c; }
        .status-late { border-color: #f39c12; }
        .status-excused { border-color: #9b59b6; }
        .remarks-input {
            width: 200px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Mark Attendance</h1>
        <p>Teacher: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
    </div>
    
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="teacher.php" class="btn">← Back to Teacher Panel</a>
            <a href="view_attendance.php" class="btn" style="background:#95a5a6;">View Attendance</a>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="form-section">
            <h2>Select Course and Date</h2>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="course_id">Select Course</label>
                    <select name="course_id" id="course_id" class="form-control" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" 
                                <?php echo ($selected_course_id == $course['course_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date">Attendance Date</label>
                    <input type="date" name="date" id="date" class="form-control" 
                           value="<?php echo htmlspecialchars($selected_date); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-success">Load Students</button>
            </form>
        </div>
        
        <?php if (!empty($selected_course_id) && !empty($selected_date)): 
            // Get course details
            $course_sql = "SELECT * FROM courses WHERE course_id = :course_id";
            $course_stmt = $conn->prepare($course_sql);
            $course_stmt->bindParam(':course_id', $selected_course_id);
            $course_stmt->execute();
            $course = $course_stmt->fetch();
            
            // Get all students
            $students_sql = "SELECT s.*, u.full_name 
                           FROM students s 
                           JOIN users u ON s.user_id = u.id 
                           ORDER BY u.full_name";
            $students_stmt = $conn->prepare($students_sql);
            $students_stmt->execute();
            $all_students = $students_stmt->fetchAll();
            
            // Get existing attendance for this date and course - FIXED LINE 282
            $existing_sql = "SELECT student_id, status, remarks FROM attendance 
                           WHERE course_id = :course_id 
                           AND attendance_date = :attendance_date";
            $existing_stmt = $conn->prepare($existing_sql);
            $existing_stmt->bindParam(':course_id', $selected_course_id);
            $existing_stmt->bindParam(':attendance_date', $selected_date);
            $existing_stmt->execute();
            $existing_attendance = $existing_stmt->fetchAll();
            
            // Create an array for easy lookup
            $existing_data = [];
            foreach ($existing_attendance as $record) {
                $existing_data[$record['student_id']] = [
                    'status' => $record['status'],
                    'remarks' => $record['remarks']
                ];
            }
        ?>
        
        <div class="form-section">
            <h2>Mark Attendance for <?php echo htmlspecialchars($course['course_name']); ?></h2>
            <p>Date: <?php echo date('F j, Y', strtotime($selected_date)); ?></p>
            
            <form method="POST" action="">
                <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Student Code</th>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_students as $student): 
                            // Get existing status if any
                            $current_status = isset($existing_data[$student['student_id']]) ? 
                                            $existing_data[$student['student_id']]['status'] : 'present';
                            $current_remarks = isset($existing_data[$student['student_id']]) ? 
                                             $existing_data[$student['student_id']]['remarks'] : '';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?></td>
                            <td>
                                <select name="students[<?php echo $student['student_id']; ?>][status]" 
                                        class="status-select status-<?php echo $current_status; ?>">
                                    <option value="present" <?php echo ($current_status == 'present') ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo ($current_status == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                    <option value="late" <?php echo ($current_status == 'late') ? 'selected' : ''; ?>>Late</option>
                                    <option value="excused" <?php echo ($current_status == 'excused') ? 'selected' : ''; ?>>Excused</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="students[<?php echo $student['student_id']; ?>][remarks]" 
                                       class="remarks-input" 
                                       value="<?php echo htmlspecialchars($current_remarks); ?>"
                                       placeholder="Optional remarks">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="save_attendance" class="btn btn-success" style="font-size: 18px; padding: 12px 30px;">
                        Save Attendance
                    </button>
                    <a href="mark_attendance.php" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px; background: white; padding: 15px; border-radius: 5px;">
            <h3>Attendance Legend</h3>
            <p><strong>Present:</strong> Student attended the class</p>
            <p><strong>Absent:</strong> Student did not attend</p>
            <p><strong>Late:</strong> Student arrived late</p>
            <p><strong>Excused:</strong> Student has valid excuse</p>
        </div>
    </div>
</body>
</html>