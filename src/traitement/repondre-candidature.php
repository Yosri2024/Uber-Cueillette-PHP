<?php
/**
 * traitement/repondre-candidature.php
 * Traite l'acceptation ou le refus d'une candidature
 */

// ============================================================================
// 1. DÉMARRER LA SESSION ET VÉRIFIER LA CONNEXION
// ============================================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agriculteur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// ============================================================================
// 2. CONNEXION À LA BASE DE DONNÉES
// ============================================================================

require_once('../config/database.php');

// ============================================================================
// 3. RÉCUPÉRER LES DONNÉES (uniquement POST)
// ============================================================================

$id_candidature = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($id_candidature <= 0 || !in_array($action, ['accepter', 'refuser'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit();
}

// ============================================================================
// 4. DÉTERMINER LA NOUVELLE DÉCISION
// ============================================================================

$decision = ($action == 'accepter') ? 'acceptee' : 'refusee';

// ============================================================================
// 5. VÉRIFIER QUE LA CANDIDATURE APPARTIENT BIEN À L'AGRICULTEUR
// ============================================================================

$sql = "SELECT c.*, o.id_agriculteur, o.nombre_ouvriers,
        (SELECT COUNT(*) FROM uber_cueillette_candidature 
                WHERE id_offre = o.id_offre AND decision = 'acceptee') as nb_acceptes
        FROM uber_cueillette_candidature c
        JOIN uber_cueillette_offre o ON c.id_offre = o.id_offre
        WHERE c.id_candidature = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_candidature]);
$candidature = $stmt->fetch();

if (!$candidature) {
    echo json_encode(['success' => false, 'message' => 'Candidature introuvable']);
    exit();
}

if ($candidature['id_agriculteur'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// ============================================================================
// 6. VÉRIFIER LES PLACES DISPONIBLES (SI ON ACCEPTE)
// ============================================================================

if ($action == 'accepter' && $candidature['nb_acceptes'] >= $candidature['nombre_ouvriers']) {
    echo json_encode(['success' => false, 'message' => 'Offre complète, plus de places disponibles']);
    exit();
}

// ============================================================================
// 7. METTRE À JOUR LA BASE DE DONNÉES
// ============================================================================

try {
    $sql = "UPDATE uber_cueillette_candidature SET decision = ? WHERE id_candidature = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$decision, $id_candidature]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>