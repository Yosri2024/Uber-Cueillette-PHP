<?php
/**
 * profil-ouvrier.php - Page de profil d'un ouvrier
 * Affiche les informations détaillées d'un ouvrier pour l'agriculteur
 */

// ============================================================================
// PARTIE 1: DÉMARRAGE DE LA SESSION ET VÉRIFICATION
// ============================================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agriculteur') {
    header('Location: ../racine/login.php');
    exit();
}

// ============================================================================
// PARTIE 2: CONNEXION À LA BASE DE DONNÉES
// ============================================================================

require_once('../config/database.php');

$id_agriculteur = $_SESSION['user_id'];
$id_ouvrier = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_ouvrier <= 0) {
    header('Location: mes-offres.php');
    exit();
}

// ============================================================================
// PARTIE 3: RÉCUPÉRER LES INFORMATIONS DE L'OUVRIER
// ============================================================================

$sql = "SELECT * FROM uber_cueillette_ouvrier WHERE id_ouvrier = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$ouvrier = $stmt->fetch();

if (!$ouvrier) {
    header('Location: mes-offres.php');
    exit();
}

// ============================================================================
// PARTIE 4: RÉCUPÉRER LES STATISTIQUES DE L'OUVRIER
// ============================================================================

// Note moyenne
$sql = "SELECT AVG(note) as moyenne, COUNT(*) as nb_notes
        FROM uber_cueillette_candidature 
        WHERE id_ouvrier = ? AND note IS NOT NULL";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$stats = $stmt->fetch();

$moyenne = $stats['moyenne'] ? number_format($stats['moyenne'], 1) : 'N/A';
$nb_notes = $stats['nb_notes'] ?? 0;

// Nombre de chantiers effectués
$sql = "SELECT COUNT(*) as total
        FROM uber_cueillette_candidature 
        WHERE id_ouvrier = ? AND decision = 'acceptee'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$nb_chantiers = $stmt->fetch()['total'] ?? 0;

// Derniers commentaires reçus
$sql = "SELECT c.*, o.adresse, tf.libelle as type_fruit, 
            o.date_debut, o.date_fin, o.prix_journee
        FROM uber_cueillette_candidature c
        JOIN uber_cueillette_offre o ON c.id_offre = o.id_offre
        JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
        WHERE c.id_ouvrier = ? AND c.commentaire IS NOT NULL
        ORDER BY c.date_candidature DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$commentaires = $stmt->fetchAll();

// Historique des chantiers
$sql = "SELECT o.*, tf.libelle as type_fruit, g.libelle as gouvernorat,
            c.note, c.commentaire, c.remuneration, c.decision
        FROM uber_cueillette_candidature c
        JOIN uber_cueillette_offre o ON c.id_offre = o.id_offre
        JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
        JOIN uber_cueillette_gouvernorat g ON o.id_gouvernorat = g.id_gouvernorat
        WHERE c.id_ouvrier = ? AND c.decision = 'acceptee'
        ORDER BY o.date_fin DESC
        LIMIT 3";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$chantiers = $stmt->fetchAll();

// ============================================================================
// PARTIE 5: PHOTO DE PROFIL (CORRECTION BLOB)
// ============================================================================

$photo_url = '../images/default-profile.jpg'; // Photo par défaut

if (!empty($ouvrier['photo'])) {
    // Vérifier si c'est un BLOB (données binaires)
    // Méthode: vérifier si la chaîne contient des caractères non imprimables
    $is_blob = false;
    $premier_caractere = substr($ouvrier['photo'], 0, 1);
    $premier_code = ord($premier_caractere);
    
    // Si le premier caractère n'est pas imprimable ou si la chaîne est très longue
    if ($premier_code < 32 || $premier_code > 126 || strlen($ouvrier['photo']) > 255) {
        $is_blob = true;
    }
    
    if ($is_blob) {
        // C'est un BLOB → encoder en base64 pour l'affichage
        $photo_data = base64_encode($ouvrier['photo']);
        $photo_url = 'data:image/jpeg;base64,' . $photo_data;
    } else {
        // C'est un chemin de fichier
        $photo_path = '../' . $ouvrier['photo'];
        if (file_exists($photo_path)) {
            $photo_url = $photo_path;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?php echo htmlspecialchars($ouvrier['prenom'] . ' ' . $ouvrier['nom']); ?> - Uber-Cueillette</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="../images/Uber-Cueillette-logo.png">
</head>
<body>

<!-- ============================================================================
PARTIE 6: BARRE DE NAVIGATION
============================================================================ -->

<nav class="navbar">
    <div class="container">
        <a href="../racine/index.html" class="logo">
            <img src="../images/Uber-Cueillette-logo.png" alt="Uber-Cueillette" class="logo-img">
            <span>Uber<span>Cueillette</span></span>
        </a>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="ajouter-offre.php"><i class="fas fa-plus-circle"></i> Ajouter offre</a></li>
            <li><a href="mes-offres.php"><i class="fas fa-list"></i> Mes offres</a></li>
            <li><a href="profil.php"><i class="fas fa-user"></i> Profil</a></li>
            <li class="nav-buttons">
                <a href="../racine/logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
                <button id="theme-toggle" class="theme-toggle">
                    <i class="fas fa-moon"></i>
                </button>
            </li>
        </ul>
    </div>
</nav>

<!-- ============================================================================
PARTIE 7: CONTENU PRINCIPAL
============================================================================ -->

<main>
    <div class="container">

        <!-- ====================================================================
        PARTIE 8: EN-TÊTE AVEC PHOTO ET NOM (PHOTO CORRIGÉE)
        ==================================================================== -->

        <div class="hero" style="padding: 30px 0; margin-bottom: 30px;">
            <div class="container">
                <div class="hero-content" style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">
                    
                    <!-- Photo de profil - CORRIGÉE -->
                    <div style="flex-shrink: 0;">
                        <img src="<?php echo $photo_url; ?>" alt="Photo de profil" 
                            style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color);"
                            onerror="this.src='../images/default-profile.jpg'">
                    </div>
                    
                    <!-- Informations principales -->
                    <div>
                        <h1 style="font-size: 2.5rem; margin-bottom: 10px;">
                            <?php echo htmlspecialchars($ouvrier['prenom'] . ' ' . $ouvrier['nom']); ?>
                        </h1>
                        <p style="font-size: 1.1rem; color: var(--text-light); margin-bottom: 15px;">
                            <i class="fas fa-id-card"></i> CIN: <?php echo htmlspecialchars($ouvrier['CIN']); ?> |
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($ouvrier['email']); ?>
                        </p>
                        
                        <!-- Badges statistiques -->
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <span class="badge badge-warning" style="font-size: 1rem; padding: 8px 15px;">
                                <i class="fas fa-star"></i> Note: <?php echo $moyenne; ?>/10 (<?php echo $nb_notes; ?> avis)
                            </span>
                            <span class="badge badge-info" style="font-size: 1rem; padding: 8px 15px;">
                                <i class="fas fa-briefcase"></i> <?php echo $nb_chantiers; ?> chantier(s) effectué(s)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====================================================================
        PARTIE 9: DESCRIPTION
        ==================================================================== -->

        <?php if (!empty($ouvrier['description'])): ?>
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-header">
                <i class="fas fa-file-alt"></i> À propos
            </div>
            <div class="card-body">
                <p style="font-size: 1.1rem; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($ouvrier['description'])); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- ====================================================================
        PARTIE 10: STATISTIQUES DÉTAILLÉES
        ==================================================================== -->

        <div class="stats-grid" style="margin-bottom: 30px;">
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo $moyenne; ?></span>
                    <span class="stat-label">Note moyenne</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo $nb_chantiers; ?></span>
                    <span class="stat-label">Chantiers effectués</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                    <i class="fas fa-comment"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo count($commentaires); ?></span>
                    <span class="stat-label">Commentaires reçus</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number">
                        <?php echo date('d/m/Y', strtotime($ouvrier['id_ouvrier'])); ?>
                    </span>
                    <span class="stat-label">Membre depuis</span>
                </div>
            </div>
            
        </div>

        <!-- ====================================================================
        PARTIE 11: DERNIERS COMMENTAIRES REÇUS
        ==================================================================== -->

        <h2 class="section-title">Derniers commentaires reçus</h2>

        <?php if (empty($commentaires)): ?>
        
        <div class="empty-state">
            <i class="fas fa-comments"></i>
            <h3>Aucun commentaire pour le moment</h3>
            <p>Cet ouvrier n'a pas encore reçu de commentaires</p>
        </div>
        
        <?php else: ?>
        
        <div class="commentaires-list" style="margin-bottom: 40px;">
            
            <?php foreach($commentaires as $c): ?>
            
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap;">
                        <div>
                            <strong style="font-size: 1.1rem;">
                                <i class="fas fa-apple-alt"></i> <?php echo htmlspecialchars($c['type_fruit']); ?>
                            </strong>
                            <span style="color: var(--text-light); margin-left: 10px;">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($c['adresse']); ?>
                            </span>
                        </div>
                        <span class="badge badge-warning">
                            <i class="fas fa-star"></i> Note: <?php echo $c['note']; ?>/10
                        </span>
                    </div>
                    
                    <div style="background: var(--light-color); padding: 20px; border-radius: var(--border-radius); margin-bottom: 15px;">
                        <i class="fas fa-quote-left" style="color: var(--primary-color); opacity: 0.5; font-size: 1.2rem; margin-right: 10px;"></i>
                        <?php echo htmlspecialchars($c['commentaire']); ?>
                    </div>
                    
                    <div style="display: flex; gap: 20px; color: var(--text-light); font-size: 0.9rem; flex-wrap: wrap;">
                        <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($c['date_candidature'])); ?></span>
                        <?php if (!empty($c['remuneration'])): ?>
                        <span><i class="fas fa-money-bill-wave"></i> Rémunération: <?php echo $c['remuneration']; ?> DT</span>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
            
            <?php endforeach; ?>
            
        </div>
        
        <?php endif; ?>

        <!-- ====================================================================
        PARTIE 12: HISTORIQUE DES CHANTIERS
        ==================================================================== -->

        <h2 class="section-title">Derniers chantiers effectués</h2>

        <?php if (empty($chantiers)): ?>
        
        <div class="empty-state">
            <i class="fas fa-briefcase"></i>
            <h3>Aucun chantier pour le moment</h3>
            <p>Cet ouvrier n'a pas encore participé à des chantiers</p>
        </div>
        
        <?php else: ?>
        
        <div class="table-container" style="margin-bottom: 40px;">
            <table>
                <thead>
                    <tr>
                        <th>Offre</th>
                        <th>Période</th>
                        <th>Note</th>
                        <th>Rémunération</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <?php foreach($chantiers as $ch): ?>
                    
                    <tr>
                        <td>
                            <i class="fas fa-apple-alt"></i> 
                            <?php echo htmlspecialchars($ch['type_fruit'] . ' - ' . $ch['gouvernorat']); ?>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($ch['date_debut'])); ?> - 
                            <?php echo date('d/m/Y', strtotime($ch['date_fin'])); ?>
                        </td>
                        <td>
                            <?php if ($ch['note']): ?>
                                <span class="badge badge-warning">
                                    <i class="fas fa-star"></i> <?php echo $ch['note']; ?>/10
                                </span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Non noté</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ch['remuneration']): ?>
                                <strong><?php echo $ch['remuneration']; ?> DT</strong>
                            <?php else: ?>
                                <span class="badge badge-secondary">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <?php endforeach; ?>
                    
                </tbody>
            </table>
        </div>
        
        <?php endif; ?>

        <!-- ====================================================================
        PARTIE 13: BOUTON DE RETOUR
        ==================================================================== -->

        <div style="text-align: center; margin: 30px 0;">
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

    </div>
</main>

<!-- ============================================================================
PARTIE 14: PIED DE PAGE
============================================================================ -->

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>Uber-Cueillette</h4>
                <p>La plateforme qui connecte agriculteurs et ouvriers agricoles.</p>
            </div>
            <div class="footer-col">
                <h4>Liens rapides</h4>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="mes-offres.php">Mes offres</a></li>
                    <li><a href="profil.php">Mon profil</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Uber-Cueillette - ISG Tunis</p>
        </div>
    </div>
</footer>

<!-- ============================================================================
PARTIE 15: FICHIER JAVASCRIPT
============================================================================ -->

<script src="../js/validation.js"></script>

<!-- ============================================================================
PARTIE 16: JAVASCRIPT POUR LA PAGE (CONSERVÉ)
============================================================================ -->

<script>
// ============================================================================
// FONCTION 1: ANIMATION DES STATISTIQUES
// ============================================================================

function animerStatistiques() {
    
    let statNumbers = document.querySelectorAll('.stat-number');
    
    for (let i = 0; i < statNumbers.length; i++) {
        
        let element = statNumbers[i];
        let valeurFinale = element.innerText;
        
        // Ne pas animer si c'est "N/A"
        if (valeurFinale === 'N/A') continue;
        
        // Ne pas animer les dates
        if (valeurFinale.includes('/')) continue;
        
        // Extraire le nombre
        let valeur = parseFloat(valeurFinale) || 0;
        
        if (valeur > 0) {
            let compteur = 0;
            element.innerText = '0';
            
            let intervalle = setInterval(function() {
                compteur = compteur + 0.1;
                if (compteur >= valeur) {
                    clearInterval(intervalle);
                    element.innerText = valeurFinale;
                } else {
                    element.innerText = compteur.toFixed(1);
                }
            }, 50);
        }
    }
}

// ============================================================================
// FONCTION 2: AGRANDIR LA PHOTO AU CLIC
// ============================================================================

function initPhotoZoom() {
    
    let photo = document.querySelector('.hero-content img');
    
    if (photo) {
        photo.addEventListener('click', function() {
            
            // Créer une modal
            let modal = document.createElement('div');
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0,0,0,0.8)';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.zIndex = '9999';
            modal.style.cursor = 'pointer';
            
            // Ajouter l'image agrandie
            let img = document.createElement('img');
            img.src = this.src;
            img.style.maxWidth = '90%';
            img.style.maxHeight = '90%';
            img.style.borderRadius = '10px';
            img.style.border = '4px solid white';
            
            modal.appendChild(img);
            
            // Fermer au clic
            modal.addEventListener('click', function() {
                document.body.removeChild(modal);
            });
            
            document.body.appendChild(modal);
        });
        
        photo.style.cursor = 'pointer';
        photo.title = 'Cliquer pour agrandir';
    }
}

// ============================================================================
// FONCTION 3: INITIALISATION
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    
    animerStatistiques();
    initPhotoZoom();
    
    // Ajouter un tooltip sur les notes
    let notes = document.querySelectorAll('.badge-warning i.fa-star');
    for (let i = 0; i < notes.length; i++) {
        if (notes[i].parentElement) {
            notes[i].parentElement.title = 'Note sur 10';
        }
    }
});

// ============================================================================
// FONCTION 4: GESTION DE LA TOUCHE RETOUR
// ============================================================================

window.addEventListener('popstate', function() {
    // Recharger la page si on revient en arrière
    location.reload();
});
</script>

</body>
</html>