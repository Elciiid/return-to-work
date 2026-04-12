<?php
require_once __DIR__ . '/../connection/database.php';

header('Content-Type: application/json');

// Only admins can save admin data
if (($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $type = $_POST['type'] ?? '';
    
    if ($type === 'supervisor') {
        $department = $_POST['department'] ?? '';
        $employee_id = $_POST['employee_id'] ?? '';
        $custom_subtitle = $_POST['custom_subtitle'] ?? '';

        if (empty($department) || empty($employee_id)) {
            throw new Exception('Missing required fields.');
        }

        // Check if already exists
        $check = $conn->prepare("SELECT id FROM rtw_supervisors WHERE department = ? AND employee_id = ?");
        $check->execute([$department, $employee_id]);
        if ($check->fetch()) {
            throw new Exception('Supervisor mapping already exists for this department.');
        }

        $stmt = $conn->prepare("INSERT INTO rtw_supervisors (department, employee_id) VALUES (?, ?)");
        $stmt->execute([$department, $employee_id]);
        echo json_encode(['success' => true]);

    } elseif ($type === 'permission') {
        $employee_id = $_POST['employee_id'] ?? '';
        $permission_type = $_POST['permission_type'] ?? '';

        if (empty($employee_id) || empty($permission_type)) {
            throw new Exception('Missing required fields.');
        }

        // Check if already exists
        $check = $conn->prepare("SELECT id FROM rtw_user_permissions WHERE employee_id = ? AND permission_type = ?");
        $check->execute([$employee_id, $permission_type]);
        if ($check->fetch()) {
            throw new Exception('Permission already exists for this employee.');
        }

        $stmt = $conn->prepare("INSERT INTO rtw_user_permissions (employee_id, permission_type) VALUES (?, ?)");
        $stmt->execute([$employee_id, $permission_type]);
        echo json_encode(['success' => true]);

    } else {
        throw new Exception('Invalid type.');
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
