<?php
require_once __DIR__ . '/../connection/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    if (!empty($username) && !empty($password)) {
        try {
            // Query to get employee information including department from PostgreSQL
            $query = "SELECT
                    lu.username,
                    lu.password,
                    ml.\"FirstName\" || ' ' || ml.\"LastName\" as fullname,
                    REPLACE(ml.\"Department\", ' - LRN', '') as department,
                    ml.\"PositionTitle\",
                    ml.\"EmployeeID\",
                    ml.\"IsActive\",
                    ml.\"BiometricsID\"
                    FROM rtw_app_users lu
                    LEFT JOIN rtw_master_list ml
                        ON lu.username = ml.\"BiometricsID\"
                    WHERE lu.username = ?";

            $stmt = $conn->prepare($query);
            $stmt->execute([$username]);

            if ($row = $stmt->fetch()) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['fullname'] = $row['fullname'];
                    $_SESSION['employee_id'] = $row['EmployeeID'] ?? $row['employee_id'];
                    // Normalize department by removing " - LRN" suffix
                    $_SESSION['department'] = str_replace(' - LRN', '', $row['department']);

                    // Load Permissions from Database
                    $can_act_as_she = false;
                    $is_authorized_approver = false;
                    
                    $perm_stmt = $conn->prepare("SELECT permission_type FROM rtw_user_permissions WHERE employee_id = ?");
                    $perm_stmt->execute([$row['EmployeeID'] ?? $row['employee_id']]);
                    $permissions = $perm_stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (in_array('SHE_IMPERSONATOR', $permissions)) {
                        $can_act_as_she = true;
                    }
                    if (in_array('APPROVER', $permissions)) {
                        $is_authorized_approver = true;
                    }

                    $_SESSION['can_act_as_she'] = $can_act_as_she;

                    // Admin Logic: IT Department + Active OR BiometricsID 4
                    $is_it_dept = (strpos($row['department'], 'Information Technology Department') !== false);
                    $is_active = ($row['IsActive'] == '1' || $row['IsActive'] === true);
                    $is_special_admin = ($row['BiometricsID'] == '4');
                    
                    $_SESSION['is_admin'] = ($is_special_admin || ($is_it_dept && $is_active));

                    // Check for nurse roles
                    $position_title = strtolower(trim($row['PositionTitle'] ?? ''));
                    $nurse_roles = ['clinic assistant', 'company nurse'];

                    if ($is_authorized_approver) {
                        $_SESSION['is_approver'] = true;
                        $_SESSION['role'] = 'approver'; 
                        header("Location: /pages/dashboard.php");
                    } elseif (in_array($position_title, $nurse_roles)) {
                        $_SESSION['is_approver'] = false;
                        $_SESSION['role'] = $position_title;
                        header("Location: /pages/nurse_dashboard.php");
                    } else {
                        $_SESSION['is_approver'] = false;
                        $_SESSION['role'] = 'employee';
                        header("Location: /pages/index.php");
                    }
                    exit();
                } else {
                    header("Location: /auth/login.php?error=invalid");
                    exit();
                }
            } else {
                header("Location: /auth/login.php?error=invalid");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Auth Error: " . $e->getMessage());
            die("Authentication error. Please try again later.");
        }
    }
}
?>