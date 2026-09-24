<?php
/**
 * noter-ouvriers.php - Page pour noter les ouvriers après un chantier
 * CORRIGÉ : Gestion des photos NULL, étoiles, password_verify, date inscription
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agriculteur') {
    header('Location: ../racine/login.php');
    exit();
}

require_once('../config/database.php');

$id_agriculteur = $_SESSION['user_id'];
$id_offre = isset($_GET['offre']) ? intval($_GET['offre']) : 0;

if ($id_offre <= 0) {
    header('Location: mes-offres.php');
    exit();
}

// Vérifier que l'offre appartient à l'agriculteur
$sql = "SELECT o.*, tf.libelle as type_fruit, g.libelle as gouvernorat
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

// Récupérer les ouvriers acceptés pour cette offre
$sql = "SELECT c.*, ouv.nom, ouv.prenom, ouv.id_ouvrier, ouv.photo
        FROM uber_cueillette_candidature c
        JOIN uber_cueillette_ouvrier ouv ON c.id_ouvrier = ouv.id_ouvrier
        WHERE c.id_offre = ? AND c.decision = 'acceptee'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_offre]);
$ouvriers = $stmt->fetchAll();

$message = '';
$erreur = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_candidature = $_POST['id_candidature'] ?? 0;
    $note = $_POST['note'] ?? 0;
    $commentaire = trim($_POST['commentaire'] ?? '');
    $remuneration = $_POST['remuneration'] ?? 0;
    
    if ($note < 1 || $note > 10) {
        $erreur = "La note doit être entre 1 et 10";
    } else {
        $sql = "UPDATE uber_cueillette_candidature SET note = ?, commentaire = ?, remuneration = ? WHERE id_candidature = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$note, $commentaire, $remuneration, $id_candidature])) {
            $message = "✅ Notation enregistrée !";
        } else {
            $erreur = "❌ Erreur lors de l'enregistrement";
        }
    }
}

// Recharger les données après mise à jour
$stmt = $pdo->prepare("SELECT c.*, ouv.nom, ouv.prenom, ouv.id_ouvrier, ouv.photo
                        FROM uber_cueillette_candidature c
                        JOIN uber_cueillette_ouvrier ouv ON c.id_ouvrier = ouv.id_ouvrier
                        WHERE c.id_offre = ? AND c.decision = 'acceptee'
                        ORDER BY c.note IS NULL DESC, c.note DESC");
$stmt->execute([$id_offre]);
$ouvriers = $stmt->fetchAll();

// ✅ FONCTION CORRIGÉE POUR AFFICHER LA PHOTO (GESTION NULL)
function getPhoto($ouvrier) {
    $default = '../images/default-profile.jpg';
    
    // ✅ CORRECTION 1: Vérifier si la photo est vide ou NULL
    if (empty($ouvrier['photo'])) {
        return $default;
    }
    
    // Vérifier si c'est un BLOB
    $premier_code = ord(substr($ouvrier['photo'], 0, 1));
    if ($premier_code < 32 || $premier_code > 126 || strlen($ouvrier['photo']) > 255) {
        $photo_data = base64_encode($ouvrier['photo']);
        return 'data:image/jpeg;base64,' . $photo_data;
    } else {
        $photo_path = '../' . $ouvrier['photo'];
        if (file_exists($photo_path)) {
            return $photo_path;
        }
    }
    return $default;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noter les ouvriers - <?php echo htmlspecialchars($offre['type_fruit']); ?></title>
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
            <!-- En-tête -->
            <div class="offre-header-card">
                <div class="offre-header-content">
                    <div>
                        <h2 class="offre-header-title">
                            <i class="fas fa-apple-alt"></i> <?php echo htmlspecialchars($offre['type_fruit'] . ' - ' . $offre['gouvernorat']); ?>
                        </h2>
                        <p class="offre-header-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?> - <?php echo date('d/m/Y', strtotime($offre['date_fin'])); ?></span>
                            <span><i class="fas fa-users"></i> <?php echo count($ouvriers); ?> ouvriers</span>
                        </p>
                    </div>
                    <div class="offre-header-badges">
                        <span class="badge badge-info">Notation des ouvriers</span>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($erreur): ?>
                <div class="alert alert-error"><?php echo $erreur; ?></div>
            <?php endif; ?>

            <!-- Liste des ouvriers -->
            <h2 class="section-title">Ouvriers à noter</h2>
            
            <?php if (empty($ouvriers)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>Aucun ouvrier</h3>
                    <p>Aucun ouvrier n'a été accepté pour cette offre</p>
                </div>
            <?php else: ?>
                <div class="notation-list">
                    <?php foreach($ouvriers as $o): 
                        $deja_note = !is_null($o['note']);
                        $photo = getPhoto($o);
                    ?>
                    <div class="card ouvrier-card">
                        <div class="card-body">
                            <div class="ouvrier-notation-content">
                                <div class="ouvrier-notation-photo">
                                    <img src="<?php echo $photo; ?>" alt="Profile" class="profile-img"
                                        onerror="this.src='../images/default-profile.jpg'">
                                    <div class="ouvrier-statut-badge <?php echo $deja_note ? 'note-faite' : 'note-attente'; ?>">
                                        <?php if ($deja_note): ?>
                                            <i class="fas fa-check-circle"></i> Noté
                                        <?php else: ?>
                                            <i class="fas fa-clock"></i> À noter
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="ouvrier-notation-info">
                                    <div class="ouvrier-notation-header">
                                        <h3 class="ouvrier-notation-nom"><?php echo htmlspecialchars($o['prenom'] . ' ' . $o['nom']); ?></h3>
                                        <?php if ($deja_note): ?>
                                            <span class="badge badge-success">Déjà noté</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">En attente</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($deja_note): ?>
                                        <!-- AFFICHAGE DE LA NOTE DÉJÀ ATTRIBUÉE -->
                                        <div class="note-attribuee">
                                            <span class="note-label">Note attribuée :</span>
                                            <div class="etoiles-attribuees">
                                                <?php for($i = 1; $i <= 10; $i++): ?>
                                                    <?php if ($i <= $o['note']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                                <span class="note-valeur"><?php echo $o['note']; ?>/10</span>
                                            </div>
                                            <?php if (!empty($o['commentaire'])): ?>
                                            <div class="commentaire-attribue">
                                                <i class="fas fa-quote-left"></i>
                                                <?php echo htmlspecialchars($o['commentaire']); ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($o['remuneration'])): ?>
                                            <div class="remuneration-attribuee">
                                                <i class="fas fa-money-bill-wave"></i> Rémunération: <?php echo $o['remuneration']; ?> DT
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <!-- Formulaire de notation -->
                                        <form method="POST" class="notation-system" data-candidature="<?php echo $o['id_candidature']; ?>">
                                            <input type="hidden" name="id_candidature" value="<?php echo $o['id_candidature']; ?>">
                                            
                                            <div class="rating-stars">
                                                <span class="rating-label">Note (1 à 10) :</span>
                                                <div class="stars-container" data-candidature="<?php echo $o['id_candidature']; ?>">
                                                    <?php for($i = 1; $i <= 10; $i++): ?>
                                                        <i class="far fa-star" data-value="<?php echo $i; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <input type="hidden" name="note" id="note-<?php echo $o['id_candidature']; ?>" class="note-input" required>
                                            <small class="rating-hint">Cliquez sur une étoile pour noter de 1 à 10</small>
                                            
                                            <div class="commentaire-zone">
                                                <textarea name="commentaire" class="comment-box" placeholder="Commentaire sur le travail... (ex: ponctuel, sérieux, travail soigné, etc.)"></textarea>
                                            </div>
                                            
                                            <div class="remuneration-zone">
                                                <label><i class="fas fa-money-bill-wave"></i> Rémunération totale (DT)</label>
                                                <input type="number" name="remuneration" class="remuneration-input" value="<?php echo $offre['prix_journee'] * 10; ?>" min="0" step="10">
                                                <small class="rating-hint">Basé sur <?php echo $offre['prix_journee']; ?> DT/jour × 10 jours estimés</small>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary" style="margin-top: 15px;">
                                                <i class="fas fa-save"></i> Enregistrer la notation
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin: 30px 0;">
                <a href="mes-offres.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Retour aux offres
                </a>
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
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Uber-Cueillette - ISG Tunis</p>
            </div>
        </div>
    </footer>

    <script src="../js/validation.js"></script>
    
    <!-- ✅ SCRIPT CORRIGÉ POUR LES ÉTOILES -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Gestionnaire pour toutes les étoiles
        const starsContainers = document.querySelectorAll('.stars-container');
        
        starsContainers.forEach(container => {
            const stars = container.querySelectorAll('i');
            const candidatureId = container.dataset.candidature;
            const noteInput = document.getElementById('note-' + candidatureId);
            
            if (!noteInput) return;
            
            // Stocker la valeur sélectionnée
            let selectedValue = 0;
            
            stars.forEach((star, index) => {
                const value = index + 1;
                star.dataset.value = value;
                
                // Mouse enter - surbrillance
                star.addEventListener('mouseenter', function() {
                    highlightStars(container, value);
                });
                
                // Mouse leave - restaurer la sélection
                star.addEventListener('mouseleave', function() {
                    if (selectedValue > 0) {
                        highlightStars(container, selectedValue);
                        updateStarsSolid(container, selectedValue);
                    } else {
                        resetStarsOutline(container);
                    }
                });
                
                // Click - sélectionner la note
                star.addEventListener('click', function() {
                    selectedValue = value;
                    noteInput.value = selectedValue;
                    updateStarsSolid(container, selectedValue);
                    
                    // Animation feedback
                    showToast('Note sélectionnée : ' + selectedValue + '/10');
                });
            });
            
            // Si une valeur est déjà pré-remplie
            if (noteInput.value && noteInput.value > 0) {
                selectedValue = parseInt(noteInput.value);
                updateStarsSolid(container, selectedValue);
            }
        });
        
        // Fonction pour surligner les étoiles (sans les figer)
        function highlightStars(container, count) {
            const stars = container.querySelectorAll('i');
            stars.forEach((star, index) => {
                if (index < count) {
                    star.style.color = '#f39c12';
                    star.classList.remove('far');
                    star.classList.add('fas');
                } else {
                    star.style.color = '';
                    star.classList.remove('fas');
                    star.classList.add('far');
                }
            });
        }
        
        // Fonction pour figer les étoiles sélectionnées
        function updateStarsSolid(container, count) {
            const stars = container.querySelectorAll('i');
            stars.forEach((star, index) => {
                if (index < count) {
                    star.classList.remove('far');
                    star.classList.add('fas');
                    star.style.color = '#f39c12';
                } else {
                    star.classList.remove('fas');
                    star.classList.add('far');
                    star.style.color = '';
                }
            });
        }
        
        // Fonction pour remettre toutes les étoiles en outline
        function resetStarsOutline(container) {
            const stars = container.querySelectorAll('i');
            stars.forEach(star => {
                star.classList.remove('fas');
                star.classList.add('far');
                star.style.color = '';
            });
        }
        
        // Petit toast de confirmation
        function showToast(message) {
            // Supprimer l'ancien toast s'il existe
            const oldToast = document.querySelector('.rating-toast');
            if (oldToast) oldToast.remove();
            
            const toast = document.createElement('div');
            toast.className = 'rating-toast';
            toast.innerHTML = message;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #2ecc71;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                z-index: 9999;
                animation: fadeOut 2s ease forwards;
            `;
            
            // Ajouter l'animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeOut {
                    0% { opacity: 1; transform: translateY(0); }
                    70% { opacity: 1; transform: translateY(0); }
                    100% { opacity: 0; transform: translateY(-20px); visibility: hidden; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (toast && toast.remove) toast.remove();
            }, 2000);
        }
        
        // Validation avant soumission
        const forms = document.querySelectorAll('.notation-system');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const noteInput = this.querySelector('.note-input');
                const note = parseInt(noteInput.value);
                
                if (!note || note < 1 || note > 10) {
                    e.preventDefault();
                    alert('Veuillez sélectionner une note entre 1 et 10');
                    return false;
                }
                
                // Confirmation avant envoi
                return confirm('Confirmer la notation ?');
            });
        });
    });
    </script>
    
    <style>
    /* Styles supplémentaires pour la notation */
    .stars-container i {
        cursor: pointer;
        font-size: 1.5rem;
        margin: 0 2px;
        transition: transform 0.2s;
    }
    .stars-container i:hover {
        transform: scale(1.1);
    }
    .rating-stars {
        margin-bottom: 10px;
    }
    .rating-label {
        display: inline-block;
        margin-right: 10px;
        font-weight: bold;
    }
    .rating-hint {
        display: block;
        color: var(--text-light);
        font-size: 0.8rem;
        margin-top: 5px;
    }
    .comment-box {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        background: var(--input-bg);
        color: var(--text-color);
        resize: vertical;
        font-family: inherit;
    }
    .remuneration-input {
        width: 200px;
        padding: 8px;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        background: var(--input-bg);
        color: var(--text-color);
    }
    .remuneration-zone {
        margin-top: 15px;
    }
    .remuneration-zone label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .commentaire-zone {
        margin: 15px 0;
    }
    .etoiles-attribuees i {
        font-size: 1rem;
        margin: 0 1px;
    }
    .etoiles-attribuees .fa-star {
        color: #f39c12;
    }
    .etoiles-attribuees .fa-star-o,
    .etoiles-attribuees .far.fa-star {
        color: #ccc;
    }
    .note-valeur {
        margin-left: 10px;
        font-weight: bold;
    }
    .note-attribuee {
        background: var(--light-color);
        padding: 15px;
        border-radius: var(--border-radius);
        margin-top: 10px;
    }
    .commentaire-attribue {
        margin-top: 10px;
        font-style: italic;
        color: var(--text-light);
    }
    .remuneration-attribuee {
        margin-top: 8px;
        font-weight: bold;
        color: var(--primary-color);
    }
    </style>
</body>
</html>