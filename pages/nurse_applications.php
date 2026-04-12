<?php
require_once __DIR__ . '/../connection/database.php';

// 1. Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit();
}

// 2. Check if user is a nurse
$current_role = strtolower(trim($_SESSION['role'] ?? ''));
$nurse_roles = ['clinic assistant', 'company nurse'];

if (!in_array($current_role, $nurse_roles)) {
    header("Location: /auth/login.php?error=access_denied&role=" . urlencode($_SESSION['role'] ?? ''));
    exit();
}

include __DIR__ . '/../db/photo_helper.php';

// Pagination Settings
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filtering and Sorting
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_order = $_GET['sort'] ?? 'DESC';

// Handle nurse declaration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['declaration'])) {
    try {
        $id = (int)$_POST['id'];
        $declaration = $_POST['declaration'];
        $reason = $_POST['reason'] ?? '';
        $clinic_clarification = $_POST['clinic_clarification'] ?? null;
        $clinic_date = !empty($_POST['clinic_date']) ? $_POST['clinic_date'] : null;
        $clinic_assessment = $_POST['clinic_assessment'] ?? null;

        if ($id > 0 && in_array($declaration, ['fit to work', 'unfit to work', 'return to work', 'Report to Clinic'])) {
            $sql = "UPDATE rtw_return_to_work
                    SET nurse_declaration = ?,
                        nurse_reason = ?,
                        nurse_declaration_date = CURRENT_TIMESTAMP,
                        clinic_med_cert_clarification = ?,
                        clinic_date_declared = ?,
                        clinic_doctor_assessment = ?
                    WHERE id = ? AND nurse_declaration IS NULL";

            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                $declaration, 
                $reason, 
                $clinic_clarification,
                $clinic_date,
                $clinic_assessment,
                $id
            ]);

            if ($result) {
                header("Location: /pages/nurse_applications.php?success=1");
                exit();
            } else {
                $error = "Failed to update declaration";
            }
        }
    } catch (Exception $e) {
        error_log("Nurse Declaration Submission Error: " . $e->getMessage());
        die("An error occurred during submission.");
    }
}

// Fetch data for the page
try {
    // 1. Get recent declarations for history reference if needed
    $declarations_stmt = $conn->query("SELECT * FROM rtw_return_to_work WHERE nurse_declaration IS NOT NULL ORDER BY nurse_declaration_date DESC LIMIT 20");
    $recent_declarations = $declarations_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get Total Count for Pagination
    $count_query = "SELECT COUNT(*) FROM rtw_return_to_work WHERE nurse_declaration IS NULL";
    $count_params = [];
    if ($date_from) {
        $count_query .= " AND filing_date >= ?";
        $count_params[] = $date_from;
    }
    if ($date_to) {
        $count_query .= " AND filing_date <= ?";
        $count_params[] = $date_to;
    }

    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($count_params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // 3. Fetch Limited Results
    $query = "SELECT * FROM rtw_return_to_work WHERE nurse_declaration IS NULL";
    $params = $count_params;
    if ($date_from) {
        $query .= " AND filing_date >= ?";
    }
    if ($date_to) {
        $query .= " AND filing_date <= ?";
    }

    $orderDirection = ($sort_order === 'ASC') ? 'ASC' : 'DESC';
    $query .= " ORDER BY filing_date $orderDirection, id $orderDirection LIMIT $limit OFFSET $offset";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get specific application if ID provided
    $app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $application = null;
    if ($app_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM rtw_return_to_work WHERE id = ?");
        $stmt->execute([$app_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application || !is_null($application['nurse_declaration'])) {
            header("Location: /pages/nurse_applications.php");
            exit();
        }
    }

} catch (PDOException $e) {
    error_log("Nurse Applications Page Error: " . $e->getMessage());
    die("Database Error.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Applications - Nurse Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/style.css" rel="stylesheet">
</head>

<body class="font-sans text-gray-800 flex flex-col md:flex-row min-h-screen">
    <div class="mesh-bg"></div>

    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <main
        class="flex-1 flex flex-col p-4 md:p-8 lg:p-12 relative z-10 custom-scrollbar overflow-y-auto w-full md:h-[100dvh]">
        <?php 
        $page_title = "New Applications";
        $page_subtitle = "Applications awaiting nurse declaration";
        include __DIR__ . '/../components/header.php'; 
        ?>

        <form method="GET" class="glass-panel rounded-[2rem] p-6 shadow-sm border border-white mb-8 shrink-0">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase ml-1 mb-1 block">Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                        class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-3 rounded-xl outline-none transition-all text-sm font-bold input-focus text-gray-800">
                </div>
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase ml-1 mb-1 block">Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                        class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-3 rounded-xl outline-none transition-all text-sm font-bold input-focus text-gray-800">
                </div>
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase ml-1 mb-1 block">Sort Order</label>
                    <select name="sort"
                        class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-3 rounded-xl outline-none transition-all text-sm font-bold input-focus text-gray-800 appearance-none">
                        <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Newest First</option>
                        <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                </div>
                <button type="submit"
                    class="bg-pink-500 text-white font-black py-3 rounded-xl shadow-lg uppercase text-[10px] tracking-widest hover:bg-pink-600 transition-all">Apply
                    Filters</button>
            </div>
        </form>

        <div class="flex-1 overflow-y-auto pr-4 space-y-6 history-scroll-area pb-[8rem] custom-scrollbar">
            <?php if (empty($applications)): ?>
                <div class="text-center py-20 bg-white/50 rounded-[3rem] border border-dashed border-pink-200">
                    <i class="fa-solid fa-folder-open text-4xl text-pink-200 mb-4"></i>
                    <p class="font-bold text-gray-400">No applications awaiting declaration.</p>
                </div>
            <?php else: ?>
                <?php foreach ($applications as $row): ?>
                    <div class="glass-card rounded-[2rem] p-8 shadow-sm group transition-all hover:shadow-md">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-500 shadow-inner">
                                    <i class="fa-solid fa-stethoscope text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-gray-800 tracking-tight">RTW
                                        <?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?>
                                    </h3>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                        <i class="fa-solid fa-calendar mr-1"></i>
                                        <?= date('M d, Y', strtotime($row['filing_date'])) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="?id=<?= (int) $row['id'] ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&sort=<?= $sort_order ?>&page=<?= $page ?>"
                                    class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest bg-blue-500 text-white hover:bg-blue-600 transition-all">
                                    <i class="fa-solid fa-stethoscope mr-1"></i> Declare
                                </a>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-6 pt-6 border-t border-pink-50">
                            <div class="text-left">
                                <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Applicant</p>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-full overflow-hidden bg-pink-100 flex items-center justify-center shrink-0">
                                        <?= getEmployeePhotoImg($row['employee_id'] ?? '', 'w-full h-full object-cover', htmlspecialchars($row['employee_name'] ?? '')) ?>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700">
                                        <?= htmlspecialchars($row['employee_number'] ?? '') ?> —
                                        <?= htmlspecialchars($row['employee_name'] ?? '') ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-left">
                                <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Area / Type</p>
                                <p class="text-sm font-bold text-gray-700">
                                    <?= htmlspecialchars($row['prodn_type'] ?: 'N/A') ?>
                                </p>
                            </div>
                            <div class="text-left">
                                <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Return Date</p>
                                <p class="text-sm font-bold text-gray-700">
                                    <?= date('M d, Y', strtotime($row['date_returned'])) ?>
                                </p>
                            </div>
                            <div class="text-left">
                                <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Medical Certificate</p>
                                <p class="text-sm font-bold text-gray-700">
                                    <?= !empty($row['medical_certificate_path']) ? '<span class="text-blue-600">Attached</span>' : '<span class="text-gray-400">N/A</span>' ?>
                                </p>
                            </div>
                            <div class="text-left">
                                <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Days Absent</p>
                                <p class="text-sm font-bold text-gray-700"><?= ($row['days_absence'] ?? 0) ?> Day(s)</p>
                            </div>
                            <div class="text-right flex flex-col items-end justify-center gap-2">
                                <button onclick='openDetails(<?= json_encode($row) ?>)'
                                    class="text-xs font-black text-pink-500 uppercase flex items-center gap-2 hover:text-pink-700 transition-colors">
                                    View Full Details <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination-capsule flex items-center gap-1">
                <?php
                $start_p = max(1, $page - 2);
                $end_p = min($total_pages, $page + 2);
                ?>

                <?php if ($start_p > 1): ?>
                    <a href="?page=1&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&sort=<?= $sort_order ?>"
                        class="page-link" title="First Page">
                        <i class="fa-solid fa-angles-left text-[10px]"></i>
                    </a>
                <?php endif; ?>

                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&sort=<?= $sort_order ?>"
                        class="page-link" title="Previous Page">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = $start_p; $i <= $end_p; $i++): ?>
                    <a href="?page=<?= $i ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&sort=<?= $sort_order ?>"
                        class="page-link <?= ($i == $page) ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&sort=<?= $sort_order ?>"
                        class="page-link" title="Next Page">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                <?php endif; ?>

                <?php if ($end_p < $total_pages): ?>
                    <a href="?page=<?= $total_pages ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&sort=<?= $sort_order ?>"
                        class="page-link" title="Last Page">
                        <i class="fa-solid fa-angles-right text-[10px]"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($application): ?>
            <!-- Declaration Modal -->
            <div id="declarationModal"
                class="fixed inset-0 z-[100] bg-slate-900/50 flex items-center justify-center p-6 performance-backdrop">
                <div
                    class="bg-white w-full max-w-6xl rounded-[3rem] shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto animate-modal-entry">
                    <div class="p-8 border-b border-pink-50 flex justify-between items-center bg-pink-50/30">
                        <div>
                            <h3 class="text-2xl font-black text-gray-800">Medical Declaration</h3>
                            <p class="text-sm font-bold text-pink-400 uppercase mt-1">RTW
                                <?= str_pad($application['id'], 4, '0', STR_PAD_LEFT) ?> -
                                <?= htmlspecialchars($application['employee_name'] ?? '') ?>
                            </p>
                        </div>
                        <button onclick="closeDeclarationModal()" class="text-gray-400 hover:text-rose-500 text-2xl"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <form action="/pages/nurse_applications.php" method="POST" class="p-8" onsubmit="return validateForm()">
                        <input type="hidden" name="id" value="<?= $application['id'] ?>">

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- Application Details -->
                            <div class="lg:col-span-2 space-y-6">
                                <div class="bg-pink-50/30 p-6 rounded-3xl border border-pink-100">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div
                                            class="w-16 h-16 rounded-full overflow-hidden bg-pink-100 flex items-center justify-center shrink-0">
                                            <?= getEmployeePhotoImg($application['employee_id'] ?? '', 'w-full h-full object-cover', htmlspecialchars($application['employee_name'] ?? '')) ?>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] font-black text-pink-400 uppercase mb-1">Employee</label>
                                            <p class="text-lg font-black text-gray-800">
                                                <?= htmlspecialchars($application['employee_name'] ?? '') ?>
                                                (<?= htmlspecialchars($application['employee_number'] ?? '') ?>)
                                            </p>
                                        </div>
                                    </div>
                                    <label class="block text-[10px] font-black text-pink-400 uppercase mb-2">Detailed
                                        Reason</label>
                                    <p class="text-gray-700 font-medium leading-relaxed">
                                        <?= htmlspecialchars($application['reason'] ?? 'No reason provided') ?>
                                    </p>
                                </div>

                                <?php if (!empty($application['medical_certificate_path'])): ?>
                                    <div class="bg-blue-50/30 p-6 rounded-3xl border border-blue-100">
                                        <label class="block text-[10px] font-black text-blue-400 uppercase mb-3">Medical
                                            Certificate</label>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600">
                                                    <i class="fa-solid fa-file-pdf text-lg"></i>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-bold text-gray-800">Medical Certificate Attached
                                                    </p>
                                                    <p class="text-xs text-gray-600">Click to view full document</p>
                                                </div>
                                            </div>
                                            <a href="/<?= htmlspecialchars($application['medical_certificate_path']) ?>"
                                                target="_blank" rel="noopener"
                                                class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-600 transition-all hover:scale-105">
                                                <i class="fa-solid fa-external-link-alt"></i>
                                                View PDF
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-4 rounded-2xl border border-pink-50"><label
                                            class="text-[9px] font-black text-gray-400 uppercase">First Date</label>
                                        <p class="font-bold"><?= htmlspecialchars($application['first_date_absence'] ?? 'N/A') ?></p>
                                    </div>
                                    <div class="p-4 rounded-2xl border border-pink-50"><label
                                            class="text-[9px] font-black text-gray-400 uppercase">Return Date</label>
                                        <p class="font-bold">
                                            <?= date('M d, Y', strtotime($application['date_returned'])) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Declaration Form -->
                            <div class="space-y-6">
                                <div class="bg-blue-50/30 p-6 rounded-3xl border border-blue-100">
                                    <h4 class="text-lg font-black text-blue-600 mb-4">Medical Declaration</h4>
                                    <p class="text-sm text-gray-600 mb-6">Assess employee's fitness to return to work
                                    </p>

                                    <div class="space-y-4">
                                        <div class="flex items-center gap-3">
                                            <input type="radio" id="fit" name="declaration" value="fit to work"
                                                class="w-5 h-5 text-emerald-500" required>
                                            <label for="fit" class="text-base font-bold text-emerald-600 cursor-pointer">
                                                <i class="fa-solid fa-check-circle mr-2"></i> Fit to Work
                                            </label>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <input type="radio" id="unfit" name="declaration" value="unfit to work"
                                                class="w-5 h-5 text-red-500" required>
                                            <label for="unfit" class="text-base font-bold text-red-600 cursor-pointer">
                                                <i class="fa-solid fa-times-circle mr-2"></i> Unfit to Work
                                            </label>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <input type="radio" id="return" name="declaration" value="return to work"
                                                class="w-5 h-5 text-blue-500" required>
                                            <label for="return"
                                                class="text-base font-bold text-blue-600 cursor-pointer">
                                                <i class="fa-solid fa-clinic-medical mr-2"></i> Return to Work
                                            </label>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <input type="radio" id="report_clinic" name="declaration" value="Report to Clinic"
                                                class="w-5 h-5 text-purple-500" required>
                                            <label for="report_clinic"
                                                class="text-base font-bold text-purple-600 cursor-pointer">
                                                <i class="fa-solid fa-hospital-user mr-2"></i> Report to Clinic
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Clinic Specific Fields -->
                                    <div id="clinic_fields" class="hidden mt-6 space-y-4 p-4 bg-purple-50/50 rounded-2xl border border-purple-100">
                                        <div>
                                            <label class="block text-[10px] font-black text-purple-400 uppercase mb-2">Clarification on Medical Certificate</label>
                                            <textarea name="clinic_clarification" id="clinic_clarification" rows="2"
                                                class="w-full bg-white border-2 border-purple-100 focus:border-purple-400 p-3 rounded-xl outline-none text-sm font-medium text-gray-800 transition-colors"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-purple-400 uppercase mb-2">Date Declared</label>
                                            <input type="date" name="clinic_date" id="clinic_date"
                                                class="w-full bg-white border-2 border-purple-100 focus:border-purple-400 p-3 rounded-xl outline-none text-sm font-bold text-gray-800 transition-colors">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-purple-400 uppercase mb-2">Physical Assessment of In-House Doctor</label>
                                            <input type="text" name="clinic_assessment" id="clinic_assessment"
                                                class="w-full bg-white border-2 border-purple-100 focus:border-purple-400 p-3 rounded-xl outline-none text-sm font-bold text-gray-800 transition-colors"
                                                placeholder="Name of physician or assessment summary...">
                                        </div>
                                    </div>

                                    <div id="reason_section" class="hidden mt-4">
                                        <label id="reason_label"
                                            class="block text-xs font-black text-gray-400 uppercase mb-2">Remarks</label>
                                        <textarea name="reason" id="reason_input" rows="3"
                                            class="w-full bg-gray-50/50 border-2 border-gray-200 focus:border-blue-400 p-3 rounded-xl outline-none text-sm font-medium text-gray-800 placeholder-gray-400 transition-colors"
                                            placeholder="Remarks are required..." required></textarea>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <button type="button" onclick="closeDeclarationModal()"
                                        class="w-full py-3 text-gray-600 font-black uppercase text-xs tracking-widest hover:text-gray-800 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="w-full bg-gradient-to-r from-blue-500 to-cyan-600 text-white font-black py-4 rounded-xl shadow-lg uppercase text-sm tracking-widest transition-all flex items-center justify-center gap-2 hover:shadow-xl hover:shadow-blue-500/70 hover:translate-y-[-2px]">
                                        <i class="fa-solid fa-stethoscope"></i> Submit Declaration
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Details Modal -->
    <div id="detailsModal"
        class="fixed inset-0 z-[100] hidden bg-slate-900/50 flex items-center justify-center p-6 performance-backdrop">
        <div class="bg-white w-full max-w-4xl rounded-[3rem] shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-pink-50 flex justify-between items-center bg-pink-50/30">
                <h3 id="modalTitle" class="text-2xl font-black text-gray-800">Application Details</h3>
                <button onclick="closeDetails()" class="text-gray-400 hover:text-rose-500 text-2xl"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-10 grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
                <div class="space-y-6 md:col-span-2">
                    <div class="bg-pink-50/30 p-6 rounded-3xl border border-pink-100">
                        <div class="flex items-center gap-4 mb-4">
                            <div id="modalEmployeePhoto"
                                class="w-16 h-16 rounded-full overflow-hidden bg-pink-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user text-gray-400 text-xl"></i>
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-black text-pink-400 uppercase mb-1">Employee</label>
                                <p id="modalEmployeeName" class="text-lg font-black text-gray-800"></p>
                            </div>
                        </div>
                        <label class="block text-[10px] font-black text-pink-400 uppercase mb-2">Detailed Reason</label>
                        <p id="modalReason" class="text-gray-700 font-medium leading-relaxed"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-2xl border border-pink-50"><label
                                class="text-[9px] font-black text-gray-400 uppercase">First Date</label>
                            <p id="modalFirstDay" class="font-bold"></p>
                        </div>
                        <div class="p-4 rounded-2xl border border-pink-50"><label
                                class="text-[9px] font-black text-gray-400 uppercase">Return Date</label>
                            <p id="modalReturnDay" class="font-bold"></p>
                        </div>
                    </div>
                    <div id="modalMedCertCard" class="p-4 rounded-2xl bg-pink-50 border border-pink-100 hidden">
                        <label class="text-[9px] font-black text-pink-400 uppercase">Medical Certificate</label>
                        <a id="modalMedCertLink" href="#" target="_blank" rel="noopener"
                            class="block mt-2 text-sm font-black text-pink-600 underline underline-offset-4 hover:text-pink-700 break-all"></a>
                        <p id="modalMedCertEmpty" class="mt-2 text-xs font-semibold text-pink-600">No medical certificate attached.</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100"><label
                            class="text-[9px] font-black text-gray-400 uppercase">Days Absent</label>
                        <p id="modalDays" class="font-bold"></p>
                    </div>
                    <div class="p-4 rounded-2xl bg-pink-50 border border-pink-100"><label
                            class="text-[9px] font-black text-pink-400 uppercase">Superior</label>
                        <p id="modalSupDetails" class="font-bold text-pink-600"></p>
                    </div>
                    <div class="p-4 rounded-2xl bg-blue-50 border border-blue-100"><label
                            class="text-[9px] font-black text-blue-400 uppercase">Status</label>
                        <p class="font-bold text-blue-600">Awaiting Declaration</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal"
        class="fixed inset-0 z-[100] hidden bg-green-900/20 backdrop-blur-sm flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-green-50 flex justify-between items-center bg-green-50/30">
                <div>
                    <h3 class="text-2xl font-black text-gray-800">Declaration Submitted</h3>
                    <p class="text-sm font-bold text-green-400 uppercase mt-1">Success!</p>
                </div>
                <button onclick="closeSuccessModal()" class="text-gray-400 hover:text-green-500 text-2xl"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-500 text-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl">
                    <i class="fa-solid fa-check text-2xl"></i>
                </div>
                <p class="text-gray-600 font-medium mb-8">Medical declaration has been recorded successfully and is now available for supervisor review.</p>
                <div class="flex gap-3">
                    <button onclick="closeSuccessModal()" class="flex-1 py-3 text-gray-600 font-black uppercase text-xs tracking-widest hover:text-gray-800 transition-colors">
                        Continue
                    </button>
                    <a href="/pages/nurse_declaration.php" class="flex-1 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-black py-3 rounded-xl shadow-lg uppercase text-xs tracking-widest hover:shadow-xl transition-all text-center">
                        View History
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleReason() {
            const unfitRadio = document.getElementById('unfit');
            const clinicRadio = document.getElementById('report_clinic');
            const declarationRadios = document.querySelectorAll('input[name="declaration"]');
            const isAnyChecked = Array.from(declarationRadios).some(r => r.checked);

            const reasonSection = document.getElementById('reason_section');
            const reasonLabel = document.getElementById('reason_label');
            const reasonInput = document.getElementById('reason_input');
            const clinicFields = document.getElementById('clinic_fields');

            if (clinicRadio.checked) {
                clinicFields.classList.remove('hidden');
                document.getElementById('clinic_clarification').required = true;
                document.getElementById('clinic_date').required = true;
                document.getElementById('clinic_assessment').required = true;
            } else {
                clinicFields.classList.add('hidden');
                document.getElementById('clinic_clarification').required = false;
                document.getElementById('clinic_date').required = false;
                document.getElementById('clinic_assessment').required = false;
            }

            if (isAnyChecked) {
                reasonSection.classList.remove('hidden');
                reasonInput.required = true;
            }

            if (unfitRadio.checked) {
                reasonLabel.innerText = "Reason for Unfitness";
                reasonLabel.classList.remove('text-gray-400');
                reasonLabel.classList.add('text-red-400');
                reasonInput.placeholder = "Specify medical reason for unfitness to work...";
            } else {
                reasonLabel.innerText = "Remarks";
                reasonLabel.classList.remove('text-red-400');
                reasonLabel.classList.add('text-gray-400');
                reasonInput.placeholder = "Enter mandatory remarks...";
            }
        }

        function validateForm() {
            const declarationRadios = document.querySelectorAll('input[name="declaration"]');
            const declarationSelected = Array.from(declarationRadios).some(radio => radio.checked);
            if (!declarationSelected) {
                alert('Please select a declaration option.');
                return false;
            }
            const reason = document.getElementById('reason_input').value.trim();
            if (!reason) {
                alert('Please provide remarks or reason.');
                return false;
            }
            return confirm('Submit this nurse declaration?');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1') {
                const successModal = document.getElementById('successModal');
                if (successModal) {
                    successModal.classList.remove('hidden');
                }
            }
            document.querySelectorAll('input[name="declaration"]').forEach(radio => {
                radio.addEventListener('change', toggleReason);
            });
        });

        function closeDeclarationModal() {
            const url = new URL(window.location);
            url.searchParams.delete('id');
            window.location.href = url.pathname + url.search;
        }

        function openDetails(data) {
            document.getElementById('detailsModal').classList.remove('hidden');
            document.getElementById('modalTitle').innerText = 'Application #' + data.id.toString().padStart(4, '0');
            document.getElementById('modalEmployeeName').innerText = (data.employee_name || 'N/A') + ' (' + (data.employee_number || '') + ')';
            document.getElementById('modalReason').innerText = data.reason || 'No reason provided';
            document.getElementById('modalFirstDay').innerText = data.first_date_absence || 'N/A';
            document.getElementById('modalReturnDay').innerText = data.date_returned ? new Date(data.date_returned).toLocaleDateString() : 'N/A';
            document.getElementById('modalDays').innerText = (data.days_absence || '0') + ' Day(s)';
            document.getElementById('modalSupDetails').innerText = data.superior_name_position || 'Not Notified';

            const photoContainer = document.getElementById('modalEmployeePhoto');
            if (data.employee_id) {
                const photoUrl = 'http://10.2.0.8/lrnph/emp_photos/' + data.employee_id + '.jpg';
                photoContainer.innerHTML = '<img src="' + photoUrl + '" alt="Employee" class="w-full h-full object-cover" onerror="this.src=\'/assets/default-avatar.png\'" />';
            }

            const medCard = document.getElementById('modalMedCertCard');
            const medLink = document.getElementById('modalMedCertLink');
            const medEmpty = document.getElementById('modalMedCertEmpty');
            const medPath = (data.medical_certificate_path || '').trim();
            if (medPath) {
                medCard.classList.remove('hidden');
                medLink.classList.remove('hidden');
                medEmpty.classList.add('hidden');
                medLink.href = '/' + medPath;
                medLink.textContent = 'Open attachment';
            } else {
                medCard.classList.add('hidden');
                medLink.classList.add('hidden');
                medEmpty.classList.remove('hidden');
            }
        }

        function closeDetails() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, '', url);
        }

        window.onclick = (e) => {
            if (e.target == document.getElementById('detailsModal')) closeDetails();
            if (e.target == document.getElementById('successModal')) closeSuccessModal();
            if (e.target == document.getElementById('declarationModal')) closeDeclarationModal();
        }
    </script>
    <?php include __DIR__ . '/../components/logout_modal.php'; ?>
</body>

</html>
y>

</html>