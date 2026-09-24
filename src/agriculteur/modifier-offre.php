<?php
/**
 * modifier-offre.php - Page de modification d'une offre
 * Permet à l'agriculteur de modifier une offre existante
 */

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un agriculteur
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agriculteur') {
    header('Location: ../racine/login.php');
    exit();
}

// Connexion à la base de données
require_once('../config/database.php');

$id_agriculteur = $_SESSION['user_id'];
$id_offre = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_offre <= 0) {
    header('Location: mes-offres.php');
    exit();
}

// Récupérer les informations de l'offre
$sql = "SELECT o.*, tf.libelle as type_fruit, g.libelle as gouvernorat_libelle
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

// Récupérer les listes pour les selects
$types_fruits = $pdo->query("SELECT * FROM uber_cueillette_type_fruit ORDER BY libelle")->fetchAll();
$gouvernorats = $pdo->query("SELECT * FROM uber_cueillette_gouvernorat ORDER BY libelle")->fetchAll();

$message = '';
$erreur = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type_fruit = $_POST['type_fruit'] ?? '';
    $gouvernorat = $_POST['gouvernorat'] ?? '';
    $adresse = trim($_POST['adresse'] ?? '');
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin = $_POST['date_fin'] ?? '';
    $date_limite = $_POST['date_limite'] ?? '';
    $nb_ouvriers = $_POST['nb_ouvriers'] ?? '';
    
    // Récupération du prix avec gestion de l'absence
    if (isset($_POST['prix']) && $_POST['prix'] !== '') {
        $prix = floatval($_POST['prix']);
    } else {
        $prix = $offre['prix_journee'];
    }
    
    $erreurs = [];
    
    // Validations
    if (empty($type_fruit)) $erreurs[] = "Le type de fruit est requis";
    if (empty($gouvernorat)) $erreurs[] = "Le gouvernorat est requis";
    if (empty($adresse)) $erreurs[] = "L'adresse est requise";
    if (empty($date_debut)) $erreurs[] = "La date de début est requise";
    if (empty($date_fin)) $erreurs[] = "La date de fin est requise";
    if (empty($date_limite)) $erreurs[] = "La date limite est requise";
    
    if (empty($nb_ouvriers) || $nb_ouvriers < 1 || $nb_ouvriers > 50) {
        $erreurs[] = "Le nombre d'ouvriers doit être entre 1 et 50";
    }
    
    // Vérification du prix
    if ($prix < 10 || $prix > 500) {
        $erreurs[] = "Le prix doit être entre 10 et 500 DT";
    }
    
    // Validation des dates
    if (!empty($date_debut) && !empty($date_fin) && !empty($date_limite)) {
        $debut = new DateTime($date_debut);
        $fin = new DateTime($date_fin);
        $limite = new DateTime($date_limite);
        $aujourdhui = new DateTime();
        $aujourdhui->setTime(0, 0, 0);
        
        if ($limite < $aujourdhui && $offre['date_limite'] != $date_limite) {
            $erreurs[] = "La date limite doit être dans le futur";
        }
        
        if ($fin <= $debut) {
            $erreurs[] = "La date de fin doit être après la date de début";
        }
        
        if ($limite > $debut) {
            $erreurs[] = "La date limite doit être avant le début de la récolte";
        }
    }
    
    if (empty($erreurs)) {
        try {
            $sql = "UPDATE uber_cueillette_offre SET
                    id_type_fruit = ?,
                    id_gouvernorat = ?,
                    adresse = ?,
                    date_debut = ?,
                    date_fin = ?,
                    date_limite = ?,
                    nombre_ouvriers = ?,
                    prix_journee = ?
                    WHERE id_offre = ? AND id_agriculteur = ?";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $type_fruit, 
                $gouvernorat, 
                $adresse, 
                $date_debut, 
                $date_fin,
                $date_limite, 
                $nb_ouvriers, 
                $prix,
                $id_offre, 
                $id_agriculteur
            ]);
            
            if ($result) {
                $message = "✅ Offre modifiée avec succès !";
                
                // Recharger l'offre
                $stmt = $pdo->prepare("SELECT o.*, tf.libelle as type_fruit, g.libelle as gouvernorat_libelle
                                       FROM uber_cueillette_offre o
                                       JOIN uber_cueillette_type_fruit tf ON o.id_type_fruit = tf.id_type_fruit
                                       JOIN uber_cueillette_gouvernorat g ON o.id_gouvernorat = g.id_gouvernorat
                                       WHERE o.id_offre = ?");
                $stmt->execute([$id_offre]);
                $offre = $stmt->fetch();
            } else {
                $erreur = "❌ Aucune modification effectuée";
            }
            
        } catch (PDOException $e) {
            $erreur = "Erreur lors de la modification : " . $e->getMessage();
        }
    } else {
        $erreur = implode("<br>", $erreurs);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une offre - Uber-Cueillette</title>
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
            <div class="hero" style="padding: 30px 0; margin-bottom: 30px;">
                <div class="container">
                    <div class="hero-content">
                        <h1><i class="fas fa-edit"></i> Modifier l'offre</h1>
                        <p><?php echo htmlspecialchars($offre['type_fruit'] . ' - ' . $offre['gouvernorat_libelle']); ?></p>
                    </div>
                    <div class="hero-image">
                        <img src="../images/hero-agriculture1.png" alt="Modifier offre" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($erreur): ?>
                <div class="alert alert-error"><?php echo $erreur; ?></div>
            <?php endif; ?>

            <div class="form-container" style="max-width: 800px;">
                <form method="POST" action="">
                    <!-- Type de fruit -->
                    <div class="form-group">
                        <label for="type_fruit"><i class="fas fa-apple-alt"></i> Type de fruit</label>
                        <select id="type_fruit" name="type_fruit" required>
                            <option value="">Sélectionnez</option>
                            <?php foreach($types_fruits as $fruit): ?>
                                <option value="<?php echo $fruit['id_type_fruit']; ?>" 
                                    <?php echo ($fruit['id_type_fruit'] == $offre['id_type_fruit']) ? 'selected' : ''; ?>>
                                    <?php echo $fruit['libelle']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Gouvernorat -->
                    <div class="form-group">
                        <label for="gouvernorat"><i class="fas fa-map-marker-alt"></i> Gouvernorat</label>
                        <select id="gouvernorat" name="gouvernorat" required>
                            <option value="">Sélectionnez</option>
                            <?php foreach($gouvernorats as $gouv): ?>
                                <option value="<?php echo $gouv['id_gouvernorat']; ?>" 
                                    <?php echo ($gouv['id_gouvernorat'] == $offre['id_gouvernorat']) ? 'selected' : ''; ?>>
                                    <?php echo $gouv['libelle']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Adresse -->
                    <div class="form-group">
                        <label for="adresse"><i class="fas fa-location-dot"></i> Adresse</label>
                        <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($offre['adresse']); ?>" required>
                    </div>

                    <!-- Dates -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="date_debut">Date début</label>
                            <input type="date" id="date_debut" name="date_debut" value="<?php echo $offre['date_debut']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="date_fin">Date fin</label>
                            <input type="date" id="date_fin" name="date_fin" value="<?php echo $offre['date_fin']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="date_limite">Date limite de postulation</label>
                        <input type="date" id="date_limite" name="date_limite" value="<?php echo $offre['date_limite']; ?>" required>
                    </div>

                    <!-- Détails -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="nb_ouvriers">Nombre d'ouvriers</label>
                            <input type="number" id="nb_ouvriers" name="nb_ouvriers" min="1" max="50" 
                                   value="<?php echo $offre['nombre_ouvriers']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="prix">Prix journalier (DT)</label>
                            <?php 
                                // ✅ CORRECTION : Gérer les valeurs NULL ou vides
                                $prix_value = (!empty($offre['prix_journee']) && $offre['prix_journee'] >= 10) ? $offre['prix_journee'] : 50;
                            ?>
                            <input type="number" id="prix" name="prix" min="10" max="500" step="5" 
                                   value="<?php echo $prix_value; ?>" required>
                            <small class="password-requirements">Prix entre 10 DT et 500 DT</small>
                        </div>
                    </div>

                    <!-- Boutons -->
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary" style="flex: 2;">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                        <a href="mes-offres.php" class="btn btn-secondary" style="flex: 1; color:var(--primary-color);">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>
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
                <p>&copy; 2026 Uber-Cueillette</p>
            </div>
        </div>
    </footer>

    <script src="../js/validation.js"></script>
</body>
</html>