<?php
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Check if user is a nurse
$current_role = strtolower(trim($_SESSION['role'] ?? ''));
$nurse_roles = ['clinic assistant', 'company nurse'];

if (!in_array($current_role, $nurse_roles)) {
    header("Location: ../auth/login.php?error=access_denied&role=" . urlencode($_SESSION['role']));
    exit();
}

include '../db/db.php';
include '../db/photo_helper.php';

// Pagination Settings
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filtering and Sorting
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_order = $_GET['sort'] ?? 'DESC';

try {
    // 1. Get Total Count for Pagination (completed nurse declarations)
    $count_query = "SELECT COUNT(*) FROM return_to_work WHERE nurse_declaration IS NOT NULL";
    $count_params = [];
    if ($date_from) {
        $count_query .= " AND nurse_declaration_date >= ?";
        $count_params[] = $date_from;
    }
    if ($date_to) {
        $count_query .= " AND nurse_declaration_date <= ?";
        $count_params[] = $date_to;
    }

    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($count_params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // 2. Fetch Limited Results (completed nurse declarations)
    $query = "SELECT * FROM return_to_work WHERE nurse_declaration IS NOT NULL";
    $params = $count_params; // Reuse filters
    if ($date_from) {
        $query .= " AND nurse_declaration_date >= ?";
    }
    if ($date_to) {
        $query .= " AND nurse_declaration_date <= ?";
    }

    $orderDirection = ($sort_order === 'ASC') ? 'ASC' : 'DESC';
    $query .= " ORDER BY nurse_declaration_date $orderDirection, id $orderDirection";
    $query .= " OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY"; // For SQL Server

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get pending count for sidebar badge
    $pending_count_stmt = $conn->query("SELECT COUNT(*) FROM return_to_work WHERE nurse_declaration IS NULL");
    $pending_count = $pending_count_stmt->fetchColumn();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Handle form submission (this should not happen on applications page, but keeping for compatibility)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $declaration = $_POST['declaration'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if ($id > 0 && in_array($declaration, ['fit to work', 'unfit to work', 'return to work'])) {
        try {
            $sql = "UPDATE return_to_work
                    SET nurse_declaration = ?,
                        nurse_reason = ?,
                        nurse_declaration_date = GETDATE(),
                        status = CASE
                            WHEN ? = 'fit to work' THEN 'Pending'
                            WHEN ? = 'unfit to work' THEN 'Declined'
                            WHEN ? = 'return to work' THEN 'Pending'
                            ELSE status
                        END
                    WHERE id = ? AND nurse_declaration IS NULL";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$declaration, $reason, $declaration, $declaration, $declaration, $id]);

            header("Location: nurse_declaration.php?success=1");
            exit();
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}

// Get application ID from URL
$app_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch application details if ID provided
$application = null;
if ($app_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM return_to_work WHERE id = ?");
        $stmt->execute([$app_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application || !is_null($application['nurse_declaration'])) {
            header("Location: nurse_applications.php");
            exit();
        }
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Declaration History - Nurse Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
</head>

<body class="font-sans text-gray-800 flex flex-col md:flex-row min-h-screen">
    <div class="mesh-bg"></div>

    <?php include '../components/sidebar.php'; ?>

    <main
        class="flex-1 flex flex-col p-4 md:p-8 lg:p-12 relative z-10 custom-scrollbar overflow-y-auto w-full md:h-[100dvh]">
        <?php 
        $page_subtitle = "All completed nurse declarations";
        include '../components/header.php'; 
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
                    <?php foreach ($applications as $app): ?>
                        <div class="glass-card rounded-[2rem] p-8 shadow-sm group transition-all hover:shadow-md">
                            <div class="flex justify-between items-start mb-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 bg-pink-100 rounded-2xl flex items-center justify-center text-pink-500 shadow-inner">
                                        <i class="fa-solid fa-file-medical text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-black text-gray-800 tracking-tight">RTW
                                            <?= str_pad($app['id'], 4, '0', STR_PAD_LEFT) ?>
                                        </h3>
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                            <i class="fa-solid fa-calendar mr-1"></i>
                                            <?= date('M d, Y', strtotime($app['filing_date'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 rounded-lg font-bold text-xs uppercase
                            <?php
                            $decl = $app['nurse_declaration'];
                            switch (strtolower($decl)) {
                                case 'fit to work':
                                    echo 'text-gray-700 bg-gray-100 border border-gray-200';
                                    break;
                                case 'unfit to work':
                                    echo 'text-red-700 bg-red-100 border border-red-200';
                                    break;
                                case 'return to work':
                                    echo 'text-blue-700 bg-blue-100 border border-blue-200';
                                    break;
                                default:
                                    echo 'text-gray-700 bg-gray-100 border border-gray-200';
                            }
                            ?>">
                                        <i class="fa-solid fa-stethoscope mr-1"></i> <?= htmlspecialchars(ucwords($decl)) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-6 gap-6 pt-6 border-t border-pink-50">
                                <div class="text-left">
                                    <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Applicant</p>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-8 h-8 rounded-full overflow-hidden bg-pink-100 flex items-center justify-center shrink-0">
                                            <?= getEmployeePhotoImg($app['employee_id'] ?? '', 'w-full h-full object-cover', htmlspecialchars($app['employee_name'])) ?>
                                        </div>
                                        <p class="text-sm font-bold text-gray-700">
                                            <?= htmlspecialchars($app['employee_number']) ?>
                                            — <?= htmlspecialchars($app['employee_name']) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Area / Type</p>
                                    <p class="text-sm font-bold text-gray-700">
                                        <?= htmlspecialchars($app['prodn_type'] ?: 'N/A') ?>
                                    </p>
                                </div>
                                <div class="text-left">
                                    <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Return Date</p>
                                    <p class="text-sm font-bold text-gray-700">
                                        <?= date('M d, Y', strtotime($app['date_returned'])) ?>
                                    </p>
                                </div>
                                <div class="text-left">
                                    <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Medical Certificate</p>
                                    <p class="text-sm font-bold text-gray-700">
                                        <?= !empty($app['medical_certificate_path']) ? '<span class="text-blue-600">Attached</span>' : '<span class="text-gray-400">N/A</span>' ?>
                                    </p>
                                </div>
                                <div class="text-left">
                                    <p class="text-[9px] font-black text-pink-400 uppercase mb-1">Declaration</p>
                                    <p class="text-sm font-bold
                            <?php
                            $decl = $app['nurse_declaration'];
                            switch (strtolower($decl)) {
                                case 'fit to work':
                                    echo 'text-black';
                                    break;
                                case 'unfit to work':
                                    echo 'text-red-600';
                                    break;
                                case 'return to work':
                                    echo 'text-blue-600';
                                    break;
                                default:
                                    echo 'text-gray-600';
                            }
                            ?>">
                                        <?= htmlspecialchars(ucwords($decl)) ?>
                                        <?php if (!empty($app['nurse_reason'])): ?>
                                            <span class="text-xs text-gray-500 block mt-1"
                                                title="<?= htmlspecialchars($app['nurse_reason']) ?>">
                                                (<?= htmlspecialchars(substr($app['nurse_reason'], 0, 20)) ?><?= strlen($app['nurse_reason']) > 20 ? '...' : '' ?>)
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="text-right flex items-center justify-end">
                                    <button onclick='openDetails(<?= json_encode($app) ?>)'
                                        class="text-xs font-black text-pink-500 uppercase flex items-center gap-2 hover:text-pink-700 transition-colors">
                                        View Full Details <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div
                class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-[#fce7f3] via-[#fce7f3]/80 to-transparent pointer-events-none z-40">
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination-capsule flex items-center gap-1">
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    ?>

                    <?php if ($start > 1): ?>
                        <a href="?page=1&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort_order ?>"
                            class="page-link" title="First Page">
                            <i class="fa-solid fa-angles-left text-[10px]"></i>
                        </a>
                    <?php endif; ?>

                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort_order ?>"
                            class="page-link" title="Previous Page">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?= $i ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort_order ?>"
                            class="page-link <?= ($i == $page) ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort_order ?>"
                            class="page-link" title="Next Page">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </a>
                    <?php endif; ?>

                    <?php if ($end < $total_pages): ?>
                        <a href="?page=<?= $total_pages ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort_order ?>"
                            class="page-link" title="Last Page">
                            <i class="fa-solid fa-angles-right text-[10px]"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </main>

    <script>
        // Function to try multiple photo extensions
        function tryPhotoExtensions(employeeId, imgElement) {
            var extensions = ['jpeg', 'png', 'JPG', 'JPEG', 'PNG']; // Skip 'jpg' as default
            var baseUrl = 'http://10.2.0.8/lrnph/emp_photos/';

            // Initialize state
            if (typeof imgElement.dataset.tryIndex === 'undefined') {
                imgElement.dataset.tryIndex = 0;
            }

            var currentIndex = parseInt(imgElement.dataset.tryIndex);

            if (currentIndex < extensions.length) {
                // Try next
                imgElement.dataset.tryIndex = currentIndex + 1;
                imgElement.src = baseUrl + employeeId + '.' + extensions[currentIndex];
            } else {
                // Give up
                imgElement.onerror = null;
                imgElement.style.display = 'none';
                if (imgElement.nextElementSibling) {
                    imgElement.nextElementSibling.style.display = 'block';
                }
            }
        }

        // Show success modal if success parameter is present
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1') {
                const successModal = document.getElementById('successModal');
                if (successModal) {
                    successModal.classList.remove('hidden');
                    // Auto-hide after 3 seconds
                    setTimeout(() => {
                        successModal.classList.add('hidden');
                    }, 3000);
                }
            }
        });
    </script>

    <?php include '../components/logout_modal.php'; ?>

    <!-- Details Modal -->
    <div id="detailsModal"
        class="fixed inset-0 z-[100] hidden bg-pink-900/20 backdrop-blur-sm flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-6xl rounded-[3rem] shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="p-8 border-b border-pink-50 flex justify-between items-center bg-pink-50/30">
                <div>
                    <h3 id="modalTitle" class="text-2xl font-black text-gray-800">Application Details</h3>
                    <p class="text-sm font-bold text-pink-400 uppercase mt-1">Declaration History</p>
                </div>
                <button onclick="closeDetails()" class="text-gray-400 hover:text-rose-500 text-2xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-10 grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
                <div class="space-y-6 md:col-span-2">
                    <div class="bg-pink-50/30 p-6 rounded-3xl border border-pink-100">
                        <label class="block text-[10px] font-black text-pink-400 uppercase mb-3">Employee
                            Information</label>
                        <div class="flex items-center gap-4 mb-4">
                            <div id="modalEmployeePhoto"
                                class="w-16 h-16 rounded-full overflow-hidden bg-pink-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user text-gray-400 text-xl"></i>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-pink-400 uppercase mb-1">Employee</label>
                                <p id="modalEmployeeName" class="text-lg font-black text-gray-800"></p>
                            </div>
                        </div>
                        <label class="block text-[10px] font-black text-pink-400 uppercase mb-2">Detailed Reason</label>
                        <p id="modalReason" class="text-gray-700 font-medium leading-relaxed"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-2xl border border-pink-50">
                            <label class="text-[9px] font-black text-gray-400 uppercase">First Date</label>
                            <p id="modalFirstDay" class="font-bold"></p>
                        </div>
                        <div class="p-4 rounded-2xl border border-pink-50">
                            <label class="text-[9px] font-black text-gray-400 uppercase">Return Date</label>
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
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <label class="text-[9px] font-black text-gray-400 uppercase">Days Absent</label>
                        <p id="modalDays" class="font-bold"></p>
                    </div>
                    <div class="p-4 rounded-2xl bg-pink-50 border border-pink-100">
                        <label class="text-[9px] font-black text-pink-400 uppercase">Superior</label>
                        <p id="modalSupDetails" class="font-bold text-pink-600"></p>
                    </div>
                    <div class="p-4 rounded-2xl bg-blue-50 border border-blue-100">
                        <label class="text-[9px] font-black text-blue-400 uppercase">Nurse Declaration</label>
                        <p id="modalNurseDecl" class="font-bold text-blue-600"></p>
                    </div>
                    <div class="p-4 rounded-2xl bg-red-50 border border-red-100">
                        <label class="text-[9px] font-black text-red-400 uppercase">Status</label>
                        <p id="modalStatus" class="font-bold text-red-600"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openDetails(data) {
            document.body.classList.add('overflow-hidden');
            document.getElementById('detailsModal').classList.remove('hidden');
            document.getElementById('modalTitle').innerText = 'Application #' + data.id.toString().padStart(4, '0');
            document.getElementById('modalEmployeeName').innerText = (data.employee_name || 'N/A') + ' (' + data.employee_number + ')';
            document.getElementById('modalReason').innerText = data.reason || 'No reason provided';
            document.getElementById('modalFirstDay').innerText = data.first_date_absence || 'N/A';
            document.getElementById('modalReturnDay').innerText = data.date_returned ? new Date(data.date_returned).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
            document.getElementById('modalDays').innerText = (data.days_absence || '0') + ' Day(s)';
            document.getElementById('modalSupDetails').innerText = data.superior_name_position || 'Not Notified';

            const decl = data.nurse_declaration || 'Pending';
            let declText = decl.charAt(0).toUpperCase() + decl.slice(1);
            if (data.nurse_reason) {
                declText += ` - ${data.nurse_reason}`;
            }
            document.getElementById('modalNurseDecl').innerText = declText;
            document.getElementById('modalStatus').innerText = data.status || 'Pending';

            const photoContainer = document.getElementById('modalEmployeePhoto');
            if (data.employee_id) {
                const photoUrl = 'http://10.2.0.8/lrnph/emp_photos/' + data.employee_id + '.jpg';
                photoContainer.innerHTML = `<img src="${photoUrl}" alt="${data.employee_name || 'Employee'}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';" /><i class="fa-solid fa-user text-gray-400 text-xl" style="display:none;"></i>`;
            }

            const medCard = document.getElementById('modalMedCertCard');
            const medLink = document.getElementById('modalMedCertLink');
            const medEmpty = document.getElementById('modalMedCertEmpty');
            const medPath = (data.medical_certificate_path || '').trim();
            if (medPath) {
                medCard.classList.remove('hidden');
                medLink.classList.remove('hidden');
                medEmpty.classList.add('hidden');
                medLink.href = '../' + medPath;
                medLink.textContent = 'Open attachment';
            } else {
                medCard.classList.add('hidden');
                medLink.classList.add('hidden');
                medEmpty.classList.remove('hidden');
                medLink.href = '#';
                medLink.textContent = '';
            }
        }

        function closeDetails() {
            document.body.classList.remove('overflow-hidden');
            document.getElementById('detailsModal').classList.add('hidden');
        }

        window.onclick = function(e) {
            const detailsModal = document.getElementById('detailsModal');
            if (detailsModal && e.target == detailsModal) {
                closeDetails();
            }
        };
    </script>
</body>

</html>