<?php
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

// ============================================================================
// PARTIE 3: RÉCUPÉRER LES INFORMATIONS DE L'AGRICULTEUR
// ============================================================================

$sql = "SELECT * FROM uber_cueillette_agriculteur WHERE id_agriculteur = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_agriculteur]);
$agriculteur = $stmt->fetch();

if (!$agriculteur) {
    session_destroy();
    header('Location: ../racine/login.php');
    exit();
}

// ============================================================================
// PARTIE 4: COMPTER LES OFFRES ACTIVES
// ============================================================================

$sql = "SELECT COUNT(*) as total FROM uber_cueillette_offre 
        WHERE id_agriculteur = ? AND date_limite >= CURDATE()";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_agriculteur]);
$resultat = $stmt->fetch();
$offres_actives = $resultat['total'] ?? 0;

// ============================================================================
// PARTIE 5: COMPTER LES CANDIDATURES EN ATTENTE
// ============================================================================

$sql = "SELECT COUNT(*) as total FROM uber_cueillette_candidature c
        JOIN uber_cueillette_offre o ON c.id_offre = o.id_offre
        WHERE o.id_agriculteur = ? AND c.decision = 'en_attente'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_agriculteur]);
$resultat = $stmt->fetch();
$candidatures_attente = $resultat['total'] ?? 0;

// ============================================================================
// PARTIE 6: COMPTER LES OFFRES TERMINÉES
// ============================================================================

$sql = "SELECT COUNT(*) as total FROM uber_cueillette_offre 
        WHERE id_agriculteur = ? AND date_fin < CURDATE()";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_agriculteur]);
$resultat = $stmt->fetch();
$offres_terminees = $resultat['total'] ?? 0;

// ============================================================================
// PARTIE 7: COMPTER LE TOTAL DES OFFRES
// ============================================================================

$sql = "SELECT COUNT(*) as total FROM uber_cueillette_offre 
        WHERE id_agriculteur = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_agriculteur]);
$resultat = $stmt->fetch();
$total_offres = $resultat['total'] ?? 0;

// ============================================================================
// PARTIE 8: CALCULER LA NOTE MOYENNE
// ============================================================================

$sql = "SELECT AVG(c.note) as moyenne FROM uber_cueillette_candidature c
        JOIN uber_cueillette_offre o ON c.id_offre = o.id_offre
        WHERE o.id_agriculteur = ? AND c.note IS NOT NULL";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_agriculteur]);
$resultat = $stmt->fetch();
$note_moyenne = $resultat['moyenne'] ?? 0;

if ($note_moyenne == 0) {
    $note_moyenne = '4.8';
} else {
    $note_moyenne = number_format($note_moyenne, 1);
}

// ============================================================================
// PARTIE 9: RÉCUPÉRER LES 3 DERNIÈRES OFFRES
// ============================================================================

$sql = "SELECT o.*, tf.libelle as type_fruit, g.libelle as gouvernorat_libelle,
        (SELECT COUNT(*) FROM uber_cueillette_candidature WHERE id_offre = o.id_offre) as nb_candidatures,
        (SELECT COUNT(*) FROM uber_cueillette_candidature WHERE id_offre = o.id_offre AND decision = 'acceptee') as nb_acceptes
        FROM uber_cueillette_offre o
        JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
        JOIN uber_cueillette_gouvernorat g ON o.id_gouvernorat = g.id_gouvernorat
        WHERE o.id_agriculteur = ?
        ORDER BY o.date_limite DESC
        LIMIT 3";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_agriculteur]);
$offres_recentes = $stmt->fetchAll();

// ============================================================================
// PARTIE 10: RÉCUPÉRER LES 5 DERNIÈRES CANDIDATURES
// ============================================================================

$sql = "SELECT c.*, o.adresse, tf.libelle as type_fruit, 
            ouv.nom as ouvrier_nom, ouv.prenom as ouvrier_prenom, ouv.id_ouvrier,
            o.id_offre, o.nombre_ouvriers
        FROM uber_cueillette_candidature c
        JOIN uber_cueillette_offre o ON c.id_offre = o.id_offre
        JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
        JOIN uber_cueillette_ouvrier ouv ON c.id_ouvrier = ouv.id_ouvrier
        WHERE o.id_agriculteur = ?
        ORDER BY c.date_candidature DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_agriculteur]);
$candidatures_recentes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Agriculteur - Uber-Cueillette</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../images/Uber-Cueillette-logo.png">
</head>
<body>

<!-- ============================================================================
PARTIE 11: BARRE DE NAVIGATION
============================================================================ -->

<nav class="navbar">
    <div class="container">
        <a href="../racine/index.html" class="logo">
            <img src="../images/Uber-Cueillette-logo.png" alt="Uber-Cueillette" class="logo-img">
            <span>Uber<span>Cueillette</span></span>
        </a>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
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
PARTIE 12: CONTENU PRINCIPAL
============================================================================ -->

<main>
    <div class="container">

        <!--====================================================================
        PARTIE 13: EN-TÊTE DE BIENVENUE
        ==================================================================== -->

        <div class="hero" style="padding: 30px 0; margin-bottom: 30px;">
            <div class="container">
                <div class="hero-content">
                    <h1><i class="fas fa-chart-line"></i> Dashboard Agriculteur</h1>
                    <p>Bienvenue <strong><?php echo htmlspecialchars($agriculteur['prenom'] . ' ' . $agriculteur['nom']); ?></strong> !</p>
                </div>
                <div class="hero-image">
                    <img src="../images/hero-agriculture1.png" alt="Dashboard" onerror="this.style.display='none'">
                </div>
            </div>
        </div>

        <!-- ====================================================================
        PARTIE 14: STATISTIQUES (4 CARTES)
        ==================================================================== -->

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-seedling"></i></div>
                <div class="stat-content">
                    <span class="stat-number" id="stat-offres-actives"><?php echo $offres_actives; ?></span>
                    <span class="stat-label">Offres actives</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number" id="stat-candidatures"><?php echo $candidatures_attente; ?></span>
                    <span class="stat-label">Candidatures en attente</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number" id="stat-offres-terminees"><?php echo $offres_terminees; ?></span>
                    <span class="stat-label">Offres terminées</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number" id="stat-total-offres"><?php echo $total_offres; ?></span>
                    <span class="stat-label">Total offres</span>
                </div>
            </div>
        </div>

        <!-- ====================================================================
        PARTIE 15: BOUTONS D'ACTIONS RAPIDES
        ==================================================================== -->

        <div style="display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap;">
            <a href="ajouter-offre.php" class="btn btn-primary btn-large">
                <i class="fas fa-plus-circle"></i> Ajouter une offre
            </a>
            <a href="mes-offres.php" class="btn btn-outline btn-large">
                <i class="fas fa-list"></i> Voir mes offres
            </a>
        </div>

        <!-- ====================================================================
        PARTIE 16: TITRE DES OFFRES RÉCENTES
        ==================================================================== -->

        <h2 class="section-title">Offres récentes</h2>

        <!-- ====================================================================
        PARTIE 17: FILTRE DES OFFRES AMÉLIORÉ
        ==================================================================== -->

        <div class="filters" style="margin-bottom: 20px;" id="filtre-offres-container">
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Filtrer par statut :</label>
                <select id="filtre-statut-offres">
                    <option value="toutes">📋 Toutes les offres</option>
                    <option value="ouverte">🟢 Offres ouvertes</option>
                    <option value="cloturee">🟡 Offres clôturées</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Rechercher :</label>
                <input type="text" id="recherche-offres" placeholder="Fruit, lieu...">
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-sort"></i> Trier par :</label>
                <select id="tri-offres">
                    <option value="date">📅 Date (récent)</option>
                    <option value="candidatures">👥 Candidatures</option>
                    <option value="prix">💰 Prix (croissant)</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button class="btn btn-secondary" id="btn-reinitialiser-offres">
                    <i class="fas fa-redo-alt"></i> Réinitialiser
                </button>
            </div>
        </div>

        <!-- ====================================================================
             PARTIE 18: COMPTEUR DE RÉSULTATS
             ==================================================================== -->

        <div id="compteur-offres" style="margin-bottom: 15px; font-weight: bold;">
            Affichage de <span id="nb-offres-affichees"><?php echo count($offres_recentes); ?></span> offre(s)
        </div>

        <!-- ====================================================================
             PARTIE 19: AFFICHAGE DES OFFRES RÉCENTES
             ==================================================================== -->

        <?php if (empty($offres_recentes)): ?>

            <div class="empty-state">
                <i class="fas fa-seedling"></i>
                <h3>Aucune offre pour le moment</h3>
                <p>Commencez par créer votre première offre de récolte</p>
                <a href="ajouter-offre.php" class="btn btn-primary">Créer une offre</a>
            </div>

        <?php else: ?>

            <div class="card-grid" id="offres-grid">
                
                <?php foreach($offres_recentes as $offre): ?>
                
                <?php
                $aujourdhui = date('Y-m-d');
                
                if ($offre['date_limite'] >= $aujourdhui) {
                    $statut = 'ouverte';
                    $statut_class = 'success';
                    $statut_text = 'Ouverte';
                } else {
                    $statut = 'cloturee';
                    $statut_class = 'warning';
                    $statut_text = 'Clôturée';
                }
                
                $pourcentage = 0;
                if ($offre['nombre_ouvriers'] > 0) {
                    $pourcentage = round(($offre['nb_acceptes'] / $offre['nombre_ouvriers']) * 100);
                }
                ?>
                
                <div class="card offre-card" 
                        data-statut="<?php echo $statut; ?>" 
                        data-id="<?php echo $offre['id_offre']; ?>"
                        data-fruit="<?php echo strtolower($offre['type_fruit']); ?>"
                        data-lieu="<?php echo strtolower($offre['gouvernorat_libelle'] . ' ' . $offre['adresse']); ?>"
                        data-prix="<?php echo $offre['prix_journee']; ?>"
                        data-candidatures="<?php echo $offre['nb_candidatures']; ?>"
                        data-date="<?php echo strtotime($offre['date_limite']); ?>">
                    
                    <div class="card-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-apple-alt"></i> 
                            <span class="titre-offre"><?php echo htmlspecialchars($offre['type_fruit'] . ' - ' . $offre['gouvernorat_libelle']); ?></span>
                        </div>
                        <div class="badge-group">
                            <span class="badge badge-<?php echo $statut_class; ?> statut-badge"><?php echo $statut_text; ?></span>
                            <span class="badge badge-info"><?php echo $offre['nb_candidatures']; ?> candidatures</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        
                        <div class="offre-details-grid">
                            
                            <div class="offre-detail">
                                <i class="fas fa-calendar-alt"></i>
                                <div>
                                    <small>Période</small>
                                    <strong><?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?> - <?php echo date('d/m/Y', strtotime($offre['date_fin'])); ?></strong>
                                </div>
                            </div>
                            
                            <div class="offre-detail">
                                <i class="fas fa-users"></i>
                                <div>
                                    <small>Ouvriers</small>
                                    <strong><span class="nb-acceptes"><?php echo $offre['nb_acceptes']; ?></span>/<span class="nb-total"><?php echo $offre['nombre_ouvriers']; ?></span> acceptés</strong>
                                </div>
                            </div>
                            
                            <div class="offre-detail">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>
                                    <small>Prix</small>
                                    <strong class="prix-offre"><?php echo $offre['prix_journee']; ?> DT/jour</strong>
                                </div>
                            </div>
                            
                            <div class="offre-detail">
                                <i class="fas fa-hourglass-half"></i>
                                <div>
                                    <small>Date limite</small>
                                    <strong><?php echo date('d/m/Y', strtotime($offre['date_limite'])); ?></strong>
                                </div>
                            </div>
                            
                        </div>
                        
                        <?php if ($statut == 'ouverte' && $pourcentage > 0): ?>
                        <div class="progress-container" style="margin-top: 15px;">
                            <div class="progress-header">
                                <span>Progression</span>
                                <span class="progress-percent"><?php echo $pourcentage; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $pourcentage; ?>%;"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="offre-location">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($offre['adresse']); ?>
                        </div>
                        
                    </div>
                    
                    <div class="card-footer">
                        <a href="postulants.php?offre=<?php echo $offre['id_offre']; ?>" class="btn btn-primary">
                            <i class="fas fa-users"></i> Voir postulants (<?php echo $offre['nb_candidatures']; ?>)
                        </a>
                        <button class="btn btn-danger btn-supprimer" data-id="<?php echo $offre['id_offre']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    
                </div>
                
                <?php endforeach; ?>
                
            </div>

        <?php endif; ?>

        <!-- ====================================================================
        PARTIE 20: TITRE DES CANDIDATURES RÉCENTES
        ==================================================================== -->

        <h2 class="section-title" style="margin-top: 50px;">Candidatures récentes</h2>

        <!-- ====================================================================
        PARTIE 21: RECHERCHE DANS LE TABLEAU AMÉLIORÉE
        ==================================================================== -->

        <div class="filters" style="margin-bottom: 15px;" id="recherche-candidatures-container">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Rechercher un ouvrier :</label>
                <input type="text" id="recherche-ouvrier" placeholder="Nom de l'ouvrier...">
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Filtrer par statut :</label>
                <select id="filtre-statut-candidatures">
                    <option value="tous">📋 Tous les statuts</option>
                    <option value="en_attente">⏳ En attente</option>
                    <option value="acceptee">✅ Acceptée</option>
                    <option value="refusee">❌ Refusée</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-calendar"></i> Filtrer par date :</label>
                <select id="filtre-date-candidatures">
                    <option value="toutes">📅 Toutes les dates</option>
                    <option value="aujourdhui">📅 Aujourd'hui</option>
                    <option value="semaine">📅 Cette semaine</option>
                    <option value="mois">📅 Ce mois</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button class="btn btn-secondary" id="btn-reinitialiser-candidatures">
                    <i class="fas fa-redo-alt"></i> Réinitialiser
                </button>
            </div>
        </div>

        <!-- ====================================================================
        PARTIE 22: COMPTEUR DE CANDIDATURES
        ==================================================================== -->

        <div id="compteur-candidatures" style="margin-bottom: 15px; font-weight: bold;">
            Affichage de <span id="nb-candidatures-affichees"><?php echo count($candidatures_recentes); ?></span> candidature(s)
        </div>

        <!-- ====================================================================
        PARTIE 23: AFFICHAGE DES CANDIDATURES RÉCENTES
        ==================================================================== -->

        <?php if (empty($candidatures_recentes)): ?>

            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>Aucune candidature pour le moment</h3>
                <p>Les candidatures apparaîtront ici quand des ouvriers postuleront</p>
            </div>

        <?php else: ?>

            <div class="table-container">
                <table id="candidatures-table">
                    
                    <thead>
                        <tr>
                            <th>Offre</th>
                            <th>Ouvrier</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    
                    <tbody id="candidatures-body">
                        
                        <?php foreach($candidatures_recentes as $candidature): ?>
                        
                        <?php
                        if ($candidature['decision'] == 'en_attente') {
                            $statut_class = 'warning';
                            $statut_text = 'En attente';
                        } elseif ($candidature['decision'] == 'acceptee') {
                            $statut_class = 'success';
                            $statut_text = 'Acceptée';
                        } else {
                            $statut_class = 'danger';
                            $statut_text = 'Refusée';
                        }
                        
                        $date_timestamp = strtotime($candidature['date_candidature']);
                        ?>
                        
                        <tr data-id="<?php echo $candidature['id_candidature']; ?>" 
                            data-statut="<?php echo $candidature['decision']; ?>"
                            data-date="<?php echo $date_timestamp; ?>"
                            data-ouvrier="<?php echo strtolower($candidature['ouvrier_prenom'] . ' ' . $candidature['ouvrier_nom']); ?>">
                            
                            <td>
                                <i class="fas fa-apple-alt"></i> <?php echo htmlspecialchars($candidature['type_fruit']); ?>
                            </td>
                            
                            <td class="nom-ouvrier">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($candidature['ouvrier_prenom'] . ' ' . $candidature['ouvrier_nom']); ?>
                            </td>
                            
                            <td class="date-candidature">
                                <?php echo date('d/m/Y', $date_timestamp); ?>
                            </td>
                            
                            <td>
                                <span class="badge badge-<?php echo $statut_class; ?> statut-candidature"><?php echo $statut_text; ?></span>
                            </td>
                            
                            <td class="actions-cell">
                                
                                <a href="profil-ouvrier.php?id=<?php echo $candidature['id_ouvrier']; ?>" class="btn btn-secondary btn-small" title="Voir le profil">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($candidature['decision'] == 'en_attente'): ?>
                                
                                <button class="btn btn-success btn-small btn-accepter" data-id="<?php echo $candidature['id_candidature']; ?>" title="Accepter">
                                    <i class="fas fa-check"></i>
                                </button>
                                
                                <button class="btn btn-danger btn-small btn-refuser" data-id="<?php echo $candidature['id_candidature']; ?>" title="Refuser">
                                    <i class="fas fa-times"></i>
                                </button>
                                
                                <?php endif; ?>
                                
                            </td>
                            
                        </tr>
                        
                        <?php endforeach; ?>
                        
                    </tbody>
                    
                </table>
            </div>

        <?php endif; ?>

        <!-- ====================================================================
        PARTIE 24: INFORMATION DE CONNEXION
        ==================================================================== -->

        <div style="margin-top: 30px; padding: 15px; background: var(--card-bg); border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
            <p>
                <i class="fas fa-info-circle"></i> 
                Connecté en tant que <strong><?php echo htmlspecialchars($agriculteur['pseudo']); ?></strong> 
                (<?php echo htmlspecialchars($agriculteur['email']); ?>)
            </p>
        </div>

    </div>
</main>

<!-- ============================================================================
PARTIE 25: PIED DE PAGE
============================================================================ -->

<footer id="contact" class="footer">
    <div class="container">
        <div class="footer-grid">
            
            <div class="footer-col">
                <h4>Uber-Cueillette</h4>
                <p>La plateforme n°1 de mise en relation agriculteurs-ouvriers en Tunisie</p>
            </div>
            
            <div class="footer-col">
                <h4>Liens rapides</h4>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="ajouter-offre.php">Ajouter offre</a></li>
                    <li><a href="mes-offres.php">Mes offres</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h4>Contact</h4>
                <ul>
                    <li><i class="fas fa-envelope"></i> contact@uber-cueillette.tn</li>
                    <li><i class="fas fa-phone"></i> +216 XX XXX XXX</li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h4>Suivez-nous</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 Uber-Cueillette - ISG Tunis. Projet Programmation Web 2</p>
        </div>
    </div>
</footer>

<!-- ============================================================================
PARTIE 26: FICHIER JAVASCRIPT PRINCIPAL
============================================================================ -->

<script src="../js/validation.js"></script>

<!-- ============================================================================
PARTIE 27: JAVASCRIPT POUR LE DASHBOARD (SANS CONSOLE.LOG)
============================================================================ -->

<script>

// ============================================================================
// FONCTION 1: ANIMER LES STATISTIQUES AU CHARGEMENT
// ============================================================================

function animerStatistiques() {
    
    let offresActives = <?php echo $offres_actives; ?>;
    let candidaturesAttente = <?php echo $candidatures_attente; ?>;
    let offresTerminees = <?php echo $offres_terminees; ?>;
    let totalOffres = <?php echo $total_offres; ?>;
    
    let elOffres = document.getElementById('stat-offres-actives');
    let elCandidatures = document.getElementById('stat-candidatures');
    let elTerminees = document.getElementById('stat-offres-terminees');
    let elTotal = document.getElementById('stat-total-offres');
    
    if (elOffres && offresActives > 0) {
        let compteur = 0;
        let intervalle = setInterval(function() {
            compteur = compteur + 1;
            elOffres.innerHTML = compteur;
            if (compteur >= offresActives) {
                clearInterval(intervalle);
                elOffres.innerHTML = offresActives;
            }
        }, 100);
    }
    
    if (elCandidatures && candidaturesAttente > 0) {
        let compteur = 0;
        let intervalle = setInterval(function() {
            compteur = compteur + 1;
            elCandidatures.innerHTML = compteur;
            if (compteur >= candidaturesAttente) {
                clearInterval(intervalle);
                elCandidatures.innerHTML = candidaturesAttente;
            }
        }, 80);
    }
    
    if (elTerminees && offresTerminees > 0) {
        let compteur = 0;
        let intervalle = setInterval(function() {
            compteur = compteur + 1;
            elTerminees.innerHTML = compteur;
            if (compteur >= offresTerminees) {
                clearInterval(intervalle);
                elTerminees.innerHTML = offresTerminees;
            }
        }, 100);
    }
    
    if (elTotal && totalOffres > 0) {
        let compteur = 0;
        let intervalle = setInterval(function() {
            compteur = compteur + 1;
            elTotal.innerHTML = compteur;
            if (compteur >= totalOffres) {
                clearInterval(intervalle);
                elTotal.innerHTML = totalOffres;
            }
        }, 70);
    }
}

// ============================================================================
// FONCTION 2: FILTRER LES OFFRES
// ============================================================================

function filtrerOffres() {
    
    let statutChoisi = document.getElementById('filtre-statut-offres').value;
    let rechercheTexte = document.getElementById('recherche-offres').value.toLowerCase();
    let triChoisi = document.getElementById('tri-offres').value;
    
    let offres = document.querySelectorAll('.offre-card');
    let offresVisibles = 0;
    
    for (let i = 0; i < offres.length; i++) {
        
        let offre = offres[i];
        let statutOffre = offre.dataset.statut;
        let fruitOffre = offre.dataset.fruit;
        let lieuOffre = offre.dataset.lieu;
        
        let statutOK = false;
        if (statutChoisi === 'toutes') {
            statutOK = true;
        } else if (statutOffre === statutChoisi) {
            statutOK = true;
        } else {
            statutOK = false;
        }
        
        let rechercheOK = false;
        if (rechercheTexte === '') {
            rechercheOK = true;
        } else if (fruitOffre.indexOf(rechercheTexte) !== -1) {
            rechercheOK = true;
        } else if (lieuOffre.indexOf(rechercheTexte) !== -1) {
            rechercheOK = true;
        } else {
            rechercheOK = false;
        }
        
        if (statutOK && rechercheOK) {
            offre.style.display = 'block';
            offresVisibles = offresVisibles + 1;
        } else {
            offre.style.display = 'none';
        }
    }
    
    document.getElementById('nb-offres-affichees').innerHTML = offresVisibles;
    
    if (triChoisi !== 'date') {
        trierOffres(triChoisi);
    }
}

// ============================================================================
// FONCTION 3: TRIER LES OFFRES
// ============================================================================

function trierOffres(critere) {
    
    let grid = document.getElementById('offres-grid');
    let offres = Array.from(document.querySelectorAll('.offre-card'));
    
    if (critere === 'candidatures') {
        offres.sort(function(a, b) {
            return b.dataset.candidatures - a.dataset.candidatures;
        });
    } else if (critere === 'prix') {
        offres.sort(function(a, b) {
            return a.dataset.prix - b.dataset.prix;
        });
    }
    
    for (let i = 0; i < offres.length; i++) {
        grid.appendChild(offres[i]);
    }
}

// ============================================================================
// FONCTION 4: RÉINITIALISER LES FILTRES OFFRES
// ============================================================================

function reinitialiserFiltresOffres() {
    
    document.getElementById('filtre-statut-offres').value = 'toutes';
    document.getElementById('recherche-offres').value = '';
    document.getElementById('tri-offres').value = 'date';
    
    filtrerOffres();
}

// ============================================================================
// FONCTION 5: FILTRER LES CANDIDATURES
// ============================================================================

function filtrerCandidatures() {
    
    let rechercheTexte = document.getElementById('recherche-ouvrier').value.toLowerCase();
    let statutChoisi = document.getElementById('filtre-statut-candidatures').value;
    let filtreDate = document.getElementById('filtre-date-candidatures').value;
    
    let lignes = document.querySelectorAll('#candidatures-body tr');
    let aujourdhui = new Date();
    aujourdhui.setHours(0, 0, 0, 0);
    
    let debutSemaine = new Date(aujourdhui);
    debutSemaine.setDate(aujourdhui.getDate() - aujourdhui.getDay());
    
    let debutMois = new Date(aujourdhui.getFullYear(), aujourdhui.getMonth(), 1);
    
    let lignesVisibles = 0;
    
    for (let i = 0; i < lignes.length; i++) {
        
        let ligne = lignes[i];
        let nomOuvrier = ligne.dataset.ouvrier;
        let statutLigne = ligne.dataset.statut;
        let timestamp = ligne.dataset.date * 1000;
        let dateLigne = new Date(timestamp);
        
        let rechercheOK = false;
        if (rechercheTexte === '') {
            rechercheOK = true;
        } else if (nomOuvrier.indexOf(rechercheTexte) !== -1) {
            rechercheOK = true;
        } else {
            rechercheOK = false;
        }
        
        let statutOK = false;
        if (statutChoisi === 'tous') {
            statutOK = true;
        } else if (statutLigne === statutChoisi) {
            statutOK = true;
        } else {
            statutOK = false;
        }
        
        let dateOK = false;
        if (filtreDate === 'toutes') {
            dateOK = true;
        } else if (filtreDate === 'aujourdhui') {
            dateOK = dateLigne.toDateString() === aujourdhui.toDateString();
        } else if (filtreDate === 'semaine') {
            dateOK = dateLigne >= debutSemaine;
        } else if (filtreDate === 'mois') {
            dateOK = dateLigne >= debutMois;
        }
        
        if (rechercheOK && statutOK && dateOK) {
            ligne.style.display = '';
            lignesVisibles = lignesVisibles + 1;
        } else {
            ligne.style.display = 'none';
        }
    }
    
    document.getElementById('nb-candidatures-affichees').innerHTML = lignesVisibles;
}

// ============================================================================
// FONCTION 6: RÉINITIALISER LES FILTRES CANDIDATURES
// ============================================================================

function reinitialiserFiltresCandidatures() {
    
    document.getElementById('recherche-ouvrier').value = '';
    document.getElementById('filtre-statut-candidatures').value = 'tous';
    document.getElementById('filtre-date-candidatures').value = 'toutes';
    
    filtrerCandidatures();
}

// ============================================================================
// FONCTION 7: GÉRER LES BOUTONS SUPPRIMER
// ============================================================================

function gererBoutonsSupprimer() {
    
    let boutons = document.querySelectorAll('.btn-supprimer');
    
    for (let i = 0; i < boutons.length; i++) {
        
        boutons[i].addEventListener('click', function(e) {
            
            e.preventDefault();
            
            let idOffre = this.getAttribute('data-id');
            let reponse = confirm('Voulez-vous vraiment supprimer cette offre ?');
            
            if (reponse === true) {
                
                alert('Offre supprimée avec succès !');
                
                let carte = this.closest('.offre-card');
                
                if (carte) {
                    carte.remove();
                    
                    let offresRestantes = document.querySelectorAll('.offre-card').length;
                    document.getElementById('nb-offres-affichees').innerHTML = offresRestantes;
                    
                    location.reload();
                }
            }
        });
    }
}

// ============================================================================
// FONCTION 8: GÉRER LES BOUTONS ACCEPTER
// ============================================================================

// ============================================================================
// FONCTION 8: GÉRER LES BOUTONS ACCEPTER
// ============================================================================

function gererBoutonsAccepter() {
    
    let boutons = document.querySelectorAll('.btn-accepter');
    
    for (let i = 0; i < boutons.length; i++) {
        
        boutons[i].addEventListener('click', function(e) {
            
            e.preventDefault();
            
            let idCandidature = this.getAttribute('data-id');
            let ligne = this.closest('tr');
            
            let reponse = confirm('Accepter cette candidature ?');
            
            if (reponse === true) {
                
                // ✅ CORRECTION ICI
                let celluleStatut = ligne.querySelector('td:nth-child(4)');
                celluleStatut.innerHTML = '<span class="badge badge-success statut-candidature">Acceptée</span>';
                
                ligne.dataset.statut = 'acceptee';
                
                let celluleActions = ligne.querySelector('td:last-child');
                celluleActions.innerHTML = '<a href="profil-ouvrier.php" class="btn btn-secondary btn-small"><i class="fas fa-eye"></i></a> <span class="badge badge-success">Accepté</span>';
                
                let compteur = document.getElementById('stat-candidatures');
                
                if (compteur) {
                    let valeur = parseInt(compteur.innerHTML);
                    compteur.innerHTML = valeur - 1;
                }
                
                filtrerCandidatures();
            }
        });
    }
}

// ============================================================================
// FONCTION 9: GÉRER LES BOUTONS REFUSER
// ============================================================================

function gererBoutonsRefuser() {
    
    let boutons = document.querySelectorAll('.btn-refuser');
    
    for (let i = 0; i < boutons.length; i++) {
        
        boutons[i].addEventListener('click', function(e) {
            
            e.preventDefault();
            
            let idCandidature = this.getAttribute('data-id');
            let ligne = this.closest('tr');
            
            let reponse = confirm('Refuser cette candidature ?');
            
            if (reponse === true) {
                
                let celluleStatut = ligne.querySelector('td:nth-child(4)');
                celluleStatut.innerHTML = '<span class="badge badge-danger statut-candidature">Refusée</span>';
                
                ligne.dataset.statut = 'refusee';
                
                let celluleActions = ligne.querySelector('td:last-child');
                celluleActions.innerHTML = '<a href="profil-ouvrier.php" class="btn btn-secondary btn-small"><i class="fas fa-eye"></i></a>';
                
                let compteur = document.getElementById('stat-candidatures');
                
                if (compteur) {
                    let valeur = parseInt(compteur.innerHTML);
                    compteur.innerHTML = valeur - 1;
                }
                
                filtrerCandidatures();
            }
        });
    }
}

// ============================================================================
// INITIALISATION: TOUT LANCER AU CHARGEMENT
// ============================================================================

window.onload = function() {
    
    animerStatistiques();
    
    document.getElementById('filtre-statut-offres').addEventListener('change', filtrerOffres);
    document.getElementById('recherche-offres').addEventListener('keyup', filtrerOffres);
    document.getElementById('tri-offres').addEventListener('change', filtrerOffres);
    document.getElementById('btn-reinitialiser-offres').addEventListener('click', reinitialiserFiltresOffres);
    
    document.getElementById('recherche-ouvrier').addEventListener('keyup', filtrerCandidatures);
    document.getElementById('filtre-statut-candidatures').addEventListener('change', filtrerCandidatures);
    document.getElementById('filtre-date-candidatures').addEventListener('change', filtrerCandidatures);
    document.getElementById('btn-reinitialiser-candidatures').addEventListener('click', reinitialiserFiltresCandidatures);
    
    gererBoutonsSupprimer();
    gererBoutonsAccepter();
    gererBoutonsRefuser();
};

</script>

</body>
</html>