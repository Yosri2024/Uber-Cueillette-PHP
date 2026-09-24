<?php
/**
 * traitement/supprimer-offre.php
 * Supprime une offre si elle n'a pas de candidatures
 */

// ============================================================================
// PARTIE 1: DÉMARRAGE DE LA SESSION ET VÉRIFICATION
// ============================================================================

session_start();

// Vérifier si l'utilisateur est connecté et est agriculteur
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agriculteur') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé. Veuillez vous connecter.']);
    exit();
}

// ============================================================================
// PARTIE 2: CONNEXION À LA BASE DE DONNÉES
// ============================================================================

require_once('../config/database.php');

$id_agriculteur = $_SESSION['user_id'];

// ============================================================================
// PARTIE 3: RÉCUPÉRER L'ID DE L'OFFRE
// ============================================================================

$id_offre = $_GET['id'] ?? 0;

if (empty($id_offre)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID offre manquant']);
    exit();
}

// ============================================================================
// PARTIE 4: VÉRIFIER QUE L'OFFRE APPARTIENT À L'AGRICULTEUR
// ============================================================================

$sql = "SELECT id_offre, id_agriculteur FROM uber_cueillette_offre WHERE id_offre = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_offre]);
$offre = $stmt->fetch();

if (!$offre) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Offre introuvable']);
    exit();
}

if ($offre['id_agriculteur'] != $id_agriculteur) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer cette offre']);
    exit();
}

// ============================================================================
// PARTIE 5: VÉRIFIER QU'IL N'Y A PAS DE CANDIDATURES
// ============================================================================

$sql = "SELECT COUNT(*) as total FROM uber_cueillette_candidature WHERE id_offre = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_offre]);
$resultat = $stmt->fetch();
$nb_candidatures = $resultat['total'] ?? 0;

if ($nb_candidatures > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Impossible de supprimer : cette offre a ' . $nb_candidatures . ' candidature(s)']);
    exit();
}

// ============================================================================
// PARTIE 6: SUPPRIMER L'OFFRE
// ============================================================================

try {
    $sql = "DELETE FROM uber_cueillette_offre WHERE id_offre = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_offre]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Offre supprimée avec succès']);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
}
?>