<?php
session_start();
if (!isset($_SESSION['id_role']) || !in_array($_SESSION['id_role'], [2, 3])) {
    header("Location: ../index.php");
    exit;
}
require '../config.php';

// Si pas d'ID, on repart
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id_ticket = (int)$_GET['id'];

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $notes = trim($_POST['notes']);
    $id_status = (int)$_POST['id_status'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Mettre à jour les notes et la date de suivi dans Tickets
        // Si le statut est "Résolu" (3) ou "Fermé" (4), on renseigne la date_reso
        if (in_array($id_status, [3, 4])) {
            $stmt = $pdo->prepare("UPDATE Tickets SET notes = ?, date_suivi = NOW(), date_reso = NOW() WHERE id_tickets = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE Tickets SET notes = ?, date_suivi = NOW() WHERE id_tickets = ?");
        }
        $stmt->execute([$notes, $id_ticket]);
        
        // 2. Mettre à jour l'association de statut
        // Pour faire simple, on supprime l'ancienne et on insère la nouvelle (ou on UPDATE)
        $stmtDel = $pdo->prepare("DELETE FROM est_associe WHERE id_tickets = ?");
        $stmtDel->execute([$id_ticket]);
        
        $stmtIns = $pdo->prepare("INSERT INTO est_associe (id_tickets, id_status) VALUES (?, ?)");
        $stmtIns->execute([$id_ticket, $id_status]);
        
        $pdo->commit();
        if(isset($_POST['from_admin'])) {
            header("Location: ../admin/tickets.php?success=1");
        } else {
            header("Location: index.php?success=1");
        }
        exit;
    } catch (\PDOException $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

// Récupérer les détails du ticket
$stmt = $pdo->prepare("
    SELECT t.*, c.libelle as categorie, s.libelle as status, s.id_status, u.nom as client_nom, u.login as client_login
    FROM Tickets t
    LEFT JOIN Categories c ON t.id_categories = c.id_categories
    LEFT JOIN est_associe ea ON t.id_tickets = ea.id_tickets
    LEFT JOIN status s ON ea.id_status = s.id_status
    LEFT JOIN Utilisateurs u ON t.login = u.login
    WHERE t.id_tickets = ?
");
$stmt->execute([$id_ticket]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header("Location: index.php");
    exit;
}

// Liste des statuts possibles
$statuts = $pdo->query("SELECT * FROM status ORDER BY id_status")->fetchAll();

require '../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <?php if(isset($_GET['from_admin'])): ?>
                    <a href="../admin/tickets.php" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-orange-600">
                <?php else: ?>
                    <a href="index.php" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-orange-600">
                <?php endif; ?>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    File d'attente
                </a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="ml-1 text-sm font-medium text-slate-700 md:ml-2">Ticket #<?php echo $id_ticket; ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 mb-6 rounded-lg shadow-sm">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informations Client & Ticket (Gauche) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Détails du problème
                    </h2>
                    <span class="text-sm text-slate-500">Ouvert le <?php echo date('d/m/Y H:i', strtotime($ticket['date_creation'])); ?></span>
                </div>
                <div class="p-6">
                    <div class="mb-6">
                        <span class="font-semibold text-slate-900 block mb-2">Description originale</span>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 text-slate-700 text-sm whitespace-pre-wrap">
<?php echo htmlspecialchars($ticket['description']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulaire de traitement -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Mise à jour et notes du technicien
                    </h2>
                </div>
                <div class="p-6">
                    <form action="" method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update">
                        <?php if(isset($_GET['from_admin'])): ?>
                            <input type="hidden" name="from_admin" value="1">
                        <?php endif; ?>
                        
                        <div>
                            <label for="id_status" class="block text-sm font-semibold text-slate-700 mb-2">Statut du ticket</label>
                            <div class="relative max-w-xs">
                                <select id="id_status" name="id_status" required
                                        class="w-full appearance-none px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white shadow-sm text-slate-700 font-medium">
                                    <?php foreach ($statuts as $s): ?>
                                        <option value="<?php echo $s['id_status']; ?>" <?php echo ($ticket['id_status'] == $s['id_status']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['libelle']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-semibold text-slate-700 mb-2">Notes et suivi</label>
                            <textarea id="notes" name="notes" rows="6" 
                                      class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700 mt-1"
                                      placeholder="Exemples de manipulations, requêtes effectuées, motifs de refus..."><?php echo htmlspecialchars($ticket['notes'] ?? ''); ?></textarea>
                            <p class="text-xs text-slate-500 mt-2">Ces notes seront partiellement visibles par le client ou l'admin selon votre workflow.</p>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <button type="submit" class="bg-slate-900 text-white px-6 py-2.5 rounded-lg font-medium shadow hover:bg-slate-800 transition-colors focus:ring-4 focus:ring-slate-200">
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Informations -->
        <div class="space-y-6">
            <!-- Client Info Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Demandeur</h3>
                </div>
                <div class="p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full border border-slate-200 flex items-center justify-center text-lg font-bold text-slate-500 bg-slate-100 mr-4">
                            <?php echo strtoupper(substr($ticket['client_nom'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($ticket['client_nom']); ?></p>
                            <p class="text-sm text-slate-500">Compte: @<?php echo htmlspecialchars($ticket['client_login']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Meta Data Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Méta-données</h3>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Catégorie</p>
                        <p class="font-medium text-slate-900 mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                                <?php echo htmlspecialchars($ticket['categorie']); ?>
                            </span>
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Dernier Suivi</p>
                        <p class="font-medium text-slate-900 mt-1 text-sm">
                            <?php echo $ticket['date_suivi'] ? date('d/m/Y à H:i', strtotime($ticket['date_suivi'])) : 'Aucun suivi'; ?>
                        </p>
                    </div>

                    <?php if ($ticket['date_reso']): ?>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Date Résolution</p>
                        <p class="font-medium text-emerald-600 mt-1 text-sm flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <?php echo date('d/m/Y à H:i', strtotime($ticket['date_reso'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
