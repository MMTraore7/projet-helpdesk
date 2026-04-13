<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: /helpdesk/index.php");
    exit;
}
require 'config.php';
require 'includes/logger.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        try {
            $stmt = $pdo->prepare("SELECT password FROM Utilisateurs WHERE login = ?");
            $stmt->execute([$_SESSION['login']]);
            $user = $stmt->fetch();

            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 6) {
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $update = $pdo->prepare("UPDATE Utilisateurs SET password = ? WHERE login = ?");
                        $update->execute([$new_hash, $_SESSION['login']]);
                        $success = "Votre mot de passe a été modifié avec succès.";
                        logAction($pdo, $_SESSION['login'], "Modification du mot de passe réussie", "INFO");
                    } else {
                        $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
                    }
                } else {
                    $error = "Les nouveaux mots de passe ne correspondent pas.";
                }
            } else {
                $error = "Le mot de passe actuel est incorrect.";
                logAction($pdo, $_SESSION['login'], "Échec de modification du mot de passe (mot de passe actuel incorrect)", "WARNING");
            }
        } catch (\PDOException $e) {
            $error = "Une erreur est survenue lors de la modification.";
        }
    } elseif ($_POST['action'] == 'change_login') {
        $new_login = trim($_POST['new_login']);
        $current_password = $_POST['current_password_login'];

        try {
            $stmt = $pdo->prepare("SELECT password FROM Utilisateurs WHERE login = ?");
            $stmt->execute([$_SESSION['login']]);
            $user = $stmt->fetch();

            if (password_verify($current_password, $user['password'])) {
                if (strlen($new_login) >= 3 && strlen($new_login) <= 20) {
                    if ($new_login !== $_SESSION['login']) {
                        $check = $pdo->prepare("SELECT login FROM Utilisateurs WHERE login = ?");
                        $check->execute([$new_login]);
                        if ($check->rowCount() == 0) {
                            $pdo->beginTransaction();
                            $pdo->exec("SET foreign_key_checks = 0");
                            
                            $updateUsers = $pdo->prepare("UPDATE Utilisateurs SET login = ? WHERE login = ?");
                            $updateUsers->execute([$new_login, $_SESSION['login']]);
                            
                            $updateTickets = $pdo->prepare("UPDATE Tickets SET login = ? WHERE login = ?");
                            $updateTickets->execute([$new_login, $_SESSION['login']]);
                            
                            $updateLogs = $pdo->prepare("UPDATE Logs SET login = ? WHERE login = ?");
                            $updateLogs->execute([$new_login, $_SESSION['login']]);
                            
                            $pdo->exec("SET foreign_key_checks = 1");
                            $pdo->commit();
                            
                            $old_login = $_SESSION['login'];
                            $_SESSION['login'] = $new_login;
                            $success = "Votre identifiant a été modifié avec succès.";
                            logAction($pdo, $_SESSION['login'], "Modification du login réussie (ancien: $old_login)", "INFO");
                        } else {
                            $error = "Cet identifiant est déjà utilisé.";
                        }
                    } else {
                        $error = "Le nouvel identifiant est identique à l'actuel.";
                    }
                } else {
                    $error = "L'identifiant doit contenir entre 3 et 20 caractères.";
                }
            } else {
                $error = "Le mot de passe est incorrect.";
            }
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                $pdo->exec("SET foreign_key_checks = 1");
            }
            $error = "Une erreur est survenue lors de la modification de l'identifiant.";
        }
    }
}

// Pour le backlink, selon le rôle
$back_link = '/helpdesk/index.php';
if ($_SESSION['id_role'] == 1) $back_link = '/helpdesk/client/index.php';
elseif ($_SESSION['id_role'] == 2) $back_link = '/helpdesk/technician/index.php';
elseif ($_SESSION['id_role'] == 3) $back_link = '/helpdesk/admin/index.php';

// Astuce : inclure le header alors qu'on est à la racine
// On ne met pas require 'includes/header.php' car il s'attend à être dans un sous-dossier pour ses paths relatifs optionnels (s'il y en a) mais on a tout passé en absolu (/helpdesk/) !
require 'includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-8 pl-4 pr-4">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="<?php echo $back_link; ?>" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-orange-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Tableau de Bord
                </a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="ml-1 text-sm font-medium text-slate-700 md:ml-2">Mon Profil</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mt-4">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center">
            <svg class="w-6 h-6 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            <h1 class="text-xl font-bold text-slate-900">Paramètres de mon compte</h1>
        </div>
        
        <div class="p-6 md:p-8 space-y-10">
            
            <?php if ($success): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <div>
                <h2 class="text-lg font-semibold text-slate-800 mb-6 border-b pb-2">Modifier mon identifiant</h2>
                <form action="profil.php" method="POST" class="space-y-5 max-w-lg">
                    <input type="hidden" name="action" value="change_login">
                    
                    <div>
                        <label for="new_login" class="block text-sm font-semibold text-slate-700 mb-1">Nouvel identifiant</label>
                        <input type="text" id="new_login" name="new_login" required minlength="3" maxlength="20"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700"
                               placeholder="Nouveau login" value="<?php echo htmlspecialchars($_SESSION['login']); ?>">
                    </div>
                    
                    <div>
                        <label for="current_password_login" class="block text-sm font-semibold text-slate-700 mb-1">Mot de passe pour confirmer</label>
                        <input type="password" id="current_password_login" name="current_password_login" required
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700"
                               placeholder="••••••••">
                    </div>
                    
                    <div class="pt-2">
                        <button type="submit" class="bg-orange-500 text-white px-5 py-2.5 rounded-lg font-medium shadow hover:bg-orange-600 transition-colors focus:ring-4 focus:ring-orange-200">
                            Changer l'identifiant
                        </button>
                    </div>
                </form>
            </div>

            <div>
                <h2 class="text-lg font-semibold text-slate-800 mb-6 border-b pb-2">Modifier mon mot de passe</h2>
                <form action="profil.php" method="POST" class="space-y-5 max-w-lg">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-slate-700 mb-1">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" required
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700"
                               placeholder="••••••••">
                    </div>
                    
                    <div class="pt-2">
                        <label for="new_password" class="block text-sm font-semibold text-slate-700 mb-1">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700"
                               placeholder="•••••••• (min 6 caractères)">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-semibold text-slate-700 mb-1">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm text-slate-700"
                               placeholder="••••••••">
                    </div>
                    
                    <div class="pt-4 flex items-center space-x-3">
                        <button type="submit" class="bg-orange-500 text-white px-5 py-2.5 rounded-lg font-medium shadow hover:bg-orange-600 transition-colors focus:ring-4 focus:ring-orange-200">
                            Modifier le mot de passe
                        </button>
                        <a href="<?php echo $back_link; ?>" class="text-slate-500 hover:text-slate-800 font-medium px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors">
                            Retour
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
