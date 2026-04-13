<?php
session_start();
require 'config.php';
require 'includes/logger.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    if (empty($login) || empty($password)) {
        header("Location: index.php?error=Veuillez remplir tous les champs.");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM Utilisateurs WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user) {
            if (!$user['actif']) {
                header("Location: index.php?error=Votre compte est inactif.");
                exit;
            }

            // Vérification du lockout
            if ($user['lockout_time'] && strtotime($user['lockout_time']) > time()) {
                header("Location: index.php?error=Trop de tentatives échouées. Vous êtes banni pour 1 heure.");
                exit;
            }

            // Vérification du mot de passe
            if (password_verify($password, $user['password'])) {
                // Succès : on réinitialise les compteurs si nécessaire
                if ($user['failed_login_attempts'] > 0 || $user['lockout_time'] !== null) {
                    $stmtReset = $pdo->prepare("UPDATE Utilisateurs SET failed_login_attempts = 0, lockout_time = NULL WHERE login = ?");
                    $stmtReset->execute([$user['login']]);
                }

                // création de la session
                $_SESSION['login'] = $user['login'];
                $_SESSION['id_role'] = $user['id_role'];
                $_SESSION['nom'] = $user['nom'];

                logAction($pdo, $user['login'], "Connexion réussie");

                // Redirection selon le rôle
                // 1 = Client, 2 = Technicien, 3 = Admin
                if ($user['id_role'] == 1) {
                    header("Location: client/index.php");
                } elseif ($user['id_role'] == 2) {
                    header("Location: technician/index.php");
                } elseif ($user['id_role'] == 3) {
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php?error=Rôle inconnu.");
                }
                exit;
            } else {
                // Mot de passe incorrect
                $attempts = $user['failed_login_attempts'] + 1;
                if ($attempts >= 3) {
                    $stmtLock = $pdo->prepare("UPDATE Utilisateurs SET failed_login_attempts = ?, lockout_time = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE login = ?");
                    $stmtLock->execute([$attempts, $login]);
                    logAction($pdo, $login, "Compte bloqué (trop de tentatives échouées)", "WARNING");
                    header("Location: index.php?error=Trop de tentatives échouées. Vous êtes banni pour 1 heure.");
                } else {
                    $stmtFail = $pdo->prepare("UPDATE Utilisateurs SET failed_login_attempts = ? WHERE login = ?");
                    $stmtFail->execute([$attempts, $login]);
                    logAction($pdo, $login, "Échec de connexion (mot de passe invalide)", "WARNING");
                    header("Location: index.php?error=Identifiant ou mot de passe incorrect.");
                }
                exit;
            }
        } else {
            logAction($pdo, $login, "Échec de connexion (utilisateur introuvable)", "WARNING");
            header("Location: index.php?error=Identifiant ou mot de passe incorrect.");
            exit;
        }

    } catch (\PDOException $e) {
        header("Location: index.php?error=Erreur de base de données.");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>
