<?php
require_once __DIR__ . '/../connection/database.php';

// 1. Check if user is logged in and is admin
if (!isset($_SESSION['username']) || !($_SESSION['is_admin'] ?? false)) {
    header("Location: /auth/login.php?error=unauthorized");
    exit();
}

include __DIR__ . '/../db/photo_helper.php';

// Get counts for badges or info (optional)
try {
    $approver_department = $_SESSION['department'] ?? '';
    $pending_count_stmt = $conn->prepare("SELECT COUNT(*) FROM rtw_return_to_work WHERE status = 'Pending' AND department = ?");
    $pending_count_stmt->execute([$approver_department]);
    $pending_count = $pending_count_stmt->fetchColumn();
} catch (PDOException $e) {
    $pending_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - La Rose Noire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Alpine JS for tab management -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Axios for API calls -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <link href="/style.css" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }

        /* Search Results Styles */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            margin-top: 0.5rem;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        .search-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
        }
        .search-item:last-child { border-bottom: none; }
        .search-item:hover { background: #fdf2f8; }
    </style>
</head>
<body x-data="settingsApp()" x-init="init()" class="font-sans text-gray-800 flex flex-col md:flex-row min-h-screen overflow-hidden">
    <div class="mesh-bg"></div>

    <!-- Sidebar -->
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col p-8 lg:p-12 overflow-y-auto custom-scrollbar relative z-10 w-full md:h-[100dvh]">
        <?php 
        $page_title = "System Settings";
        $page_subtitle = "Administrative Management Hub";
        include __DIR__ . '/../components/header.php'; 
        ?>

        <!-- Tabs Navigation -->
        <div class="flex gap-4 mb-8 bg-white/50 p-2 rounded-3xl border border-white w-fit shadow-sm">
            <button @click="currentTab = 'permissions'" 
                :class="currentTab === 'permissions' ? 'bg-pink-500 text-white shadow-lg scale-105' : 'text-gray-500 hover:text-pink-500 hover:bg-white'"
                class="px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition-all">
                <i class="fa-solid fa-shield-halved mr-2"></i> Permissions
            </button>
            <button @click="currentTab = 'supervisors'" 
                :class="currentTab === 'supervisors' ? 'bg-indigo-500 text-white shadow-lg scale-105' : 'text-gray-500 hover:text-indigo-500 hover:bg-white'"
                class="px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition-all">
                <i class="fa-solid fa-user-tie mr-2"></i> Supervisors
            </button>
        </div>

        <!-- Tab: Permissions -->
        <div x-show="currentTab === 'permissions'" x-transition x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Add New Permission -->
                <div class="glass-panel rounded-[2.5rem] p-8 shadow-sm h-fit">
                    <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center gap-3">
                        <span class="w-10 h-10 bg-pink-500/10 text-pink-500 rounded-xl flex items-center justify-center"><i class="fa-solid fa-plus"></i></span>
                        Grant Permission
                    </h3>
                    <div class="space-y-6">
                        <div class="relative">
                            <label class="text-[9px] font-black text-gray-400 uppercase ml-1 mb-2 block">Search Employee</label>
                            <input type="text" x-model="searchQuery" @input="searchEmployees" 
                                class="w-full bg-white border-2 border-slate-100 p-4 rounded-2xl outline-none focus:border-pink-300 transition-all font-bold text-sm shadow-sm"
                                placeholder="Name or ID...">
                            <div class="search-results custom-scrollbar shadow-xl" x-show="searchResults.length > 0">
                                <template x-for="emp in searchResults" :key="emp.employee_id">
                                    <div @click="selectEmployee(emp)" class="search-item flex flex-col">
                                        <span class="font-black text-sm text-slate-800" x-text="emp.employee_name"></span>
                                        <span class="text-[10px] text-slate-400 font-bold tracking-tight uppercase" x-text="emp.department + ' • ' + emp.employee_id"></span>
                                    </div>
                                </template>
                            </div>
                            <div x-show="selectedEmployee" class="mt-4 p-4 bg-emerald-50 rounded-2xl border border-emerald-100 flex items-center gap-3">
                                <span class="w-8 h-8 bg-emerald-500 text-white rounded-lg flex items-center justify-center"><i class="fa-solid fa-check"></i></span>
                                <div>
                                    <p class="text-xs font-black text-emerald-800" x-text="selectedEmployee.employee_name"></p>
                                    <p class="text-[9px] text-emerald-600 font-bold uppercase tracking-widest" x-text="selectedEmployee.employee_id"></p>
                                </div>
                                <button @click="selectedEmployee = null; searchQuery = ''" class="ml-auto text-emerald-300 hover:text-rose-500"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </div>

                        <div>
                            <label class="text-[9px] font-black text-gray-400 uppercase ml-1 mb-2 block">Role Level</label>
                            <select x-model="permissionType" class="w-full bg-white border-2 border-slate-100 p-4 rounded-2xl outline-none focus:border-pink-300 transition-all font-bold text-sm shadow-sm appearance-none">
                                <option value="">Select Role...</option>
                                <option value="APPROVER">Application Approver</option>
                                <option value="SHE_IMPERSONATOR">SHE Access (Act as SHE)</option>
                            </select>
                        </div>

                        <button @click="savePermission" 
                            :disabled="!selectedEmployee || !permissionType || loading"
                            class="w-full bg-gradient-to-r from-pink-500 to-rose-500 text-white font-black py-4 rounded-2xl shadow-lg hover:shadow-pink-500/30 transition-all active:scale-95 disabled:opacity-50 disabled:grayscale uppercase text-[11px] tracking-widest">
                            <span x-show="!loading">Add Permission</span>
                            <span x-show="loading"><i class="fa-solid fa-circle-notch animate-spin mr-2"></i> Processing...</span>
                        </button>
                    </div>
                </div>

                <!-- Existing Permissions List -->
                <div class="lg:col-span-2">
                    <div class="max-h-[calc(100vh-350px)] overflow-y-auto pr-2 custom-scrollbar space-y-4">
                        <template x-for="perm in permissions" :key="perm.id">
                            <div class="glass-card rounded-[2rem] p-6 shadow-sm flex items-center justify-between group">
                                <div class="flex items-center gap-5">
                                    <div class="w-14 h-14 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center text-xl shadow-inner shrink-0 relative overflow-hidden">
                                         <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(perm.first_name + ' ' + perm.last_name || perm.employee_id)}&background=random&color=fff&size=128&bold=true`" 
                                              class="w-full h-full object-cover relative z-10">
                                         <div class="absolute inset-0 flex items-center justify-center text-gray-400 z-0" style="display:none">
                                             <i :class="perm.permission_type === 'APPROVER' ? 'fa-solid fa-user-check' : 'fa-solid fa-user-nurse'"></i>
                                         </div>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3">
                                            <h4 class="font-black text-slate-800 tracking-tight" x-text="(perm.first_name || '') + ' ' + (perm.last_name || '')"></h4>
                                            <span :class="perm.permission_type === 'APPROVER' ? 'bg-sky-100 text-sky-600 border-sky-200' : 'bg-pink-100 text-pink-600 border-pink-200'" 
                                                class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-lg border" 
                                                x-text="perm.permission_type === 'APPROVER' ? 'Approver' : 'SHE Access'"></span>
                                        </div>
                                        <p class="text-[10px] font-bold text-slate-400 mt-1">
                                            <span class="text-slate-600" x-text="perm.employee_name"></span> • 
                                            ID: <span class="text-slate-600" x-text="perm.employee_id"></span>
                                        </p>
                                        <p class="text-[9px] font-bold text-slate-300 mt-0.5">
                                            Granted on <span x-text="formatDate(perm.granted_at)"></span> by <span x-text="perm.granted_by"></span>
                                        </p>
                                    </div>
                                </div>
                                <button @click="deleteEntry('permission', perm.id)" class="w-10 h-10 text-slate-300 hover:bg-rose-50 hover:text-rose-500 rounded-xl transition-all"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Supervisors -->
        <div x-show="currentTab === 'supervisors'" x-transition x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Add New Supervisor Mapping -->
                <div class="glass-panel rounded-[2.5rem] p-8 shadow-sm h-fit">
                    <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center gap-3">
                        <span class="w-10 h-10 bg-indigo-500/10 text-indigo-500 rounded-xl flex items-center justify-center"><i class="fa-solid fa-plus"></i></span>
                        Map Supervisor
                    </h3>
                    <div class="space-y-6">
                        <div>
                            <label class="text-[9px] font-black text-gray-400 uppercase ml-1 mb-2 block">Target Department</label>
                            <select x-model="targetDept" class="w-full bg-white border-2 border-slate-100 p-4 rounded-2xl outline-none focus:border-indigo-300 transition-all font-bold text-sm shadow-sm appearance-none">
                                <option value="">Select Department...</option>
                                <template x-for="dept in departments" :key="dept">
                                    <option :value="dept" x-text="dept"></option>
                                </template>
                            </select>
                        </div>

                        <div class="relative">
                            <label class="text-[9px] font-black text-gray-400 uppercase ml-1 mb-2 block">Supervisor Selection</label>
                            <input type="text" x-model="searchQuerySup" @input="searchEmployeesSup" 
                                class="w-full bg-white border-2 border-slate-100 p-4 rounded-2xl outline-none focus:border-indigo-300 transition-all font-bold text-sm shadow-sm"
                                placeholder="Search by name...">
                            <div class="search-results custom-scrollbar shadow-xl" x-show="searchResultsSup.length > 0">
                                <template x-for="emp in searchResultsSup" :key="'sup-'+emp.employee_id">
                                    <div @click="selectSupervisor(emp)" class="search-item flex flex-col">
                                        <span class="font-black text-sm text-slate-800" x-text="emp.employee_name"></span>
                                        <span class="text-[10px] text-slate-400 font-bold tracking-tight uppercase" x-text="emp.department"></span>
                                    </div>
                                </template>
                            </div>
                            <div x-show="selectedSup" class="mt-4 p-4 bg-indigo-50 rounded-2xl border border-indigo-100 flex items-center gap-3">
                                <span class="w-8 h-8 bg-indigo-500 text-white rounded-lg flex items-center justify-center"><i class="fa-solid fa-check"></i></span>
                                <div>
                                    <p class="text-xs font-black text-indigo-800" x-text="selectedSup.employee_name"></p>
                                    <p class="text-[9px] text-indigo-600 font-bold uppercase tracking-widest" x-text="selectedSup.employee_id"></p>
                                </div>
                                <button @click="selectedSup = null; searchQuerySup = ''" class="ml-auto text-indigo-300 hover:text-rose-500"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </div>

                        <div>
                            <label class="text-[9px] font-black text-gray-400 uppercase ml-1 mb-2 block">Custom Subtitle (Optional)</label>
                            <input type="text" x-model="supSubtitle" class="w-full bg-white border-2 border-slate-100 p-4 rounded-2xl outline-none focus:border-indigo-300 transition-all font-bold text-xs" placeholder="e.g. Facilities - Production Manager">
                        </div>

                        <button @click="saveSupervisor" 
                            :disabled="!targetDept || !selectedSup || loading"
                            class="w-full bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-black py-4 rounded-2xl shadow-lg hover:shadow-indigo-500/30 transition-all active:scale-95 disabled:opacity-50 disabled:grayscale uppercase text-[11px] tracking-widest">
                            <span x-show="!loading">Add Supervisor</span>
                            <span x-show="loading"><i class="fa-solid fa-circle-notch animate-spin mr-2"></i> Processing...</span>
                        </button>
                    </div>
                </div>

                <!-- Existing Supervisors List -->
                <div class="lg:col-span-2">
                    <div class="max-h-[calc(100vh-350px)] overflow-y-auto pr-2 custom-scrollbar space-y-4">
                        <template x-for="sup in supervisors" :key="sup.id">
                            <div class="glass-card rounded-[2rem] p-6 shadow-sm flex items-center justify-between group">
                                <div class="flex items-center gap-5">
                                    <div class="w-14 h-14 bg-indigo-50 text-indigo-500 rounded-2xl flex items-center justify-center text-xl shadow-inner shrink-0 relative overflow-hidden">
                                         <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(sup.first_name + ' ' + sup.last_name || sup.employee_id)}&background=random&color=fff&size=128&bold=true`" 
                                              class="w-full h-full object-cover relative z-10">
                                         <div class="absolute inset-0 flex items-center justify-center text-gray-400 z-0" style="display:none">
                                             <i class="fa-solid fa-user-tie"></i>
                                         </div>
                                    </div>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h4 class="font-black text-slate-800 tracking-tight uppercase text-xs" x-text="(sup.first_name || '') + ' ' + (sup.last_name || '')"></h4>
                                            <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-600 text-[10px] font-bold border border-indigo-100" x-text="sup.employee_id"></span>
                                        </div>
                                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mt-1" x-text="sup.department"></p>
                                        <p class="text-sm font-bold text-slate-600 mt-1" x-text="sup.custom_subtitle || 'Default Header'"></p>
                                    </div>
                                </div>
                                <button @click="deleteEntry('supervisor', sup.id)" class="w-10 h-10 text-slate-300 hover:bg-rose-50 hover:text-rose-500 rounded-xl transition-all"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-auto pt-8 text-center">
            <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest">&copy; 2026 Admin Hub • LRN IT Solution</p>
        </div>
    </main>

    <script>
        function settingsApp() {
            return {
                currentTab: 'permissions',
                loading: false,
                permissions: [],
                supervisors: [],
                departments: [],
                
                // Permission Inputs
                searchQuery: '',
                searchResults: [],
                selectedEmployee: null,
                permissionType: '',
                
                // Supervisor Inputs
                targetDept: '',
                searchQuerySup: '',
                searchResultsSup: [],
                selectedSup: null,
                supSubtitle: '',

                init() {
                    this.fetchData();
                },

                fetchData() {
                    axios.get('/db/get_admin_data.php')
                        .then(res => {
                            if(res.data.error) return alert(res.data.error);
                            this.permissions = res.data.permissions;
                            this.supervisors = res.data.supervisors;
                            this.departments = res.data.departments;
                        });
                },

                searchEmployees() {
                    if (this.searchQuery.length < 2) {
                        this.searchResults = [];
                        return;
                    }
                    axios.get(`/db/search_employees.php?term=${this.searchQuery}`)
                        .then(res => {
                            this.searchResults = res.data;
                        });
                },

                selectEmployee(emp) {
                    this.selectedEmployee = emp;
                    this.searchResults = [];
                    this.searchQuery = emp.employee_name;
                },

                searchEmployeesSup() {
                    if (this.searchQuerySup.length < 2) {
                        this.searchResultsSup = [];
                        return;
                    }
                    axios.get(`/db/search_employees.php?term=${this.searchQuerySup}`)
                        .then(res => {
                            this.searchResultsSup = res.data;
                        });
                },

                selectSupervisor(emp) {
                    this.selectedSup = emp;
                    this.searchResultsSup = [];
                    this.searchQuerySup = emp.employee_name;
                },

                savePermission() {
                    this.loading = true;
                    let formData = new FormData();
                    formData.append('action', 'add_approver');
                    formData.append('employee_id', this.selectedEmployee.employee_id);
                    formData.append('permission', this.permissionType);

                    axios.post('/db/save_admin_data.php', formData)
                        .then(res => {
                            // save_admin_data.php now redirects, but let's handle JSON if it was JSON
                            // Actually I'll re-init data
                            this.fetchData();
                            this.selectedEmployee = null;
                            this.permissionType = '';
                            this.searchQuery = '';
                        })
                        .catch(err => alert('Failed to save permission'))
                        .finally(() => this.loading = false);
                },

                saveSupervisor() {
                    this.loading = true;
                    let formData = new FormData();
                    formData.append('action', 'add_supervisor');
                    formData.append('department', this.targetDept);
                    formData.append('employee_id', this.selectedSup.employee_id);
                    formData.append('custom_subtitle', this.supSubtitle);

                    axios.post('/db/save_admin_data.php', formData)
                        .then(res => {
                            this.fetchData();
                            this.selectedSup = null;
                            this.targetDept = '';
                            this.supSubtitle = '';
                            this.searchQuerySup = '';
                        })
                        .catch(err => alert('Failed to save supervisor'))
                        .finally(() => this.loading = false);
                },

                deleteEntry(type, id) {
                    if(!confirm('Are you sure you want to remove this entry?')) return;
                    
                    let formData = new FormData();
                    formData.append('action', type === 'permission' ? 'delete_approver' : 'delete_supervisor');
                    formData.append('id', id);

                    axios.post('/db/delete_admin_data.php', formData)
                        .then(res => {
                            this.fetchData();
                        })
                        .catch(err => alert('Deletion failed'));
                },

                formatDate(dateStr) {
                    if(!dateStr) return 'N/A';
                    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                }
            }
        }
    </script>
    <?php include __DIR__ . '/../components/logout_modal.php'; ?>
</body>
</html>
