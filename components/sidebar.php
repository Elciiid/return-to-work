<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_it_admin = $_SESSION['is_admin'] ?? false;
$is_approver = $_SESSION['is_approver'] ?? false;
$role = $_SESSION['role'] ?? 'employee';

// Get pending counts for badges if approver
$pending_count = 0;
if ($is_approver) {
    try {
        $approver_department = $_SESSION['department'] ?? '';
        $pending_count_stmt = $conn->prepare("SELECT COUNT(*) FROM rtw_return_to_work WHERE status = 'Pending' AND department = ?");
        $pending_count_stmt->execute([$approver_department]);
        $pending_count = $pending_count_stmt->fetchColumn();
    } catch (PDOException $e) { $pending_count = 0; }
}

// Get pending counts for nurse if applicable
$nurse_pending_count = 0;
if ($role === 'company nurse' || $role === 'clinic assistant') {
    try {
        $nurse_pending_stmt = $conn->query("SELECT COUNT(*) FROM rtw_return_to_work WHERE nurse_declaration IS NULL");
        $nurse_pending_count = $nurse_pending_stmt->fetchColumn();
    } catch (PDOException $e) { $nurse_pending_count = 0; }
}
?>
<!-- Mobile Overlay -->
<div id="mobileOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-transparent z-[90] hidden md:hidden"></div>

<aside id="sidebar"
    class="sidebar w-72 flex flex-col h-[100dvh] shrink-0 fixed top-0 left-0 z-[100] transform transition-transform duration-300 -translate-x-full md:relative md:translate-x-0 bg-white/80 md:bg-white/80">
    <div class="flex-1 flex flex-col pt-12 px-6">
        <div class="flex items-center gap-4 mb-10 shrink-0 ml-2 floating-element">
            <div class="w-14 h-14 glass-panel flex items-center justify-center shadow-2xl shrink-0 rounded-2xl bg-white/50">
                <img src="/assets/logo.jpg" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="flex flex-col justify-center text-left">
                <h1 class="font-black text-black leading-none text-lg tracking-tighter uppercase">La Rose Noire</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="h-[2px] w-2 bg-gradient-to-r from-pink-400 to-rose-500"></span>
                    <p class="text-[8px] uppercase tracking-[0.3em] bg-gradient-to-r from-pink-400 to-rose-500 bg-clip-text text-transparent font-bold whitespace-nowrap">Return to Work</p>
                </div>
            </div>
        </div>

        <div class="profile-card mb-6 py-3 px-4 rounded-3xl !flex !flex-col !items-center !text-center bg-white/40 border border-white/50 shadow-sm relative group">
            <div class="absolute inset-0 bg-white/40 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <div class="relative w-fit mx-auto mb-2">
                <div class="w-16 h-16 rounded-full border-[3px] border-white shadow-lg overflow-hidden bg-slate-100 flex items-center justify-center relative z-10">
                    <?php if (function_exists('getEmployeePhotoImg')): ?>
                        <?= getEmployeePhotoImg($_SESSION['employee_id'] ?? '', 'w-full h-full object-cover', $_SESSION['fullname'] ?? '') ?>
                    <?php else: ?>
                        <i class="fa-solid fa-user text-gray-400 text-2xl"></i>
                    <?php endif; ?>
                </div>
                <div class="absolute bottom-0 left-0 flex h-4 w-4 z-20">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-[2px] border-white"></span>
                </div>
            </div>
            <p class="text-base font-black text-slate-800 leading-tight relative z-10"><?= htmlspecialchars($_SESSION['fullname'] ?? '') ?></p>
            <p class="text-[9px] uppercase font-bold text-slate-400 tracking-wider relative z-10"><?= htmlspecialchars($_SESSION['department'] ?? '') ?></p>
        </div>

        <div class="overflow-y-auto custom-scrollbar flex-1">
            <nav class="space-y-2">
                <?php if ($role === 'company nurse' || $role === 'clinic assistant'): ?>
                    <a href="/pages/nurse_dashboard.php" class="nav-item <?= $current_page == 'nurse_dashboard.php' ? 'active' : '' ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                        <i class="fa-solid fa-chart-pie text-lg w-6"></i> Dashboard
                    </a>
                    <a href="/pages/nurse_applications.php" class="nav-item <?= $current_page == 'nurse_applications.php' ? 'active' : '' ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                        <i class="fa-solid fa-clipboard-list text-lg w-6"></i> Applications
                        <?php if ($nurse_pending_count > 0): ?>
                            <span class="ml-auto bg-pink-500 text-white text-[10px] px-2 py-1 rounded-full"><?= $nurse_pending_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/pages/nurse_declaration.php" class="nav-item <?= $current_page == 'nurse_declaration.php' ? 'active' : '' ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                        <i class="fa-solid fa-history text-lg w-6"></i> History
                    </a>
                <?php else: ?>
                    <?php if ($is_approver): ?>
                        <a href="/pages/dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                            <i class="fa-solid fa-chart-pie text-lg w-6"></i> Dashboard
                        </a>
                    <?php endif; ?>
                    
                    <a href="/pages/index.php" class="nav-item <?= $current_page == 'index.php' ? 'active' : '' ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                        <i class="fa-solid fa-file-signature text-lg w-6"></i> New Application
                    </a>

                    <?php if ($is_approver): ?>
                        <a href="/pages/approvals.php" class="nav-item <?= $current_page == 'approvals.php' ? 'active' : '' ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                            <i class="fa-solid fa-clipboard-check text-lg w-6"></i> Approvals
                            <?php if ($pending_count > 0): ?>
                                <span class="ml-auto bg-pink-500 text-white text-[10px] px-2 py-1 rounded-full"><?= $pending_count ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($is_it_admin): ?>
                    <a href="/pages/settings.php" class="nav-item <?= $current_page == 'settings.php' ? 'active' : '' ?> flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-sm">
                        <i class="fa-solid fa-gear text-lg w-6"></i> Settings
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="pt-6 border-t border-slate-200 mb-4 font-bold flex flex-col gap-2">
             <?php if (($_SESSION['can_act_as_she'] ?? false) || (isset($_SESSION['original_user']) && ($_SESSION['original_can_act_as_she'] ?? false))): ?>
                <a href="/auth/role_switcher.php" class="group flex items-center gap-4 px-6 py-4 rounded-2xl text-sm <?= isset($_SESSION['original_user']) ? 'text-emerald-500 hover:bg-emerald-50' : 'text-pink-500 hover:bg-pink-50' ?> transition-colors">
                    <i class="fa-solid <?= isset($_SESSION['original_user']) ? 'fa-user-check' : 'fa-user-nurse' ?> text-lg w-6"></i> 
                    <?= isset($_SESSION['original_user']) ? 'Back to Self' : 'Act as SHE' ?>
                </a>
            <?php endif; ?>
            <a href="#" onclick="openLogoutModal()" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-sm text-rose-500 hover:bg-rose-50 transition-colors">
                <i class="fa-solid fa-right-from-bracket text-lg w-6"></i> Logout
            </a>
        </div>

        <div class="px-6 pb-6 mt-auto">
            <img src="/assets/it-logo.png" alt="IT Logo" class="w-20 mx-auto opacity-50">
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        const isHidden = sidebar.classList.contains('-translate-x-full');
        
        if (isHidden) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
</script>
