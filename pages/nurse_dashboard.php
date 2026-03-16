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

try {
    // Get total declarations made (lifetime)
    $total_declarations_stmt = $conn->query("SELECT COUNT(*) FROM return_to_work WHERE nurse_declaration IS NOT NULL");
    $total_declarations = $total_declarations_stmt->fetchColumn();

    // Get declarations made in last 7 days
    $weekly_declarations_stmt = $conn->query("SELECT COUNT(*) FROM return_to_work WHERE nurse_declaration IS NOT NULL AND nurse_declaration_date >= DATEADD(day, -7, GETDATE())");
    $weekly_declarations = $weekly_declarations_stmt->fetchColumn();

    // Get recent declarations
    $recent_stmt = $conn->query("SELECT TOP 5 filing_date, employee_name, employee_id, prodn_type, nurse_declaration FROM return_to_work WHERE nurse_declaration IS NOT NULL ORDER BY nurse_declaration_date DESC");
    $recent_activity = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get counts...

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - La Rose Noire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>

<body class="font-sans text-gray-800 flex flex-col md:flex-row min-h-screen">
    <div class="mesh-bg"></div>


    <?php include '../components/sidebar.php'; ?>

    <main
        class="flex-1 flex flex-col p-4 md:p-8 lg:p-12 relative z-10 custom-scrollbar overflow-y-auto w-full md:h-[100dvh]">
        <?php if (isset($_GET['success'])): ?>
            <div id="successModal"
                class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex justify-between items-center relative z-20">
                <div>
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline">Medical declaration has been recorded successfully.</span>
                </div>
                <button onclick="document.getElementById('successModal').style.display='none'"
                    class="text-green-700 hover:text-green-900">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php 
        $page_subtitle = "Overview of medical declarations and health assessments.";
        ob_start(); ?>
        <div class="flex gap-3">
            <div class="h-12 px-4 rounded-2xl glass-panel flex items-center gap-3 text-slate-600 font-bold shadow-sm">
                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                <span>Online</span>
            </div>
        </div>
        <?php 
        $header_right = ob_get_clean();
        include '../components/header.php'; 
        ?>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 shrink-0">
            <!-- Card 1: Total Declarations -->
            <div class="glass-card p-6 rounded-3xl relative overflow-hidden group animate-enter delay-100">
                <div
                    class="absolute -right-6 -top-6 w-32 h-32 bg-gradient-to-br from-pink-100 to-rose-100 rounded-full opacity-50 blur-2xl group-hover:opacity-100 transition-opacity">
                </div>

                <div class="flex justify-between items-start mb-4 relative z-10">
                    <div
                        class="w-12 h-12 rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center text-white shadow-lg shadow-pink-500/30 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-folder-tree text-lg"></i>
                    </div>
                    <span
                        class="px-3 py-1 rounded-full bg-pink-50 text-pink-600 text-[10px] font-black uppercase tracking-wider border border-pink-100">Lifetime</span>
                </div>

                <div class="relative z-10">
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Total Declarations</p>
                    <h3 class="text-4xl font-black text-slate-800 tracking-tight">
                        <?= number_format($total_declarations) ?>
                    </h3>
                </div>
            </div>

            <!-- Card 2: Weekly Activity -->
            <div class="glass-card p-6 rounded-3xl relative overflow-hidden group animate-enter delay-200">
                <div
                    class="absolute -right-6 -top-6 w-32 h-32 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full opacity-50 blur-2xl group-hover:opacity-100 transition-opacity">
                </div>

                <div class="flex justify-between items-start mb-4 relative z-10">
                    <div
                        class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-calendar-check text-lg"></i>
                    </div>
                    <span
                        class="px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-wider border border-blue-100">7
                        Days</span>
                </div>

                <div class="relative z-10">
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Weekly Activity</p>
                    <h3 class="text-4xl font-black text-slate-800 tracking-tight">
                        <?= number_format($weekly_declarations) ?>
                    </h3>
                </div>
            </div>

            <!-- Card 3: Quick Action (Pending) -->
            <a href="nurse_applications.php"
                class="glass-card p-6 rounded-3xl relative overflow-hidden group animate-enter delay-300 border-l-4 border-l-orange-400 hover:border-l-orange-500 cursor-pointer block">
                <div
                    class="absolute -right-6 -top-6 w-32 h-32 bg-gradient-to-br from-orange-100 to-amber-100 rounded-full opacity-50 blur-2xl group-hover:opacity-100 transition-opacity">
                </div>

                <div class="flex justify-between items-start mb-4 relative z-10">
                    <div
                        class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/30 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-bolt text-lg animate-pulse"></i>
                    </div>
                    <?php if ($pending_count > 0): ?>
                        <span
                            class="px-3 py-1 rounded-full bg-orange-100 text-orange-600 text-[10px] font-black uppercase tracking-wider border border-orange-200">Action
                            Needed</span>
                    <?php else: ?>
                        <span
                            class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-wider border border-emerald-100">All
                            Clear</span>
                    <?php endif; ?>
                </div>

                <div class="relative z-10">
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Pending Requests</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-4xl font-black text-slate-800 tracking-tight">
                            <?= number_format($pending_count) ?>
                        </h3>
                        <?php if ($pending_count > 0): ?>
                            <span class="text-xs font-bold text-orange-500">start review &rarr;</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>

        <!-- Recent Submissions Table -->
        <div class="flex-1 glass-panel rounded-3xl flex flex-col overflow-hidden shadow-xl animate-enter delay-300">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-white/40">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-pink-100 text-pink-500 flex items-center justify-center"><i
                            class="fa-solid fa-list-check"></i></div>
                    <h3 class="text-lg font-black text-slate-800 tracking-tight">Recent Submissions</h3>
                </div>
                <a href="nurse_declaration.php"
                    class="text-xs font-bold text-pink-500 hover:text-pink-700 uppercase tracking-wider transition-colors">View
                    History &rarr;</a>
            </div>

            <div class="flex-1 overflow-auto custom-scrollbar p-2">
                <table class="w-full text-left border-collapse custom-table">
                    <thead
                        class="sticky top-0 bg-white/80 backdrop-blur-md z-10 text-xs uppercase font-black text-slate-400 tracking-wider">
                        <tr>
                            <th class="p-4 rounded-l-xl">Date</th>
                            <th class="p-4">Employee</th>
                            <th class="p-4">Area / Type</th>
                            <th class="p-4 rounded-r-xl">Declaration</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm font-medium text-slate-600">
                        <?php if (empty($recent_activity)): ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-400 italic">No recent declarations found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_activity as $recent): ?>
                                <tr class="group rounded-xl cursor-default">
                                    <td class="p-4 rounded-l-xl border-b-0">
                                        <div class="font-bold text-slate-700">
                                            <?= date('M d', strtotime($recent['filing_date'])) ?>
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            <?= date('Y', strtotime($recent['filing_date'])) ?>
                                        </div>
                                    </td>
                                    <td class="p-4 border-b-0">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-10 h-10 rounded-full bg-white shadow-sm p-0.5 shrink-0 overflow-hidden">
                                                <?= getEmployeePhotoImg($recent['employee_id'] ?? '', 'w-full h-full object-cover rounded-full', htmlspecialchars($recent['employee_name'])) ?>
                                            </div>
                                            <div>
                                                <div
                                                    class="font-bold text-slate-800 group-hover:text-pink-600 transition-colors">
                                                    <?= htmlspecialchars($recent['employee_name']) ?>
                                                </div>
                                                <div class="text-[10px] text-slate-400 tracking-wider font-bold">
                                                    <?= htmlspecialchars($recent['employee_id']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 border-b-0">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            <?= htmlspecialchars($recent['prodn_type'] ?: 'General') ?>
                                        </span>
                                    </td>
                                    <td class="p-4 rounded-r-xl border-b-0">
                                        <?php
                                        $nurseDecl = $recent['nurse_declaration'];
                                        $statusClass = 'bg-slate-100 text-slate-600 border-slate-200';

                                        if ($nurseDecl) {
                                            switch (strtolower($nurseDecl)) {
                                                case 'fit to work':
                                                    $statusClass = 'bg-emerald-50 text-emerald-600 border-emerald-100';
                                                    break;
                                                case 'unfit to work':
                                                    $statusClass = 'bg-rose-50 text-rose-600 border-rose-100';
                                                    break;
                                                case 'return to work':
                                                    $statusClass = 'bg-blue-50 text-blue-600 border-blue-100';
                                                    break;
                                            }
                                        }
                                        ?>
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wide border <?= $statusClass ?>">
                                            <?php if ($nurseDecl): ?>
                                                <?= htmlspecialchars(strtoupper($nurseDecl)) ?>
                                            <?php else: ?>
                                                <span class="italic opacity-50">Pending</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php include '../components/logout_modal.php'; ?>
</body>

</html>