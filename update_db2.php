<?php
require 'config.php';

try {
    $pdo->exec("ALTER TABLE Utilisateurs ADD COLUMN failed_login_attempts INT DEFAULT 0");
} catch (\PDOException $e) {}

try {
    $pdo->exec("ALTER TABLE Utilisateurs ADD COLUMN lockout_time DATETIME NULL");
} catch (\PDOException $e) {}

echo "Columns added successfully!\n";
?>
