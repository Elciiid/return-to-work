<?php // THE ULTIMATE LOGIN PAGE ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login • Return to Work</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f472b6',
                        'primary-dark': '#ec4899',
                        secondary: '#a78bfa',
                        success: '#34d399',
                        warning: '#fbbf24',
                        danger: '#f87171'
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-glow': 'pulse-glow 2s ease-in-out infinite',
                        'bounce-in': 'bounce-in 0.6s ease-out'
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Animated Background Blobs -->
    <div class="fixed inset-0 -z-10">
        <div
            class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-gradient-to-r from-pink-300 to-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float">
        </div>
        <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-gradient-to-r from-purple-300 to-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float"
            style="animation-delay: 2s;"></div>
        <div class="absolute bottom-[-20%] left-[20%] w-96 h-96 bg-gradient-to-r from-yellow-200 to-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float"
            style="animation-delay: 4s;"></div>
        <div class="absolute bottom-[-10%] right-[10%] w-72 h-72 bg-gradient-to-r from-green-200 to-blue-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float"
            style="animation-delay: 1s;"></div>
    </div>

    <!-- Main Login Container -->
    <div
        class="w-full max-w-5xl glass-panel rounded-3xl overflow-hidden flex flex-col md:flex-row shadow-2xl relative z-10 animate-bounce-in">

        <!-- Left Panel - Brand -->
        <div
            class="w-full md:w-5/12 bg-gradient-to-br from-pink-400 via-pink-300 to-purple-300 p-12 text-white flex flex-col justify-center items-center relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent"></div>
            <div class="absolute inset-0 backdrop-blur-[1px]"></div>

            <div class="relative z-10 text-center space-y-6">
                <!-- Logo/Icon -->
                <div
                    class="w-24 h-24 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center mx-auto animate-pulse-glow shadow-2xl">
                    <i class="fas fa-spa text-5xl text-white drop-shadow-lg"></i>
                </div>

                <!-- Brand Name -->
                <div class="space-y-2">
                    <h1 class="text-4xl font-bold tracking-tight text-pink-100 drop-shadow-sm opacity-90 mb-4">
                        La Rose Noire
                    </h1>
                    <div class="relative inline-block">
                        <p class="text-white text-5xl font-black uppercase tracking-widest drop-shadow-xl leading-none"
                            style="text-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                            Return <br><span class="text-4xl">to Work</span>
                        </p>
                        <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-24 h-1.5 bg-white/40 rounded-full">
                        </div>
                    </div>
                </div>

                <!-- Decorative Elements -->
                <div class="flex items-center justify-center gap-2 pt-4">
                    <div class="w-12 h-1 bg-white/60 rounded-full"></div>
                    <div class="w-8 h-1 bg-white/40 rounded-full"></div>
                    <div class="w-4 h-1 bg-white/20 rounded-full"></div>
                </div>
            </div>

            <!-- Floating Decorative Elements -->
            <div class="absolute top-8 right-8 w-16 h-16 bg-white/10 rounded-full flex items-center justify-center animate-float"
                style="animation-delay: 1s;">
                <i class="fas fa-star text-yellow-200 text-lg"></i>
            </div>
            <div class="absolute bottom-12 left-8 w-12 h-12 bg-white/10 rounded-full flex items-center justify-center animate-float"
                style="animation-delay: 3s;">
                <i class="fas fa-heart text-pink-200 text-sm"></i>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="w-full md:w-7/12 p-12 bg-gradient-to-br from-white/80 to-white/60 backdrop-blur-xl">
            <div class="space-y-8">
                <!-- Header -->
                <div class="text-center space-y-3">
                    <h2
                        class="text-4xl font-bold text-gray-800 bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                        Welcome Back
                    </h2>
                    <p class="text-xs font-bold text-pink-500 uppercase tracking-widest">Sign in to Return to Work</p>
                    <div class="w-16 h-1 bg-gradient-to-r from-pink-400 to-purple-400 rounded-full mx-auto mt-2"></div>
                </div>

                <!-- Login Form -->
                <form action="authenticate.php" method="POST" class="space-y-6">
                    <!-- Username Field -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <i class="fas fa-user text-pink-400"></i>
                            Username
                        </label>
                        <div class="relative">
                            <input type="text" name="username" required
                                class="form-input pl-12 pr-4 py-4 text-gray-700 placeholder-gray-400"
                                placeholder="Enter your username">
                            <div
                                class="absolute left-4 top-1/2 -translate-y-1/2 w-6 h-6 bg-gradient-to-r from-pink-400 to-purple-400 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <i class="fas fa-lock text-pink-400"></i>
                            Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password" required
                                class="form-input pl-12 pr-12 py-4 text-gray-700 placeholder-gray-400"
                                placeholder="••••••••" id="password">
                            <div
                                class="absolute left-4 top-1/2 -translate-y-1/2 w-6 h-6 bg-gradient-to-r from-pink-400 to-purple-400 rounded-full flex items-center justify-center">
                                <i class="fas fa-lock text-white text-xs"></i>
                            </div>
                            <button type="button" onclick="togglePassword()"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-pink-500 transition-colors p-1">
                                <i class="fas fa-eye text-sm" id="password-toggle"></i>
                            </button>
                        </div>

                        <!-- Forgot Password Link -->
                        <div class="flex justify-end">
                            <button type="button" id="forgot-link"
                                class="text-sm font-semibold text-pink-500 hover:text-pink-600 transition-all duration-300 flex items-center gap-2 group">
                                <i class="fas fa-key text-xs group-hover:rotate-12 transition-transform"></i>
                                Forgot Password?
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-center">
                        <button type="submit" class="btn-primary w-auto px-8 py-2.5 text-base font-semibold group">
                            <span>Sign In</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>

                <!-- Footer -->
                <div class="pt-8 border-t border-gray-200/50 text-center">
                    <p class="text-gray-500 text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-shield-alt text-green-500"></i>
                        Secure access to your workspace
                    </p>
                </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgot-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none">
        <div class="bg-white/95 backdrop-blur-2xl border border-white/20 rounded-[2.5rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.3)] p-10 max-w-md w-full transform transition-all duration-500 scale-90 opacity-0 relative group">
            <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-24 h-24 bg-gradient-to-br from-pink-400 to-purple-400 rounded-3xl rotate-12 flex items-center justify-center shadow-xl group-hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-key text-4xl text-white"></i>
            </div>
            
            <div class="mt-12 text-center space-y-6 text-slate-800">
                <div>
                    <h3 class="text-3xl font-black tracking-tight">Need a Reset?</h3>
                    <p class="text-pink-500 text-xs font-black uppercase tracking-widest mt-1">Credentials Recovery</p>
                </div>
                
                <p class="leading-relaxed font-medium">
                    For your security, we don't handle automated resets. Please visit the <span class="text-pink-600 font-bold">IT Helpdesk</span> in person to verify your identity and restore access.
                </p>
                
                <button class="w-full bg-gradient-to-r from-pink-500 to-purple-500 hover:from-pink-600 hover:to-purple-600 text-white font-black py-4 px-8 rounded-2xl shadow-lg shadow-pink-200 transition-all active:scale-95 flex items-center justify-center gap-3" onclick="closeModal()">
                    <i class="fas fa-check"></i>
                    <span>Got it, thanks!</span>
                </button>
            </div>
            
            <button class="absolute top-6 right-6 text-gray-400 hover:text-gray-600 transition-colors" onclick="closeModal()">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="error-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none">
        <div class="bg-white/95 backdrop-blur-2xl border border-white/20 rounded-[2.5rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.3)] p-10 max-w-md w-full transform transition-all duration-500 scale-90 opacity-0 relative group">
            <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-24 h-24 bg-gradient-to-br from-red-500 to-pink-500 rounded-3xl -rotate-12 flex items-center justify-center shadow-xl group-hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-shield-virus text-4xl text-white"></i>
            </div>
            
            <div class="mt-12 text-center space-y-6 text-slate-800">
                <div>
                    <h3 class="text-3xl font-black tracking-tight">Access Denied</h3>
                    <p class="text-red-500 text-xs font-black uppercase tracking-widest mt-1">Security Verification Failed</p>
                </div>
                
                <p class="leading-relaxed font-medium" id="error-message">
                    The credentials you entered don't match our records. Please double-check your username and try again.
                </p>
                
                <button class="w-full bg-slate-800 hover:bg-black text-white font-black py-4 px-8 rounded-2xl shadow-xl transition-all active:scale-95 flex items-center justify-center gap-3" onclick="closeErrorModal()">
                    <i class="fas fa-redo-alt"></i>
                    <span>Try Again</span>
                </button>
            </div>
            
            <button class="absolute top-6 right-6 text-gray-400 hover:text-gray-600 transition-colors" onclick="closeErrorModal()">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
    </div>

    <style>
        .modal-active {
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        .modal-active > div {
            opacity: 1 !important;
            transform: scale(1) !important;
        }
    </style>

    <script>
        // Password Toggle
        function togglePassword() {
            const password = document.getElementById('password');
            const toggle = document.getElementById('password-toggle');
            if (password.type === 'password') {
                password.type = 'text';
                toggle.className = 'fas fa-eye-slash text-sm';
            } else {
                password.type = 'password';
                toggle.className = 'fas fa-eye text-sm';
            }
        }

        // Modal Functions
        function openModal() {
            const modal = document.getElementById('forgot-modal');
            modal.classList.remove('hidden');
            // Force reflow
            modal.offsetHeight;
            modal.classList.add('modal-active');
        }

        function closeModal() {
            const modal = document.getElementById('forgot-modal');
            modal.classList.remove('modal-active');
            setTimeout(() => {
                if (!modal.classList.contains('modal-active')) {
                    modal.classList.add('hidden');
                }
            }, 500);
        }

        function openErrorModal() {
            const modal = document.getElementById('error-modal');
            modal.classList.remove('hidden');
            // Force reflow
            modal.offsetHeight;
            modal.classList.add('modal-active');
        }

        function closeErrorModal() {
            const modal = document.getElementById('error-modal');
            modal.classList.remove('modal-active');
            setTimeout(() => {
                if (!modal.classList.contains('modal-active')) {
                    modal.classList.add('hidden');
                }
            }, 500);
            
            // Remove error parameter from URL without reloading
            const url = new URL(window.location);
            url.searchParams.delete('error');
            url.searchParams.delete('role');
            window.history.replaceState({}, '', url);
        }

        // Event Listeners
        document.getElementById('forgot-link').addEventListener('click', openModal);

        // Keyboard Navigation
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                closeModal();
                closeErrorModal();
            }
        });

        // Close modal when clicking overlay
        document.getElementById('forgot-modal').addEventListener('click', function(e) {
            if(e.target === this) closeModal();
        });

        document.getElementById('error-modal').addEventListener('click', function(e) {
            if(e.target === this) closeErrorModal();
        });

        // Check for error parameter in URL and show modal
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const errorType = urlParams.get('error');
            const userRole = urlParams.get('role');

            if(errorType === 'invalid') {
                openErrorModal();
            } else if (errorType === 'access_denied') {
                const errorMessage = document.getElementById('error-message');
                errorMessage.textContent = `Access denied. Your role "${userRole}" does not have permission to access this page. Please contact your administrator.`;
                openErrorModal();
            }
        });
    </script>
</body>

</html>
</body>

</html>