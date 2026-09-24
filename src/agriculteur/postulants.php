<?php
/**
 * postulants.php - Page des postulants pour une offre
 * Affiche tous les ouvriers qui ont postulé à une offre
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
$id_offre = isset($_GET['offre']) ? intval($_GET['offre']) : 0;

if ($id_offre <= 0) {
    header('Location: mes-offres.php');
    exit();
}

// ============================================================================
// PARTIE 3: VÉRIFIER QUE L'OFFRE APPARTIENT À L'AGRICULTEUR
// ============================================================================

$sql = "SELECT o.*, tf.libelle as type_fruit, g.libelle as gouvernorat
        FROM uber_cueillette_offre o
        JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
        JOIN uber_cueillette_gouvernorat g ON o.id_gouvernorat = g.id_gouvernorat
        WHERE o.id_offre = ? AND o.id_agriculteur = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_offre, $id_agriculteur]);
$offre = $stmt->fetch();

if (!$offre) {
    header('Location: mes-offres.php');
    exit();
}

// ============================================================================
// PARTIE 4: RÉCUPÉRER TOUS LES POSTULANTS AVEC PHOTO
// ============================================================================

$sql = "SELECT c.*, ouv.nom, ouv.prenom, ouv.id_ouvrier, ouv.description, ouv.photo,
        (SELECT AVG(note) FROM uber_cueillette_candidature WHERE id_ouvrier = ouv.id_ouvrier AND note IS NOT NULL) as moyenne_notes
        FROM uber_cueillette_candidature c
        JOIN uber_cueillette_ouvrier ouv ON c.id_ouvrier = ouv.id_ouvrier
        WHERE c.id_offre = ?
        ORDER BY 
            CASE c.decision
                WHEN 'en_attente' THEN 1
                WHEN 'acceptee' THEN 2
                ELSE 3
            END,
            c.date_candidature DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_offre]);
$postulants = $stmt->fetchAll();

// ============================================================================
// ✅ FONCTION POUR CONVERTIR LES PHOTOS BLOB
// ============================================================================

function getPhotoUrl($photo_blob) {
    $default = '../images/default-profile.jpg';
    
    if (empty($photo_blob)) {
        return $default;
    }
    
    // Vérifier si c'est un BLOB (données binaires)
    $premier_code = ord(substr($photo_blob, 0, 1));
    
    // Si le premier caractère n'est pas imprimable ou si la chaîne est très longue
    if ($premier_code < 32 || $premier_code > 126 || strlen($photo_blob) > 255) {
        // C'est un BLOB → encoder en base64
        $photo_data = base64_encode($photo_blob);
        return 'data:image/jpeg;base64,' . $photo_data;
    } else {
        // C'est un chemin de fichier
        $photo_path = '../' . $photo_blob;
        if (file_exists($photo_path)) {
            return $photo_path;
        }
        return $default;
    }
}

// ============================================================================
// PARTIE 5: STATISTIQUES DES POSTULANTS
// ============================================================================

$total_postulants = count($postulants);
$en_attente = 0;
$acceptes = 0;
$refuses = 0;

foreach ($postulants as $p) {
    if ($p['decision'] == 'en_attente') $en_attente++;
    elseif ($p['decision'] == 'acceptee') $acceptes++;
    elseif ($p['decision'] == 'refusee') $refuses++;
}

$places_restantes = $offre['nombre_ouvriers'] - $acceptes;
$offre_complete = ($acceptes >= $offre['nombre_ouvriers']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postulants - <?php echo htmlspecialchars($offre['type_fruit']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="../images/Uber-Cueillette-logo.png">
</head>
<body>

<!-- NAVBAR -->
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

<!-- MAIN CONTENT -->
<main>
    <div class="container">

        <!-- EN-TÊTE DE L'OFFRE -->
        <div class="offre-header-card">
            <div class="offre-header-content">
                <div>
                    <h1 class="offre-header-title">
                        <i class="fas fa-apple-alt"></i> 
                        <?php echo htmlspecialchars($offre['type_fruit'] . ' - ' . $offre['gouvernorat']); ?>
                    </h1>
                    <div class="offre-header-meta">
                        <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?> - <?php echo date('d/m/Y', strtotime($offre['date_fin'])); ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($offre['adresse']); ?></span>
                        <span><i class="fas fa-users"></i> <?php echo $offre['nombre_ouvriers']; ?> ouvriers demandés</span>
                        <span><i class="fas fa-money-bill-wave"></i> <?php echo $offre['prix_journee']; ?> DT/jour</span>
                    </div>
                </div>
                
                <div class="offre-header-badges">
                    <?php if ($offre_complete): ?>
                        <span class="badge badge-success">✅ Offre complète</span>
                    <?php else: ?>
                        <span class="badge badge-warning">⏳ <?php echo $places_restantes; ?> place(s) restante(s)</span>
                    <?php endif; ?>
                    
                    <?php if ($offre['date_limite'] >= date('Y-m-d')): ?>
                        <span class="badge badge-info">📅 Postulation ouverte</span>
                    <?php else: ?>
                        <span class="badge badge-danger">🚫 Postulation fermée</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- STATISTIQUES DES CANDIDATURES -->
        <div class="candidatures-stats-grid">
            <div class="card candidature-stat">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-users" style="font-size: 2rem; color: var(--primary-color);"></i>
                    <div>
                        <span style="font-size: 1.8rem; font-weight: bold;"><?php echo $total_postulants; ?></span>
                        <span style="display: block; color: var(--text-light);">Total postulants</span>
                    </div>
                </div>
            </div>
            
            <div class="card candidature-stat">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-clock" style="font-size: 2rem; color: #f39c12;"></i>
                    <div>
                        <span style="font-size: 1.8rem; font-weight: bold;"><?php echo $en_attente; ?></span>
                        <span style="display: block; color: var(--text-light);">En attente</span>
                    </div>
                </div>
            </div>
            
            <div class="card candidature-stat">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; color: #2ecc71;"></i>
                    <div>
                        <span style="font-size: 1.8rem; font-weight: bold;"><?php echo $acceptes; ?></span>
                        <span style="display: block; color: var(--text-light);">Acceptés</span>
                    </div>
                </div>
            </div>
            
            <div class="card candidature-stat">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-times-circle" style="font-size: 2rem; color: #e74c3c;"></i>
                    <div>
                        <span style="font-size: 1.8rem; font-weight: bold;"><?php echo $refuses; ?></span>
                        <span style="display: block; color: var(--text-light);">Refusés</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTRES -->
        <div class="filters">
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Filtrer par statut :</label>
                <select id="filtre-statut">
                    <option value="tous">📋 Tous les postulants</option>
                    <option value="en_attente">⏳ En attente</option>
                    <option value="acceptee">✅ Acceptés</option>
                    <option value="refusee">❌ Refusés</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Rechercher :</label>
                <input type="text" id="recherche" placeholder="Nom du postulant...">
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-star"></i> Filtrer par note :</label>
                <select id="filtre-note">
                    <option value="tous">⭐ Toutes les notes</option>
                    <option value="8">🌟 8 étoiles et +</option>
                    <option value="5">✨ 5 étoiles et +</option>
                    <option value="3">⭐ 3 étoiles et +</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button class="btn btn-secondary" id="btn-reinitialiser">
                    <i class="fas fa-redo-alt"></i> Réinitialiser
                </button>
            </div>
        </div>

        <!-- COMPTEUR DE RÉSULTATS -->
        <div id="compteur-resultats" style="margin-bottom: 20px; font-weight: bold;">
            Affichage de <span id="nb-affiches"><?php echo count($postulants); ?></span> postulant(s)
        </div>

        <!-- LISTE DES POSTULANTS -->
        <?php if (empty($postulants)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>Aucun postulant pour le moment</h3>
                <p>Personne n'a encore postulé à cette offre</p>
                <a href="mes-offres.php" class="btn btn-outline">Retour aux offres</a>
            </div>
        <?php else: ?>
            <div class="candidatures-list" id="liste-postulants">
                
                <?php foreach($postulants as $p): 
                    
                    if ($p['decision'] == 'en_attente') {
                        $statut_class = 'badge-warning';
                        $statut_texte = '⏳ En attente';
                        $statut_data = 'en_attente';
                    } elseif ($p['decision'] == 'acceptee') {
                        $statut_class = 'badge-success';
                        $statut_texte = '✅ Accepté';
                        $statut_data = 'acceptee';
                    } else {
                        $statut_class = 'badge-danger';
                        $statut_texte = '❌ Refusé';
                        $statut_data = 'refusee';
                    }
                    
                    $moyenne = round($p['moyenne_notes'] ?? 0, 1);
                    
                    // ✅ CORRECTION: Générer l'URL de la photo
                    $photo_url = getPhotoUrl($p['photo']);
                ?>
                
                <div class="card candidature-card" 
                    data-statut="<?php echo $statut_data; ?>"
                    data-nom="<?php echo strtolower($p['prenom'] . ' ' . $p['nom']); ?>"
                    data-note="<?php echo $moyenne; ?>">
                    
                    <div class="card-body">
                        
                        <div class="candidature-content">
                            
                            <!-- ✅ PHOTO CORRIGÉE -->
                            <div class="candidature-photo">
                                <img src="<?php echo $photo_url; ?>" alt="Photo" class="profile-img"
                                     onerror="this.src='../images/default-profile.jpg'">
                                <br><br>
                                <div class="candidature-statut-badge statut-<?php echo $p['decision']; ?>">
                                    <?php echo $statut_texte; ?>
                                </div>
                            </div>
                            
                            <!-- Informations -->
                            <div class="candidature-info">
                                
                                <div class="candidature-header">
                                    <h3 class="candidature-nom">
                                        <?php echo htmlspecialchars($p['prenom'] . ' ' . $p['nom']); ?>
                                    </h3>
                                    <div class="candidature-badges">
                                        <?php if ($moyenne > 0): ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-star"></i> <?php echo $moyenne; ?>/10
                                        </span>
                                        <?php else: ?>
                                        <span class="badge badge-secondary">
                                            ⭐ Nouveau
                                        </span>
                                        <?php endif; ?>
                                        
                                        <span class="badge badge-info">
                                            <i class="fas fa-calendar"></i> 
                                            <?php echo date('d/m/Y', strtotime($p['date_candidature'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <?php if (!empty($p['description'])): ?>
                                <div class="candidature-comment">
                                    <i class="fas fa-quote-left"></i>
                                    <div class="comment-content">
                                        <p><?php echo htmlspecialchars($p['description']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Actions -->
                                <div class="candidature-footer">
                                    <div class="candidature-actions">
                                        
                                        <a href="profil-ouvrier.php?id=<?php echo $p['id_ouvrier']; ?>" class="btn btn-secondary">
                                            <i class="fas fa-eye"></i> Voir profil
                                        </a>
                                        
                                        <?php if ($p['decision'] == 'en_attente' && !$offre_complete): ?>
                                        
                                        <button class="btn btn-success btn-accepter" data-id="<?php echo $p['id_candidature']; ?>">
                                            <i class="fas fa-check"></i> Accepter
                                        </button>
                                        
                                        <button class="btn btn-danger btn-refuser" data-id="<?php echo $p['id_candidature']; ?>">
                                            <i class="fas fa-times"></i> Refuser
                                        </button>
                                        
                                        <?php elseif ($p['decision'] == 'en_attente' && $offre_complete): ?>
                                        
                                        <span class="badge badge-warning">Offre complète - En attente</span>
                                        
                                        <?php elseif ($p['decision'] == 'acceptee'): ?>
                                        
                                        <span class="badge badge-success">Déjà accepté</span>
                                        
                                        <?php else: ?>
                                        
                                        <span class="badge badge-danger">Refusé</span>
                                        
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
                
            </div>
        <?php endif; ?>

        <!-- BOUTON RETOUR -->
        <div style="text-align: center; margin: 40px 0;">
            <a href="mes-offres.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Retour aux offres
            </a>
        </div>

        <!-- MESSAGE AUCUN RÉSULTAT -->
        <div class="empty-state" id="emptyState" style="display: none;">
            <i class="fas fa-search"></i>
            <h3>Aucun postulant trouvé</h3>
            <p>Aucun postulant ne correspond aux filtres sélectionnés</p>
            <button class="btn btn-primary" id="btn-reset-empty">Réinitialiser les filtres</button>
        </div>

    </div>
</main>

<!-- FOOTER -->
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
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Uber-Cueillette - ISG Tunis</p>
        </div>
    </div>
</footer>

<script src="../js/validation.js"></script>

<script>
// ============================================================================
// FONCTION 1: FILTRER LES POSTULANTS
// ============================================================================

function filtrerPostulants() {
    let statutChoisi = document.getElementById('filtre-statut').value;
    let rechercheTexte = document.getElementById('recherche').value.toLowerCase();
    let noteChoisie = document.getElementById('filtre-note').value;
    
    let postulants = document.querySelectorAll('.candidature-card');
    let visibles = 0;
    
    for (let i = 0; i < postulants.length; i++) {
        let p = postulants[i];
        let statutOK = true;
        let rechercheOK = true;
        let noteOK = true;
        
        if (statutChoisi !== 'tous') {
            statutOK = (p.dataset.statut === statutChoisi);
        }
        
        if (rechercheTexte !== '') {
            let nom = p.dataset.nom;
            rechercheOK = (nom.indexOf(rechercheTexte) !== -1);
        }
        
        if (noteChoisie !== 'tous') {
            let note = parseFloat(p.dataset.note);
            noteOK = (note >= parseFloat(noteChoisie));
        }
        
        if (statutOK && rechercheOK && noteOK) {
            p.style.display = 'block';
            visibles++;
        } else {
            p.style.display = 'none';
        }
    }
    
    document.getElementById('nb-affiches').innerHTML = visibles;
    
    let emptyMessage = document.getElementById('emptyState');
    if (visibles === 0) {
        emptyMessage.style.display = 'block';
    } else {
        emptyMessage.style.display = 'none';
    }
}

function reinitialiserFiltres() {
    document.getElementById('filtre-statut').value = 'tous';
    document.getElementById('recherche').value = '';
    document.getElementById('filtre-note').value = 'tous';
    filtrerPostulants();
}

function accepterCandidature(id) {
    if (confirm('Voulez-vous accepter cette candidature ?')) {
        fetch('../traitement/repondre-candidature.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&action=accepter'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Candidature acceptée !');
                location.reload();
            } else {
                alert('❌ Erreur : ' + data.message);
            }
        })
        .catch(error => { alert('Erreur de connexion'); });
    }
}

function refuserCandidature(id) {
    if (confirm('Voulez-vous refuser cette candidature ?')) {
        fetch('../traitement/repondre-candidature.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&action=refuser'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('❌ Candidature refusée');
                location.reload();
            } else {
                alert('❌ Erreur : ' + data.message);
            }
        })
        .catch(error => { alert('Erreur de connexion'); });
    }
}

// INITIALISATION
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtre-statut').addEventListener('change', filtrerPostulants);
    document.getElementById('recherche').addEventListener('keyup', filtrerPostulants);
    document.getElementById('filtre-note').addEventListener('change', filtrerPostulants);
    document.getElementById('btn-reinitialiser').addEventListener('click', reinitialiserFiltres);
    document.getElementById('btn-reset-empty').addEventListener('click', reinitialiserFiltres);
    
    let boutonsAccepter = document.querySelectorAll('.btn-accepter');
    for (let i = 0; i < boutonsAccepter.length; i++) {
        boutonsAccepter[i].addEventListener('click', function() {
            accepterCandidature(this.getAttribute('data-id'));
        });
    }
    
    let boutonsRefuser = document.querySelectorAll('.btn-refuser');
    for (let i = 0; i < boutonsRefuser.length; i++) {
        boutonsRefuser[i].addEventListener('click', function() {
            refuserCandidature(this.getAttribute('data-id'));
        });
    }
});
</script>

</body>
</html>