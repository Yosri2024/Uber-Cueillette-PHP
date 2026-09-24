<?php
/**
 * traitement/offre.php
 * Traite l'ajout d'une nouvelle offre par un agriculteur
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agriculteur') {
    $_SESSION['erreurs'] = ["Vous devez être connecté en tant qu'agriculteur"];
    header('Location: ../racine/login.php');
    exit();
}

require_once('../config/database.php');

$id_agriculteur = $_SESSION['user_id'];

// Récupération des données
$type_fruit = $_POST['type_fruit'] ?? '';
$gouvernorat = $_POST['gouvernorat'] ?? '';
$adresse = trim($_POST['adresse'] ?? '');
$date_debut = $_POST['date_debut'] ?? '';
$date_fin = $_POST['date_fin'] ?? '';
$date_limite = $_POST['date_limite'] ?? '';
$nb_ouvriers = $_POST['nb_ouvriers'] ?? '';
$prix = $_POST['prix'] ?? '';
$description = trim($_POST['description'] ?? '');
$conditions = isset($_POST['conditions']);

$erreurs = [];

// Validations
if (empty($type_fruit)) $erreurs[] = "Le type de fruit est requis";
if (empty($gouvernorat)) $erreurs[] = "Le gouvernorat est requis";
if (empty($adresse)) $erreurs[] = "L'adresse est requise";
if (strlen($adresse) < 5) $erreurs[] = "L'adresse doit contenir au moins 5 caractères";
if (empty($date_debut)) $erreurs[] = "La date de début est requise";
if (empty($date_fin)) $erreurs[] = "La date de fin est requise";
if (empty($date_limite)) $erreurs[] = "La date limite est requise";

if (!empty($date_debut) && !empty($date_fin) && !empty($date_limite)) {
    $debut = new DateTime($date_debut);
    $fin = new DateTime($date_fin);
    $limite = new DateTime($date_limite);
    $aujourdhui = new DateTime();
    $aujourdhui->setTime(0, 0, 0);
    
    if ($limite < $aujourdhui) $erreurs[] = "La date limite doit être dans le futur";
    if ($fin <= $debut) $erreurs[] = "La date de fin doit être après la date de début";
}

if (empty($nb_ouvriers) || $nb_ouvriers < 1 || $nb_ouvriers > 50) $erreurs[] = "Le nombre d'ouvriers doit être entre 1 et 50";
if (empty($prix) || $prix < 10 || $prix > 500) $erreurs[] = "Le prix doit être entre 10 et 500 DT";
if (!$conditions) $erreurs[] = "Vous devez accepter les conditions générales";

if (!empty($erreurs)) {
    $_SESSION['erreurs'] = $erreurs;
    $_SESSION['old'] = $_POST;
    header('Location: ../agriculteur/ajouter-offre.php');
    exit();
}

try {
    $sql = "INSERT INTO uber_cueillette_offre 
            (id_agriculteur, id_type_fruit, id_gouvernorat, adresse, date_debut, date_fin, date_limite, nombre_ouvriers, prix_journee, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $id_agriculteur,
        $type_fruit,
        $gouvernorat,
        $adresse,
        $date_debut,
        $date_fin,
        $date_limite,
        $nb_ouvriers,
        $prix,
        $description   // ✅ CORRECTION : ajout de la description
    ]);
    
    $_SESSION['success'] = "✅ Offre ajoutée avec succès !";
    header('Location: ../agriculteur/mes-offres.php');
    exit();
    
} catch (PDOException $e) {
    $_SESSION['erreurs'] = ["Erreur lors de l'ajout : " . $e->getMessage()];
    $_SESSION['old'] = $_POST;
    header('Location: ../agriculteur/ajouter-offre.php');
    exit();
}
?>