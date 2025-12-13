<?php
// simple_reset.php - Reset passwords to known values
$host = 'localhost';
$dbname = 'student_attendance_system';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Resetting Passwords to '123456'</h3>";
    
    // Hash for '123456'
    $hash = password_hash('123456', PASSWORD_DEFAULT);
    
    // Update all users
    $sql = "UPDATE users SET password = :hash";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':hash', $hash);
    $stmt->execute();
    
    echo "<p style='color:green;'>✅ All passwords reset to: <strong>123456</strong></p>";
    echo "<p>Hash used: $hash</p>";
    
    // Show users
    $stmt = $conn->query("SELECT username, email FROM users");
    echo "<h4>Login with any user:</h4>";
    echo "<ul>";
    while ($row = $stmt->fetch()) {
        echo "<li>Username: <strong>{$row['username']}</strong> OR Email: <strong>{$row['email']}</strong> - Password: <strong>123456</strong></li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>