<?php
/**
 * mes-candidatures.php - Suivi des candidatures de l'ouvrier
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ouvrier') {
    header('Location: ../racine/login.php');
    exit();
}

require_once('../config/database.php');

$id_ouvrier = $_SESSION['user_id'];

// Récupérer toutes les candidatures
$sql = "SELECT c.*, 
        o.adresse, o.date_debut, o.date_fin, o.prix_journee, o.date_limite,
        tf.libelle as type_fruit, 
        g.libelle as gouvernorat
        FROM uber_cueillette_candidature c
        JOIN uber_cueillette_offre o ON c.id_offre = o.id_offre
        JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
        JOIN uber_cueillette_gouvernorat g ON o.id_gouvernorat = g.id_gouvernorat
        WHERE c.id_ouvrier = ?
        ORDER BY c.date_candidature DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$candidatures = $stmt->fetchAll();

// Statistiques
$en_attente = 0;
$acceptees = 0;
$refusees = 0;

foreach ($candidatures as $c) {
    if ($c['decision'] == 'en_attente') $en_attente++;
    elseif ($c['decision'] == 'acceptee') $acceptees++;
    else $refusees++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes candidatures - Uber-Cueillette</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../images/Uber-Cueillette-logo.png">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="../racine/index.html" class="logo">
            <img src="../images/Uber-Cueillette-logo.png" alt="Uber-Cueillette" class="logo-img">
            <span>Uber<span>Cueillette</span></span>
        </a>
        <ul class="nav-menu">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="offres-disponibles.php"><i class="fas fa-search"></i> Offres</a></li>
            <li><a href="mes-candidatures.php" class="active"><i class="fas fa-clock"></i> Mes candidatures</a></li>
            <li><a href="mes-chantiers.php"><i class="fas fa-briefcase"></i> Mes chantiers</a></li>
            <li><a href="profil.php"><i class="fas fa-user"></i> Profil</a></li>
            <li class="nav-buttons">
                <a href="../racine/logout.php" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
            </li>
        </ul>
    </div>
</nav>

<main>
    <div class="container">
        <div class="hero" style="padding: 30px 0; margin-bottom: 30px;">
            <div class="container">
                <div class="hero-content">
                    <h1><i class="fas fa-clock"></i> Mes candidatures</h1>
                    <p>Suivez l'évolution de vos candidatures</p>
                </div>
                <div class="hero-image">
                    <img src="../images/hero-agriculture1.png" alt="Candidatures" onerror="this.style.display='none'">
                </div>
            </div>
        </div>

        <div class="offres-stats">
            <div class="offre-stat-badge">
                <i class="fas fa-clock"></i>
                <div><span><?php echo $en_attente; ?></span><small>En attente</small></div>
            </div>
            <div class="offre-stat-badge">
                <i class="fas fa-check-circle"></i>
                <div><span><?php echo $acceptees; ?></span><small>Acceptées</small></div>
            </div>
            <div class="offre-stat-badge">
                <i class="fas fa-times-circle"></i>
                <div><span><?php echo $refusees; ?></span><small>Refusées</small></div>
            </div>
            <div class="offre-stat-badge">
                <i class="fas fa-file-alt"></i>
                <div><span><?php echo count($candidatures); ?></span><small>Total</small></div>
            </div>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Filtrer par statut</label>
                <select id="filtre-statut">
                    <option value="tous">Tous</option>
                    <option value="en_attente">En attente</option>
                    <option value="acceptee">Acceptées</option>
                    <option value="refusee">Refusées</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Rechercher</label>
                <input type="text" id="recherche" placeholder="Fruit, lieu...">
            </div>
            <div class="filter-actions">
                <button class="btn btn-secondary" id="btn-reinitialiser"><i class="fas fa-redo-alt"></i> Réinitialiser</button>
            </div>
        </div>

        <div id="compteur-resultats" style="margin-bottom: 15px; font-weight: bold;">
            Affichage de <span id="nb-candidatures"><?php echo count($candidatures); ?></span> candidature(s)
        </div>

        <?php if (empty($candidatures)): ?>
            <div class="empty-state">
                <i class="fas fa-clock"></i>
                <h3>Aucune candidature</h3>
                <p>Vous n'avez pas encore postulé à des offres</p>
                <a href="offres-disponibles.php" class="btn btn-primary">Explorer les offres</a>
            </div>
        <?php else: ?>
            <div class="candidatures-list" id="candidatures-list">
                <?php foreach($candidatures as $c):
                    if ($c['decision'] == 'en_attente') {
                        $statut_class = 'badge-warning';
                        $statut_texte = '⏳ En attente';
                    } elseif ($c['decision'] == 'acceptee') {
                        $statut_class = 'badge-success';
                        $statut_texte = '✅ Acceptée';
                    } else {
                        $statut_class = 'badge-danger';
                        $statut_texte = '❌ Refusée';
                    }
                ?>
                <div class="card" data-statut="<?php echo $c['decision']; ?>" data-recherche="<?php echo strtolower($c['type_fruit'] . ' ' . $c['gouvernorat']); ?>">
                    <div class="card-header">
                        <i class="fas fa-apple-alt"></i> <?php echo htmlspecialchars($c['type_fruit'] . ' - ' . $c['gouvernorat']); ?>
                        <span class="badge <?php echo $statut_class; ?>"><?php echo $statut_texte; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="offre-details-grid">
                            <div class="offre-detail">
                                <i class="fas fa-calendar"></i>
                                <div><?php echo date('d/m/Y', strtotime($c['date_debut'])); ?> - <?php echo date('d/m/Y', strtotime($c['date_fin'])); ?></div>
                            </div>
                            <div class="offre-detail">
                                <i class="fas fa-money-bill-wave"></i>
                                <div><?php echo $c['prix_journee']; ?> DT/jour</div>
                            </div>
                            <div class="offre-detail">
                                <i class="fas fa-clock"></i>
                                <div>Postulé le <?php echo date('d/m/Y', strtotime($c['date_candidature'])); ?></div>
                            </div>
                        </div>
                        <div class="offre-location">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($c['adresse']); ?>
                        </div>
                        <?php if ($c['decision'] == 'acceptee' && !empty($c['note'])): ?>
                        <div style="margin-top: 10px;">
                            <span class="badge badge-warning"><i class="fas fa-star"></i> Note: <?php echo $c['note']; ?>/10</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="empty-state-dynamic" id="emptyState" style="display: none;">
            <i class="fas fa-search"></i>
            <h3>Aucune candidature trouvée</h3>
            <button class="btn btn-primary" id="btn-reset-empty">Réinitialiser les filtres</button>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Retour au dashboard</a>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>Uber-Cueillette</h4>
                <p>La plateforme qui connecte agriculteurs et ouvriers agricoles.</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Uber-Cueillette - ISG Tunis</p>
        </div>
    </div>
</footer>

<script src="../js/validation.js"></script>
<script>
function filtrerCandidatures() {
    let statut = document.getElementById('filtre-statut').value;
    let recherche = document.getElementById('recherche').value.toLowerCase();
    let candidatures = document.querySelectorAll('#candidatures-list .card');
    let visibles = 0;
    
    for (let i = 0; i < candidatures.length; i++) {
        let c = candidatures[i];
        let statutOK = (statut === 'tous') || (c.dataset.statut === statut);
        let rechercheOK = (recherche === '') || (c.dataset.recherche.indexOf(recherche) !== -1);
        
        if (statutOK && rechercheOK) {
            c.style.display = 'block';
            visibles++;
        } else {
            c.style.display = 'none';
        }
    }
    
    document.getElementById('nb-candidatures').innerHTML = visibles;
    let emptyState = document.getElementById('emptyState');
    if (visibles === 0) {
        emptyState.style.display = 'block';
    } else {
        emptyState.style.display = 'none';
    }
}

function reinitialiserFiltres() {
    document.getElementById('filtre-statut').value = 'tous';
    document.getElementById('recherche').value = '';
    filtrerCandidatures();
}

document.addEventListener('DOMContentLoaded', function() {
    let filtreStatut = document.getElementById('filtre-statut');
    let recherche = document.getElementById('recherche');
    let btnReinitialiser = document.getElementById('btn-reinitialiser');
    let btnResetEmpty = document.getElementById('btn-reset-empty');
    
    if (filtreStatut) filtreStatut.addEventListener('change', filtrerCandidatures);
    if (recherche) recherche.addEventListener('keyup', filtrerCandidatures);
    if (btnReinitialiser) btnReinitialiser.addEventListener('click', reinitialiserFiltres);
    if (btnResetEmpty) btnResetEmpty.addEventListener('click', reinitialiserFiltres);
});
</script>
</body>
</html>