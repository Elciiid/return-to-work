<?php
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Check Authorization Authority
// Only authorized approvers can access this page
$is_authorized = $_SESSION['is_approver'] ?? false;

if (!$is_authorized) {
    header("Location: ../auth/login.php?error=access_denied&role=" . urlencode($_SESSION['department'] ?? ''));
    exit();
}

include '../db/db.php';
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
    $count_query = "SELECT COUNT(*) FROM return_to_work WHERE status = 'Pending' AND department = ?";
    $count_params = [$approver_department];
    if ($search) {
        $count_query .= " AND (UPPER(employee_name) LIKE UPPER(?) OR UPPER(employee_id) LIKE UPPER(?) OR UPPER(employee_number) LIKE UPPER(?) OR UPPER(prodn_type) LIKE UPPER(?))";
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
    $query = "SELECT * FROM return_to_work WHERE status = 'Pending' AND department = ?";
    $params = [$approver_department];
    if ($search) {
        $query .= " AND (UPPER(employee_name) LIKE UPPER(?) OR UPPER(employee_id) LIKE UPPER(?) OR UPPER(employee_number) LIKE UPPER(?) OR UPPER(prodn_type) LIKE UPPER(?))";
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
    $query .= " OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY"; // For SQL Server

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get pending count for sidebar badge (filtered by department)
    $pending_count_stmt = $conn->prepare("SELECT COUNT(*) FROM return_to_work WHERE status = 'Pending' AND department = ?");
    $pending_count_stmt->execute([$approver_department]);
    $pending_count = $pending_count_stmt->fetchColumn();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pending - La Rose Noire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@500;700;900&display=swap');

        :root {
            --primary: #ec4899;
            --primary-dark: #be185d;
            --secondary: #8b5cf6;
            --bg-mesh-1: #ffedd5;
            --bg-mesh-2: #fae8ff;
            --bg-mesh-3: #fce7f3;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        .font-heading {
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: #f8fafc;
            background-image:
                radial-gradient(at 0% 0%, hsla(253, 16%, 7%, 1) 0, transparent 50%),
                radial-gradient(at 50% 0%, hsla(225, 39%, 30%, 1) 0, transparent 50%),
                radial-gradient(at 100% 0%, hsla(339, 49%, 30%, 1) 0, transparent 50%);
            background: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%);
            height: 100vh;
            margin: 0;
            display: flex;
            overflow: hidden;
            position: relative;
        }

        /* Mesh Background */
        .mesh-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background:
                radial-gradient(at 0% 0%, var(--bg-mesh-3) 0px, transparent 50%),
                radial-gradient(at 80% 0%, var(--bg-mesh-2) 0px, transparent 50%),
                radial-gradient(at 0% 50%, var(--bg-mesh-1) 0px, transparent 50%),
                radial-gradient(at 80% 50%, var(--bg-mesh-3) 0px, transparent 50%),
                radial-gradient(at 0% 100%, var(--bg-mesh-2) 0px, transparent 50%),
                radial-gradient(at 80% 100%, var(--bg-mesh-1) 0px, transparent 50%),
                radial-gradient(at 0% 0%, var(--bg-mesh-3) 0px, transparent 50%);
            filter: blur(80px);
            opacity: 0.8;
            animation: meshFlow 20s infinite alternate;
        }

        @keyframes meshFlow {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(1.1);
            }
        }

        /* Glassmorphism Utilities */
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow:
                0 4px 6px -1px rgba(0, 0, 0, 0.02),
                0 2px 4px -1px rgba(0, 0, 0, 0.02),
                inset 0 0 0 1px rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            transform: translateY(-5px) scale(1.01);
            background: rgba(255, 255, 255, 0.95);
            box-shadow:
                0 20px 25px -5px rgba(0, 0, 0, 0.05),
                0 10px 10px -5px rgba(0, 0, 0, 0.02),
                inset 0 0 0 1px rgba(255, 255, 255, 0.8);
            border-color: rgba(236, 72, 153, 0.3);
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-enter {
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }

        .delay-100 {
            animation-delay: 0.1s;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }

        /* Sidebar */
        aside.sidebar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.02);
            z-index: 50;
        }

        .nav-item {
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, #ec4899, #f43f5e);
            border-radius: 0 4px 4px 0;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .nav-item.active::before,
        .nav-item:hover::before {
            transform: scaleY(1);
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(236, 72, 153, 0.1), transparent);
            color: #db2777;
        }

        .nav-item:hover:not(.active) {
            background: rgba(0, 0, 0, 0.02);
            color: #db2777;
        }

        /* Custom scrollbar for main area */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(236, 72, 153, 0.2);
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(236, 72, 153, 0.4);
        }

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

        .floating-element {
            animation: none;
        }

        .no-animation {
            animation: none !important;
        }
    </style>
</head>

<body class="font-sans text-gray-800 flex flex-col md:flex-row min-h-screen">
    <div class="mesh-bg"></div>

    <!-- Mobile Overlay -->
    <div id="mobileOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-transparent z-[90] hidden md:hidden">
    </div>

    <aside id="sidebar"
        class="sidebar w-72 flex flex-col h-[100dvh] shrink-0 fixed top-0 left-0 z-[100] transform transition-transform duration-300 -translate-x-full md:relative md:translate-x-0 bg-white/80 md:bg-white/80">
        <div class="flex-1 flex flex-col pt-12 px-6">
            <div class="flex items-center gap-4 mb-10 shrink-0 ml-2 floating-element">
                <div class="w-14 h-14 glass-effect flex items-center justify-center shadow-2xl shrink-0 rounded-2xl">
                    <img src="../logo.jpg" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div class="flex flex-col justify-center text-left">
                    <h1 class="font-black text-black leading-none text-lg tracking-tighter uppercase">La Rose Noire</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="h-[2px] w-2 bg-gradient-to-r from-pink-400 to-rose-500"></span>
                        <p
                            class="text-[8px] uppercase tracking-[0.3em] bg-gradient-to-r from-pink-400 to-rose-500 bg-clip-text text-transparent font-bold whitespace-nowrap">
                            Return to Work</p>
                    </div>
                </div>
            </div>

            <div
                class="profile-card mb-6 py-3 px-4 rounded-3xl group relative overflow-visible !flex !flex-col !items-center !justify-center !text-center !gap-2">
                <div
                    class="absolute inset-0 bg-white/40 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                </div>

                <div class="relative w-fit mx-auto mb-1">
                    <div
                        class="avatar w-16 h-16 rounded-full flex items-center justify-center text-white shadow-lg shadow-pink-500/20 shrink-0 border-[3px] border-white relative z-10">
                        <?= getEmployeePhotoImg($_SESSION['employee_id'] ?? '', 'w-full h-full object-cover rounded-full', htmlspecialchars($_SESSION['fullname'])) ?>
                    </div>
                    <div class="absolute bottom-0 left-0 flex h-4 w-4 z-20">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span
                            class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-[2px] border-white"></span>
                    </div>
                </div>

                <div class="meta w-full relative z-10 flex flex-col items-center justify-center">
                    <p
                        class="text-base font-black text-slate-800 truncate w-full group-hover:text-pink-600 transition-colors leading-tight">
                        <?= htmlspecialchars($_SESSION['fullname']) ?>
                    </p>
                    <p class="role text-[9px] uppercase font-bold text-slate-400 tracking-wider">
                        <?= htmlspecialchars($_SESSION['department'] ?? '') ?>
                    </p>
                </div>
            </div>

            <div class="overflow-y-auto custom-scrollbar flex-1">
                <nav class="space-y-4">
                    <?php
                    $is_authorized = $_SESSION['is_approver'] ?? false;
                    ?>

                    <?php if ($is_authorized): ?>
                        <a href="dashboard.php"
                            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                            <i class="fa-solid fa-chart-pie text-lg w-6"></i>
                            <span class="tracking-tight">Dashboard</span>
                        </a>
                    <?php endif; ?>

                    <a href="index.php"
                        class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                        <i class="fa-solid fa-file-signature text-lg w-6"></i>
                        <span class="tracking-tight">New Application</span>
                    </a>

                    <?php if ($is_authorized): ?>
                        <a href="pending.php"
                            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'pending.php' ? 'active' : ''; ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                            <i class="fa-solid fa-hourglass-half text-lg w-6"></i>
                            <span class="tracking-tight flex items-center gap-2">
                                Pending
                                <?php if ($pending_count > 0): ?>
                                    <span
                                        class="bg-pink-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full min-w-[16px] h-4 flex items-center justify-center">
                                        <?= $pending_count ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </a>
                        <a href="history.php"
                            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : ''; ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                            <i class="fa-solid fa-circle-check text-lg w-6"></i>
                            <span class="tracking-tight">Approved</span>
                        </a>
                        <a href="declined.php"
                            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'declined.php' ? 'active' : ''; ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                            <i class="fa-solid fa-times-circle text-lg w-6"></i>
                            <span class="tracking-tight">Declined</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <div class="px-6 pb-6 flex items-center justify-center floating-element">
                <img src="../it-logo.png" alt="IT Logo"
                    class="w-24 opacity-70 transition-all duration-300 hover:opacity-100 hover:scale-110">
            </div>
            <div class="pt-6 border-t border-slate-700 mt-auto mb-6">
                <a href="#" onclick="openLogoutModal()"
                    class="group nav-item flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                    <i class="fa-solid fa-right-from-bracket text-lg w-6"></i>
                    <span class="tracking-tight">Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <main
        class="flex-1 flex flex-col p-4 md:p-8 lg:p-12 relative z-10 custom-scrollbar overflow-y-auto w-full md:h-[100dvh]">
        <!-- Mobile Header -->
        <div class="md:hidden flex justify-between items-center mb-6 shrink-0 relative z-[60]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 glass-effect flex items-center justify-center shadow-lg rounded-xl">
                    <img src="../logo.jpg" alt="Logo" class="w-full h-full object-contain p-1">
                </div>
                <span class="font-black text-gray-800 tracking-tight">Return to Work</span>
            </div>
            <button onclick="toggleSidebar()"
                class="w-10 h-10 bg-white/80 backdrop-blur-md rounded-xl shadow-lg flex items-center justify-center text-pink-500 hover:bg-white transition-colors cursor-pointer active:scale-95">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </div>
        <header class="mb-8 shrink-0">
            <h2 class="text-4xl font-black text-gray-800 tracking-tight">Pending Approvals</h2>
            <p class="text-gray-600 font-medium text-lg italic">Entries waiting for your decision</p>
        </header>

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
                                    <form method="POST" action="../db/update_status.php" class="inline-block">
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
                                        <?= getEmployeePhotoImg($row['employee_id'] ?? '', 'w-full h-full object-cover', htmlspecialchars($row['employee_name'])) ?>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700"><?= htmlspecialchars($row['employee_number']) ?>
                                        — <?= htmlspecialchars($row['employee_name']) ?></p>
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
            <form method="POST" action="../db/update_status.php" class="p-8">
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

    <!-- Logout Modal -->
    <div id="logoutModal"
        class="fixed inset-0 z-[100] hidden bg-pink-900/20 backdrop-blur-sm flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-sm rounded-[3rem] shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-pink-50 flex justify-between items-center bg-pink-50/30">
                <div>
                    <h3 class="text-2xl font-black text-gray-800">Confirm Logout</h3>
                    <p class="text-sm font-bold text-pink-400 uppercase mt-1">Are you sure?</p>
                </div>
                <button onclick="closeLogoutModal()" class="text-gray-400 hover:text-rose-500 text-2xl"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-8 text-center">
                <div
                    class="w-16 h-16 bg-gradient-to-br from-pink-400 to-rose-500 text-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl">
                    <i class="fa-solid fa-right-from-bracket text-2xl"></i>
                </div>
                <p class="text-gray-600 font-medium mb-8">You will be logged out of your account and redirected to the
                    login page.</p>
                <div class="flex gap-3">
                    <button onclick="closeLogoutModal()"
                        class="flex-1 py-3 text-gray-600 font-black uppercase text-xs tracking-widest hover:text-gray-800 transition-colors">
                        Cancel
                    </button>
                    <a href="../auth/logout.php"
                        class="flex-1 bg-gradient-to-r from-pink-500 to-rose-500 text-white font-black py-3 rounded-xl shadow-lg uppercase text-xs tracking-widest hover:shadow-xl transition-all text-center">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to try multiple photo extensions
        function tryPhotoExtensions(employeeId, imgElement) {
            var extensions = ['jpeg', 'png', 'JPG', 'JPEG', 'PNG']; // Skip 'jpg' as it is the default
            var baseUrl = 'http://10.2.0.8/lrnph/emp_photos/';

            // Initialize state if first time failure
            if (typeof imgElement.dataset.tryIndex === 'undefined') {
                imgElement.dataset.tryIndex = 0;
            }

            var currentIndex = parseInt(imgElement.dataset.tryIndex);

            if (currentIndex < extensions.length) {
                // Try next extension
                imgElement.dataset.tryIndex = currentIndex + 1;
                imgElement.src = baseUrl + employeeId + '.' + extensions[currentIndex];
            } else {
                // All extensions failed, stop trying and show fallback
                imgElement.onerror = null;
                imgElement.style.display = 'none';
                if (imgElement.nextElementSibling) {
                    imgElement.nextElementSibling.style.display = 'block';
                }
            }
        }

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

        function openLogoutModal() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }

        window.onclick = (e) => {
            if (e.target == document.getElementById('detailsModal')) closeDetails();
            if (e.target == document.getElementById('declineModal')) closeDeclineModal();
            if (e.target == document.getElementById('logoutModal')) closeLogoutModal();
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');

            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>

</html>