<?php
/**
 * profil.php - Page de profil de l'agriculteur
 * Affiche et permet de modifier les informations personnelles
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agriculteur') {
    header('Location: ../racine/login.php');
    exit();
}

require_once('../config/database.php');

$id_agriculteur = $_SESSION['user_id'];

// Récupérer les informations actuelles
$sql = "SELECT * FROM uber_cueillette_agriculteur WHERE id_agriculteur = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_agriculteur]);
$agriculteur = $stmt->fetch();

$message = '';
$erreur = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    
    $erreurs = [];
    
    if (empty($nom)) $erreurs[] = "Le nom est requis";
    if (empty($prenom)) $erreurs[] = "Le prénom est requis";
    if (empty($email)) $erreurs[] = "L'email est requis";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "Email invalide";
    
    if (empty($erreurs)) {
        $sql = "UPDATE uber_cueillette_agriculteur SET nom = ?, prenom = ?, email = ?, adresse = ? WHERE id_agriculteur = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$nom, $prenom, $email, $adresse, $id_agriculteur])) {
            $message = "✅ Profil mis à jour avec succès !";
            // Recharger les données
            $stmt = $pdo->prepare("SELECT * FROM uber_cueillette_agriculteur WHERE id_agriculteur = ?");
            $stmt->execute([$id_agriculteur]);
            $agriculteur = $stmt->fetch();
        } else {
            $erreur = "❌ Erreur lors de la mise à jour";
        }
    } else {
        $erreur = implode("<br>", $erreurs);
    }
}

// Traitement du changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['changer_mdp'])) {
    $ancien = $_POST['ancien_mdp'] ?? '';
    $nouveau = $_POST['nouveau_mdp'] ?? '';
    $confirmation = $_POST['confirmation_mdp'] ?? '';
    
    $erreurs = [];
    
    if (empty($ancien)) $erreurs[] = "L'ancien mot de passe est requis";
    if (empty($nouveau)) $erreurs[] = "Le nouveau mot de passe est requis";
    if ($nouveau != $confirmation) $erreurs[] = "Les mots de passe ne correspondent pas";
    if (strlen($nouveau) < 8) $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères";
    if (!preg_match('/[$#]$/', $nouveau)) $erreurs[] = "Le mot de passe doit se terminer par $ ou #";
    
    if (empty($erreurs)) {
        if ($ancien == $agriculteur['password']) {
            $sql = "UPDATE uber_cueillette_agriculteur SET password = ? WHERE id_agriculteur = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$nouveau, $id_agriculteur])) {
                $message = "✅ Mot de passe changé avec succès !";
            } else {
                $erreur = "❌ Erreur lors du changement";
            }
        } else {
            $erreur = "❌ Ancien mot de passe incorrect";
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
    <title>Mon Profil - Uber-Cueillette</title>
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
                <li><a href="profil.php" class="active"><i class="fas fa-user"></i> Profil</a></li>
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
                        <h1><i class="fas fa-user-circle"></i> Mon Profil</h1>
                        <p>Gérez vos informations personnelles</p>
                    </div>
                    <div class="hero-image">
                        <img src="../images/hero-agriculture1.png" alt="Profil" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($erreur): ?>
                <div class="alert alert-error"><?php echo $erreur; ?></div>
            <?php endif; ?>

            <div class="form-container" style="max-width: 600px;">
                <h2 class="form-title">Informations personnelles</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nom</label>
                        <input type="text" name="nom" value="<?php echo htmlspecialchars($agriculteur['nom']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Prénom</label>
                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($agriculteur['prenom']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> CIN</label>
                        <input type="text" value="<?php echo htmlspecialchars($agriculteur['CIN']); ?>" readonly disabled>
                        <small class="password-requirements">(non modifiable)</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($agriculteur['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Adresse</label>
                        <input type="text" name="adresse" value="<?php echo htmlspecialchars($agriculteur['adresse']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Pseudo</label>
                        <input type="text" value="<?php echo htmlspecialchars($agriculteur['pseudo']); ?>" readonly disabled>
                        <small class="password-requirements">(non modifiable)</small>
                    </div>
                    
                    <button type="submit" name="modifier" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                </form>

                <hr style="margin: 40px 0; border-color: var(--border-color);">

                <h2 class="form-title">Changer le mot de passe</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Ancien mot de passe</label>
                        <input type="password" name="ancien_mdp" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Nouveau mot de passe</label>
                        <input type="password" name="nouveau_mdp" required>
                        <small class="password-requirements">Min 8 caractères, finit par $ ou #</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirmer</label>
                        <input type="password" name="confirmation_mdp" required>
                    </div>
                    
                    <button type="submit" name="changer_mdp" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-key"></i> Changer le mot de passe
                    </button>
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