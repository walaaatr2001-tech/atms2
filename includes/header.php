<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();
$user = getUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'AT-AMS' ?> - Algérie Télécom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: radial-gradient(circle at center, #0a1f1c 0%, #020808 100%); min-height: 100vh; }
        .glass { background: rgba(13, 13, 13, 0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .input-dark { background: #121212 !important; border: 1px solid #2a2a2a !important; color: white !important; }
        .input-dark:focus { border-color: #00BFA5 !important; box-shadow: 0 0 0 2px rgba(0, 191, 165, 0.15); }
    </style>
</head>
<body class="text-white flex flex-col min-h-screen">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-[#0a0f0e] border-r border-white/5 fixed h-full z-10">
            <div class="p-6 border-b border-white/5">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-teal-800 flex items-center justify-center">
                        <i class="fa-solid fa-box-archive text-black text-lg"></i>
                    </div>
                    <div>
                        <span class="block font-bold text-white">AT-AMS</span>
                        <span class="text-[10px] text-teal-500 uppercase tracking-widest">Archives</span>
                    </div>
                </div>
            </div>
            <nav class="mt-4 p-4 space-y-1">
                <?php
                $currentPage = basename($_SERVER['PHP_SELF']);
                $menuItems = [
                    '../dashboard/index.php' => ['icon' => 'fa-gauge-high', 'label' => 'Dashboard'],
                    '../documents/list.php' => ['icon' => 'fa-file-lines', 'label' => 'Documents'],
                    '../documents/upload.php' => ['icon' => 'fa-cloud-arrow-up', 'label' => 'Uploader'],
                    '../documents/search.php' => ['icon' => 'fa-magnifying-glass', 'label' => 'Recherche'],
                    '../documents/advanced_search.php' => ['icon' => 'fa-layer-group', 'label' => 'Recherche Avancée'],
                ];
                
                if (in_array($user['role'], ['super_admin', 'dept_admin'])) {
                    $menuItems['../admin/users.php'] = ['icon' => 'fa-users', 'label' => 'Utilisateurs'];
                    $menuItems['../admin/enterprises-pending.php'] = ['icon' => 'fa-building', 'label' => 'Entreprises'];
                    $menuItems['../admin/departments.php'] = ['icon' => 'fa-sitemap', 'label' => 'Départements'];
                    $menuItems['../admin/ai_panel.php'] = ['icon' => 'fa-robot', 'label' => 'IA Panel'];
                    $menuItems['../admin/settings.php'] = ['icon' => 'fa-gear', 'label' => 'Paramètres'];
                }
                
                $menuItems['../documents/contracts.php'] = ['icon' => 'fa-file-contract', 'label' => 'Contrats'];
                $menuItems['../documents/ods.php'] = ['icon' => 'fa-file-lines', 'label' => 'ODS'];
                $menuItems['../documents/payments.php'] = ['icon' => 'fa-credit-card', 'label' => 'Paiements'];
                $menuItems['../documents/archive.php'] = ['icon' => 'fa-archive', 'label' => 'Archives'];
                $menuItems['../documents/reports.php'] = ['icon' => 'fa-chart-bar', 'label' => 'Rapports'];
                $menuItems['../profile/index.php'] = ['icon' => 'fa-user', 'label' => 'Mon Profil'];
                $menuItems['../notifications.php'] = ['icon' => 'fa-bell', 'label' => 'Notifications'];
                
                foreach ($menuItems as $url => $item):
                    $isActive = strpos($_SERVER['REQUEST_URI'], basename($url)) !== false;
                ?>
                <a href="<?= $url ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-white/5 hover:text-teal-400 <?= $isActive ? 'bg-teal-500/10 text-teal-400 border-l-2 border-teal-400' : '' ?>">
                    <i class="fa-solid <?= $item['icon'] ?> w-5"></i>
                    <span class="text-sm font-medium"><?= $item['label'] ?></span>
                </a>
                <?php endforeach; ?>
                
                <a href="../../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 mt-4 border-t border-white/5">
                    <i class="fa-solid fa-right-from-bracket w-5"></i>
                    <span class="text-sm font-medium">Déconnexion</span>
                </a>
            </nav>
        </aside>
        
        <div class="flex-1 ml-64">
            <header class="bg-[#0a0f0e]/80 backdrop-blur border-b border-white/5 fixed top-0 left-64 right-0 z-10">
                <div class="px-8 py-4 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-white"><?= $pageTitle ?? 'Dashboard' ?></h2>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-400"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                        <span class="px-3 py-1 bg-teal-500 text-black text-xs rounded-full font-semibold"><?= ucfirst($user['role']) ?></span>
                    </div>
                </div>
            </header>
            <main class="pt-20 px-8 pb-8">