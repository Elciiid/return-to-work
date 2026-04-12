<?php
require_once __DIR__ . '/../connection/database.php';

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit();
}
include __DIR__ . '/../db/photo_helper.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, interactive-widget=resizes-content">
    <title>Return to Work</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/style.css" rel="stylesheet">
    <style>
        .input-focus:focus {
            border-color: #ec4899;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 0 4px rgba(236, 114, 182, 0.15);
            color: #2d3748;
        }

        .dropdown-style {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
    </style>
</head>

<body class="font-sans text-gray-800 flex flex-col md:flex-row min-h-screen">
    <div class="mesh-bg"></div>

    <!-- Sidebar -->
    <?php include __DIR__ . '/../components/sidebar.php'; ?>


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


        <div class="w-full max-w-[1400px] mx-auto text-left overflow-visible">
        <?php 
        $page_subtitle = "Complete the form to initiate your health clearance";
        include __DIR__ . '/../components/header.php'; 
        ?>

            <form action="/db/submit.php" method="POST" enctype="multipart/form-data" id="rtwForm"
                onsubmit="return validateForm()"
                class="flex flex-col lg:flex-row gap-8 items-stretch text-left overflow-visible">
                <div class="flex-[3] space-y-6 overflow-visible">
                    <div class="glass-panel rounded-3xl p-8 text-left overflow-visible relative z-10">
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 text-left">
                            <div class="lg:col-span-3 relative">
                                <label
                                    class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Employee
                                    Name</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-user absolute left-6 top-1/2 -translate-y-1/2 text-pink-400 text-xl"></i>
                                    <input type="text" id="emp_name" name="emp_name" readonly
                                        value="<?= htmlspecialchars($_SESSION['fullname'] ?? '') ?>"
                                        class="w-full bg-slate-100 border-2 border-slate-200 p-5 pl-16 rounded-2xl outline-none text-xl font-bold text-gray-500 shadow-sm cursor-not-allowed">
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Date
                                    of Filing</label>
                                <input type="date" name="filing_date" required
                                    class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-5 rounded-2xl outline-none transition-all text-xl font-bold input-focus text-gray-800"
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="glass-panel rounded-3xl p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase mb-2 ml-1">Assigned
                                    Area</label>
                                <select name="assigned_area" required
                                    class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-4 rounded-2xl outline-none text-lg font-bold input-focus text-gray-800 appearance-none">
                                    <option value="" disabled selected>Select Area Type</option>
                                    <option value="Production">Production</option>
                                    <option value="Non-Production">Non-Production</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase mb-2 ml-1">Days
                                    Absent</label>
                                <input type="number" name="days" required min="0" max="999"
                                    class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-4 rounded-2xl outline-none text-lg font-bold input-focus text-gray-800">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">First
                                        Date</label>
                                    <input type="date" name="start_date" required
                                        class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-4 rounded-2xl outline-none text-sm font-bold input-focus text-gray-800">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Return
                                        Date</label>
                                    <input type="date" name="return_date" required
                                        class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-4 rounded-2xl outline-none text-sm font-bold input-focus text-gray-800">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase mb-2 ml-1">Detailed
                                Reason</label>
                            <textarea name="reason" rows="2" required
                                class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-4 rounded-2xl outline-none text-lg font-bold input-focus text-gray-800 placeholder-gray-600"
                                placeholder="Provide reason for absence..."></textarea>
                        </div>

                        <div class="glass-panel p-6 rounded-3xl space-y-6">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-black text-pink-400 uppercase tracking-widest">Superior
                                    Notified?</label>
                                <div class="flex gap-10">
                                    <label class="flex items-center gap-3 text-lg font-bold cursor-pointer"><input
                                            type="radio" name="notified" value="Yes" onclick="toggleSuperior(true)"
                                            class="w-6 h-6 accent-pink-500"> Yes</label>
                                    <label class="flex items-center gap-3 text-lg font-bold cursor-pointer"><input
                                            type="radio" name="notified" value="No" onclick="toggleSuperior(false)"
                                            checked class="w-6 h-6 accent-pink-500"> No</label>
                                </div>
                            </div>
                            <div id="superior_input_area" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase text-left block">Superior
                                        Name</label>
                                    <input type="text" id="sup_name" name="sup_name" readonly
                                        onclick="openSupervisorModal()" placeholder="Click to select..."
                                        class="w-full bg-slate-300/20 border-2 border-pink-500/30 p-4 rounded-2xl outline-none text-base font-bold input-focus text-gray-800 shadow-sm cursor-pointer hover:bg-slate-300/30 transition-colors">
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase text-left block">Position/Area</label>
                                    <input type="text" id="sup_pos" name="sup_pos" readonly
                                        class="w-full bg-slate-100 border-2 border-slate-200 p-4 rounded-2xl outline-none text-base font-bold text-gray-500 shadow-sm cursor-not-allowed">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 flex flex-col gap-6">
                    <div class="glass-panel rounded-3xl p-8 flex flex-col gap-6">
                        <div class="flex flex-col gap-6">
                            <div class="text-center p-8 rounded-3xl glass-panel border-2 border-pink-300/60"
                                style="background: linear-gradient(135deg, rgba(248, 182, 204, 0.7), rgba(255, 251, 252, 1));">
                                <p class="text-xs font-black text-pink-400 uppercase mb-2 tracking-widest">Employee ID
                                </p>
                                <input type="text" id="emp_no" name="emp_no" readonly
                                    value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>"
                                    class="text-4xl font-black text-gray-800 bg-transparent text-center w-full outline-none">
                                <input type="hidden" id="emp_id" name="emp_id"
                                    value="<?= htmlspecialchars($_SESSION['employee_id'] ?? '') ?>">
                            </div>
                            <div class="p-6 md:p-8 rounded-3xl flex flex-col items-center justify-center min-h-[140px] border-2 border-pink-300/60"
                                style="background: linear-gradient(135deg, rgba(248, 182, 204, 0.7), rgba(255, 251, 252, 1));">
                                <p class="text-[10px] font-black text-pink-400 uppercase mb-2 tracking-widest">
                                    Department</p>
                                <textarea id="dept" name="dept" readonly
                                    class="text-xl md:text-2xl font-black text-gray-800 bg-transparent text-center w-full outline-none resize-none leading-tight overflow-hidden"><?= htmlspecialchars($_SESSION['department'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="space-y-4 mt-auto">
                            <button type="button" onclick="resetForm()"
                                class="w-full py-4 text-slate-400 font-black uppercase text-xs tracking-widest hover:text-pink-400 transition-colors">Reset
                                Form</button>

                            <div class="w-full">
                                <label
                                    class="block text-xs font-black text-slate-400 uppercase mb-2 ml-1 align-middle">Medical
                                    Certificate <span class="text-pink-500">*</span></label>
                                <input type="file" name="medical_certificate" required
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.heic,application/pdf,image/*"
                                    class="w-full bg-slate-300/20 border-2 border-pink-500/30 file:bg-pink-500 file:text-white file:border-0 file:px-4 file:py-2 file:mr-3 file:rounded-xl file:text-xs file:font-black file:uppercase file:tracking-widest rounded-2xl p-3 text-sm text-gray-800 cursor-pointer input-focus">
                                <p class="mt-1 text-[11px] text-slate-500 font-medium">Accepted: PDF, Word, JPG, JPEG,
                                    PNG, HEIC</p>
                            </div>

                            <button type="submit"
                                class="w-full bg-gradient-to-r from-pink-500 to-rose-600 text-white font-black py-6 rounded-2xl shadow-xl shadow-pink-500/50 uppercase text-sm tracking-widest transition-all flex items-center justify-center gap-3 hover:shadow-2xl hover:shadow-pink-500/70 hover:translate-y-[-2px]">
                                <i class="fa-solid fa-paper-plane"></i> Submit Application
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <?php if (isset($_GET['success'])): ?>
        <div id="successModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/40 backdrop-blur-md">
            <div
                class="bg-white/90 backdrop-blur-xl rounded-3xl p-10 shadow-2xl text-center max-w-sm animate-modal border border-white/50">
                <div
                    class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-green-500 text-black rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl">
                    <i class="fa-solid fa-check text-4xl"></i>
                </div>
                <h3 class="text-2xl font-black text-slate 700 mb-2">Success!</h3>
                <p class="text-slate-500 font-medium mb-8">Your application has been submitted successfully.</p>
                <button onclick="window.location.href='/pages/index.php'"
                    class="w-full bg-gradient-to-r from-pink-500 to-rose-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-pink-500/50 uppercase text-xs tracking-widest hover:shadow-2xl hover:shadow-pink-500/60 transition-all">
                    Great, Thanks!
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Supervisor Selection Modal -->
    <div id="supervisorModal"
        class="fixed inset-0 z-[99999] flex items-center justify-center bg-slate-900/40 backdrop-blur-md hidden">
        <div
            class="bg-white/95 backdrop-blur-xl rounded-3xl p-8 shadow-2xl w-full max-w-lg animate-modal border border-white/50 m-4">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight">Select Supervisor</h3>
                    <p class="text-[10px] font-black text-pink-500 uppercase tracking-widest mt-1">
                        <?php echo htmlspecialchars($_SESSION['department'] ?? 'Department'); ?>
                    </p>
                </div>
                <button type="button" onclick="closeSupervisorModal()"
                    class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:bg-pink-50 hover:text-pink-500 transition-colors">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <div id="supervisorList" class="max-h-[60vh] overflow-y-auto custom-scrollbar space-y-3 p-1">
                <!-- Supervisors loaded here -->
                <div class="flex justify-center py-8">
                    <i class="fa-solid fa-spinner fa-spin text-3xl text-pink-500"></i>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Monitor detailed reason for "dysmennorhea" exemption
        const reasonInput = document.querySelector('textarea[name="reason"]');
        const fileInput = document.querySelector('input[name="medical_certificate"]');
        // The label is the previous element sibling of the input
        const fileLabel = fileInput.previousElementSibling;
        const asterisk = fileLabel.querySelector('span');

        function checkReason() {
            const reason = reasonInput.value.toLowerCase();
            // Check for both spellings just in case
            if (reason.includes('dysmennorhea') || reason.includes('dysmenorrhea')) {
                fileInput.removeAttribute('required');
                if (asterisk) asterisk.style.display = 'none';
            } else {
                fileInput.setAttribute('required', 'required');
                if (asterisk) asterisk.style.display = 'inline';
            }
        }

        reasonInput.addEventListener('input', checkReason);
        // Run on load in case the browser refills the form
        checkReason();

        // Supervisor Selection Logic

        function openSupervisorModal() {
            const modal = document.getElementById('supervisorModal');
            modal.classList.remove('hidden');

            const list = document.getElementById('supervisorList');
            list.innerHTML = '<div class="flex justify-center py-8"><i class="fa-solid fa-circle-notch fa-spin text-3xl text-pink-500"></i></div>';

            fetch('/db/get_supervisors.php')
                .then(res => res.json())
                .then(data => {
                    list.innerHTML = '';
                    if (data.error) {
                        list.innerHTML = `<div class="text-center text-red-500 font-bold p-4 text-sm opacity-70">${data.error}</div>`;
                        return;
                    }
                    if (data.length === 0) {
                        list.innerHTML = `<div class="text-center text-slate-400 font-bold p-8 flex flex-col items-center gap-3">
                            <i class="fa-solid fa-users-slash text-3xl opacity-50"></i>
                            <span class="text-xs uppercase tracking-widest">No approvers found</span>
                        </div>`;
                        return;
                    }

                    data.forEach(sup => {
                        const div = document.createElement('div');
                        div.className = "group p-4 rounded-2xl bg-slate-50/50 border border-slate-200 hover:border-pink-300 hover:bg-pink-50 cursor-pointer transition-all flex items-center gap-4 hover:shadow-md hover:shadow-pink-100";
                        div.onclick = () => selectSupervisor(sup.employee_name, sup.position);

                        div.innerHTML = `
                            <div class="w-12 h-12 rounded-full bg-white text-pink-600 flex items-center justify-center font-black text-sm border-2 border-slate-100 group-hover:border-pink-200 shadow-sm shrink-0">
                                ${getInitials(sup.employee_name)}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-black text-slate-700 text-sm group-hover:text-pink-600 truncate transition-colors">${sup.employee_name}</p>
                                <p class="text-[9px] uppercase font-bold text-slate-400 tracking-wider truncate">${sup.position}</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-pink-400 transition-colors text-xs"></i>
                        `;
                        list.appendChild(div);
                    });
                })
                .catch(err => {
                    console.error(err);
                    list.innerHTML = `<div class="text-center text-red-500 font-bold p-4 text-sm">Failed to load supervisors.</div>`;
                });
        }

        function selectSupervisor(name, position) {
            document.getElementById('sup_name').value = name;
            document.getElementById('sup_pos').value = position;
            closeSupervisorModal();
        }

        function closeSupervisorModal() {
            document.getElementById('supervisorModal').classList.add('hidden');
        }

        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        }

        function toggleSuperior(show) {
            const area = document.getElementById('superior_input_area');
            if (show) {
                area.classList.remove('hidden');
                openSupervisorModal();
            } else {
                area.classList.add('hidden');
                document.getElementById('sup_name').value = '';
                document.getElementById('sup_pos').value = '';
            }
        }
        function validateForm() { const notified = document.querySelector('input[name="notified"]:checked').value; if (notified === 'Yes') { const name = document.getElementById('sup_name').value.trim(); const pos = document.getElementById('sup_pos').value.trim(); if (!name || !pos) { alert("Please provide the Superior's Name and Position."); return false; } } return true; }
        function resetForm() { if (confirm("Discard all changes?")) location.reload(); }
    </script>

    <?php include __DIR__ . '/../components/logout_modal.php'; ?>
</body>

</html>