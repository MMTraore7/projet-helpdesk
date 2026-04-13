<?php
if (!isset($_SESSION['login'])) {
    header("Location: /helpdesk/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Desk</title>
    <!-- Tailwind CSS pour un style moderne et premium -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex flex-col">
    <nav class="bg-white shadow-sm border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo / Titre -->
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-orange-500 to-red-500">SupportDesk</span>
                </div>
                <!-- Navigation Menu User -->
                <div class="flex items-center space-x-6">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold mr-2">
                            <?php echo strtoupper(substr($_SESSION['nom'] ?? 'U', 0, 1)); ?>
                        </div>
                        <span class="text-sm font-medium text-slate-600 hidden sm:block"><?php echo htmlspecialchars($_SESSION['nom'] ?? $_SESSION['login']); ?></span>
                        <!-- Badge Role -->
                        <span class="ml-2 px-2 py-0.5 text-[10px] uppercase font-bold tracking-wider rounded-full <?php 
                            if($_SESSION['id_role'] == 1) echo 'bg-blue-100 text-blue-700'; 
                            elseif($_SESSION['id_role'] == 2) echo 'bg-amber-100 text-amber-700';
                            else echo 'bg-purple-100 text-purple-700';
                        ?>">
                            <?php 
                                if($_SESSION['id_role'] == 1) echo 'Client'; 
                                elseif($_SESSION['id_role'] == 2) echo 'Technicien';
                                else echo 'Admin';
                            ?>
                        </span>
                    </div>
                    <a href="/helpdesk/profil.php" class="mr-4 text-sm font-medium text-slate-500 hover:text-orange-500 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span class="hidden sm:inline">Mon Profil</span>
                    </a>
                    <a href="/helpdesk/logout.php" class="text-sm font-medium text-slate-500 hover:text-red-500 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span class="hidden sm:inline">Quitter</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
