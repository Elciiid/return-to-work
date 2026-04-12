<?php
require_once __DIR__ . '/../connection/database.php';

// Only admins can delete admin data
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';

    if (!$id) {
        die("Missing ID.");
    }

    try {
        if ($action === 'delete_approver') {
            $stmt = $conn->prepare("DELETE FROM rtw_user_permissions WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'delete_supervisor') {
            $stmt = $conn->prepare("DELETE FROM rtw_supervisors WHERE id = ?");
            $stmt->execute([$id]);
        }

        header("Location: /pages/settings.php?deleted=1");
        exit();

    } catch (PDOException $e) {
        error_log("Delete Admin Data Error: " . $e->getMessage());
        die("Database Error.");
    }
}
?>
