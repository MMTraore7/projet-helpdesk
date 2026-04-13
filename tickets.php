<?php
session_start();
if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] != 3) {
    header("Location: ../index.php");
    exit;
}
require '../config.php';
require '../includes/header.php';

// Récupérer tous les tickets sans exception
$stmt = $pdo->query("
    SELECT t.*, c.libelle as categorie, s.libelle as status, s.id_status, u.nom as client_nom
    FROM Tickets t
    LEFT JOIN Categories c ON t.id_categories = c.id_categories
    LEFT JOIN est_associe ea ON t.id_tickets = ea.id_tickets
    LEFT JOIN status s ON ea.id_status = s.id_status
    LEFT JOIN Utilisateurs u ON t.login = u.login
    ORDER BY t.date_creation DESC
");
$tickets = $stmt->fetchAll();

$total = count($tickets);
$nouveaux = count(array_filter($tickets, fn($t) => $t['status'] == 'Nouveau'));
$encours = count(array_filter($tickets, fn($t) => $t['status'] == 'En cours'));
$resolus = count(array_filter($tickets, fn($t) => in_array($t['status'], ['Résolu', 'Fermé'])));
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
                    <span class="ml-1 text-sm font-medium text-slate-700 md:ml-2">Vue Globale des Tickets</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Vue Globale des Tickets</h1>
            <p class="text-slate-500 mt-1">Supervision de tous les tickets enregistrés sur la plateforme.</p>
        </div>
    </div>

    <!-- KPI -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 flex items-center justify-between">
            <span class="text-slate-500 text-sm font-medium">Total Tickets</span>
            <span class="text-2xl font-bold text-slate-800"><?php echo $total; ?></span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 flex items-center justify-between">
            <span class="text-blue-500 text-sm font-medium">Nouveaux</span>
            <span class="text-2xl font-bold text-blue-600"><?php echo $nouveaux; ?></span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 flex items-center justify-between">
            <span class="text-amber-500 text-sm font-medium">En cours</span>
            <span class="text-2xl font-bold text-amber-600"><?php echo $encours; ?></span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 flex items-center justify-between">
            <span class="text-emerald-500 text-sm font-medium">Résolus / Fermés</span>
            <span class="text-2xl font-bold text-emerald-600"><?php echo $resolus; ?></span>
        </div>
    </div>

    <!-- Tableau de suivi -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Ticket & Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Demandeur</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Problème</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <?php if ($total > 0): ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-900">#<?php echo $ticket['id_tickets']; ?></div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        <?php echo date('d/m/Y H:i', strtotime($ticket['date_creation'])); ?>
                                    </div>
                                    <div class="text-xs font-medium text-slate-500 mt-1"><?php echo htmlspecialchars($ticket['categorie']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-800 flex items-center">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-slate-500 bg-slate-100 mr-2 border border-slate-200">
                                            <?php echo strtoupper(substr($ticket['client_nom'], 0, 1) ?: substr($ticket['login'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($ticket['client_nom'] ?? $ticket['login']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-700 max-w-sm xl:max-w-md line-clamp-2" title="<?php echo htmlspecialchars($ticket['description']); ?>">
                                        <?php echo htmlspecialchars($ticket['description']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                        $statusColor = 'bg-slate-100 text-slate-800 border-slate-200';
                                        if(in_array($ticket['status'], ['Nouveau'])) $statusColor = 'bg-blue-50 text-blue-700 border-blue-200';
                                        elseif(in_array($ticket['status'], ['En cours'])) $statusColor = 'bg-amber-50 text-amber-700 border-amber-200';
                                        elseif(in_array($ticket['status'], ['Résolu', 'Fermé'])) $statusColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border <?php echo $statusColor; ?>">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?php echo str_replace(['bg-', '-50', '-100'], ['bg-', '-500', '-500'], $statusColor); ?>"></span>
                                        <?php echo htmlspecialchars($ticket['status'] ?? 'Non assigné'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="../technician/traiter.php?id=<?php echo $ticket['id_tickets']; ?>&from_admin=1" 
                                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-slate-800 hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors">
                                        Voir & Traiter
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                Aucun ticket dans le système.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
