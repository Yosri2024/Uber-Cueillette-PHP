<?php
/**
 * offres-disponibles.php - Liste des offres disponibles pour l'ouvrier
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ouvrier') {
    header('Location: ../racine/login.php');
    exit();
}

require_once('../config/database.php');

$id_ouvrier = $_SESSION['user_id'];

// ============================================================================
// TRAITEMENT POSTULER (intégré dans la même page)
// ============================================================================

$message = '';
$erreur = '';

if (isset($_GET['action']) && $_GET['action'] === 'postuler' && isset($_GET['id'])) {
    $id_offre = intval($_GET['id']);

    // Vérifier que l'offre existe et est encore ouverte
    $check_offre = $pdo->prepare("SELECT * FROM uber_cueillette_offre WHERE id_offre = ? AND date_limite >= CURDATE()");
    $check_offre->execute([$id_offre]);
    $offre_check = $check_offre->fetch();

    if (!$offre_check) {
        $erreur = "❌ Cette offre n'est plus disponible.";
    } else {
        // Vérifier que l'ouvrier n'a pas déjà postulé
        $check_cand = $pdo->prepare("SELECT id_candidature FROM uber_cueillette_candidature WHERE id_offre = ? AND id_ouvrier = ?");
        $check_cand->execute([$id_offre, $id_ouvrier]);

        if ($check_cand->fetch()) {
            $erreur = "❌ Vous avez déjà postulé à cette offre.";
        } else {
            // Vérifier que les places ne sont pas pleines
            $check_places = $pdo->prepare("SELECT COUNT(*) as acceptes FROM uber_cueillette_candidature WHERE id_offre = ? AND decision = 'acceptee'");
            $check_places->execute([$id_offre]);
            $places = $check_places->fetch();

            if ($places['acceptes'] >= $offre_check['nombre_ouvriers']) {
                $erreur = "❌ Cette offre est complète.";
            } else {
                // Insérer la candidature
                $insert = $pdo->prepare("INSERT INTO uber_cueillette_candidature (id_offre, id_ouvrier, decision) VALUES (?, ?, 'en_attente')");
                if ($insert->execute([$id_offre, $id_ouvrier])) {
                    $message = "✅ Candidature envoyée avec succès ! L'agriculteur examinera votre profil.";
                } else {
                    $erreur = "❌ Erreur lors de l'envoi de la candidature.";
                }
            }
        }
    }
}

// ============================================================================
// RÉCUPÉRER LES OFFRES DISPONIBLES
// ============================================================================

$sql = "SELECT o.*, tf.libelle as type_fruit, g.libelle as gouvernorat,
        (SELECT COUNT(*) FROM uber_cueillette_candidature WHERE id_offre = o.id_offre AND decision = 'acceptee') as places_prises,
        (SELECT COUNT(*) FROM uber_cueillette_candidature WHERE id_offre = o.id_offre AND id_ouvrier = ?) as a_postule
        FROM uber_cueillette_offre o
        JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
        JOIN uber_cueillette_gouvernorat g ON o.id_gouvernorat = g.id_gouvernorat
        WHERE o.date_limite >= CURDATE() AND o.date_fin >= CURDATE()
        AND (SELECT COUNT(*) FROM uber_cueillette_candidature WHERE id_offre = o.id_offre AND decision = 'acceptee') < o.nombre_ouvriers
        ORDER BY o.date_limite ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$offres = $stmt->fetchAll();

// Filtres
$types_fruits = $pdo->query("SELECT * FROM uber_cueillette_type_fruit ORDER BY libelle")->fetchAll();
$gouvernorats = $pdo->query("SELECT * FROM uber_cueillette_gouvernorat ORDER BY libelle")->fetchAll();

$total_offres = count($offres);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres disponibles - Uber-Cueillette</title>
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
            <li><a href="offres-disponibles.php" class="active"><i class="fas fa-search"></i> Offres</a></li>
            <li><a href="mes-candidatures.php"><i class="fas fa-clock"></i> Mes candidatures</a></li>
            <li><a href="mes-chantiers.php"><i class="fas fa-briefcase"></i> Mes chantiers</a></li>
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

<main>
    <div class="container">

        <div class="hero" style="padding: 30px 0; margin-bottom: 30px;">
            <div class="container">
                <div class="hero-content">
                    <h1><i class="fas fa-search"></i> Offres de récolte</h1>
                    <p>Trouvez le chantier qui correspond à vos compétences</p>
                </div>
                <div class="hero-image">
                    <img src="../images/hero-agriculture1.png" alt="Offres" onerror="this.style.display='none'">
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?php echo $erreur; ?></div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="offres-stats">
            <div class="offre-stat-badge">
                <i class="fas fa-seedling"></i>
                <div>
                    <span><?php echo $total_offres; ?></span>
                    <small>Offres disponibles</small>
                </div>
            </div>
            <div class="offre-stat-badge">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <span><?php echo count($gouvernorats); ?></span>
                    <small>Gouvernorats</small>
                </div>
            </div>
            <div class="offre-stat-badge">
                <i class="fas fa-apple-alt"></i>
                <div>
                    <span><?php echo count($types_fruits); ?></span>
                    <small>Types de fruits</small>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters">
            <div class="filter-group">
                <label><i class="fas fa-apple-alt"></i> Type de fruit</label>
                <select id="filtre-fruit">
                    <option value="tous">Tous</option>
                    <?php foreach($types_fruits as $fruit): ?>
                        <option value="<?php echo strtolower($fruit['libelle']); ?>">
                            <?php echo $fruit['libelle']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><i class="fas fa-map-marker-alt"></i> Gouvernorat</label>
                <select id="filtre-gouvernorat">
                    <option value="tous">Tous</option>
                    <?php foreach($gouvernorats as $gouv): ?>
                        <option value="<?php echo strtolower($gouv['libelle']); ?>">
                            <?php echo $gouv['libelle']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><i class="fas fa-sort-amount-down"></i> Trier par prix</label>
                <select id="filtre-prix">
                    <option value="default">Par défaut</option>
                    <option value="asc">Prix croissant</option>
                    <option value="desc">Prix décroissant</option>
                </select>
            </div>

            <div class="filter-actions">
                <button class="btn btn-outline" id="btn-reinitialiser">
                    <i class="fas fa-redo-alt"></i> Réinitialiser
                </button>
            </div>
        </div>

        <!-- Compteur -->
        <div style="margin-bottom: 15px; font-weight: bold;">
            Affichage de <span id="nb-offres"><?php echo $total_offres; ?></span> offre(s)
        </div>

        <!-- Liste des offres -->
        <?php if (empty($offres)): ?>
            <div class="empty-state">
                <i class="fas fa-seedling"></i>
                <h3>Aucune offre disponible</h3>
                <p>Revenez plus tard, de nouvelles offres seront bientôt publiées</p>
            </div>
        <?php else: ?>
            <div class="card-grid" id="offres-grid">
                <?php foreach($offres as $offre):
                    $places_restantes = $offre['nombre_ouvriers'] - $offre['places_prises'];
                    $a_deja_postule = ($offre['a_postule'] > 0);
                ?>
                <div class="card offre-card"
                    data-fruit="<?php echo strtolower($offre['type_fruit']); ?>"
                    data-gouvernorat="<?php echo strtolower($offre['gouvernorat']); ?>"
                    data-prix="<?php echo $offre['prix_journee']; ?>">

                    <div class="card-header">
                        <i class="fas fa-apple-alt"></i>
                        <?php echo htmlspecialchars($offre['type_fruit'] . ' - ' . $offre['gouvernorat']); ?>
                        <?php if ($a_deja_postule): ?>
                            <span class="badge badge-info" style="margin-left: auto;">
                                <i class="fas fa-check"></i> Postulé
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <div class="offre-details-grid">
                            <div class="offre-detail">
                                <i class="fas fa-calendar-alt"></i>
                                <div>
                                    <small>Période</small>
                                    <strong>
                                        <?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?> →
                                        <?php echo date('d/m/Y', strtotime($offre['date_fin'])); ?>
                                    </strong>
                                </div>
                            </div>
                            <div class="offre-detail">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>
                                    <small>Prix journalier</small>
                                    <strong><?php echo $offre['prix_journee']; ?> DT/jour</strong>
                                </div>
                            </div>
                            <div class="offre-detail">
                                <i class="fas fa-users"></i>
                                <div>
                                    <small>Places restantes</small>
                                    <strong><?php echo $places_restantes; ?> / <?php echo $offre['nombre_ouvriers']; ?></strong>
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

                        <div class="offre-location">
                            <i class="fas fa-location-dot"></i>
                            <?php echo htmlspecialchars($offre['adresse']); ?>
                        </div>
                    </div>

                    <div class="card-footer">
                        <?php if ($a_deja_postule): ?>
                            <span class="badge badge-info">
                                <i class="fas fa-check-circle"></i> Déjà postulé
                            </span>
                        <?php else: ?>
                            <!-- ✅ CORRECTION : action=postuler dans la même page, pas de fichier séparé -->
                            <a href="offres-disponibles.php?action=postuler&id=<?php echo $offre['id_offre']; ?>"
                               class="btn btn-primary"
                               onclick="return confirm('Voulez-vous postuler à cette offre ?')">
                                <i class="fas fa-paper-plane"></i> Postuler
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Aucun résultat après filtre -->
        <div class="empty-state-dynamic" id="emptyState" style="display: none;">
            <i class="fas fa-search"></i>
            <h3>Aucune offre trouvée</h3>
            <p>Aucune offre ne correspond à vos critères</p>
            <button class="btn btn-primary" id="btn-reset-empty">Réinitialiser les filtres</button>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Retour au dashboard
            </a>
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
            <div class="footer-col">
                <h4>Liens rapides</h4>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="mes-candidatures.php">Mes candidatures</a></li>
                    <li><a href="mes-chantiers.php">Mes chantiers</a></li>
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
function filtrerOffres() {
    let fruit = document.getElementById('filtre-fruit').value;
    let gouvernorat = document.getElementById('filtre-gouvernorat').value;
    let triPrix = document.getElementById('filtre-prix').value;

    let offres = document.querySelectorAll('#offres-grid .offre-card');
    let offresArray = Array.from(offres);
    let visibles = 0;

    for (let i = 0; i < offresArray.length; i++) {
        let offre = offresArray[i];
        let fruitOK = (fruit === 'tous') || (offre.dataset.fruit === fruit);
        let gouvOK = (gouvernorat === 'tous') || (offre.dataset.gouvernorat === gouvernorat);

        if (fruitOK && gouvOK) {
            offre.style.display = 'block';
            visibles++;
        } else {
            offre.style.display = 'none';
        }
    }

    if (triPrix !== 'default') {
        let grid = document.getElementById('offres-grid');
        let visiblesArray = offresArray.filter(function(o) { return o.style.display !== 'none'; });
        visiblesArray.sort(function(a, b) {
            let prixA = parseFloat(a.dataset.prix);
            let prixB = parseFloat(b.dataset.prix);
            return triPrix === 'asc' ? prixA - prixB : prixB - prixA;
        });
        for (let i = 0; i < visiblesArray.length; i++) {
            grid.appendChild(visiblesArray[i]);
        }
    }

    document.getElementById('nb-offres').innerHTML = visibles;
    document.getElementById('emptyState').style.display = visibles === 0 ? 'block' : 'none';
}

function reinitialiserFiltres() {
    document.getElementById('filtre-fruit').value = 'tous';
    document.getElementById('filtre-gouvernorat').value = 'tous';
    document.getElementById('filtre-prix').value = 'default';
    filtrerOffres();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtre-fruit').addEventListener('change', filtrerOffres);
    document.getElementById('filtre-gouvernorat').addEventListener('change', filtrerOffres);
    document.getElementById('filtre-prix').addEventListener('change', filtrerOffres);
    document.getElementById('btn-reinitialiser').addEventListener('click', reinitialiserFiltres);
    document.getElementById('btn-reset-empty').addEventListener('click', reinitialiserFiltres);
});
</script>

</body>
</html>