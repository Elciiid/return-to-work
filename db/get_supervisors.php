<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['department'])) {
    echo json_encode(['error' => 'No department found in session']);
    exit;
}

$user_department = $_SESSION['department']; // e.g., "Admin Department"

// Database connection for LRNPH_E (Master List)
$serverNameE = "10.2.0.9";
$connectionOptionsE = [
    "UID" => "sa",
    "PWD" => "S3rverDB02lrn25",
    "Database" => "LRNPH_E"
];
$connE = sqlsrv_connect($serverNameE, $connectionOptionsE);

// Database connection for LRNPH_OJT (Supervisors Table)
$serverNameOJT = "10.2.0.9";
$connectionOptionsOJT = [
    "UID" => "sa",
    "PWD" => "S3rverDB02lrn25",
    "Database" => "LRNPH_OJT"
];
$connOJT = sqlsrv_connect($serverNameOJT, $connectionOptionsOJT);

if ($connE === false || $connOJT === false) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 1. Fetch authorized supervisor IDs and custom subtitles for this department
$sql_ojt = "SELECT employee_id, custom_subtitle FROM rtw_supervisors WHERE department = ?";
$params_ojt = array($user_department);
$stmt_ojt = sqlsrv_query($connOJT, $sql_ojt, $params_ojt);

if ($stmt_ojt === false) {
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

$valid_ids = [];
$custom_subtitles = [];
while ($row = sqlsrv_fetch_array($stmt_ojt, SQLSRV_FETCH_ASSOC)) {
    $emp_id = $row['employee_id'];
    $valid_ids[] = $emp_id;
    if (!empty($row['custom_subtitle'])) {
        $custom_subtitles[$emp_id] = $row['custom_subtitle'];
    }
}

if (empty($valid_ids)) {
    echo json_encode([]);
    exit;
}

// 2. Fetch details for these IDs from LRNPH_E to get Names and default Titles
$placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
$sql_e = "SELECT 
            EmployeeID, 
            FirstName + ' ' + LastName as FullName, 
            PositionTitle 
        FROM lrn_master_list 
        WHERE EmployeeID IN ($placeholders)
        ORDER BY FullName ASC";

$stmt_e = sqlsrv_query($connE, $sql_e, $valid_ids);

if ($stmt_e === false) {
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

$supervisors = [];
while ($row = sqlsrv_fetch_array($stmt_e, SQLSRV_FETCH_ASSOC)) {
    $emp_id = $row['EmployeeID'];
    $position = isset($custom_subtitles[$emp_id]) ? $custom_subtitles[$emp_id] : $row['PositionTitle'];

    $supervisors[] = [
        'id' => $emp_id,
        'name' => $row['FullName'],
        'position' => $position
    ];
}

sqlsrv_close($connE);
sqlsrv_close($connOJT);

echo json_encode($supervisors);
exit();
?>