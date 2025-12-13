<?php
// reset_passwords.php - Reset all passwords
require_once 'config.php';

$conn = db_connect();

// New passwords and their hashes
$new_passwords = [
    'admin' => 'admin123',
    'teacher1' => 'teacher123',
    'student1' => 'student123',
    'ubalde' => 'password123'
];

echo "<h3>Resetting Passwords</h3>";

foreach ($new_passwords as $username => $new_password) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $sql = "UPDATE users SET password = :password WHERE username = :username";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':username', $username);
    
    if ($stmt->execute()) {
        echo "<p>✅ Updated $username password to: <strong>$new_password</strong></p>";
        echo "<p>Hash: $hashed_password</p>";
    } else {
        echo "<p>❌ Failed to update $username</p>";
    }
}

echo "<hr><h4>Test Login Credentials:</h4>";
foreach ($new_passwords as $username => $password) {
    echo "<p><strong>$username</strong> / <strong>$password</strong></p>";
}
?>