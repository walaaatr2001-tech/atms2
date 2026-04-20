<?php
require_once '../../config/config.php';
$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $_SESSION['registration_success'] = true;
}

if (isset($_SESSION['registration_success'])) {
    $success = 'Votre compte a été créé avec succès! Il est en attente de validation par un administrateur.';
    unset($_SESSION['registration_success']);
}

if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
if (login($username, $password)) {
    switch ($_SESSION['user_role']) {
        case 'super_admin':
        case 'dept_admin':
            redirect('../admin/dashboard.php');
            break;
        case 'enterprise':
            redirect('../dashboard/enterprise/index.php');
            break;
        default:
            redirect('../auth/login.php');
    }

}{ 
}
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AT Archive Intelligence</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: radial-gradient(circle at center, #0a1f1c 0%, #020808 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(13, 13, 13, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .input-dark {
            background: #121212 !important;
            border: 1px solid #2a2a2a !important;
            color: white !important;
        }
        .input-dark:focus {
            border-color: #00BFA5 !important;
            box-shadow: 0 0 0 2px rgba(0, 191, 165, 0.15);
        }
        .glow-line {
            height: 1px;
            width: 80px;
            background: linear-gradient(90deg, transparent, #00BFA5, transparent);
        }
        @media (min-width: 768px) {
            .glow-line { width: 120px; }
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center p-4 md:p-8 text-white">

    <div class="w-full max-w-7xl flex justify-start mb-12" data-aos="fade-down">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-teal-800 flex items-center justify-center shadow-lg border border-teal-300/20">
                <i class="fa-solid fa-box-archive text-black text-lg"></i>
            </div>
            <div>
                <span class="block font-bold tracking-tight text-white leading-none">AT-AMS</span>
                <span class="text-[10px] text-teal-500 uppercase tracking-widest font-semibold">Digital Archives</span>
            </div>
        </div>
    </div>

    <div class="w-full max-w-2xl text-center mb-8 md:mb-12" data-aos="zoom-in" data-aos-delay="200">
        <h1 class="text-3xl md:text-5xl font-bold mb-3 tracking-tight">
            <span class="text-white">Preserve with </span>
            <span class="text-teal-400">Precision</span>
        </h1>
        <h2 class="text-lg md:text-2xl font-light text-gray-400">
            Analyze. Manage. Retrieve.
        </h2>
        
        <div class="flex items-center justify-center space-x-4 mt-6 opacity-50">
            <div class="glow-line"></div>
            <i class="fa-solid fa-microchip text-teal-500 text-xs"></i>
            <div class="glow-line"></div>
        </div>
    </div>

    <div class="w-full max-w-md glass-card rounded-[2rem] md:rounded-[2.5rem] p-6 md:p-10 shadow-2xl relative mb-12" data-aos="fade-up" data-aos-delay="400">
        <div class="text-center mb-8 md:mb-10">
            <h3 class="text-xl md:text-2xl font-semibold mb-2">Systems Access</h3>
            <p class="text-gray-500 text-sm">Secure gateway for centralized document analysis</p>
        </div>

        <?php if ($success): ?>
            <div id="success-message" class="mb-6 bg-teal-900/20 border border-teal-500/40 text-teal-200 px-4 py-3 rounded-xl text-sm text-center">
                <i class="fa-solid fa-circle-check mr-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-900/20 border border-red-500/40 text-red-200 px-4 py-3 rounded-xl text-sm text-center">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5 md:space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-2 ml-1">Operator Identity</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-600">
                        <i class="fa-regular fa-user"></i>
                    </span>
                    <input type="text" name="username" placeholder="Username or Email" required
                           class="input-dark w-full pl-12 pr-5 py-3.5 md:py-4 rounded-2xl outline-none transition-all placeholder:text-gray-700">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-2 ml-1">Security Key</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-600">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>
                    <input type="password" id="password" name="password" placeholder="••••••••" required
                           class="input-dark w-full pl-12 pr-12 py-3.5 md:py-4 rounded-2xl outline-none transition-all placeholder:text-gray-700">
                    <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 hover:text-teal-400">
                        <i id="toggleIcon" class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <div class="flex justify-between items-center mt-3 px-1">
                    <label class="flex items-center text-xs text-gray-500 cursor-pointer">
                        <input type="checkbox" class="mr-2 accent-teal-500 rounded-sm"> Remember
                    </label>
                    <a href="#" class="text-xs text-gray-500 hover:text-teal-400 transition-colors">Recovery Mode</a>
                </div>
            </div>

            <button type="submit" name="login"
                    class="w-full bg-[#00BFA5] hover:bg-[#00e6c4] text-black font-extrabold py-3.5 md:py-4 rounded-2xl transition-all transform hover:scale-[1.01] active:scale-[0.99] mt-2 shadow-lg shadow-teal-500/10">
                AUTHORIZE ACCESS
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-white/5 text-center">
            <p class="text-sm text-gray-500">
                New Enterprise? 
                <a href="register.php" class="text-teal-400 font-semibold hover:text-teal-300 ml-1 transition-colors underline underline-offset-4">
                    Register Institution
                </a>
            </p>
        </div>
    </div>

    <footer class="mt-auto py-8 flex flex-col items-center space-y-4 w-full" data-aos="fade-up">
        <div class="flex flex-wrap justify-center gap-6 text-gray-600 text-[10px] uppercase tracking-widest font-medium">
            <span class="hover:text-teal-500 cursor-pointer">Privacy Protocol</span>
            <span class="hover:text-teal-500 cursor-pointer">Security Audit</span>
            <span class="hover:text-teal-500 cursor-pointer">Support</span>
        </div>
        <p class="text-gray-700 text-[10px] tracking-widest uppercase text-center px-4">
            &copy; <?= date('Y') ?> Algérie Télécom Archive Management System
        </p>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize Animations
        AOS.init({
            duration: 1000,
            once: true
        });

        // Auto-hide success message after 2 seconds
        const successMsg = document.getElementById('success-message');
        if (successMsg) {
            setTimeout(() => {
                successMsg.style.transition = 'opacity 0.5s ease';
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 500);
            }, 2000);
        }

        function togglePassword() {
            const passwordField = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>