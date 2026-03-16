<?php
session_start();
header('Content-Type: application/json');

if (!($_SESSION['is_admin'] ?? false)) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

include 'db.php';

try {
    // 1. Fetch User Permissions with Names
    $stmt_perms = $conn->query("SELECT up.*, 
                                LTRIM(RTRIM(ISNULL(m.FirstName,''))) as first_name,
                                LTRIM(RTRIM(ISNULL(m.LastName,''))) as last_name
                                FROM user_permissions up
                                LEFT JOIN LRNPH_E.dbo.lrn_master_list m ON up.employee_id COLLATE DATABASE_DEFAULT = m.EmployeeID COLLATE DATABASE_DEFAULT
                                ORDER BY up.id DESC");
    $permissions = $stmt_perms->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Supervisors with Names
    $stmt_sups = $conn->query("SELECT rs.*, 
                               LTRIM(RTRIM(ISNULL(m.FirstName,''))) as first_name,
                               LTRIM(RTRIM(ISNULL(m.LastName,''))) as last_name
                               FROM rtw_supervisors rs
                               LEFT JOIN LRNPH_E.dbo.lrn_master_list m ON rs.employee_id COLLATE DATABASE_DEFAULT = m.EmployeeID COLLATE DATABASE_DEFAULT
                               ORDER BY rs.id DESC");
    $supervisors = $stmt_sups->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Departments from Master List for the dropdown
    // Note: We use LRNPH_E.dbo.lrn_master_list
    $stmt_depts = $conn->query("SELECT DISTINCT REPLACE(Department, ' - LRN', '') as department 
                                FROM LRNPH_E.dbo.lrn_master_list 
                                WHERE Department IS NOT NULL 
                                ORDER BY department ASC");
    $departments = $stmt_depts->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'permissions' => $permissions,
        'supervisors' => $supervisors,
        'departments' => $departments
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
