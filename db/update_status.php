<?php
session_start();

// Only authorized approvers can approve/decline
$is_authorized = $_SESSION['is_approver'] ?? false;

if (!isset($_SESSION['username']) || !$is_authorized) {
    die("Unauthorized access.");
}

include './db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $action = $_POST['action'] ?? '';
    $decline_reason = $_POST['decline_reason'] ?? '';
    $return = $_POST['return'] ?? '../pages/dashboard.php';
    // Normalize return path so redirects don't point to /db/...
    if (
        strpos($return, '../') !== 0 &&          // not already relative to pages
        !preg_match('#^https?://#i', $return) && // not absolute URL
        strpos($return, '/') !== 0               // not root-based path
    ) {
        $return = '../pages/' . ltrim($return, '/');
    }

    if ($id <= 0 || !in_array($action, ['approve', 'decline'], true)) {
        header('Location: ' . $return);
        exit();
    }

    try {
        if ($action === 'approve') {
            $status = 'Approved';
            $approved_by = $_SESSION['fullname'] ?? 'Unknown';

            $sql = "UPDATE return_to_work 
                    SET status = ?, 
                        approved_by = ?, 
                        approved_at = GETDATE() 
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$status, $approved_by, $id]);
        } else {
            // Decline: set status to Declined and store reason
            $status = 'Declined';
            $declined_by = $_SESSION['fullname'] ?? 'Unknown';

            $sql = "UPDATE return_to_work
                    SET status = ?,
                        decline_reason = ?,
                        declined_by = ?,
                        declined_at = GETDATE()
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$status, $decline_reason, $declined_by, $id]);
        }

        header('Location: ' . $return);
        exit();
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}

header("Location: ../pages/dashboard.php");
exit();
