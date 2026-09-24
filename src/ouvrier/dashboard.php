<?php
/**
 * dashboard.php - Page d'accueil de l'ouvrier
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ouvrier') {
    header('Location: ../racine/login.php');
    exit();
}

require_once('../config/database.php');

$id_ouvrier = $_SESSION['user_id'];

// Récupérer les infos de l'ouvrier
$sql = "SELECT * FROM uber_cueillette_ouvrier WHERE id_ouvrier = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$ouvrier = $stmt->fetch();

// Statistiques
$sql = "SELECT COUNT(*) as total FROM uber_cueillette_candidature WHERE id_ouvrier = ? AND decision = 'acceptee'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$chantiers_realises = $stmt->fetch()['total'] ?? 0;

$sql = "SELECT SUM(remuneration) as total FROM uber_cueillette_candidature WHERE id_ouvrier = ? AND decision = 'acceptee' AND remuneration IS NOT NULL";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$gains_totaux = $stmt->fetch()['total'] ?? 0;

$sql = "SELECT AVG(note) as moyenne FROM uber_cueillette_candidature WHERE id_ouvrier = ? AND note IS NOT NULL";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$note_moyenne = $stmt->fetch()['moyenne'] ?? 0;
$note_moyenne = $note_moyenne ? number_format($note_moyenne, 1) : 'N/A';

$sql = "SELECT COUNT(*) as total FROM uber_cueillette_candidature WHERE id_ouvrier = ? AND decision = 'en_attente'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$candidatures_attente = $stmt->fetch()['total'] ?? 0;

// Dernières offres disponibles
$sql = "SELECT o.*, tf.libelle as type_fruit, g.libelle as gouvernorat,
        (SELECT COUNT(*) FROM uber_cueillette_candidature WHERE id_offre = o.id_offre AND decision = 'acceptee') as places_prises
        FROM uber_cueillette_offre o
        JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
        JOIN uber_cueillette_gouvernorat g ON o.id_gouvernorat = g.id_gouvernorat
        WHERE o.date_limite >= CURDATE() AND o.date_fin >= CURDATE()
        AND (SELECT COUNT(*) FROM uber_cueillette_candidature WHERE id_offre = o.id_offre AND decision = 'acceptee') < o.nombre_ouvriers
        ORDER BY o.date_limite ASC
        LIMIT 5";
$stmt = $pdo->query($sql);
$offres_recentes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ouvrier - Uber-Cueillette</title>
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="offres-disponibles.php"><i class="fas fa-search"></i> Offres</a></li>
            <li><a href="mes-candidatures.php"><i class="fas fa-clock"></i> Mes candidatures</a></li>
            <li><a href="mes-chantiers.php"><i class="fas fa-briefcase"></i> Mes chantiers</a></li>
            <li><a href="profil.php"><i class="fas fa-user"></i> Profile</a></li>
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
                    <h1><i class="fas fa-chart-line"></i> Dashboard Ouvrier</h1>
                    <p>Bienvenue <strong><?php echo htmlspecialchars($ouvrier['prenom'] . ' ' . $ouvrier['nom']); ?></strong> !</p>
                </div>
                <div class="hero-image">
                    <img src="../images/hero-agriculture1.png" alt="Dashboard" onerror="this.style.display='none'">
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo $chantiers_realises; ?></span>
                    <span class="stat-label">Chantiers réalisés</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo $gains_totaux; ?> DT</span>
                    <span class="stat-label">Gains totaux</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo $note_moyenne; ?>/10</span>
                    <span class="stat-label">Note moyenne</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo $candidatures_attente; ?></span>
                    <span class="stat-label">Candidatures en attente</span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin: 30px 0; flex-wrap: wrap;">
            <a href="offres-disponibles.php" class="btn btn-primary btn-large">
                <i class="fas fa-search"></i> Explorer les offres
            </a>
            <a href="mes-candidatures.php" class="btn btn-outline btn-large">
                <i class="fas fa-clock"></i> Voir mes candidatures
            </a>
        </div>

        <h2 class="section-title">Offres disponibles</h2>

        <?php if (empty($offres_recentes)): ?>
            <div class="empty-state">
                <i class="fas fa-seedling"></i>
                <h3>Aucune offre disponible</h3>
                <p>Revenez plus tard, de nouvelles offres seront bientôt publiées</p>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach($offres_recentes as $offre): 
                    $places_restantes = $offre['nombre_ouvriers'] - $offre['places_prises'];
                ?>
                <div class="card offre-card">
                    <div class="card-header">
                        <i class="fas fa-apple-alt"></i>
                        <?php echo htmlspecialchars($offre['type_fruit']); ?>
                    </div>
                    <div class="card-body">
                        <div class="offre-details-grid">
                            <div class="offre-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <div><?php echo htmlspecialchars($offre['gouvernorat']); ?></div>
                            </div>
                            <div class="offre-detail">
                                <i class="fas fa-calendar"></i>
                                <div><?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?> - <?php echo date('d/m/Y', strtotime($offre['date_fin'])); ?></div>
                            </div>
                            <div class="offre-detail">
                                <i class="fas fa-money-bill-wave"></i>
                                <div><?php echo $offre['prix_journee']; ?> DT/jour</div>
                            </div>
                            <div class="offre-detail">
                                <i class="fas fa-users"></i>
                                <div><?php echo $places_restantes; ?> place(s)</div>
                            </div>
                        </div>
                        <div class="offre-location">
                            <i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($offre['adresse']); ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="offres-disponibles.php?postuler=<?php echo $offre['id_offre']; ?>" class="btn btn-primary" onclick="return confirm('Postuler à cette offre ?')">
                            <i class="fas fa-paper-plane"></i> Postuler
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="offres-disponibles.php" class="btn btn-outline">Voir toutes les offres</a>
            </div>
        <?php endif; ?>
        <div style="margin-top: 30px; padding: 15px; background: var(--card-bg); border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
            <p><i class="fas fa-info-circle"></i> Connecté en tant que <strong><?php echo htmlspecialchars($ouvrier['pseudo']); ?></strong> (<?php echo htmlspecialchars($ouvrier['email']); ?>)</p>
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
                    <li><a href="offres-disponibles.php">Offres</a></li>
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
</body>
</html>