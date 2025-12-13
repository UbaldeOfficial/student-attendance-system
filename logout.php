<?php
// logout.php - Logout user
require_once 'config.php';

// Destroy all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>