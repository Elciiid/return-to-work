<?php
require_once __DIR__ . '/../connection/database.php';

$term = $_GET['term'] ?? '';

if (strlen($term) >= 1) {
    // Trim and prepare search term
    $term = trim($term);

    try {
        // PostgreSQL version: uses || for concatenation, COALESCE instead of ISNULL, and ILIKE for case-insensitive search
        $query = "SELECT
                    \"BiometricsID\" as employee_number,
                    \"EmployeeID\" as employee_id,
                    LTRIM(RTRIM(COALESCE(\"FirstName\",''))) || ' ' || LTRIM(RTRIM(COALESCE(\"LastName\",''))) as employee_name,
                    REPLACE(\"Department\", ' - LRN', '') as department
                  FROM rtw_master_list
                  WHERE \"BiometricsID\" ILIKE ?
                     OR \"EmployeeID\" ILIKE ?
                     OR (LTRIM(RTRIM(COALESCE(\"FirstName\",''))) || ' ' || LTRIM(RTRIM(COALESCE(\"LastName\",'')))) ILIKE ?
                     OR \"Department\" ILIKE ?
                  ORDER BY 
                    CASE 
                        WHEN (LTRIM(RTRIM(COALESCE(\"FirstName\",''))) || ' ' || LTRIM(RTRIM(COALESCE(\"LastName\",'')))) ILIKE ? THEN 1 
                        ELSE 2 
                    END,
                    LTRIM(RTRIM(COALESCE(\"FirstName\",''))) || ' ' || LTRIM(RTRIM(COALESCE(\"LastName\",'')))";

        $like = '%' . $term . '%';
        $params = [$like, $like, $like, $like, $like];

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($results);
    } catch (PDOException $e) {
        error_log("Search Error: " . $e->getMessage());
        echo json_encode(['error' => 'Database error.']);
    }
}
?>