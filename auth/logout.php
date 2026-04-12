<?php
require_once __DIR__ . '/../connection/database.php';
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session

// Redirect to login page
header("Location: /auth/login.php");
exit();
?>