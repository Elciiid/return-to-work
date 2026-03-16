<?php
session_start();
header('Content-Type: application/json');

if (!($_SESSION['is_admin'] ?? false)) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

include 'db.php';

$type = $_POST['type'] ?? ''; // 'permission' or 'supervisor'

try {
    if ($type === 'permission') {
        $employee_id = $_POST['employee_id'] ?? '';
        $employee_name = $_POST['employee_name'] ?? '';
        $permission_type = $_POST['permission_type'] ?? '';
        $granted_by = $_SESSION['fullname'] ?? 'System';

        if (empty($employee_id) || empty($permission_type)) {
            echo json_encode(['error' => 'Missing required fields.']);
            exit;
        }

        // Check if already exists
        $check = $conn->prepare("SELECT id FROM user_permissions WHERE employee_id = ? AND permission_type = ?");
        $check->execute([$employee_id, $permission_type]);
        if ($check->fetch()) {
            echo json_encode(['error' => 'Permission already exists for this user.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO user_permissions (employee_id, employee_name, permission_type, granted_by, granted_at) VALUES (?, ?, ?, ?, GETDATE())");
        $stmt->execute([$employee_id, $employee_name, $permission_type, $granted_by]);
        echo json_encode(['success' => true]);

    } elseif ($type === 'supervisor') {
        $department = $_POST['department'] ?? '';
        $employee_id = $_POST['employee_id'] ?? '';
        $custom_subtitle = $_POST['custom_subtitle'] ?? '';

        if (empty($department) || empty($employee_id)) {
            echo json_encode(['error' => 'Missing required fields.']);
            exit;
        }

        // Check if already exists
        $check = $conn->prepare("SELECT id FROM rtw_supervisors WHERE department = ? AND employee_id = ?");
        $check->execute([$department, $employee_id]);
        if ($check->fetch()) {
            echo json_encode(['error' => 'Supervisor mapping already exists for this department.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO rtw_supervisors (department, employee_id, custom_subtitle) VALUES (?, ?, ?)");
        $stmt->execute([$department, $employee_id, $custom_subtitle]);
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['error' => 'Invalid type.']);
    }

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
