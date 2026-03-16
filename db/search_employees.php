<?php
include 'db.php';

$term = $_GET['term'] ?? '';

if (strlen($term) >= 1) {
    // Trim and prepare search term
    $term = trim($term);

    try {
        // Simplified query using supervisor's logic with LTRIM/RTRIM
        $query = "SELECT
                    BiometricsID as employee_number,
                    EmployeeID as employee_id,
                    LTRIM(RTRIM(ISNULL(FirstName,''))) + ' ' + LTRIM(RTRIM(ISNULL(LastName,''))) as employee_name,
                    REPLACE(Department, ' - LRN', '') as department
                  FROM LRNPH_E.dbo.lrn_master_list
                  WHERE UPPER(BiometricsID) LIKE UPPER(?)
                     OR UPPER(EmployeeID) LIKE UPPER(?)
                     OR UPPER(LTRIM(RTRIM(ISNULL(FirstName,''))) + ' ' + LTRIM(RTRIM(ISNULL(LastName,'')))) LIKE UPPER(?)
                     OR UPPER(Department) LIKE UPPER(?)
                  ORDER BY 
                    CASE 
                        WHEN UPPER(LTRIM(RTRIM(ISNULL(FirstName,''))) + ' ' + LTRIM(RTRIM(ISNULL(LastName,'')))) LIKE UPPER(?) THEN 1 
                        ELSE 2 
                    END,
                    LTRIM(RTRIM(ISNULL(FirstName,''))) + ' ' + LTRIM(RTRIM(ISNULL(LastName,'')))";

        // Supervisor's approach: same parameter for all fields
        $like = '%' . $term . '%';
        // We added one more parameter in the ORDER BY clause
        $params = [$like, $like, $like, $like, $like];

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($results);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>