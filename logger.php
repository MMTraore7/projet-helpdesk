<?php
/**
 * logger.php - Système de journalisation
 */

function logAction($pdo, $login, $action, $type = 'INFO') {
    try {
        // Enregistrer l'IP de l'utilisateur
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO Logs (login, action, ip_address, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$login, $action, $ip_address, $type]);
    } catch (\PDOException $e) {
        // Ignorer silencieusement pour ne pas bloquer l'application en cas de problème de log
        error_log("Erreur de journalisation : " . $e->getMessage());
    }
}
?>
