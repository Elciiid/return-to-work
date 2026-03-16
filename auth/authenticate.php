<?php
session_start();

$auth_server_name = "10.2.0.9";
$connectionOptions = [
    "UID" => "sa",
    "PWD" => "S3rverDB02lrn25",
    "Database" => "LRNPH_E"
];

$auth_conn = sqlsrv_connect($auth_server_name, $connectionOptions);

if (!$auth_conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    if (!empty($username) && !empty($password)) {
        // Query to get employee information including department
        $query = "SELECT
                lu.username,
                lu.password,
                ml.FirstName + ' ' + ml.LastName as fullname,
                REPLACE(ml.Department, ' - LRN', '') as department,
                ml.PositionTitle,
                ml.EmployeeID,
                ml.IsActive,
                ml.BiometricsID
                FROM LRNPH.dbo.lrnph_users lu
                LEFT JOIN LRNPH_E.dbo.lrn_master_list ml
                    ON lu.username = ml.BiometricsID
                WHERE lu.username = ?";

        $params = array($username);
        $stmt = sqlsrv_query($auth_conn, $query, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['username'] = $row['username'];
                $_SESSION['fullname'] = $row['fullname'];
                $_SESSION['employee_id'] = $row['EmployeeID'];
                // Normalize department by removing " - LRN" suffix
                $_SESSION['department'] = str_replace(' - LRN', '', $row['department']);

                // Load Permissions from Database
                include '../db/db.php'; // Connects to LRNPH_OJT database using PDO ($conn)
                
                $can_act_as_she = false;
                $is_authorized_approver = false;
                
                try {
                    $perm_stmt = $conn->prepare("SELECT permission_type FROM user_permissions WHERE employee_id = ?");
                    $perm_stmt->execute([$row['EmployeeID']]);
                    $permissions = $perm_stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (in_array('SHE_IMPERSONATOR', $permissions)) {
                        $can_act_as_she = true;
                    }
                    if (in_array('APPROVER', $permissions)) {
                        $is_authorized_approver = true;
                    }
                } catch (PDOException $e) {
                    // Log error or handle gracefully
                    error_log("Permission Error: " . $e->getMessage());
                }

                $_SESSION['can_act_as_she'] = $can_act_as_she;

                // Admin Logic: IT Department + Active OR BiometricsID 4
                $is_it_dept = (strpos($row['department'], 'Information Technology Department') !== false);
                $is_active = ($row['IsActive'] == '1');
                $is_special_admin = ($row['BiometricsID'] == '4');
                
                $_SESSION['is_admin'] = ($is_special_admin || ($is_it_dept && $is_active));

                // Check for nurse roles
                $position_title = strtolower(trim($row['PositionTitle'] ?? ''));
                $nurse_roles = ['clinic assistant', 'company nurse'];

                if ($is_authorized_approver) {
                    $_SESSION['is_approver'] = true;
                    $_SESSION['role'] = 'approver'; // Set role for display purposes
                    header("Location: ../pages/dashboard.php");
                } elseif (in_array($position_title, $nurse_roles)) {
                    $_SESSION['is_approver'] = false;
                    $_SESSION['role'] = $position_title; // Set nurse role for nurse pages
                    header("Location: ../pages/nurse_dashboard.php");
                } else {
                    $_SESSION['is_approver'] = false;
                    $_SESSION['role'] = 'employee'; // Set default role
                    header("Location: ../pages/index.php");
                }
                exit();
            } else {
                header("Location: login.php?error=invalid");
                exit();
            }
        } else {
            header("Location: login.php?error=invalid");
            exit();
        }
    }
}
sqlsrv_close($auth_conn);
?>