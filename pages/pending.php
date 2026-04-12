<?php
require_once __DIR__ . '/../connection/database.php';

// 1. Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit();
}

// 2. Check Authorization Authority
// Only authorized approvers can access this page
$is_authorized = $_SESSION['is_approver'] ?? false;

if (!$is_authorized) {
    header("Location: /auth/login.php?error=access_denied&role=" . urlencode($_SESSION['department'] ?? ''));
    exit();
}

include '../db/photo_helper.php';

// Get approver's department for filtering
$approver_department = $_SESSION['department'] ?? '';

// Pagination Settings
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filtering and Sorting
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_order = $_GET['sort'] ?? 'DESC';

try {
    // 1. Get Total Count for Pagination (Filtered by department)
    $count_query = "SELECT COUNT(*) FROM rtw_return_to_work WHERE status = 'Pending' AND department = ?";
    $count_params = [$approver_department];
    if ($search) {
        $count_query .= " AND (\"employee_name\" ILIKE ? OR \"employee_id\" ILIKE ? OR \"employee_number\" ILIKE ? OR \"prodn_type\" ILIKE ?)";
        $search_param = '%' . $search . '%';
        $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param]);
    }
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

    // 2. Fetch Limited Results (Filtered by department)
    $query = "SELECT * FROM rtw_return_to_work WHERE status = 'Pending' AND department = ?";
    $params = [$approver_department];
    if ($search) {
        $query .= " AND (\"employee_name\" ILIKE ? OR \"employee_id\" ILIKE ? OR \"employee_number\" ILIKE ? OR \"prodn_type\" ILIKE ?)";
        $search_param = '%' . $search . '%';
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    if ($date_from) {
        $query .= " AND filing_date >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $query .= " AND filing_date <= ?";
        $params[] = $date_to;
    }

    $orderDirection = ($sort_order === 'ASC') ? 'ASC' : 'DESC';
    $query .= " ORDER BY filing_date $orderDirection, id $orderDirection";
    $query .= " LIMIT $limit OFFSET $offset";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get pending count for sidebar badge (filtered by department)
    $pending_count_stmt = $conn->prepare("SELECT COUNT(*) FROM rtw_return_to_work WHERE status = 'Pending' AND department = ?");
    $pending_count_stmt->execute([$approver_department]);
    $pending_count = $pending_count_stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Pending Page Error: " . $e->getMessage());
    die("Database Error. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pending - La Rose Noire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/style.css" rel="stylesheet">
    <style>
        .pagination-capsule {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, rgba(255, 250, 245, 0.9), rgba(251, 245, 255, 0.9));
            backdrop-filter: blur(20px);
            padding: 0.75rem 1.5rem;
            border-radius: 5rem;
            border: 1px solid rgba(236, 72, 153, 0.2);
            box-shadow: 0 10px 25px -5px rgba(236, 72, 153, 0.15);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 60;
        }

        .page-link {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            font-weight: 800;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            color: #94a3b8;
        }

        .page-link.active {
            background-color: #ec4899;
            color: white;
            box-shadow: 0 4px 12px rgba(236, 72, 153, 0.4);
        }

        .page-link:hover:not(.active) {
            background-color: rgba(236, 72, 153, 0.1);
            color: #ec4899;
        }
    </style>
</head>

<body class="font-sans text-gray-800 flex flex-col md:flex-row min-h-screen">
    <div class="mesh-bg"></div>

    <!-- Mobile Overlay -->
    <div id="mobileOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-transparent z-[90] hidden md:hidden">
    </div>

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <main
        class="flex-1 flex flex-col p-4 md:p-8 lg:p-12 relative z-10 custom-scrollbar overflow-y-auto w-full md:h-[100dvh]">
        <!-- Mobile Header -->
        <div class="md:hidden flex justify-between items-center mb-6 shrink-0 relative z-[60]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 glass-effect flex items-center justify-center shadow-lg rounded-xl">
                    <img src="/assets/logo.jpg" alt="Logo" class="w-full h-full object-contain p-1">
                </div>
                <span class="font-black text-gray-800 tracking-tight">Return to Work</span>
            </div>
            <button onclick="toggleSidebar()"
                class="w-10 h-10 bg-white/80 backdrop-blur-md rounded-xl shadow-lg flex items-center justify-center text-pink-500 hover:bg-white transition-colors cursor-pointer active:scale-95">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </div>
        
        <?php 
        $page_title = "Pending Approvals";
        $page_subtitle = "Entries waiting for your decision";
        include '../components/header.php'; 
        ?>

        <form method="GET" class="glass-panel rounded-[2rem] p-6 shadow-sm border border-white mb-8 shrink-0">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase ml-1 mb-1 block">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Name, ID, or Area..."
                        class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-3 rounded-xl outline-none transition-all text-sm font-bold input-focus text-gray-800 placeholder-gray-600"
                        autocomplete="off">
                </div>
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
                <div>
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-pink-500 to-rose-500 text-white font-black py-3 rounded-xl shadow-lg uppercase text-[10px] tracking-widest hover:shadow-xl transition-all hover:scale-105">
                        <i class="fa-solid fa-magnifying-glass mr-2"></i>Apply Filters
                    </button>
                </div>
            </div>
        </form>

        <div class="flex-1 overflow-y-auto pr-4 space-y-6 history-scroll-area pb-[8rem] custom-scrollbar">
            <?php if (empty($submissions)): ?>
                <div class="text-center py-20 bg-white/50 rounded-[3rem] border border-dashed border-pink-200">
                    <i class="fa-solid fa-folder-open text-4xl text-pink-200 mb-4"></i>
                    <p class="font-bold text-gray-400">No pending entries.</p>
                </div>
            <?php else: ?>
                <?php foreach ($submissions as $row): ?>
                    <div class="glass-card rounded-[2rem] p-8 shadow-sm group transition-all hover:shadow-md">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 bg-pink-100 rounded-2xl flex items-center justify-center text-pink-500 shadow-inner">
                                    <i class="fa-solid fa-file-lines text-xl"></i>
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
                                <?php if ($row['nurse_declaration'] === null): ?>
                                    <!-- Single disabled button for undeclared applications -->
                                    <button disabled
                                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest bg-gray-400 text-gray-200 cursor-not-allowed"
                                        title="Application needs nurse declaration before any decision">
                                        <i class="fa-solid fa-clock mr-1"></i> Waiting for Declaration
                                    </button>
                                <?php else: ?>
                                    <!-- Enabled buttons for declared applications -->
                                    <form method="POST" action="/db/update_status.php" class="inline-block">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="return" value="pending.php">
                                        <button type="submit"
                                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest bg-sky-500 text-white hover:bg-sky-600 transition-all">
                                            <i class="fa-solid fa-check mr-1"></i> Approve
                                        </button>
                                    </form>

                                    <button onclick="openDeclineModal(<?= (int) $row['id'] ?>)"
                                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest bg-rose-500 text-white hover:bg-rose-600 transition-all">
                                        <i class="fa-solid fa-xmark mr-1"></i> Decline
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-6 pt-6 border-t border-pink-50">
                            <div class="text-left">
                                <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Applicant</p>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-full overflow-hidden bg-pink-100 flex items-center justify-center shrink-0">
                                        <?= getEmployeePhotoImg($row['employee_id'] ?? '', 'w-full h-full object-cover shadow-sm', htmlspecialchars($row['employee_name'] ?? '')) ?>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700"><?= htmlspecialchars($row['employee_number'] ?? '') ?>
                                        — <?= htmlspecialchars($row['employee_name'] ?? '') ?></p>
                                </div>
                            </div>
                            <div class="text-left">
                                <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Area / Type</p>
                                <p class="text-sm font-bold text-gray-700"><?= htmlspecialchars($row['prodn_type'] ?: 'N/A') ?>
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
                                <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Nurse Declaration</p>
                                <p class="text-sm font-bold text-gray-700">
                                    <?php
                                    $nurseDecl = $row['nurse_declaration'];
                                    if ($nurseDecl === null) {
                                        echo '<span class="text-orange-600">Not Declared Yet</span>';
                                    } else {
                                        $declColor = 'text-black';
                                        if ($nurseDecl === 'unfit to work')
                                            $declColor = 'text-red-600';
                                        echo '<span class="' . $declColor . '">' . ucfirst($nurseDecl) . '</span>';
                                        if (!empty($row['nurse_reason'])) {
                                            $reason = htmlspecialchars($row['nurse_reason']);
                                            $short_reason = strlen($reason) > 20 ? substr($reason, 0, 20) . '...' : $reason;
                                            echo '<span class="text-xs text-gray-500 block mt-1" title="' . $reason . '">(' . $short_reason . ')</span>';
                                        }
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="text-right flex items-center justify-end">
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
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                ?>

                <?php if ($start > 1): ?>
                    <a href="?page=1&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort_order ?>"
                        class="page-link" title="First Page">
                        <i class="fa-solid fa-angles-left text-[10px]"></i>
                    </a>
                <?php endif; ?>

                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort_order ?>"
                        class="page-link" title="Previous Page">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort_order ?>"
                        class="page-link <?= ($i == $page) ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort_order ?>"
                        class="page-link" title="Next Page">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                <?php endif; ?>

                <?php if ($end < $total_pages): ?>
                    <a href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort_order ?>"
                        class="page-link" title="Last Page">
                        <i class="fa-solid fa-angles-right text-[10px]"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <div id="detailsModal"
        class="fixed inset-0 z-[100] hidden bg-pink-900/20 backdrop-blur-sm flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-4xl rounded-[3rem] shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-pink-50 flex justify-between items-center bg-pink-50/30">
                <div>
                    <h3 id="modalTitle" class="text-2xl font-black text-gray-800">Application Details</h3>
                    <p id="modalSubtitle" class="text-sm font-bold text-pink-400 uppercase mt-1"></p>
                </div>
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
                        <p id="modalMedCertEmpty" class="mt-2 text-xs font-semibold text-pink-600">No medical
                            certificate attached.</p>
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
                            class="text-[9px] font-black text-blue-400 uppercase">Nurse Declaration</label>
                        <p id="modalNurseDeclaration" class="font-bold text-blue-600"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Decline Reason Modal -->
    <div id="declineModal"
        class="fixed inset-0 z-[100] hidden bg-pink-900/20 backdrop-blur-sm flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-pink-50 flex justify-between items-center bg-rose-50/30">
                <div>
                    <h3 class="text-2xl font-black text-gray-800">Decline Application</h3>
                    <p class="text-sm font-bold text-rose-400 uppercase mt-1">Provide reason for decline</p>
                </div>
                <button onclick="closeDeclineModal()" class="text-gray-400 hover:text-rose-500 text-2xl"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="/db/update_status.php" class="p-8">
                <input type="hidden" name="id" id="decline_id" value="">
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="return" value="pending.php">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-black text-gray-600 uppercase mb-2">Reason for
                            Declining</label>
                        <textarea name="decline_reason" id="decline_reason" rows="4" required
                            class="w-full bg-rose-50/30 border-2 border-rose-200 p-4 rounded-2xl outline-none text-sm font-medium placeholder-gray-500 resize-none"
                            placeholder="Please provide a detailed reason for declining this application..."></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closeDeclineModal()"
                            class="flex-1 py-3 text-gray-600 font-black uppercase text-xs tracking-widest hover:text-gray-800 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 bg-rose-500 text-white font-black py-3 rounded-2xl shadow-lg uppercase text-xs tracking-widest hover:bg-rose-600 transition-all">
                            Decline Application
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include '../components/logout_modal.php'; ?>

    <script>
        let currentViewId = null;
        function openDetails(data) {
            currentViewId = data.id;
            document.getElementById('detailsModal').classList.remove('hidden');
            document.getElementById('modalTitle').innerText = 'Application #' + data.id.toString().padStart(4, '0');
            document.getElementById('modalSubtitle').innerText = 'Employee: ' + data.employee_number + ' | ' + (data.employee_name || 'N/A');
            document.getElementById('modalEmployeeName').innerText = (data.employee_name || 'N/A') + ' (' + data.employee_number + ')';
            document.getElementById('modalReason').innerText = data.reason || 'No reason provided';
            document.getElementById('modalFirstDay').innerText = data.first_date_absence || 'N/A';
            document.getElementById('modalReturnDay').innerText = data.date_returned || 'N/A';
            document.getElementById('modalDays').innerText = (data.days_absence || '0') + ' Day(s)';
            document.getElementById('modalSupDetails').innerText = data.superior_name_position || 'Not Notified';

            // Display nurse declaration
            const nurseDecl = data.nurse_declaration;
            let displayText, declColor;

            if (nurseDecl === null) {
                displayText = 'Not Declared Yet';
                declColor = 'text-orange-600';
            } else {
                displayText = nurseDecl.charAt(0).toUpperCase() + nurseDecl.slice(1);
                declColor = 'text-black';
                if (nurseDecl === 'unfit to work') declColor = 'text-red-600';
            }

            document.getElementById('modalNurseDeclaration').innerHTML = `<span class="${declColor}">${displayText}</span>`;
            if (data.nurse_reason) {
                document.getElementById('modalNurseDeclaration').innerHTML += `<br><small class="text-gray-500">${data.nurse_reason}</small>`;
            }

            // Update employee photo
            const photoContainer = document.getElementById('modalEmployeePhoto');
            if (data.employee_id) {
                const photoUrl = 'http://10.2.0.8/lrnph/emp_photos/' + data.employee_id + '.jpg';
                photoContainer.innerHTML = '<img src="' + photoUrl + '" alt="' + (data.employee_name || 'Employee') + '" class="w-full h-full object-cover" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'inline-block\';" /><i class="fa-solid fa-user text-gray-400 text-xl" style="display:none;"></i>';
            }

            // Handle medical certificate
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
        function closeDetails() { document.getElementById('detailsModal').classList.add('hidden'); }

        function openDeclineModal(id) {
            document.getElementById('decline_id').value = id;
            document.getElementById('decline_reason').value = '';
            document.getElementById('declineModal').classList.remove('hidden');
            document.getElementById('decline_reason').focus();
        }

        function closeDeclineModal() {
            document.getElementById('declineModal').classList.add('hidden');
        }

        window.onclick = (e) => {
            if (e.target == document.getElementById('detailsModal')) closeDetails();
            if (e.target == document.getElementById('declineModal')) closeDeclineModal();
        }
    </script>
</body>

</html>