<?php
// hash_password.php - Generate password hashes
echo "<h3>Password Hash Generator</h3>";

$passwords = [
    'admin123',
    'teacher123', 
    'student123',
    'password123',
    'password'
];

foreach ($passwords as $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "<p><strong>$password:</strong><br>$hash</p>";
}
?>