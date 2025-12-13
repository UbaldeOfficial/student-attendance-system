<?php
// my_courses.php - Student's courses view
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

// Get student's courses from attendance records - FIXED QUERY
$courses_sql = "SELECT DISTINCT c.*, 
               (SELECT COUNT(*) FROM attendance WHERE student_id = :student_id1 AND course_id = c.course_id) as total_classes,
               (SELECT COUNT(*) FROM attendance WHERE student_id = :student_id2 AND course_id = c.course_id AND status = 'present') as present_classes
               FROM courses c 
               JOIN attendance a ON c.course_id = a.course_id 
               WHERE a.student_id = :student_id3 
               ORDER BY c.course_name";
               
$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bindParam(':student_id1', $student_id);
$courses_stmt->bindParam(':student_id2', $student_id);
$courses_stmt->bindParam(':student_id3', $student_id);
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Courses - Student Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; }
        .nav { background: #2ecc71; padding: 10px; }
        .nav a { color: white; text-decoration: none; padding: 10px 15px; margin: 0 5px; border-radius: 4px; }
        .nav a:hover { background: #27ae60; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .page-title { color: #2c3e50; margin-bottom: 20px; }
        .courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .course-card { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .course-code { font-size: 18px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
        .course-name { color: #555; margin-bottom: 10px; }
        .course-schedule { color: #666; font-size: 14px; margin-bottom: 15px; }
        .attendance-stats { display: flex; justify-content: space-between; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .stat { text-align: center; }
        .stat-number { font-size: 20px; font-weight: bold; }
        .stat-label { font-size: 12px; color: #7f8c8d; }
        .btn { background: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .no-courses { text-align: center; padding: 40px; background: white; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; margin-top: 30px; color: #7f8c8d; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="header">
        <h1>My Courses</h1>
    </div>
    
    <div class="nav">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="student.php">Student Panel</a>
        <a href="my_attendance.php">My Attendance</a>
        <a href="my_courses.php" style="background:#27ae60;">My Courses</a>
        <a href="profile.php">My Profile</a>
        <a href="logout.php" style="float:right;">Logout</a>
    </div>
    
    <div class="container">
        <h2 class="page-title">Registered Courses</h2>
        <p>Student: <?php echo htmlspecialchars($_SESSION['full_name']); ?> | 
           Student Code: <?php echo htmlspecialchars($student['student_code']); ?></p>
        
        <?php if (count($courses) > 0): ?>
            <div class="courses-grid">
                <?php foreach ($courses as $course): 
                    $attendance_rate = $course['total_classes'] > 0 ? 
                        round(($course['present_classes'] / $course['total_classes']) * 100, 2) : 0;
                ?>
                <div class="course-card">
                    <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                    <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
                    
                    <?php if ($course['schedule_day'] && $course['schedule_time']): ?>
                        <div class="course-schedule">
                            📅 <?php echo htmlspecialchars($course['schedule_day']); ?> 
                            at <?php echo date('g:i A', strtotime($course['schedule_time'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="attendance-stats">
                        <div class="stat">
                            <div class="stat-number"><?php echo $course['total_classes']; ?></div>
                            <div class="stat-label">Total Classes</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number"><?php echo $course['present_classes']; ?></div>
                            <div class="stat-label">Present</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number"><?php echo $attendance_rate; ?>%</div>
                            <div class="stat-label">Attendance</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <a href="my_attendance.php?course_id=<?php echo $course['course_id']; ?>" class="btn">View Attendance</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 30px; background: white; padding: 20px; border-radius: 5px;">
                <h3>Course Summary</h3>
                <p>Total registered courses: <?php echo count($courses); ?></p>
                <p>Note: Courses are shown based on your attendance records.</p>
            </div>
            
        <?php else: ?>
            <div class="no-courses">
                <h3>No Courses Found</h3>
                <p>You don't have any courses registered yet.</p>
                <p>Your teacher will mark your attendance for courses you attend.</p>
                <div style="margin-top: 20px;">
                    <a href="student.php" class="btn">Go to Student Panel</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>© 2025 Student Attendance System | Student Portal</p>
    </div>
</body>
</html>