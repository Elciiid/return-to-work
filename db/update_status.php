<?php
require_once __DIR__ . '/../connection/database.php';

// Only authorized approvers can approve/decline
$is_authorized = $_SESSION['is_approver'] ?? false;

if (!isset($_SESSION['username']) || !$is_authorized) {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $action = $_POST['action'] ?? ''; // Approve/Decline
    $decline_reason = $_POST['decline_reason'] ?? '';
    $status = ($action === 'Approve') ? 'Approved' : 'Declined';
    $approved_by = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'System';

    if ($id <= 0) {
        die("Invalid ID provided.");
    }

    try {
        // PostgreSQL query with rtw_ prefix
        $sql = "UPDATE rtw_return_to_work 
                SET status = ?, 
                    approved_by = ?, 
                    approved_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$status, $approved_by, $id]);

        // Redirect back based on role
        $role = strtolower(trim($_SESSION['role'] ?? 'employee'));
        $nurse_roles = ['clinic assistant', 'company nurse'];
        
        if (in_array($role, $nurse_roles)) {
            header("Location: /pages/nurse_applications.php?status_updated=1");
        } else {
            header("Location: /pages/approvals.php?status_updated=1");
        }
        exit();
    } catch (PDOException $e) {
        error_log("Update Status Error: " . $e->getMessage());
        die("Database Error.");
    }
}

header("Location: /pages/dashboard.php");
exit();
