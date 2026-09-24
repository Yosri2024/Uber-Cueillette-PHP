<?php
/**
 * mes-offres.php - Page des offres de l'agriculteur
 * Affiche toutes les offres de l'agriculteur connecté
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agriculteur') {
    header('Location: ../racine/login.php');
    exit();
}

require_once('../config/database.php');

$id_agriculteur = $_SESSION['user_id'];

$sql = "SELECT o.*, tf.libelle as type_fruit, g.libelle as gouvernorat_libelle,
        (SELECT COUNT(*) FROM uber_cueillette_candidature WHERE id_offre = o.id_offre) as total_candidatures,
        (SELECT COUNT(*) FROM uber_cueillette_candidature WHERE id_offre = o.id_offre AND decision = 'acceptee') as acceptees,
        (SELECT AVG(note) FROM uber_cueillette_candidature WHERE id_offre = o.id_offre AND note IS NOT NULL) as note_moyenne
        FROM uber_cueillette_offre o
        JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
        JOIN uber_cueillette_gouvernorat g ON o.id_gouvernorat = g.id_gouvernorat
        WHERE o.id_agriculteur = ?
        ORDER BY o.date_limite DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_agriculteur]);
$offres = $stmt->fetchAll();

$total_offres = count($offres);
$offres_ouvertes = 0;
$offres_terminees = 0;
$offres_cloturees = 0;
$total_candidatures = 0;

foreach ($offres as $offre) {
    $total_candidatures += $offre['total_candidatures'];
    
    $aujourdhui = date('Y-m-d');
    if ($offre['date_fin'] < $aujourdhui) {
        $offres_terminees++;
    } elseif ($offre['date_limite'] >= $aujourdhui && $offre['date_debut'] >= $aujourdhui) {
        $offres_ouvertes++;
    } else {
        $offres_cloturees++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes offres - Uber-Cueillette</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <li><a href="ajouter-offre.php"><i class="fas fa-plus-circle"></i> Ajouter offre</a></li>
                <li><a href="mes-offres.php" class="active"><i class="fas fa-list"></i> Mes offres</a></li>
                <li><a href="profil.php"><i class="fas fa-user"></i> Profil</a></li>
                <li class="nav-buttons">
                    <a href="../racine/logout.php" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    <button id="theme-toggle" class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </nav>

    <main>
        <div class="container">
            <div class="hero" style="padding: 40px 0; margin-bottom: 40px;">
                <div class="container">
                    <div class="hero-content">
                        <h1><i class="fas fa-seedling"></i> Mes offres de récolte</h1>
                        <p>Gérez l'ensemble de vos offres et suivez leur évolution</p>
                    </div>
                    <div class="hero-image">
                        <img src="../images/hero-agriculture1.png" alt="Mes offres" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>
            
            <div class="offres-stats">
                <div class="offre-stat-badge">
                    <i class="fas fa-seedling"></i>
                    <div>
                        <span><?php echo $total_offres; ?></span>
                        <small>Total offres</small>
                    </div>
                </div>
                <div class="offre-stat-badge">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span><?php echo $offres_ouvertes; ?></span>
                        <small>Offres ouvertes</small>
                    </div>
                </div>
                <div class="offre-stat-badge">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <span><?php echo $offres_terminees; ?></span>
                        <small>Offres terminées</small>
                    </div>
                </div>
                <div class="offre-stat-badge">
                    <i class="fas fa-users"></i>
                    <div>
                        <span><?php echo $total_candidatures; ?></span>
                        <small>Total candidatures</small>
                    </div>
                </div>
            </div>
            
            <div style="text-align: right; margin: 20px 0;">
                <a href="ajouter-offre.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> + Nouvelle offre
                </a>
            </div>

            <?php if (empty($offres)): ?>
                <div class="empty-state">
                    <i class="fas fa-seedling"></i>
                    <h3>Aucune offre pour le moment</h3>
                    <p>Commencez par créer votre première offre de récolte</p>
                    <a href="ajouter-offre.php" class="btn btn-primary">Créer une offre</a>
                </div>
            <?php else: ?>
                <?php foreach($offres as $offre): 
                    $aujourdhui = date('Y-m-d');

                    if ($offre['date_fin'] < $aujourdhui) {
                        $statut = 'terminee';
                        $statut_class = 'info';
                        $statut_texte = '🔵 Terminée';
                    } elseif ($offre['date_limite'] >= $aujourdhui && $offre['date_debut'] >= $aujourdhui) {
                        $statut = 'ouverte';
                        $statut_class = 'success';
                        $statut_texte = '🟢 Ouverte';
                    } else {
                        $statut = 'cloturee';
                        $statut_class = 'warning';
                        $statut_texte = '🟡 Clôturée';
                    }
                    
                    $pourcentage = 0;
                    if ($offre['nombre_ouvriers'] > 0) {
                        $pourcentage = round(($offre['acceptees'] / $offre['nombre_ouvriers']) * 100);
                    }
                ?>
                
                <div class="card offre-card" data-id="<?php echo $offre['id_offre']; ?>" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-apple-alt"></i> 
                            <strong><?php echo htmlspecialchars($offre['type_fruit'] . ' - ' . $offre['gouvernorat_libelle']); ?></strong>
                        </div>
                        <div>
                            <span class="badge badge-<?php echo $statut_class; ?>"><?php echo $statut_texte; ?></span>
                            <span class="badge badge-info">📋 <?php echo $offre['total_candidatures']; ?> candidatures</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 15px;">
                            <div>
                                <small style="color: gray;">📅 Période</small><br>
                                <strong><?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?> - <?php echo date('d/m/Y', strtotime($offre['date_fin'])); ?></strong>
                            </div>
                            <div>
                                <small style="color: gray;">👥 Ouvriers</small><br>
                                <strong><?php echo $offre['acceptees']; ?>/<?php echo $offre['nombre_ouvriers']; ?> acceptés</strong>
                            </div>
                            <div>
                                <small style="color: gray;">💰 Prix</small><br>
                                <strong><?php echo $offre['prix_journee']; ?> DT/jour</strong>
                            </div>
                            <div>
                                <small style="color: gray;">⏳ Date limite</small><br>
                                <strong><?php echo date('d/m/Y', strtotime($offre['date_limite'])); ?></strong>
                            </div>
                        </div>
                        
                        <?php if ($statut == 'ouverte' && $pourcentage > 0): ?>
                        <div style="margin: 15px 0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span>Progression</span>
                                <span><?php echo $pourcentage; ?>%</span>
                            </div>
                            <div style="height: 8px; background: #eee; border-radius: 4px;">
                                <div style="height: 8px; width: <?php echo $pourcentage; ?>%; background: #2ecc71; border-radius: 4px;"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div style="color: gray; font-size: 0.9rem;">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($offre['adresse']); ?>
                        </div>
                        
                        <?php if ($statut == 'terminee' && $offre['note_moyenne']): ?>
                        <div style="margin-top: 10px; color: #f39c12;">
                            <i class="fas fa-star"></i> Note moyenne: <?php echo number_format($offre['note_moyenne'], 1); ?>/10
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer" style="display: flex; gap: 10px; flex-wrap: wrap;">
                        
                        <a href="postulants.php?offre=<?php echo $offre['id_offre']; ?>" class="btn btn-primary">
                            <i class="fas fa-users"></i> Voir postulants (<?php echo $offre['total_candidatures']; ?>)
                        </a>
                        
                        <?php if ($statut == 'terminee'): ?>
                        
                        <a href="noter-ouvriers.php?offre=<?php echo $offre['id_offre']; ?>" class="btn btn-warning" style="background: #f39c12; color: white;">
                            <i class="fas fa-star"></i> ⭐ Noter les ouvriers
                        </a>
                        
                        <button class="btn btn-outline btn-dupliquer" data-id="<?php echo $offre['id_offre']; ?>">
                            <i class="fas fa-copy"></i> Dupliquer
                        </button>
                        
                        <?php endif; ?>
                        
                        <?php if ($statut != 'terminee'): ?>
                        <a href="modifier-offre.php?id=<?php echo $offre['id_offre']; ?>" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($offre['total_candidatures'] == 0): ?>
                        <button class="btn btn-danger btn-supprimer" data-id="<?php echo $offre['id_offre']; ?>">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                        <?php endif; ?>
                        
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

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
                    <p>Plateforme de mise en relation agriculteurs-ouvriers</p>
                </div>
                <div class="footer-col">
                    <h4>Liens rapides</h4>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="ajouter-offre.php">Ajouter offre</a></li>
                        <li><a href="profil.php">Mon profil</a></li>
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
    function supprimerOffre(id, element) {
        if (confirm('⚠️ Voulez-vous vraiment supprimer cette offre ?')) {
            fetch('../traitement/supprimer-offre.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        // Supprimer la carte du DOM
                        let carte = element.closest('.offre-card');
                        if (carte) {
                            carte.remove();
                            
                            // Mettre à jour le compteur total d'offres
                            let totalOffres = document.querySelectorAll('.offre-card').length;
                            let statTotal = document.querySelector('.offre-stat-badge:first-child span');
                            if (statTotal) {
                                statTotal.innerHTML = totalOffres;
                            }
                            
                            // Si plus d'offres, afficher le message
                            if (totalOffres === 0) {
                                location.reload();
                            }
                        }
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('❌ Erreur de connexion');
                });
        }
    }

    function dupliquerOffre(id) {
        window.location.href = 'ajouter-offre.php?duplicate=' + id;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Boutons supprimer
        let boutonsSupprimer = document.querySelectorAll('.btn-supprimer');
        for (let i = 0; i < boutonsSupprimer.length; i++) {
            boutonsSupprimer[i].addEventListener('click', function() {
                let id = this.getAttribute('data-id');
                supprimerOffre(id, this);
            });
        }
        
        // Boutons dupliquer
        let boutonsDupliquer = document.querySelectorAll('.btn-dupliquer');
        for (let i = 0; i < boutonsDupliquer.length; i++) {
            boutonsDupliquer[i].addEventListener('click', function() {
                let id = this.getAttribute('data-id');
                dupliquerOffre(id);
            });
        }
    });
    </script>
</body>
</html>