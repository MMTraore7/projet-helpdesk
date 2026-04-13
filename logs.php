<?php
session_start();
if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] != 3) {
    header("Location: ../index.php");
    exit;
}
require '../config.php';

// Récupérer les logs
$logs = $pdo->query("SELECT * FROM Logs ORDER BY date_heure DESC")->fetchAll();

require '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8 pl-4 pr-4">
    <!-- Breadcrumb -->
    <nav class="flex mb-2" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="index.php" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-orange-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Panneau d'administration
                </a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="ml-1 text-sm font-medium text-slate-700 md:ml-2">Historique des Actions</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-slate-900">Journaux Système</h1>
    </div>

    <!-- Tableau des logs -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
            <h2 class="font-semibold text-slate-800">Historique récent</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date & Heure</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['date_heure'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                    <?php echo htmlspecialchars($log['login'] ?? 'Système'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-normal text-sm text-slate-700">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $type_classes = [
                                        'INFO' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'WARNING' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'ERROR' => 'bg-red-50 text-red-700 border-red-200'
                                    ];
                                    $class = $type_classes[strtoupper($log['type'])] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border <?php echo $class; ?>">
                                        <?php echo htmlspecialchars($log['type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                Aucun log trouvé dans le système.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
