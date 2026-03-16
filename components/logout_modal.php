<!-- Logout Modal Component -->
<div id="logoutModal" class="fixed inset-0 z-[200] hidden flex items-center justify-center p-6 performance-backdrop">
    <div class="bg-white w-full max-w-sm rounded-[3rem] shadow-2xl overflow-hidden animate-modal-entry">
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
    function openLogoutModal() {
        document.body.classList.add('performance-mode');
        document.getElementById('logoutModal').classList.remove('hidden');
    }
    function closeLogoutModal() {
        document.body.classList.remove('performance-mode');
        document.getElementById('logoutModal').classList.add('hidden');
    }
</script>
