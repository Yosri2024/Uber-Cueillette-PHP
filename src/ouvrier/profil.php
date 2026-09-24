<?php
/**
 * profil.php - Page de profil de l'ouvrier
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ouvrier') {
    header('Location: ../racine/login.php');
    exit();
}

require_once('../config/database.php');

$id_ouvrier = $_SESSION['user_id'];

// Récupérer les informations actuelles
$sql = "SELECT * FROM uber_cueillette_ouvrier WHERE id_ouvrier = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_ouvrier]);
$ouvrier = $stmt->fetch();

if (!$ouvrier) {
    session_destroy();
    header('Location: ../racine/login.php');
    exit();
}

// Gestion de la photo
$photo_url = '../images/default-profile.jpg';
if (!empty($ouvrier['photo'])) {
    $photo_data = base64_encode($ouvrier['photo']);
    $photo_url = 'data:image/jpeg;base64,' . $photo_data;
}

$message = '';
$erreur = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $erreurs = [];
    if (empty($nom)) $erreurs[] = "Le nom est requis";
    if (empty($prenom)) $erreurs[] = "Le prénom est requis";
    if (empty($email)) $erreurs[] = "L'email est requis";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "Email invalide";

    // Vérifier si email déjà utilisé par quelqu'un d'autre
    if (empty($erreurs)) {
        $check = $pdo->prepare("SELECT id_ouvrier FROM uber_cueillette_ouvrier WHERE email = ? AND id_ouvrier != ?");
        $check->execute([$email, $id_ouvrier]);
        if ($check->fetch()) {
            $erreurs[] = "Cet email est déjà utilisé";
        }
    }

    if (empty($erreurs)) {
        try {
            $sql = "UPDATE uber_cueillette_ouvrier SET nom = ?, prenom = ?, email = ?, description = ? WHERE id_ouvrier = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$nom, $prenom, $email, $description, $id_ouvrier])) {
                $message = "✅ Profil mis à jour avec succès !";
                $_SESSION['user_nom'] = $nom;
                $_SESSION['user_prenom'] = $prenom;
                $ouvrier['nom'] = $nom;
                $ouvrier['prenom'] = $prenom;
                $ouvrier['email'] = $email;
                $ouvrier['description'] = $description;
            } else {
                $erreur = "❌ Erreur lors de la mise à jour";
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $erreur = "❌ Cet email est déjà utilisé";
            } else {
                $erreur = "❌ Erreur : " . $e->getMessage();
            }
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
        // ✅ CORRECTION : comparaison en clair, cohérent avec la BDD
        if ($ancien == $ouvrier['password']) {
            try {
                $sql = "UPDATE uber_cueillette_ouvrier SET password = ? WHERE id_ouvrier = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$nouveau, $id_ouvrier])) {
                    $message = "✅ Mot de passe changé avec succès !";
                    $ouvrier['password'] = $nouveau;
                } else {
                    $erreur = "❌ Erreur lors du changement";
                }
            } catch (PDOException $e) {
                $erreur = "❌ Erreur : " . $e->getMessage();
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
            <li><a href="mes-chantiers.php"><i class="fas fa-briefcase"></i> Mes chantiers</a></li>
            <li><a href="profil.php" class="active"><i class="fas fa-user"></i> Profil</a></li>
            <li class="nav-buttons">
                <a href="../racine/logout.php" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
            </li>
        </ul>
    </div>
</nav>

<main>
    <div class="container">

        <!-- Hero -->
        <div class="hero" style="padding: 30px 0; margin-bottom: 30px;">
            <div class="container">
                <div class="hero-content" style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">
                    <div style="flex-shrink: 0;">
                        <img src="<?php echo $photo_url; ?>" alt="Photo de profil"
                            style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color);"
                            onerror="this.src='../images/default-profile.jpg'">
                    </div>
                    <div>
                        <h1 style="font-size: 2rem;"><i class="fas fa-user-circle"></i> Mon Profil</h1>
                        <p>Bonjour <strong><?php echo htmlspecialchars($ouvrier['prenom'] . ' ' . $ouvrier['nom']); ?></strong> !</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="alert alert-error"><?php echo $erreur; ?></div>
        <?php endif; ?>

        <div class="form-container" style="max-width: 600px;">

            <!-- Formulaire modification profil -->
            <h2 class="form-title">Informations personnelles</h2>

            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nom</label>
                    <input type="text" name="nom" value="<?php echo htmlspecialchars($ouvrier['nom']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Prénom</label>
                    <input type="text" name="prenom" value="<?php echo htmlspecialchars($ouvrier['prenom']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> CIN</label>
                    <input type="text" value="<?php echo htmlspecialchars($ouvrier['CIN']); ?>" readonly disabled>
                    <small class="password-requirements">(non modifiable)</small>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($ouvrier['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-file-alt"></i> Description</label>
                    <textarea name="description" rows="4" placeholder="Niveau éducatif, expérience, compétences..."><?php echo htmlspecialchars($ouvrier['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Pseudo</label>
                    <input type="text" value="<?php echo htmlspecialchars($ouvrier['pseudo']); ?>" readonly disabled>
                    <small class="password-requirements">(non modifiable)</small>
                </div>

                <button type="submit" name="modifier" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
            </form>

            <hr style="margin: 40px 0; border-color: var(--border-color);">

            <!-- Formulaire changement mot de passe -->
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
                    <label><i class="fas fa-lock"></i> Confirmer le mot de passe</label>
                    <input type="password" name="confirmation_mdp" required>
                </div>

                <button type="submit" name="changer_mdp" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-key"></i> Changer le mot de passe
                </button>
            </form>

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
                    <li><a href="offres-disponibles.php">Offres disponibles</a></li>
                    <li><a href="mes-candidatures.php">Mes candidatures</a></li>
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