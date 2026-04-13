<?php
/**
 * Configuration de la connexion à la base de données avec PDO
 */

$host = '127.0.0.1'; // ou localhost
$db   = 'helpdesk';
// MAMP utilise souvent 'root' et 'root' par défaut sur Mac/Windows, sinon essayez 'root' et ''
$user = 'root';
$pass = 'root'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En production, ne jamais afficher l'erreur brute.
    die("Erreur de connexion à la base de données : " . $e->getMessage() . "<br>Vérifiez les identifiants dans config.php (MAMP: root/root souvent).");
}
?>
