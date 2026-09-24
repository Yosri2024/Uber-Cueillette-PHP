<?php
/**
 * config/database.php
 * Configuration de la connexion à la base de données
 */

$host = 'localhost';
$dbname = 'uber_cueillette';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>