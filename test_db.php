<?php
// test_db.php - Test database connection
echo "<h3>Testing Database Connection</h3>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=student_attendance_system", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connected to database successfully!</p>";
    
    // Test query
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>Total users in database: " . $result['count'] . "</p>";
    
    // List users
    $stmt = $conn->query("SELECT username, email, role FROM users");
    echo "<h4>Users in system:</h4>";
    echo "<ul>";
    while ($user = $stmt->fetch()) {
        echo "<li>" . htmlspecialchars($user['username']) . " (" . $user['role'] . ") - " . htmlspecialchars($user['email']) . "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Connection failed: " . $e->getMessage() . "</p>";
}
?>