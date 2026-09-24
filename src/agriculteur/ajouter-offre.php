<?php
    /**
     * ajouter-offre.php - Page d'ajout d'offre
     * Permet à l'agriculteur de créer une nouvelle offre
     */

    // ============================================================================
    // PARTIE 1: DÉMARRAGE DE LA SESSION ET VÉRIFICATION
    // ============================================================================

    session_start();

    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agriculteur') {
        header('Location: ../racine/login.php');
        exit();
    }

    // ============================================================================
    // PARTIE 2: CONNEXION À LA BASE DE DONNÉES
    // ============================================================================

    require_once('../config/database.php');

    $id_agriculteur = $_SESSION['user_id'];

    // ============================================================================
    // PARTIE 3: RÉCUPÉRER LES MESSAGES D'ERREUR/SUCCÈS (S'IL Y EN A)
    // ============================================================================

    $erreurs = $_SESSION['erreurs'] ?? [];
    $success = $_SESSION['success'] ?? '';

    // Effacer les messages après les avoir récupérés
    unset($_SESSION['erreurs']);
    unset($_SESSION['success']);

    // ============================================================================
    // PARTIE 4: RÉCUPÉRER LES DONNÉES PRÉCÉDENTES (EN CAS D'ERREUR)
    // ============================================================================

    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);

    // ============================================================================
    // PARTIE 5: RÉCUPÉRER LES LISTES POUR LES SELECTS
    // ============================================================================

    // Types de fruits depuis la BDD
    $sql = "SELECT * FROM uber_cueillette_type_fruit ORDER BY libelle";
    $stmt = $pdo->query($sql);
    $types_fruits = $stmt->fetchAll();

    // Gouvernorats depuis la BDD
    $sql = "SELECT * FROM uber_cueillette_gouvernorat ORDER BY libelle";
    $stmt = $pdo->query($sql);
    $gouvernorats = $stmt->fetchAll();

    // Si les tables sont vides, on utilise des valeurs par défaut
    if (empty($types_fruits)) {
        $types_fruits = [
            ['id_type_fruit' => 1, 'libelle' => 'Olives'],
            ['id_type_fruit' => 2, 'libelle' => 'Agrumes'],
            ['id_type_fruit' => 3, 'libelle' => 'Tomates'],
            ['id_type_fruit' => 4, 'libelle' => 'Raisins'],
            ['id_type_fruit' => 5, 'libelle' => 'Pêches'],
            ['id_type_fruit' => 6, 'libelle' => 'Pommes'],
            ['id_type_fruit' => 7, 'libelle' => 'Figues'],
            ['id_type_fruit' => 8, 'libelle' => 'Grenades'],
            ['id_type_fruit' => 9, 'libelle' => 'Autres']
        ];
    }

    if (empty($gouvernorats)) {
        $gouvernorats = [
            ['id_gouvernorat' => 1, 'libelle' => 'Tunis'],
            ['id_gouvernorat' => 2, 'libelle' => 'Ariana'],
            ['id_gouvernorat' => 3, 'libelle' => 'Ben Arous'],
            ['id_gouvernorat' => 4, 'libelle' => 'Manouba'],
            ['id_gouvernorat' => 5, 'libelle' => 'Nabeul'],
            ['id_gouvernorat' => 6, 'libelle' => 'Zaghouan'],
            ['id_gouvernorat' => 7, 'libelle' => 'Bizerte'],
            ['id_gouvernorat' => 8, 'libelle' => 'Béja'],
            ['id_gouvernorat' => 9, 'libelle' => 'Jendouba'],
            ['id_gouvernorat' => 10, 'libelle' => 'Kef'],
            ['id_gouvernorat' => 11, 'libelle' => 'Siliana'],
            ['id_gouvernorat' => 12, 'libelle' => 'Sousse'],
            ['id_gouvernorat' => 13, 'libelle' => 'Monastir'],
            ['id_gouvernorat' => 14, 'libelle' => 'Mahdia'],
            ['id_gouvernorat' => 15, 'libelle' => 'Sfax'],
            ['id_gouvernorat' => 16, 'libelle' => 'Kairouan'],
            ['id_gouvernorat' => 17, 'libelle' => 'Kasserine'],
            ['id_gouvernorat' => 18, 'libelle' => 'Sidi Bouzid'],
            ['id_gouvernorat' => 19, 'libelle' => 'Gabès'],
            ['id_gouvernorat' => 20, 'libelle' => 'Médenine'],
            ['id_gouvernorat' => 21, 'libelle' => 'Tataouine'],
            ['id_gouvernorat' => 22, 'libelle' => 'Gafsa'],
            ['id_gouvernorat' => 23, 'libelle' => 'Tozeur'],
            ['id_gouvernorat' => 24, 'libelle' => 'Kébili']
        ];
    }
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une offre - Uber-Cueillette</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="../images/Uber-Cueillette-logo.png">
</head>
<body>

<!-- ============================================================================
    PARTIE 6: BARRE DE NAVIGATION
    ============================================================================ -->

<nav class="navbar">
    <div class="container">
        <a href="../racine/index.html" class="logo">
            <img src="../images/Uber-Cueillette-logo.png" alt="Uber-Cueillette" class="logo-img">
            <span>Uber<span>Cueillette</span></span>
        </a>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="ajouter-offre.php" class="active"><i class="fas fa-plus-circle"></i> Ajouter offre</a></li>
            <li><a href="mes-offres.php"><i class="fas fa-list"></i> Mes offres</a></li>
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

<!-- ============================================================================
    PARTIE 7: CONTENU PRINCIPAL
    ============================================================================ -->

<main>
    <div class="container">

        <!-- ====================================================================
            PARTIE 8: TITRE DE LA PAGE
            ==================================================================== -->

        <div class="hero" style="padding: 30px 0; margin-bottom: 30px;">
            <div class="container">
                <div class="hero-content">
                    <h1><i class="fas fa-plus-circle"></i> Ajouter une offre</h1>
                    <p>Proposez votre récolte et trouvez des ouvriers agricoles</p>
                </div>
                <div class="hero-image">
                    <img src="../images/hero-agriculture1.png" alt="Ajouter offre" onerror="this.style.display='none'">
                </div>
            </div>
        </div>

        <!-- ====================================================================
            PARTIE 9: AFFICHAGE DES MESSAGES
            ==================================================================== -->

        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-error">
                <?php foreach($erreurs as $e): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?php echo $e; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- ====================================================================
            PARTIE 10: FORMULAIRE D'AJOUT
            ==================================================================== -->

        <div class="form-container" style="max-width: 800px; margin: 0 auto 50px;">
            
            <form id="ajoutOffreForm" action="../traitement/ajouter-offre.php" method="POST">
                
                <!-- Type de fruit -->
                <div class="form-group">
                    <label for="type_fruit">
                        <i class="fas fa-apple-alt"></i> Type de fruit
                    </label>
                    <select id="type_fruit" name="type_fruit">
                        <option value="">Sélectionnez un fruit</option>
                        <?php foreach($types_fruits as $fruit): ?>
                            <option value="<?php echo $fruit['id_type_fruit']; ?>" 
                                <?php echo (isset($old['type_fruit']) && $old['type_fruit'] == $fruit['libelle']) ? 'selected' : ''; ?>>
                                <?php echo $fruit['libelle']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="error-message" id="type_fruit-error"></div>
                </div>

                <!-- Localisation -->
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
                    
                    <div class="form-group">
                        <label for="gouvernorat">
                            <i class="fas fa-map-marker-alt"></i> Gouvernorat
                        </label>
                        <select id="gouvernorat" name="gouvernorat">
                            <option value="">Sélectionnez</option>
                            <?php foreach($gouvernorats as $gouv): ?>
                                <option value="<?php echo $gouv['id_gouvernorat']; ?>"
                                    <?php echo (isset($old['gouvernorat']) && $old['gouvernorat'] == $gouv['libelle']) ? 'selected' : ''; ?>>
                                    <?php echo $gouv['libelle']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-message" id="gouvernorat-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="adresse">
                            <i class="fas fa-location-dot"></i> Adresse précise
                        </label>
                        <input type="text" id="adresse" name="adresse" 
                            placeholder="Route, ville, point de repère..."
                            value="<?php echo htmlspecialchars($old['adresse'] ?? ''); ?>">
                        <div class="error-message" id="adresse-error"></div>
                    </div>
                    
                </div>

                <!-- Période -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-alt"></i> Période de récolte</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        
                        <div class="form-group">
                            <label for="date_debut">Date de début</label>
                            <input type="date" id="date_debut" name="date_debut"
                                value="<?php echo $old['date_debut'] ?? ''; ?>">
                            <div class="error-message" id="date_debut-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_fin">Date de fin</label>
                            <input type="date" id="date_fin" name="date_fin"
                                value="<?php echo $old['date_fin'] ?? ''; ?>">
                            <div class="error-message" id="date_fin-error"></div>
                        </div>
                        
                    </div>
                </div>

                <!-- Date limite -->
                <div class="form-section">
                    <h3><i class="fas fa-clock"></i> Date limite</h3>
                    
                    <div class="form-group">
                        <label for="date_limite">Date limite de postulation</label>
                        <input type="date" id="date_limite" name="date_limite"
                            value="<?php echo $old['date_limite'] ?? ''; ?>">
                        <small style="color: gray; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Après cette date, l'offre ne sera plus visible
                        </small>
                        <div class="error-message" id="date_limite-error"></div>
                    </div>
                </div>

                <!-- Détails de l'offre -->
                <div class="form-section">
                    <h3><i class="fas fa-chart-bar"></i> Détails de l'offre</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        
                        <div class="form-group">
                            <label for="nb_ouvriers">
                                <i class="fas fa-users"></i> Nombre d'ouvriers
                            </label>
                            <input type="number" id="nb_ouvriers" name="nb_ouvriers" 
                                min="1" max="50" value="<?php echo $old['nb_ouvriers'] ?? '5'; ?>">
                            <div class="error-message" id="nb_ouvriers-error"></div>
                        </div>

                        <div class="form-group">
                            <label for="prix">
                                <i class="fas fa-money-bill-wave"></i> Prix journalier (DT)
                            </label>
                            <input type="number" id="prix" name="prix" 
                                min="10" max="500" step="5" value="<?php echo $old['prix'] ?? '50'; ?>">
                            <div class="error-message" id="prix-error"></div>
                        </div>
                        
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="description">
                            <i class="fas fa-file-alt"></i> Description complémentaire
                        </label>
                        <textarea id="description" name="description" rows="4" 
                                placeholder="Précisions sur le travail, hébergement, repas, équipement fourni, etc."><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Conditions générales -->
                <div class="checkbox-group">
                    <input type="checkbox" id="conditions" name="conditions" <?php echo isset($old['conditions']) ? 'checked' : ''; ?>>
                    <label for="conditions">
                        Je certifie que les informations fournies sont exactes et je m'engage à respecter les 
                        <a href="#">conditions générales</a> d'utilisation
                    </label>
                    <div class="error-message" id="conditions-error"></div>
                </div>

                <!-- Boutons d'action -->
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" name="publier" class="btn btn-primary btn-large" style="flex: 2;">
                        <i class="fas fa-check"></i> Publier l'offre
                    </button>
                    <button type="reset" class="btn btn-secondary btn-large" style="flex: 1;">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </button>
                </div>
                
            </form>
        </div>

        <!-- ====================================================================
            PARTIE 11: CONSEILS
            ==================================================================== -->

        <div class="how-it-works" style="padding: 40px 0;">
            <div class="container">
                <h2 class="section-title">Conseils pour une offre réussie</h2>
                <div class="steps">
                    
                    <div class="step">
                        <div class="step-icon"><i class="fas fa-camera"></i></div>
                        <h3>Photos</h3>
                        <p>Ajoutez des photos du champ et des fruits pour attirer plus d'ouvriers</p>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon"><i class="fas fa-clock"></i></div>
                        <h3>Délais</h3>
                        <p>Publiez votre offre au moins 2 semaines à l'avance</p>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon"><i class="fas fa-coins"></i></div>
                        <h3>Prix compétitif</h3>
                        <p>Proposez un tarif en adéquation avec le marché local</p>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon"><i class="fas fa-utensils"></i></div>
                        <h3>Avantages</h3>
                        <p>Mentionnez les avantages (repas, transport, hébergement)</p>
                    </div>
                    
                </div>
            </div>
        </div>

    </div>
</main>

<!-- ============================================================================
    PARTIE 12: PIED DE PAGE
    ============================================================================ -->

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
                    <li><a href="profil.php">Mon profil</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Uber-Cueillette - ISG Tunis</p>
        </div>
    </div>
</footer>

<!-- ============================================================================
    PARTIE 13: FICHIER JAVASCRIPT
    ============================================================================ -->

<script src="../js/validation.js"></script>


<script>
// ============================================================================
// FONCTION 1: VALIDATION DU FORMULAIRE
// ============================================================================

function validateOffreForm() {
    
    // Réinitialiser les erreurs
    let errorDivs = document.querySelectorAll('.error-message');
    for (let i = 0; i < errorDivs.length; i++) {
        errorDivs[i].innerHTML = '';
    }
    
    let inputs = document.querySelectorAll('input, select, textarea');
    for (let i = 0; i < inputs.length; i++) {
        inputs[i].style.borderColor = '';
    }
    
    let isValid = true;
    
    // Récupérer les valeurs
    let typeFruit = document.getElementById('type_fruit').value;
    let gouvernorat = document.getElementById('gouvernorat').value;
    let adresse = document.getElementById('adresse').value.trim();
    let dateDebut = document.getElementById('date_debut').value;
    let dateFin = document.getElementById('date_fin').value;
    let dateLimite = document.getElementById('date_limite').value;
    let nbOuvriers = document.getElementById('nb_ouvriers').value;
    let prix = document.getElementById('prix').value;
    let conditions = document.getElementById('conditions').checked;
    
    // Validation type fruit
    if (typeFruit === '') {
        document.getElementById('type_fruit-error').innerHTML = 'Veuillez sélectionner un type de fruit';
        document.getElementById('type_fruit').style.borderColor = 'red';
        isValid = false;
    }
    
    // Validation gouvernorat
    if (gouvernorat === '') {
        document.getElementById('gouvernorat-error').innerHTML = 'Veuillez sélectionner un gouvernorat';
        document.getElementById('gouvernorat').style.borderColor = 'red';
        isValid = false;
    }
    
    // Validation adresse
    if (adresse === '') {
        document.getElementById('adresse-error').innerHTML = 'L\'adresse est requise';
        document.getElementById('adresse').style.borderColor = 'red';
        isValid = false;
    } else if (adresse.length < 5) {
        document.getElementById('adresse-error').innerHTML = 'L\'adresse doit contenir au moins 5 caractères';
        document.getElementById('adresse').style.borderColor = 'red';
        isValid = false;
    }
    
    // Validation dates
    if (dateDebut === '') {
        document.getElementById('date_debut-error').innerHTML = 'La date de début est requise';
        document.getElementById('date_debut').style.borderColor = 'red';
        isValid = false;
    }
    
    if (dateFin === '') {
        document.getElementById('date_fin-error').innerHTML = 'La date de fin est requise';
        document.getElementById('date_fin').style.borderColor = 'red';
        isValid = false;
    }
    
    if (dateLimite === '') {
        document.getElementById('date_limite-error').innerHTML = 'La date limite est requise';
        document.getElementById('date_limite').style.borderColor = 'red';
        isValid = false;
    }
    
    // Validation relations entre dates
    if (dateDebut && dateFin && dateLimite) {
        let debut = new Date(dateDebut);
        let fin = new Date(dateFin);
        let limite = new Date(dateLimite);
        let aujourdhui = new Date();
        aujourdhui.setHours(0,0,0,0);
        
        if (limite < aujourdhui) {
            document.getElementById('date_limite-error').innerHTML = 'La date limite doit être dans le futur';
            document.getElementById('date_limite').style.borderColor = 'red';
            isValid = false;
        }
        
        if (fin <= debut) {
            document.getElementById('date_fin-error').innerHTML = 'La date de fin doit être après la date de début';
            document.getElementById('date_fin').style.borderColor = 'red';
            isValid = false;
        }
    }
    
    // Validation nombre d'ouvriers
    if (nbOuvriers === '') {
        document.getElementById('nb_ouvriers-error').innerHTML = 'Le nombre d\'ouvriers est requis';
        document.getElementById('nb_ouvriers').style.borderColor = 'red';
        isValid = false;
    } else if (nbOuvriers < 1 || nbOuvriers > 50) {
        document.getElementById('nb_ouvriers-error').innerHTML = 'Le nombre d\'ouvriers doit être entre 1 et 50';
        document.getElementById('nb_ouvriers').style.borderColor = 'red';
        isValid = false;
    }
    
    // Validation prix
    if (prix === '') {
        document.getElementById('prix-error').innerHTML = 'Le prix journalier est requis';
        document.getElementById('prix').style.borderColor = 'red';
        isValid = false;
    } else if (prix < 10 || prix > 500) {
        document.getElementById('prix-error').innerHTML = 'Le prix doit être entre 10 et 500 DT';
        document.getElementById('prix').style.borderColor = 'red';
        isValid = false;
    }
    
    // Validation conditions
    if (!conditions) {
        document.getElementById('conditions-error').innerHTML = 'Vous devez accepter les conditions générales';
        isValid = false;
    }
    
    return isValid;
}

// ============================================================================
// FONCTION 2: INITIALISATION (animation supprimée)
// ============================================================================

window.onload = function() {
    
    // Confirmation avant réinitialisation
    const resetBtn = document.querySelector('button[type="reset"]');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ?')) {
                e.preventDefault();
            }
        });
    }
};

// ============================================================================
// FONCTION 3: ATTACHER LA VALIDATION AU FORMULAIRE
// ============================================================================

document.getElementById('ajoutOffreForm').onsubmit = function() {
    return validateOffreForm();
};
</script>

</body>
</html>