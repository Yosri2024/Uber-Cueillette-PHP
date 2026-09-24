<?php
// login.php - Page de connexion
require_once('../config/database.php');
session_start();

// Si déjà connecté, redirection
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] == 'agriculteur') {
        header('Location: ../agriculteur/dashboard.php');
    } else {
        header('Location: ../ouvrier/dashboard.php');
    }
    exit();
}

$erreur = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        // Vérifier agriculteur
        $sql = "SELECT * FROM uber_cueillette_agriculteur WHERE pseudo = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && $password == $user['password']) {
            $_SESSION['user_id'] = $user['id_agriculteur'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_pseudo'] = $user['pseudo'];
            $_SESSION['user_type'] = 'agriculteur';
            header('Location: ../agriculteur/dashboard.php');
            exit();
        }
        
        // Vérifier ouvrier
        $sql = "SELECT * FROM uber_cueillette_ouvrier WHERE pseudo = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && $password == $user['password']) {
            $_SESSION['user_id'] = $user['id_ouvrier'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_pseudo'] = $user['pseudo'];
            $_SESSION['user_type'] = 'ouvrier';
            header('Location: ../ouvrier/dashboard.php');
            exit();
        }
        
        $erreur = "Pseudo ou mot de passe incorrect";
    } else {
        $erreur = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uber-Cueillette - Connexion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../images/Uber-Cueillette-logo.png">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="../racine/index.html" class="logo">
                <img src="../images/Uber-Cueillette-logo.png" alt="Uber-Cueillette" class="logo-img">
                <span>Uber<span>Cueillette</span></span>
            </a>
            <ul class="nav-menu">
                <li><a href="../racine/index.html">Accueil</a></li>
                <li><a href="../racine/index.html#fonctionnement">Comment ça marche</a></li>
                <li><a href="../racine/index.html#avantages">Avantages</a></li>
                <li><a href="../racine/index.html#contact">Contact</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="login.php" class="btn btn-outline active">Connexion</a>
                <a href="registre.php" class="btn btn-primary">Inscription</a>
                <button id="theme-toggle" class="theme-toggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container">
            <div class="form-container">
                <h1 class="form-title">Connexion</h1>
                
                <?php if ($erreur): ?>
                    <div class="alert alert-error"><?php echo $erreur; ?></div>
                <?php endif; ?>
                
                <div id="login-alert" class="alert" style="display: none;"></div>
                
                <!-- Formulaire de connexion -->
                <form id="login-form" action="" method="POST" onsubmit="return validateLoginForm(event)">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Pseudo
                        </label>
                        <input type="text" id="username" name="username" placeholder="Entrez votre pseudo">
                        <div class="error-message" id="username-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Mot de passe
                        </label>
                        <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe">
                        <div class="error-message" id="password-error"></div>
                    </div>
                    
                    <div class="forgot-password">
                        <a href="#">Mot de passe oublié ?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>
                </form>
                
                <div class="login-divider">
                    <p>
                        Pas encore de compte ? 
                        <a href="registre.php">Inscrivez-vous</a>
                    </p>
                </div>
                
                <div class="quick-register-buttons">
                    <a href="registre.php?type=agriculteur" class="btn btn-outline">
                        <i class="fas fa-tractor"></i> Agriculteur
                    </a>
                    <a href="registre.php?type=ouvrier" class="btn btn-outline">
                        <i class="fas fa-user-hard-hat"></i> Ouvrier
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
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
                        <li><a href="../racine/index.html">Accueil</a></li>
                        <li><a href="../racine/index.html#fonctionnement">Comment ça marche</a></li>
                        <li><a href="../racine/index.html#avantages">Avantages</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li><i class="fas fa-envelope"></i> contact@uber-cueillette.tn</li>
                        <li><i class="fas fa-phone"></i> +216 XX XXX XXX</li>
                        <li><i class="fas fa-map-marker-alt"></i> Tunis, Tunisie</li>
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

    <!-- ✅ Appel au fichier JS -->
    <script src="../js/validation.js"></script>
</body>
</html>