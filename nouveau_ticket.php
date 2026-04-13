<?php
session_start();
if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] != 1) {
    header("Location: ../index.php");
    exit;
}
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = trim($_POST['description']);
    $id_categories = $_POST['id_categories'];
    
    if (!empty($description) && !empty($id_categories)) {
        try {
            // Check si l'association de statut existe
            $pdo->beginTransaction();
            
            // 1. Créer le ticket
            $stmt = $pdo->prepare("INSERT INTO Tickets (description, id_categories, login) VALUES (?, ?, ?)");
            $stmt->execute([$description, $id_categories, $_SESSION['login']]);
            $id_ticket = $pdo->lastInsertId();
            
            // 2. Assigner le statut "Nouveau" (id_status = 1)
            $stmt2 = $pdo->prepare("INSERT INTO est_associe (id_tickets, id_status) VALUES (?, 1)");
            $stmt2->execute([$id_ticket]);
            
            $pdo->commit();
            header("Location: index.php?success=1");
            exit;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la création du ticket : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir complétement le formulaire.";
    }
}

// Récupérer les catégories pour le formulaire
$stmt = $pdo->query("SELECT * FROM Categories");
$categories = $stmt->fetchAll();

require '../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <!-- Breadcrumb & Retour -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="index.php" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-orange-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Tableau de Bord
                </a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="ml-1 text-sm font-medium text-slate-700 md:ml-2">Nouveau Ticket</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 md:p-8 border-b border-slate-100 bg-slate-50/50">
            <h1 class="text-2xl font-bold text-slate-900">Signaler un nouveau problème</h1>
            <p class="text-slate-500 mt-1">Détaillez votre demande pour permettre à un technicien de vous aider efficacement.</p>
        </div>

        <div class="p-6 md:p-8">
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-200 p-4 mb-6 rounded-lg flex items-start">
                    <svg class="w-5 h-5 text-red-500 mr-3 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                <div>
                    <label for="id_categories" class="block text-sm font-semibold text-slate-700 mb-2">Catégorie du problème</label>
                    <div class="relative">
                        <select id="id_categories" name="id_categories" required
                                class="w-full appearance-none px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white shadow-sm text-slate-700 transition-shadow">
                            <option value="">-- Sélectionnez la catégorie correspondante --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id_categories']; ?>"><?php echo htmlspecialchars($cat['libelle']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-slate-700 mb-2">Description détaillée</label>
                    <textarea id="description" name="description" rows="6" required
                              class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700 transition-shadow resize-y"
                              placeholder="Veuillez décrire votre problème en incluant toutes les informations utiles (messages d'erreur, actions effectuées, etc.)..."></textarea>
                    <p class="mt-2 text-xs text-slate-500">Votre description aidera nos techniciens à résoudre votre problème plus rapidement.</p>
                </div>

                <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-4">
                    <a href="index.php" class="text-slate-500 hover:text-slate-800 font-medium text-sm transition-colors py-2 px-4">
                        Annuler
                    </a>
                    <button type="submit" class="bg-slate-900 text-white px-6 py-2.5 rounded-lg font-medium shadow hover:bg-slate-800 transition-colors focus:ring-4 focus:ring-slate-200 focus:outline-none flex items-center">
                        Soumettre le ticket
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
