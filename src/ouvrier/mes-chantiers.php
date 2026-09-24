<?php
/**
 * mes-chantiers.php - Historique des chantiers effectués par l'ouvrier
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ouvrier') {
    header('Location: ../racine/login.php');
    exit();
}

require_once('../config/database.php');

$id_ouvrier = $_SESSION['user_id'];

// Récupérer les chantiers acceptés
$sql = "SELECT c.*, 
        o.adresse, o.date_debut, o.date_fin, o.prix_journee,
        tf.libelle as type_fruit, 
        g.libelle as gouvernorat
        FROM uber_cueillette_candidature c
        JOIN uber_cueillette_offre o ON c.id_offre = o.id_offre
        JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
        JOIN uber_cueillette_gouvernorat g ON o.id_gouvernorat = g.id_gouvernorat
        WHERE c.id_ouvrier = ? AND c.decision = 'acceptee'
        ORDER BY o.date_fin DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$chantiers = $stmt->fetchAll();

// Calculer les statistiques
$total_remuneration = 0;
$total_jours = 0;
foreach ($chantiers as $ch) {
    $total_remuneration += $ch['remuneration'] ?? 0;
    if (!empty($ch['date_debut']) && !empty($ch['date_fin'])) {
        $debut = new DateTime($ch['date_debut']);
        $fin = new DateTime($ch['date_fin']);
        $nb_jours = $debut->diff($fin)->days + 1;
        $total_jours += $nb_jours;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes chantiers - Uber-Cueillette</title>
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
            <li><a href="mes-candidatures.php"><i class="fas fa-clock"></i> Mes candidatures</a></li>
            <li><a href="mes-chantiers.php" class="active"><i class="fas fa-briefcase"></i> Mes chantiers</a></li>
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
                    <h1><i class="fas fa-briefcase"></i> Mes chantiers</h1>
                    <p>Historique de vos travaux et gains</p>
                </div>
                <div class="hero-image">
                    <img src="../images/hero-agriculture1.png" alt="Chantiers" onerror="this.style.display='none'">
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo count($chantiers); ?></span>
                    <span class="stat-label">Chantiers réalisés</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo $total_jours; ?></span>
                    <span class="stat-label">Jours travaillés</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo $total_remuneration; ?> DT</span>
                    <span class="stat-label">Gains totaux</span>
                </div>
            </div>
        </div>

        <?php if (empty($chantiers)): ?>
            <div class="empty-state">
                <i class="fas fa-briefcase"></i>
                <h3>Aucun chantier effectué</h3>
                <p>Vous n'avez pas encore participé à des chantiers</p>
                <a href="offres-disponibles.php" class="btn btn-primary">Explorer les offres</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Offre</th>
                            <th>Période</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>Rémunération</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($chantiers as $ch): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($ch['type_fruit']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($ch['gouvernorat']); ?></small>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($ch['date_debut'])); ?><br>
                                <small>au <?php echo date('d/m/Y', strtotime($ch['date_fin'])); ?></small>
                            </td>
                            <td>
                                <?php if (!empty($ch['note'])): ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-star"></i> <?php echo $ch['note']; ?>/10
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Non noté</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo !empty($ch['commentaire']) ? htmlspecialchars($ch['commentaire']) : '-'; ?>
                            </td>
                            <td>
                                <?php if (!empty($ch['remuneration'])): ?>
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
</body>
</html>