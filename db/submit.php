<?php
require_once __DIR__ . '/../connection/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Handle REQUIRED medical certificate upload
        // Check if exemption applies
        $reason = strtolower($_POST['reason'] ?? '');
        $is_exempt = (strpos($reason, 'dysmennorhea') !== false) || (strpos($reason, 'dysmenorrhea') !== false);

        $uploaded_med_cert_path = null;

        // processing file upload
        if (isset($_FILES['medical_certificate']) && $_FILES['medical_certificate']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['medical_certificate'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                die("<script>alert('Error uploading file. Code: " . $file['error'] . "'); window.history.back();</script>");
            }

            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'heic'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_extensions, true)) {
                die("<script>alert('Error: Invalid file type. Allowed: PDF, Word, JPG, PNG, HEIC'); window.history.back();</script>");
            }

            // Ensure upload directory exists
            $upload_dir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'medical_certificates';
            if (!is_dir($upload_dir)) {
                if (!@mkdir($upload_dir, 0775, true)) {
                    // On Vercel this will fail or be ephemeral, but we keep it for demo purposes
                }
            }

            if (is_dir($upload_dir) && is_writable($upload_dir)) {
                $safe_name = preg_replace('/[^A-Za-z0-9_\.-]/', '_', basename($file['name']));
                $new_filename = date('Ymd_His') . '_' . uniqid('mc_', true) . '.' . $file_ext;
                $target_path = $upload_dir . DIRECTORY_SEPARATOR . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Store relative path
                    $uploaded_med_cert_path = 'uploads/medical_certificates/' . $new_filename;
                }
            }
        } elseif (!$is_exempt) {
            die("<script>alert('Error: Medical certificate is required.'); window.history.back();</script>");
        }

        // Concatenate Superior Name and Position for the single DB column
        $superior_info = "";
        if (isset($_POST['notified']) && $_POST['notified'] === 'Yes') {
            $name = $_POST['sup_name'] ?? '';
            $pos = $_POST['sup_pos'] ?? '';
            $superior_info = $name . " - " . $pos;
        }

        // Updated query for PostgreSQL with rtw_ prefix
        $sql = "INSERT INTO rtw_return_to_work (
                    date,
                    employee_number,
                    employee_id,
                    employee_name,
                    department,
                    prodn_type,
                    days_absence,
                    first_date_absence,
                    date_returned,
                    reason,
                    notified_superior,
                    superior_name_position,
                    filing_date,
                    medical_certificate_path,
                    status,
                    approved_by,
                    approved_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            date('Y-m-d H:i:s'),       // Submission timestamp
            $_POST['emp_no'],          // Employee Number (BiometricsID)
            $_POST['emp_id'],          // Employee ID (for photos)
            $_POST['emp_name'],        // Full Name
            str_replace(' - LRN', '', $_POST['dept']),  // Department
            $_POST['assigned_area'],   // Area
            $_POST['days'],            // Absence count
            $_POST['start_date'],      // First day out
            $_POST['return_date'],     // Day of return
            $_POST['reason'],          // Detailed reason
            $_POST['notified'],        // Superior notified status
            $superior_info,            // Combined Name & Position
            $_POST['filing_date'],     // User-selected filing date
            $uploaded_med_cert_path,   // Uploaded medical certificate (if any)
            'Pending',                 // Initial status
            null,                      // approved_by
            null                       // approved_at
        ]);

        // Redirect back using root-relative path
        header("Location: /pages/index.php?success=1");
        exit();

    } catch (PDOException $e) {
        error_log("Submit Error: " . $e->getMessage());
        die("Database Error. Please try again later.");
    }
}
?>