<?php
require_once __DIR__ . '/../connection/database.php';
header('Content-Type: application/json');

if (!($_SESSION['is_admin'] ?? false)) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

try {
    // 1. Fetch User Permissions with Names from PostgreSQL
    $stmt_perms = $conn->query("SELECT up.*, 
                                LTRIM(RTRIM(COALESCE(m.\"FirstName\",''))) as first_name,
                                LTRIM(RTRIM(COALESCE(m.\"LastName\",''))) as last_name
                                FROM rtw_user_permissions up
                                LEFT JOIN rtw_master_list m ON up.employee_id = m.\"EmployeeID\"
                                ORDER BY up.id DESC");
    $permissions = $stmt_perms->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Supervisors with Names
    $stmt_sups = $conn->query("SELECT rs.*, 
                               LTRIM(RTRIM(COALESCE(m.\"FirstName\",''))) as first_name,
                               LTRIM(RTRIM(COALESCE(m.\"LastName\",''))) as last_name
                               FROM rtw_supervisors rs
                               LEFT JOIN rtw_master_list m ON rs.employee_id = m.\"EmployeeID\"
                               ORDER BY rs.id DESC");
    $supervisors = $stmt_sups->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Departments from Master List for the dropdown
    $stmt_depts = $conn->query("SELECT DISTINCT REPLACE(\"Department\", ' - LRN', '') as department 
                                FROM rtw_master_list 
                                WHERE \"Department\" IS NOT NULL 
                                ORDER BY department ASC");
    $departments = $stmt_depts->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'permissions' => $permissions,
        'supervisors' => $supervisors,
        'departments' => $departments
    ]);

} catch (PDOException $e) {
    error_log("Get Admin Data Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error.']);
}
?>
