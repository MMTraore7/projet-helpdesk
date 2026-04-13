<?php
session_start();
if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] != 2) {
    header("Location: ../index.php");
    exit;
}
require '../config.php';
require '../includes/header.php';

// Récupérer tous les tickets, triés par ceux nécessitant l'attention la plus urgente (Nouveau en premier)
$stmt = $pdo->query("
    SELECT t.*, c.libelle as categorie, s.libelle as status, s.id_status, u.nom as client_nom
    FROM Tickets t
    LEFT JOIN Categories c ON t.id_categories = c.id_categories
    LEFT JOIN est_associe ea ON t.id_tickets = ea.id_tickets
    LEFT JOIN status s ON ea.id_status = s.id_status
    LEFT JOIN Utilisateurs u ON t.login = u.login
    ORDER BY FIELD(s.libelle, 'Nouveau', 'En cours', 'Résolu', 'Fermé'), t.date_creation ASC
");
$tickets = $stmt->fetchAll();

// Statistiques
$total = count($tickets);
$nouveaux = count(array_filter($tickets, fn($t) => $t['status'] == 'Nouveau'));
$encours = count(array_filter($tickets, fn($t) => $t['status'] == 'En cours'));
$resolus = count(array_filter($tickets, fn($t) => in_array($t['status'], ['Résolu', 'Fermé'])));
?>

<!-- En-tête -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Espace Technicien</h1>
    <p class="text-slate-500 mt-1">Supervisez et traitez les demandes d'assistance des clients.</p>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex items-center">
        <div class="bg-slate-100 text-slate-600 p-3 rounded-lg mr-4 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Total Tickets</p>
            <p class="text-xl font-bold text-slate-800"><?php echo $total; ?></p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex items-center">
        <div class="bg-blue-100 text-blue-600 p-3 rounded-lg mr-4 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Nouveaux</p>
            <p class="text-xl font-bold text-slate-800"><?php echo $nouveaux; ?></p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex items-center">
        <div class="bg-amber-100 text-amber-600 p-3 rounded-lg mr-4 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">En cours</p>
            <p class="text-xl font-bold text-slate-800"><?php echo $encours; ?></p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex items-center">
        <div class="bg-emerald-100 text-emerald-600 p-3 rounded-lg mr-4 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Résolus</p>
            <p class="text-xl font-bold text-slate-800"><?php echo $resolus; ?></p>
        </div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-8 flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <span>Le ticket a été mis à jour avec succès.</span>
    </div>
<?php endif; ?>

<!-- File d'attente des tickets -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
        <h2 class="font-semibold text-slate-800">File d'attente globale</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Ticket</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Aperçu</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                <?php if ($total > 0): ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-slate-900">#<?php echo $ticket['id_tickets']; ?></div>
                                <div class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars($ticket['categorie']); ?></div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    <?php echo date('d/m/Y H:i', strtotime($ticket['date_creation'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-800 flex items-center">
                                    <div class="w-6 h-6 rounded-full border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-500 bg-slate-100 mr-2">
                                        <?php echo strtoupper(substr($ticket['client_nom'], 0, 1) ?: substr($ticket['login'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($ticket['client_nom'] ?? $ticket['login']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-slate-700 max-w-xs xl:max-w-md line-clamp-2" title="<?php echo htmlspecialchars($ticket['description']); ?>">
                                    <?php echo htmlspecialchars($ticket['description']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $statusColor = 'bg-slate-100 text-slate-800 border-slate-200';
                                    if(in_array($ticket['status'], ['Nouveau'])) $statusColor = 'bg-blue-50 text-blue-700 border-blue-200';
                                    elseif(in_array($ticket['status'], ['En cours'])) $statusColor = 'bg-amber-50 text-amber-700 border-amber-200';
                                    elseif(in_array($ticket['status'], ['Résolu'])) $statusColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border <?php echo $statusColor; ?>">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?php echo str_replace(['bg-', '-50', '-100'], ['bg-', '-500', '-500'], $statusColor); ?>"></span>
                                    <?php echo htmlspecialchars($ticket['status'] ?? 'Non assigné'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="traiter.php?id=<?php echo $ticket['id_tickets']; ?>" 
                                   class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-slate-800 hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors">
                                    Traiter
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            Aucun ticket dans la base de données.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
