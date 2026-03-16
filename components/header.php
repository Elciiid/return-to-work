<?php
date_default_timezone_set('Asia/Manila');
$h = date('G');
$greeting = $h < 12 ? 'Good Morning' : ($h < 18 ? 'Good Afternoon' : 'Good Evening');
$firstName = explode(' ', trim($_SESSION['fullname'] ?? ''))[0];
$page_title = $page_title ?? '';
$page_subtitle = $page_subtitle ?? '';
?>
<header class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4 animate-enter">
    <div>
        <div class="flex items-center gap-2 mb-2 text-slate-500 font-bold uppercase tracking-widest text-xs">
            <span class="w-2 h-2 rounded-full bg-pink-500 animate-pulse"></span>
            <?= date('l, F j, Y') ?>
        </div>
        <h2 class="text-4xl lg:text-5xl font-black text-slate-800 tracking-tight">
            <?php if (!empty($page_title)): ?>
                <?= $page_title ?>
            <?php else: ?>
                <?= $greeting ?>, <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-purple-600"><?= htmlspecialchars($firstName) ?></span>
            <?php endif; ?>
        </h2>
        <?php if (!empty($page_subtitle)): ?>
            <p class="text-slate-500 font-medium text-lg mt-2"><?= $page_subtitle ?></p>
        <?php endif; ?>
    </div>

    <?php if (isset($header_right)): ?>
        <?= $header_right ?>
    <?php endif; ?>
</header>
