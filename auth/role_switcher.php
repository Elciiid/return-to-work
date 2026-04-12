<?php
require_once __DIR__ . '/../connection/database.php';

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit();
}

// Verify if the current user is authorized or is an authorized user impersonating someone else
if (!($_SESSION['can_act_as_she'] ?? false) && !($_SESSION['original_can_act_as_she'] ?? false)) {
    die("Unauthorized access.");
}

// Case 1: Switch BACK to Original User
if (isset($_SESSION['original_user'])) {
    $_SESSION['username'] = $_SESSION['original_user'];
    $_SESSION['fullname'] = $_SESSION['original_fullname'];
    $_SESSION['role'] = $_SESSION['original_role'];
    $_SESSION['department'] = $_SESSION['original_department'];
    $_SESSION['employee_id'] = $_SESSION['original_employee_id'];
    $_SESSION['is_approver'] = $_SESSION['original_is_approver'] ?? false;
    $_SESSION['can_act_as_she'] = $_SESSION['original_can_act_as_she'] ?? false;
    $_SESSION['is_admin'] = $_SESSION['original_is_admin'] ?? false;

    // Clear original session keys
    unset($_SESSION['original_user']);
    unset($_SESSION['original_fullname']);
    unset($_SESSION['original_role']);
    unset($_SESSION['original_department']);
    unset($_SESSION['original_employee_id']);
    unset($_SESSION['original_is_approver']);
    unset($_SESSION['original_can_act_as_she']);
    unset($_SESSION['original_is_admin']);

    header("Location: /pages/index.php");
    exit();
}

// Case 2: Switch TO 'SHE' (Grant Nurse Role)
// Store original user details first
$_SESSION['original_user'] = $_SESSION['username'];
$_SESSION['original_fullname'] = $_SESSION['fullname'];
$_SESSION['original_role'] = $_SESSION['role'];
$_SESSION['original_department'] = $_SESSION['department'];
$_SESSION['original_employee_id'] = $_SESSION['employee_id'];
$_SESSION['original_is_approver'] = $_SESSION['is_approver'] ?? false;
$_SESSION['original_can_act_as_she'] = $_SESSION['can_act_as_she'] ?? false;
$_SESSION['original_is_admin'] = $_SESSION['is_admin'] ?? false;

// Update session to Nurse role
// We maintain identity (fullname, employee_id, etc.) but change the role
$_SESSION['role'] = 'company nurse';
$_SESSION['department'] = 'MEDICAL';
$_SESSION['is_approver'] = false;
$_SESSION['can_act_as_she'] = false; // Prevents "Act as SHE" button from showing while already acting as SHE

header("Location: /pages/nurse_dashboard.php");
exit();
?>
