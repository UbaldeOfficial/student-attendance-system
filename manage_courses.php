<?php
// manage_courses.php - Manage Courses (Admin only)
require_once 'config.php';
check_role(['admin']);

$conn = db_connect();
$action = $_GET['action'] ?? 'list';
$course_id = $_GET['id'] ?? 0;

$error = '';
$success = '';

// Handle different actions
switch ($action) {
    case 'add':
    case 'edit':
        // Get teachers for dropdown
        $teachers_sql = "SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name";
        $teachers_stmt = $conn->query($teachers_sql);
        $teachers = $teachers_stmt->fetchAll();
        
        // Form processing
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $course_code = trim($_POST['course_code']);
            $course_name = trim($_POST['course_name']);
            $teacher_id = $_POST['teacher_id'];
            $schedule_day = $_POST['schedule_day'];
            $schedule_time = $_POST['schedule_time'];
            
            // Validation
            if (empty($course_code) || empty($course_name)) {
                $error = "Course code and name are required";
            } else {
                try {
                    if ($action == 'add') {
                        // Check if course code exists
                        $check_sql = "SELECT course_id FROM courses WHERE course_code = :course_code";
                        $check_stmt = $conn->prepare($check_sql);
                        $check_stmt->bindParam(':course_code', $course_code);
                        $check_stmt->execute();
                        
                        if ($check_stmt->rowCount() > 0) {
                            $error = "Course code already exists";
                        } else {
                            $sql = "INSERT INTO courses (course_code, course_name, teacher_id, schedule_day, schedule_time) 
                                   VALUES (:course_code, :course_name, :teacher_id, :schedule_day, :schedule_time)";
                            $stmt = $conn->prepare($sql);
                        }
                    } else {
                        // Edit existing course
                        $sql = "UPDATE courses SET 
                               course_code = :course_code,
                               course_name = :course_name,
                               teacher_id = :teacher_id,
                               schedule_day = :schedule_day,
                               schedule_time = :schedule_time
                               WHERE course_id = :course_id";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':course_id', $course_id);
                    }
                    
                    if (empty($error)) {
                        $stmt->bindParam(':course_code', $course_code);
                        $stmt->bindParam(':course_name', $course_name);
                        $stmt->bindParam(':teacher_id', $teacher_id);
                        $stmt->bindParam(':schedule_day', $schedule_day);
                        $stmt->bindParam(':schedule_time', $schedule_time);
                        
                        if ($stmt->execute()) {
                            $success = $action == 'add' ? "Course added successfully!" : "Course updated successfully!";
                            if ($action == 'add') {
                                // Reset form
                                $_POST = [];
                            } else {
                                // Redirect to list
                                header('Location: manage_courses.php?message=' . urlencode($success));
                                exit();
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
        
        // Get course data for editing
        $course_data = [];
        if ($action == 'edit' && $course_id > 0) {
            try {
                $sql = "SELECT * FROM courses WHERE course_id = :course_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':course_id', $course_id);
                $stmt->execute();
                $course_data = $stmt->fetch();
            } catch (PDOException $e) {
                $error = "Error loading course data: " . $e->getMessage();
            }
        }
        
        // Display form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php echo $action == 'add' ? 'Add Course' : 'Edit Course'; ?> - Student Attendance System</title>
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
                .row { display: flex; gap: 20px; }
                .col { flex: 1; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo $action == 'add' ? 'Add New Course' : 'Edit Course'; ?></h1>
            </div>
            
            <div class="container">
                <div class="back-link">
                    <a href="manage_courses.php" class="btn">← Back to Courses List</a>
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
                            <label for="course_code">Course Code *</label>
                            <input type="text" id="course_code" name="course_code" 
                                   value="<?php echo isset($_POST['course_code']) ? htmlspecialchars($_POST['course_code']) : ($course_data['course_code'] ?? ''); ?>" 
                                   required placeholder="e.g., CS101">
                        </div>
                        
                        <div class="form-group">
                            <label for="course_name">Course Name *</label>
                            <input type="text" id="course_name" name="course_name" 
                                   value="<?php echo isset($_POST['course_name']) ? htmlspecialchars($_POST['course_name']) : ($course_data['course_name'] ?? ''); ?>" 
                                   required placeholder="e.g., Introduction to Programming">
                        </div>
                        
                        <div class="form-group">
                            <label for="teacher_id">Teacher</label>
                            <select id="teacher_id" name="teacher_id">
                                <option value="">-- Select Teacher --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>"
                                        <?php echo (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $teacher['id']) || 
                                                  (isset($course_data['teacher_id']) && $course_data['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="schedule_day">Schedule Day</label>
                                    <select id="schedule_day" name="schedule_day">
                                        <option value="">-- Select Day --</option>
                                        <option value="Monday" <?php echo (isset($_POST['schedule_day']) && $_POST['schedule_day'] == 'Monday') || (isset($course_data['schedule_day']) && $course_data['schedule_day'] == 'Monday') ? 'selected' : ''; ?>>Monday</option>
                                        <option value="Tuesday" <?php echo (isset($_POST['schedule_day']) && $_POST['schedule_day'] == 'Tuesday') || (isset($course_data['schedule_day']) && $course_data['schedule_day'] == 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                                        <option value="Wednesday" <?php echo (isset($_POST['schedule_day']) && $_POST['schedule_day'] == 'Wednesday') || (isset($course_data['schedule_day']) && $course_data['schedule_day'] == 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                                        <option value="Thursday" <?php echo (isset($_POST['schedule_day']) && $_POST['schedule_day'] == 'Thursday') || (isset($course_data['schedule_day']) && $course_data['schedule_day'] == 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                                        <option value="Friday" <?php echo (isset($_POST['schedule_day']) && $_POST['schedule_day'] == 'Friday') || (isset($course_data['schedule_day']) && $course_data['schedule_day'] == 'Friday') ? 'selected' : ''; ?>>Friday</option>
                                        <option value="Saturday" <?php echo (isset($_POST['schedule_day']) && $_POST['schedule_day'] == 'Saturday') || (isset($course_data['schedule_day']) && $course_data['schedule_day'] == 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="schedule_time">Schedule Time</label>
                                    <input type="time" id="schedule_time" name="schedule_time" 
                                           value="<?php echo isset($_POST['schedule_time']) ? $_POST['schedule_time'] : ($course_data['schedule_time'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">
                                <?php echo $action == 'add' ? 'Add Course' : 'Update Course'; ?>
                            </button>
                            <a href="manage_courses.php" class="btn btn-danger">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
        break;
        
    case 'delete':
        // Delete course
        if ($course_id > 0) {
            try {
                $sql = "DELETE FROM courses WHERE course_id = :course_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':course_id', $course_id);
                
                if ($stmt->execute()) {
                    $success = "Course deleted successfully!";
                }
            } catch (PDOException $e) {
                $error = "Error deleting course: " . $e->getMessage();
            }
        }
        // Fall through to list view
        
    default:
    case 'list':
        // Display courses list
        try {
            $sql = "SELECT c.*, u.full_name as teacher_name 
                   FROM courses c 
                   LEFT JOIN users u ON c.teacher_id = u.id 
                   ORDER BY c.course_code";
            $stmt = $conn->query($sql);
            $courses = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error = "Error loading courses: " . $e->getMessage();
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Manage Courses - Student Attendance System</title>
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
                .action-buttons { display: flex; gap: 5px; }
                .footer { text-align: center; padding: 20px; margin-top: 30px; color: #7f8c8d; border-top: 1px solid #eee; }
                .stats { display: flex; gap: 20px; margin-bottom: 20px; }
                .stat-card { background: white; padding: 15px; border-radius: 5px; flex: 1; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .stat-number { font-size: 24px; font-weight: bold; color: #3498db; }
                .stat-label { color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Manage Courses</h1>
            </div>
            
            <div class="nav">
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="admin.php">Admin Panel</a>
                <a href="manage_users.php">Manage Users</a>
                <a href="manage_courses.php" style="background:#e74c3c;">Manage Courses</a>
                <a href="logout.php" style="float:right;">Logout</a>
            </div>
            
            <div class="container">
                <h2 class="page-title">Course Management</h2>
                
                <?php if (isset($_GET['message'])): ?>
                    <div class="success"><?php echo htmlspecialchars($_GET['message']); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php
                // Get statistics
                $total_courses = count($courses);
                $courses_with_teacher = 0;
                foreach ($courses as $course) {
                    if (!empty($course['teacher_id'])) {
                        $courses_with_teacher++;
                    }
                }
                ?>
                
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_courses; ?></div>
                        <div class="stat-label">Total Courses</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $courses_with_teacher; ?></div>
                        <div class="stat-label">Assigned to Teachers</div>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <a href="manage_courses.php?action=add" class="btn btn-success">+ Add New Course</a>
                    <span style="float:right; color:#666;">Showing <?php echo $total_courses; ?> courses</span>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Course Name</th>
                            <th>Teacher</th>
                            <th>Schedule</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['teacher_name'] ?? 'Not Assigned'); ?></td>
                            <td>
                                <?php if ($course['schedule_day'] && $course['schedule_time']): ?>
                                    <?php echo htmlspecialchars($course['schedule_day'] . ' ' . $course['schedule_time']); ?>
                                <?php else: ?>
                                    Not Scheduled
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                            <td class="action-buttons">
                                <a href="manage_courses.php?action=edit&id=<?php echo $course['course_id']; ?>" class="btn">Edit</a>
                                <a href="manage_courses.php?action=delete&id=<?php echo $course['course_id']; ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this course? All attendance records for this course will also be deleted.')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 30px; background: white; padding: 20px; border-radius: 5px;">
                    <h3>Course Management Tips</h3>
                    <p><strong>1.</strong> Each course must have a unique course code.</p>
                    <p><strong>2.</strong> Assign teachers to courses for attendance marking.</p>
                    <p><strong>3.</strong> Set schedule to help teachers know when to mark attendance.</p>
                    <p><strong>4.</strong> Deleting a course will also delete all attendance records for that course.</p>
                </div>
            </div>
            
            <div class="footer">
                <p>© 2025 Student Attendance System | Course Management</p>
            </div>
        </body>
        </html>
        <?php
        break;
}
?>