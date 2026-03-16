<?php
session_start();
header('Content-Type: application/json');

if (!($_SESSION['is_admin'] ?? false)) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

include 'db.php';

$type = $_POST['type'] ?? ''; // 'permission' or 'supervisor'
$id = $_POST['id'] ?? '';

if (empty($id)) {
    echo json_encode(['error' => 'Missing ID.']);
    exit;
}

try {
    if ($type === 'permission') {
        $stmt = $conn->prepare("DELETE FROM user_permissions WHERE id = ?");
    } elseif ($type === 'supervisor') {
        $stmt = $conn->prepare("DELETE FROM rtw_supervisors WHERE id = ?");
    } else {
        echo json_encode(['error' => 'Invalid type.']);
        exit;
    }

    $stmt->execute([$id]);
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
