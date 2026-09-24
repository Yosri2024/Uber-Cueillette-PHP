<?php
/**
 * registre.php - Page d'inscription
 * Permet aux agriculteurs et ouvriers de créer un compte
 */

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

$erreurs = [];
$succes = '';
$type = $_GET['type'] ?? 'agriculteur';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'] ?? 'agriculteur';
    
    // Champs communs
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $cin = trim($_POST['cin'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pseudo = trim($_POST['pseudo'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validations communes
    if (empty($nom)) $erreurs[] = "Le nom est requis";
    if (empty($prenom)) $erreurs[] = "Le prénom est requis";
    
    if (empty($cin)) {
        $erreurs[] = "Le CIN est requis";
    } elseif (!preg_match('/^[0-9]{8}$/', $cin)) {
        $erreurs[] = "Le CIN doit contenir 8 chiffres";
    }
    
    if (empty($email)) {
        $erreurs[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "Email invalide";
    }
    
    if (empty($pseudo)) {
        $erreurs[] = "Le pseudo est requis";
    } elseif (!preg_match('/^[A-Za-z]+$/', $pseudo)) {
        $erreurs[] = "Le pseudo ne doit contenir que des lettres";
    }
    
    if (empty($password)) {
        $erreurs[] = "Le mot de passe est requis";
    } elseif (strlen($password) < 8) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères";
    } elseif (!preg_match('/[$#]$/', $password)) {
        $erreurs[] = "Le mot de passe doit se terminer par $ ou #";
    }
    
    if ($password != $confirm_password) {
        $erreurs[] = "Les mots de passe ne correspondent pas";
    }
    
    // Vérifier si le pseudo ou email existe déjà
    if (empty($erreurs)) {
        try {
            // Vérifier dans agriculteur
            $sql = "SELECT id_agriculteur FROM uber_cueillette_agriculteur WHERE pseudo = ? OR email = ? OR CIN = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pseudo, $email, $cin]);
            if ($stmt->fetch()) {
                $erreurs[] = "Ce CIN, pseudo ou email est déjà utilisé";
            }
            
            if (empty($erreurs)) {
                $sql = "SELECT id_ouvrier FROM uber_cueillette_ouvrier WHERE pseudo = ? OR email = ? OR CIN = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$pseudo, $email, $cin]);
                if ($stmt->fetch()) {
                    $erreurs[] = "Ce CIN, pseudo ou email est déjà utilisé";
                }
            }
        } catch (PDOException $e) {
            $erreurs[] = "Erreur lors de la vérification";
        }
    }
    
    // Si pas d'erreurs, insérer dans la BDD
    if (empty($erreurs)) {
        try {
            if ($type == 'agriculteur') {
                $adresse = trim($_POST['adresse'] ?? '');
                if (empty($adresse)) {
                    $erreurs[] = "L'adresse est requise";
                } else {
                    $sql = "INSERT INTO uber_cueillette_agriculteur (nom, prenom, CIN, email, adresse, pseudo, password) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    // ✅ CORRECTION : Pas de hachage, mot de passe en clair
                    $stmt->execute([$nom, $prenom, $cin, $email, $adresse, $pseudo, $password]);
                    $succes = "✅ Inscription réussie ! Vous pouvez maintenant vous connecter.";
                }
            } else {
                $description = trim($_POST['description'] ?? '');
                $photo = null;
                
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                    $maxSize = 2 * 1024 * 1024;
                    if ($_FILES['photo']['size'] > $maxSize) {
                        $erreurs[] = "La photo ne doit pas dépasser 2Mo";
                    } else {
                        $typePhoto = $_FILES['photo']['type'];
                        if (!in_array($typePhoto, ['image/jpeg', 'image/png'])) {
                            $erreurs[] = "Format accepté : JPG, PNG";
                        } else {
                            $photo = file_get_contents($_FILES['photo']['tmp_name']);
                        }
                    }
                }
                
                if (empty($erreurs)) {
                    $sql = "INSERT INTO uber_cueillette_ouvrier (nom, prenom, CIN, email, photo, description, pseudo, password) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    // ✅ CORRECTION : Pas de hachage, mot de passe en clair
                    $stmt->execute([$nom, $prenom, $cin, $email, $photo, $description, $pseudo, $password]);
                    $succes = "✅ Inscription réussie ! Vous pouvez maintenant vous connecter.";
                }
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $erreurs[] = "❌ Ce CIN, email ou pseudo est déjà utilisé.";
            } else {
                $erreurs[] = "❌ Erreur lors de l'inscription";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uber-Cueillette - Inscription</title>
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
                <a href="login.php" class="btn btn-outline btn-outline-custom">Connexion</a>
                <a href="registre.php" class="btn btn-primary active">Inscription</a>
                <button id="theme-toggle" class="theme-toggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container">
            <div class="form-container" style="max-width: 800px;">
                <h1 class="form-title">
                    <i class="fas fa-user-plus" style="color: var(--primary-color);"></i>
                    Créer un compte
                </h1>
                
                <?php if ($succes): ?>
                    <div class="alert alert-success"><?php echo $succes; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($erreurs)): ?>
                    <div class="alert alert-error">
                        <?php foreach($erreurs as $e): ?>
                            <p><i class="fas fa-exclamation-circle"></i> <?php echo $e; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Sélecteur de type d'utilisateur -->
                <div class="user-type-selector">
                    <a href="?type=agriculteur" class="user-type-btn <?php echo $type == 'agriculteur' ? 'active' : ''; ?>" id="btnAgriculteur">
                        <i class="fas fa-tractor"></i>
                        Je suis Agriculteur
                    </a>
                    <a href="?type=ouvrier" class="user-type-btn <?php echo $type == 'ouvrier' ? 'active' : ''; ?>" id="btnOuvrier">
                        <i class="fas fa-user-hard-hat"></i>
                        Je suis Ouvrier
                    </a>
                </div>

                <!-- Formulaire Agriculteur -->
                <form id="agriculteurForm" method="POST" enctype="multipart/form-data" class="form-section" style="<?php echo $type == 'agriculteur' ? 'display: block;' : 'display: none;'; ?>">
                    <input type="hidden" name="type" value="agriculteur">
                    <h3>
                        <i class="fas fa-tractor"></i>
                        Inscription Agriculteur
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="nom_agri"><i class="fas fa-user"></i> Nom</label>
                            <input type="text" id="nom_agri" name="nom" placeholder="Votre nom" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                            <div class="error-message" id="nom_agri-error"></div>
                        </div>

                        <div class="form-group">
                            <label for="prenom_agri"><i class="fas fa-user"></i> Prénom</label>
                            <input type="text" id="prenom_agri" name="prenom" placeholder="Votre prénom" value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                            <div class="error-message" id="prenom_agri-error"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cin_agri"><i class="fas fa-id-card"></i> CIN (8 chiffres)</label>
                        <input type="text" id="cin_agri" name="cin" placeholder="12345678" maxlength="8" value="<?php echo htmlspecialchars($_POST['cin'] ?? ''); ?>">
                        <small class="password-requirements">8 chiffres uniquement</small>
                        <div class="error-message" id="cin_agri-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="email_agri"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email_agri" name="email" placeholder="exemple@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <div class="error-message" id="email_agri-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="adresse_agri"><i class="fas fa-map-marker-alt"></i> Adresse personnelle</label>
                        <input type="text" id="adresse_agri" name="adresse" placeholder="Votre adresse complète" value="<?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?>">
                        <div class="error-message" id="adresse_agri-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="pseudo_agri"><i class="fas fa-user-tag"></i> Pseudo (lettres uniquement)</label>
                        <input type="text" id="pseudo_agri" name="pseudo" placeholder="monpseudo" value="<?php echo htmlspecialchars($_POST['pseudo'] ?? ''); ?>">
                        <small class="password-requirements">Lettres uniquement, sans chiffres ni caractères spéciaux</small>
                        <div class="error-message" id="pseudo_agri-error"></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="password_agri"><i class="fas fa-lock"></i> Mot de passe</label>
                            <input type="password" id="password_agri" name="password" placeholder="********">
                            <div class="password-requirements">
                                <i class="fas fa-info-circle"></i>
                                Au moins 8 caractères (lettres ou chiffres), doit se terminer par $ ou #
                            </div>
                            <div class="error-message" id="password_agri-error"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password_agri"><i class="fas fa-lock"></i> Confirmer</label>
                            <input type="password" id="confirm_password_agri" name="confirm_password" placeholder="********">
                            <div class="error-message" id="confirm_password_agri-error"></div>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="cgv_agri" name="cgv" <?php echo isset($_POST['cgv']) ? 'checked' : ''; ?>>
                        <label for="cgv_agri">
                            J'accepte les <a href="#">conditions d'utilisation</a>
                        </label>
                        <div class="error-message" id="cgv_agri-error"></div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">
                        <i class="fas fa-user-plus"></i> S'inscrire comme Agriculteur
                    </button>
                </form>

                <!-- Formulaire Ouvrier -->
                <form id="ouvrierForm" method="POST" enctype="multipart/form-data" class="form-section" style="<?php echo $type == 'ouvrier' ? 'display: block;' : 'display: none;'; ?>">
                    <input type="hidden" name="type" value="ouvrier">
                    <h3>
                        <i class="fas fa-user-hard-hat"></i>
                        Inscription Ouvrier
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="nom_ouv"><i class="fas fa-user"></i> Nom</label>
                            <input type="text" id="nom_ouv" name="nom" placeholder="Votre nom" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                            <div class="error-message" id="nom_ouv-error"></div>
                        </div>

                        <div class="form-group">
                            <label for="prenom_ouv"><i class="fas fa-user"></i> Prénom</label>
                            <input type="text" id="prenom_ouv" name="prenom" placeholder="Votre prénom" value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                            <div class="error-message" id="prenom_ouv-error"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cin_ouv"><i class="fas fa-id-card"></i> CIN (8 chiffres)</label>
                        <input type="text" id="cin_ouv" name="cin" placeholder="12345678" maxlength="8" value="<?php echo htmlspecialchars($_POST['cin'] ?? ''); ?>">
                        <small class="password-requirements">8 chiffres uniquement</small>
                        <div class="error-message" id="cin_ouv-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="email_ouv"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email_ouv" name="email" placeholder="exemple@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <div class="error-message" id="email_ouv-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="photo"><i class="fas fa-camera"></i> Photo d'identité</label>
                        <input type="file" id="photo" name="photo" accept="image/*" onchange="previewPhoto(event)">
                        <img id="photo-preview" class="photo-preview" alt="Aperçu photo">
                        <small class="password-requirements">Formats acceptés : JPG, PNG (max 2Mo)</small>
                        <div class="error-message" id="photo-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="description"><i class="fas fa-file-alt"></i> Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Niveau éducatif, expérience, compétences..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <div class="error-message" id="description-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="pseudo_ouv"><i class="fas fa-user-tag"></i> Pseudo (lettres uniquement)</label>
                        <input type="text" id="pseudo_ouv" name="pseudo" placeholder="monpseudo" value="<?php echo htmlspecialchars($_POST['pseudo'] ?? ''); ?>">
                        <small class="password-requirements">Lettres uniquement, sans chiffres ni caractères spéciaux</small>
                        <div class="error-message" id="pseudo_ouv-error"></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="password_ouv"><i class="fas fa-lock"></i> Mot de passe</label>
                            <input type="password" id="password_ouv" name="password" placeholder="********">
                            <div class="password-requirements">
                                <i class="fas fa-info-circle"></i>
                                Au moins 8 caractères (lettres ou chiffres), doit se terminer par $ ou #
                            </div>
                            <div class="error-message" id="password_ouv-error"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password_ouv"><i class="fas fa-lock"></i> Confirmer</label>
                            <input type="password" id="confirm_password_ouv" name="confirm_password" placeholder="********">
                            <div class="error-message" id="confirm_password_ouv-error"></div>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="cgv_ouv" name="cgv" <?php echo isset($_POST['cgv']) ? 'checked' : ''; ?>>
                        <label for="cgv_ouv">
                            J'accepte les <a href="#">conditions d'utilisation</a>
                        </label>
                        <div class="error-message" id="cgv_ouv-error"></div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">
                        <i class="fas fa-user-plus"></i> S'inscrire comme Ouvrier
                    </button>
                </form>
                
                <p style="text-align: center; margin-top: 30px; color: var(--text-light);">
                    <i class="fas fa-question-circle"></i>
                    Déjà inscrit ? 
                    <a href="login.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">
                        Connectez-vous <i class="fas fa-arrow-right"></i>
                    </a>
                </p>
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
    
    <!-- Inclusion du fichier JS externe -->
    <script src="../js/validation.js"></script>
</body>
</html>