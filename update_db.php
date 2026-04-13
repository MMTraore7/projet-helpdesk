<?php
require 'config.php';

try {
    $pdo->exec("ALTER TABLE Utilisateurs ADD COLUMN failed_login_attempts INT DEFAULT 0");
    echo "Columns failed_login_attempts added successfully!\n";
} catch (\PDOException $e) {}

try {
    $pdo->exec("ALTER TABLE Utilisateurs ADD COLUMN lockout_time DATETIME NULL");
    echo "Columns lockout_time added successfully!\n";
} catch (\PDOException $e) {}

try {
    $pdo->exec("DROP TABLE IF EXISTS Bannissements_IP");
    echo "Table Bannissements_IP supprimée.\n";
} catch (\PDOException $e) {}

echo "Mise à jour de la base de données terminée !\n";
?>
