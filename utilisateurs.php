<?php
session_start();
if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] != 3) {
    header("Location: ../index.php");
    exit;
}
require '../config.php';
require '../includes/logger.php';

// Variables de message
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // Bascule Actif/Inactif
    if ($_POST['action'] == 'toggle') {
        $login_cible = $_POST['login_cible'];
        // Protection pour ne pas se désactiver soi-même
        if ($login_cible !== $_SESSION['login']) {
            try {
                $stmt = $pdo->prepare("UPDATE Utilisateurs SET actif = NOT actif WHERE login = ?");
                $stmt->execute([$login_cible]);
                $success = "Statut de l'utilisateur modifié avec succès.";
                logAction($pdo, $_SESSION['login'], "Modification du statut de l'utilisateur : $login_cible");
            } catch (\PDOException $e) {
                $error = "Erreur lors de la modification du statut.";
            }
        } else {
            $error = "Vous ne pouvez pas désactiver votre propre compte.";
        }
    }
    
    // Création d'un nouvel utilisateur
    if ($_POST['action'] == 'create') {
        $login = trim($_POST['login']);
        $nom = trim($_POST['nom']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $id_role = (int)$_POST['id_role'];
        
        try {
            // Vérifier si le login existe déjà
            $check = $pdo->prepare("SELECT COUNT(*) FROM Utilisateurs WHERE login = ?");
            $check->execute([$login]);
            if ($check->fetchColumn() > 0) {
                $error = "Cet identifiant est déjà utilisé. Veuillez en choisir un autre.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO Utilisateurs (login, nom, password, actif, id_role) VALUES (?, ?, ?, TRUE, ?)");
                $stmt->execute([$login, $nom, $password, $id_role]);
                $success = "Nouvel utilisateur créé avec succès.";
                logAction($pdo, $_SESSION['login'], "Création d'un nouvel utilisateur : $login");
            }
        } catch (\PDOException $e) {
            $error = "Erreur lors de la création de l'utilisateur : " . $e->getMessage();
        }
    }
    
    // Suppression d'un utilisateur
    if ($_POST['action'] == 'delete') {
        $login_cible = $_POST['login_cible'];
        if ($login_cible !== $_SESSION['login']) {
            try {
                $stmt = $pdo->prepare("DELETE FROM Utilisateurs WHERE login = ?");
                $stmt->execute([$login_cible]);
                $success = "Utilisateur supprimé définitivement avec succès.";
                logAction($pdo, $_SESSION['login'], "Suppression de l'utilisateur : $login_cible", "WARNING");
            } catch (\PDOException $e) {
                $error = "Erreur lors de la suppression de l'utilisateur.";
            }
        } else {
            $error = "Vous ne pouvez pas supprimer votre propre compte.";
        }
    }
    
    // Débannissement d'un utilisateur
    if ($_POST['action'] == 'unban') {
        $login_cible = $_POST['login_cible'];
        try {
            $stmt = $pdo->prepare("UPDATE Utilisateurs SET failed_login_attempts = 0, lockout_time = NULL WHERE login = ?");
            $stmt->execute([$login_cible]);
            $success = "Utilisateur débanni avec succès.";
            logAction($pdo, $_SESSION['login'], "Débannissement de l'utilisateur : $login_cible");
        } catch (\PDOException $e) {
            $error = "Erreur lors du débannissement de l'utilisateur.";
        }
    }
}

// Récupérer la liste des utilisateurs
$utilisateurs = $pdo->query("
    SELECT u.*, r.nom as role_nom 
    FROM Utilisateurs u 
    LEFT JOIN Role r ON u.id_role = r.id_role
    ORDER BY r.id_role DESC, u.nom ASC
")->fetchAll();

$roles = $pdo->query("SELECT * FROM Role ORDER BY id_role")->fetchAll();

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
                    <span class="ml-1 text-sm font-medium text-slate-700 md:ml-2">Gestion des Utilisateurs</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-slate-900">Utilisateurs Enregistrés</h1>
    </div>

    <?php if ($success): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Liste des Utilisateurs (Prend 2 colonnes sur grand écran) -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                    <h2 class="font-semibold text-slate-800">Comptes actifs et inactifs</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Identifiant & Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Rôle</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <?php foreach ($utilisateurs as $u): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 mr-3 <?php echo $u['actif'] ? 'bg-slate-100 text-slate-600' : 'bg-red-50 text-red-400'; ?>">
                                                <?php echo strtoupper(substr($u['nom'], 0, 1) ?: substr($u['login'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="font-medium text-slate-900 <?php echo !$u['actif'] ? 'text-slate-400 line-through' : ''; ?>"><?php echo htmlspecialchars($u['nom']); ?></div>
                                                <div class="text-xs text-slate-500">@<?php echo htmlspecialchars($u['login']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if($u['id_role'] == 1): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Client</span>
                                        <?php elseif($u['id_role'] == 2): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Tech</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <?php if ($u['lockout_time'] && strtotime($u['lockout_time']) > time()): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-200" title="Banni jusqu'à <?php echo date('H:i', strtotime($u['lockout_time'])); ?>">
                                                <span class="w-1.5 h-1.5 bg-orange-500 rounded-full mr-1.5"></span> Banni
                                            </span>
                                        <?php elseif ($u['actif']): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5"></span> Actif
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span> Inactif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <?php if ($u['login'] !== $_SESSION['login']): ?>
                                            <div class="flex items-center justify-end space-x-2">
                                                <?php if ($u['lockout_time'] && strtotime($u['lockout_time']) > time()): ?>
                                                    <form action="" method="POST" class="inline-block">
                                                        <input type="hidden" name="action" value="unban">
                                                        <input type="hidden" name="login_cible" value="<?php echo htmlspecialchars($u['login']); ?>">
                                                        <button type="submit" title="Débannir l'utilisateur" class="text-orange-500 hover:text-white bg-orange-50 hover:bg-orange-500 px-3 py-1.5 rounded-lg transition-colors font-medium">
                                                            Débannir
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form action="" method="POST" class="inline-block">
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="login_cible" value="<?php echo htmlspecialchars($u['login']); ?>">
                                                    <button type="submit" class="text-slate-500 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg transition-colors font-medium">
                                                        <?php echo $u['actif'] ? 'Désactiver' : 'Activer'; ?>
                                                    </button>
                                                </form>
                                                <form action="" method="POST" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cet utilisateur ? Ses tickets seront conservés (sans affectation).');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="login_cible" value="<?php echo htmlspecialchars($u['login']); ?>">
                                                    <button type="submit" title="Supprimer l'utilisateur" class="text-red-500 hover:text-white bg-red-50 hover:bg-red-500 px-3 py-1.5 rounded-lg transition-colors font-medium">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic text-xs">C'est vous</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Formulaire de Création (Sidebar Droite) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-24">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="font-semibold text-slate-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        Nouvel Utilisateur
                    </h2>
                </div>
                <div class="p-6">
                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="create">
                        
                        <div>
                            <label for="login" class="block text-sm font-semibold text-slate-700 mb-1">Identifiant (Login)</label>
                            <input type="text" id="login" name="login" required maxlength="20"
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700"
                                   placeholder="Ex: jdupont">
                        </div>
                        
                        <div>
                            <label for="nom" class="block text-sm font-semibold text-slate-700 mb-1">Nom Complet</label>
                            <input type="text" id="nom" name="nom" required maxlength="50"
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700"
                                   placeholder="Ex: Jean Dupont">
                        </div>
                        
                        <div>
                            <label for="id_role" class="block text-sm font-semibold text-slate-700 mb-1">Rôle</label>
                            <div class="relative">
                                <select id="id_role" name="id_role" required
                                        class="w-full appearance-none px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700 font-medium">
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?php echo $r['id_role']; ?>"><?php echo htmlspecialchars($r['nom']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700 mb-1">Mot de passe provisoire</label>
                            <input type="password" id="password" name="password" required minlength="6"
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700"
                                   placeholder="••••••••">
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="w-full bg-slate-900 text-white px-4 py-2.5 rounded-lg font-medium shadow hover:bg-slate-800 transition-colors focus:ring-4 focus:ring-slate-200">
                                Créer l'utilisateur
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
