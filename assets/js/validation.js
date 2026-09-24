/**
 * =====================================================================
 * FICHIER DE VALIDATION JAVASCRIPT - Uber-Cueillette
 * =====================================================================
 * Ce fichier sert à vérifier que les formulaires sont bien remplis
 * avant de les envoyer au serveur.
 * 
 * Il est utilisé dans toutes les pages du site :
 * - index.html (page d'accueil)
 * - login.php (page de connexion)
 * - registre.php (page d'inscription)
 * =====================================================================
 */

// ============================================================================
// 1. VARIABLES GLOBALES (utilisées partout)
// ============================================================================
let nomApplication = "Uber-Cueillette";
let versionApplication = 1.0;
let estDeploye = true;

// ============================================================================
// 2. FONCTIONS POUR VERIFIER LES FORMATS (CIN, pseudo, mot de passe)
// ============================================================================

// Cette fonction vérifie si un champ respecte le bon format
// Elle est utilisée dans la page registre.php
function validerFormat(valeur, type) {
    
    if (type === 'cin') {
        // CIN : 8 chiffres exactement
        if (valeur.length !== 8) return false;
        
        for (let i = 0; i < 8; i++) {
            let c = valeur.charAt(i);
            if (c < '0' || c > '9') return false;
        }
        return true;
    }
    
    if (type === 'pseudo') {
        // Pseudo : uniquement des lettres
        for (let i = 0; i < valeur.length; i++) {
            let c = valeur.charAt(i);
            let estMinuscule = (c >= 'a' && c <= 'z');
            let estMajuscule = (c >= 'A' && c <= 'Z');
            
            if (!estMinuscule && !estMajuscule) return false;
        }
        return true;
    }
    
    if (type === 'password') {
        // Mot de passe : 8+ caractères, finit par $ ou #
        if (valeur.length < 8) return false;
        
        let dernier = valeur.charAt(valeur.length - 1);
        return (dernier === '$' || dernier === '#');
    }
    
    return false;  // Type inconnu
} 

// ============================================================================
// 3. FONCTIONS POUR AFFICHER LES MESSAGES D'ERREUR (utilisées partout)
// ============================================================================

// Cette fonction montre un message d'erreur sous un champ
// Utilisée dans : login.php, registre.php
function showError(inputId, message) {
    
    // 1. Chercher l'endroit où afficher l'erreur
    let errorElement = document.getElementById(inputId + '-error');
    
    // 2. Si cet endroit existe
    if (errorElement) {
        // Mettre le message avec une icône d'erreur
        errorElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
        
        // Mettre le texte en rouge
        errorElement.style.color = '#e74c3c';
        
        // Taille du texte
        errorElement.style.fontSize = '12px';
        
        // Espacement au-dessus
        errorElement.style.marginTop = '5px';
        
        // Espacement en-dessous
        errorElement.style.marginBottom = '5px';
        
        // Afficher l'élément--------------------------------------------------
        errorElement.style.display = 'block';
    }
    
    // 3. Chercher le champ qui a l'erreur
    let input = document.getElementById(inputId);
    
    // 4. Si le champ existe
    if (input) {
        // Ajouter une classe "error" pour le CSS
        input.classList.add('error');
        
        // Mettre la bordure en rouge
        input.style.borderColor = '#e74c3c';
        
        // Mettre la bordure plus épaisse
        input.style.borderWidth = '2px';
    }
}

// Cette fonction efface le message d'erreur d'un champ
// Utilisée dans : login.php, registre.php
function clearError(inputId) {
    
    // 1. Chercher l'endroit où l'erreur était affichée
    let errorElement = document.getElementById(inputId + '-error');
    
    // 2. Si cet endroit existe
    if (errorElement) {
        // Vider le message
        errorElement.innerHTML = '';
        
        // Cacher l'élément
        errorElement.style.display = 'none';
    }
    
    // 3. Chercher le champ
    let input = document.getElementById(inputId);
    
    // 4. Si le champ existe
    if (input) {
        // Enlever la classe "error"
        input.classList.remove('error');
        
        // Remettre la bordure normale
        input.style.borderColor = '';
        
        // Remettre la bordure fine
        input.style.borderWidth = '1px';
    }
}

// Cette fonction efface toutes les erreurs d'un formulaire
// Utilisée dans : registre.php (avant de valider)
function clearErrors(formId) {
    
    // 1. Chercher le formulaire
    let form = document.getElementById(formId);
    
    // 2. Si le formulaire n'existe pas, on arrête
    if (!form) {
        return;
    }
    
    // 3. Chercher tous les messages d'erreur dans le formulaire
    let errorMessages = form.querySelectorAll('.error-message');
    
    // 4. Pour chaque message d'erreur
    for (let i = 0; i < errorMessages.length; i++) {
        // Vider le texte
        errorMessages[i].innerHTML = '';
        
        // Cacher l'élément
        errorMessages[i].style.display = 'none';
    }
    
    // 5. Chercher tous les champs du formulaire
    let inputs = form.querySelectorAll('input, textarea, select');
    
    // 6. Pour chaque champ
    for (let i = 0; i < inputs.length; i++) {
        // Enlever la classe "error"
        inputs[i].classList.remove('error');
        
        // Remettre la bordure normale
        inputs[i].style.borderColor = '';
        
        // Remettre la bordure fine
        inputs[i].style.borderWidth = '1px';
    }
}

// ============================================================================
// 4. MODE SOMBRE/CLAIR (utilisé dans toutes les pages)
// ============================================================================

// Quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le thème (sombre/clair)
    initTheme();
    
    // Initialiser les formulaires
    initForms();
    
    // Ajouter la classe active aux liens
    ajouterClassesActive();
    
    // Initialiser la validation en direct
    initLiveValidation();
});

// Cette fonction initialise le mode sombre/clair
function initTheme() {
    
    // Chercher le bouton de changement de thème
    let themeToggle = document.getElementById('theme-toggle');
    
    // Si le bouton n'existe pas, on arrête
    if (!themeToggle) {
        return;
    }
    
    // Récupérer l'élément HTML principal
    let htmlElement = document.documentElement;
    
    // Récupérer le thème sauvegardé dans le navigateur
    let savedTheme = localStorage.getItem('theme');
    
    // Si pas de thème sauvegardé, on met "light" (clair)
    if (savedTheme === null) {
        savedTheme = 'light';
    }
    
    // Appliquer le thème à la page
    htmlElement.setAttribute('data-theme', savedTheme);
    
    // Mettre à jour l'icône du bouton
    updateThemeIcon(savedTheme);
    
    // Quand on clique sur le bouton
    themeToggle.addEventListener('click', function() {
        
        // Récupérer le thème actuel
        let currentTheme = htmlElement.getAttribute('data-theme');
        
        // Changer le thème
        let newTheme;
        if (currentTheme === 'light') {
            newTheme = 'dark';
        } else {
            newTheme = 'light';
        }
        
        // Appliquer le nouveau thème
        htmlElement.setAttribute('data-theme', newTheme);
        
        // Sauvegarder dans le navigateur
        localStorage.setItem('theme', newTheme);
        
        // Mettre à jour l'icône
        updateThemeIcon(newTheme);
    });
}

// Cette fonction met à jour l'icône du bouton de thème
function updateThemeIcon(theme) {
    
    // Chercher le bouton
    let themeToggle = document.getElementById('theme-toggle');
    
    // Si le bouton n'existe pas, on arrête
    if (!themeToggle) {
        return;
    }
    
    // Chercher l'icône à l'intérieur du bouton
    let icon = themeToggle.querySelector('i');
    
    // Si l'icône existe
    if (icon) {
        // Si le thème est clair, on met une lune
        if (theme === 'light') {
            icon.className = 'fas fa-moon';
        } 
        // Si le thème est sombre, on met un soleil
        else {
            icon.className = 'fas fa-sun';
        }
    }
}

// ============================================================================
// 5. VALIDATION EN DIRECT (utilisée dans login.php et registre.php)
// ============================================================================

// Cette fonction active la validation pendant qu'on tape
function initLiveValidation() {
    
    // ================================================================
    // Pour la page login.php
    // ================================================================
    
    let loginForm = document.getElementById('login-form');
    
    if (loginForm) {
        
        let usernameInput = document.getElementById('username');
        let passwordInput = document.getElementById('password');
        
        // Validation du pseudo en direct
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                validateField('username', this.value, 'pseudo');
            });
            
            usernameInput.addEventListener('blur', function() {
                validateField('username', this.value, 'pseudo');
            });
        }
        
        // Validation du mot de passe en direct
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                validateField('password', this.value, 'password');
            });
            
            passwordInput.addEventListener('blur', function() {
                validateField('password', this.value, 'password');
            });
        }
    }
    
    // ================================================================
    // Pour la page registre.php - Formulaire Agriculteur
    // ================================================================
    
    let agriculteurForm = document.getElementById('agriculteurForm');
    
    if (agriculteurForm) {
        
        // Validation du nom
        let nomAgri = document.getElementById('nom_agri');
        if (nomAgri) {
            nomAgri.addEventListener('input', function() {
                validateField('nom_agri', this.value, 'text');
            });
            nomAgri.addEventListener('blur', function() {
                validateField('nom_agri', this.value, 'text');
            });
        }
        
        // Validation du prénom
        let prenomAgri = document.getElementById('prenom_agri');
        if (prenomAgri) {
            prenomAgri.addEventListener('input', function() {
                validateField('prenom_agri', this.value, 'text');
            });
            prenomAgri.addEventListener('blur', function() {
                validateField('prenom_agri', this.value, 'text');
            });
        }
        
        // Validation du CIN
        let cinAgri = document.getElementById('cin_agri');
        if (cinAgri) {
            cinAgri.addEventListener('input', function() {
                validateField('cin_agri', this.value, 'cin');
            });
            cinAgri.addEventListener('blur', function() {
                validateField('cin_agri', this.value, 'cin');
            });
        }
        
        // Validation de l'email
        let emailAgri = document.getElementById('email_agri');
        if (emailAgri) {
            emailAgri.addEventListener('input', function() {
                validateField('email_agri', this.value, 'email');
            });
            emailAgri.addEventListener('blur', function() {
                validateField('email_agri', this.value, 'email');
            });
        }
        
        // Validation de l'adresse
        let adresseAgri = document.getElementById('adresse_agri');
        if (adresseAgri) {
            adresseAgri.addEventListener('input', function() {
                validateField('adresse_agri', this.value, 'text');
            });
            adresseAgri.addEventListener('blur', function() {
                validateField('adresse_agri', this.value, 'text');
            });
        }
        
        // Validation du pseudo
        let pseudoAgri = document.getElementById('pseudo_agri');
        if (pseudoAgri) {
            pseudoAgri.addEventListener('input', function() {
                validateField('pseudo_agri', this.value, 'pseudo');
            });
            pseudoAgri.addEventListener('blur', function() {
                validateField('pseudo_agri', this.value, 'pseudo');
            });
        }
        
        // Validation du mot de passe
        let passwordAgri = document.getElementById('password_agri');
        if (passwordAgri) {
            passwordAgri.addEventListener('input', function() {
                validateField('password_agri', this.value, 'password');
            });
            passwordAgri.addEventListener('blur', function() {
                validateField('password_agri', this.value, 'password');
            });
        }
        
        // Validation de la confirmation
        let confirmAgri = document.getElementById('confirm_password_agri');
        if (confirmAgri) {
            confirmAgri.addEventListener('input', function() {
                validateConfirmField('confirm_password_agri', this.value, 'password_agri');
            });
            confirmAgri.addEventListener('blur', function() {
                validateConfirmField('confirm_password_agri', this.value, 'password_agri');
            });
        }
    }
    
    // ================================================================
    // Pour la page registre.php - Formulaire Ouvrier
    // ================================================================
    
    let ouvrierForm = document.getElementById('ouvrierForm');
    
    if (ouvrierForm) {
        
        // Validation du nom
        let nomOuv = document.getElementById('nom_ouv');
        if (nomOuv) {
            nomOuv.addEventListener('input', function() {
                validateField('nom_ouv', this.value, 'text');
            });
            nomOuv.addEventListener('blur', function() {
                validateField('nom_ouv', this.value, 'text');
            });
        }
        
        // Validation du prénom
        let prenomOuv = document.getElementById('prenom_ouv');
        if (prenomOuv) {
            prenomOuv.addEventListener('input', function() {
                validateField('prenom_ouv', this.value, 'text');
            });
            prenomOuv.addEventListener('blur', function() {
                validateField('prenom_ouv', this.value, 'text');
            });
        }
        
        // Validation du CIN
        let cinOuv = document.getElementById('cin_ouv');
        if (cinOuv) {
            cinOuv.addEventListener('input', function() {
                validateField('cin_ouv', this.value, 'cin');
            });
            cinOuv.addEventListener('blur', function() {
                validateField('cin_ouv', this.value, 'cin');
            });
        }
        
        // Validation de l'email
        let emailOuv = document.getElementById('email_ouv');
        if (emailOuv) {
            emailOuv.addEventListener('input', function() {
                validateField('email_ouv', this.value, 'email');
            });
            emailOuv.addEventListener('blur', function() {
                validateField('email_ouv', this.value, 'email');
            });
        }
        
        // Validation de la description
        let descriptionOuv = document.getElementById('description');
        if (descriptionOuv) {
            descriptionOuv.addEventListener('input', function() {
                validateField('description', this.value, 'text');
            });
            descriptionOuv.addEventListener('blur', function() {
                validateField('description', this.value, 'text');
            });
        }
        
        // Validation du pseudo
        let pseudoOuv = document.getElementById('pseudo_ouv');
        if (pseudoOuv) {
            pseudoOuv.addEventListener('input', function() {
                validateField('pseudo_ouv', this.value, 'pseudo');
            });
            pseudoOuv.addEventListener('blur', function() {
                validateField('pseudo_ouv', this.value, 'pseudo');
            });
        }
        
        // Validation du mot de passe
        let passwordOuv = document.getElementById('password_ouv');
        if (passwordOuv) {
            passwordOuv.addEventListener('input', function() {
                validateField('password_ouv', this.value, 'password');
            });
            passwordOuv.addEventListener('blur', function() {
                validateField('password_ouv', this.value, 'password');
            });
        }
        
        // Validation de la confirmation
        let confirmOuv = document.getElementById('confirm_password_ouv');
        if (confirmOuv) {
            confirmOuv.addEventListener('input', function() {
                validateConfirmField('confirm_password_ouv', this.value, 'password_ouv');
            });
            confirmOuv.addEventListener('blur', function() {
                validateConfirmField('confirm_password_ouv', this.value, 'password_ouv');
            });
        }
        
        // Validation spéciale pour la photo
        let photoOuv = document.getElementById('photo');
        if (photoOuv) {
            photoOuv.addEventListener('change', function() {
                validatePhoto(this);
            });
        }
    }
}

// ============================================================================
// 6. FONCTIONS DE VALIDATION DES CHAMPS (utilisées dans les formulaires)
// ============================================================================

// Cette fonction vérifie un champ selon son type
function validateField(fieldId, valeur, type) {
    
    // ===========================================
    // 1. D'ABORD, ON EFFACE L'ANCIENNE ERREUR
    // ===========================================
    clearError(fieldId);
    
    // ===========================================
    // 2. VERIFIER SI LE CHAMP EST VIDE
    // ===========================================
    if (valeur === "" || valeur === null) {
        showError(fieldId, "Ce champ est obligatoire");
        return false;  // On arrête tout de suite
    }
    
    // Enlever les espaces au début et à la fin
    valeur = valeur.trim();
    
    // ===========================================
    // 3. VERIFIER SELON LE TYPE DE CHAMP
    // ===========================================
    
    // -------------------------------------------
    // CAS 1 : EMAIL
    // -------------------------------------------
    if (type === "email") {
        // Vérifier 1 : doit contenir @
        if (valeur.indexOf("@") === -1) {
            showError(fieldId, "L'email doit contenir un @");
            return false;
        }
        
        // Vérifier 2 : doit contenir . après le @
        let positionArobase = valeur.indexOf("@");
        let partieApresArobase = valeur.substring(positionArobase + 1);
        
        if (partieApresArobase.indexOf(".") === -1) {
            showError(fieldId, "L'email doit avoir un point après le @ (ex: .com)");
            return false;
        }
        
        // Vérifier 3 : pas d'espace
        if (valeur.indexOf(" ") !== -1) {
            showError(fieldId, "L'email ne doit pas contenir d'espace");
            return false;
        }
        
        // Si tout est bon
        return true;
    }
    
    // -------------------------------------------
    // CAS 2 : CIN (8 chiffres)
    // -------------------------------------------
    if (type === "cin") {
        // Vérifier 1 : exactement 8 caractères
        if (valeur.length !== 8) {
            showError(fieldId, "Le CIN doit faire 8 chiffres (ex: 12345678)");
            return false;
        }
        
        // Vérifier 2 : tous les caractères sont des chiffres
        for (let i = 0; i < 8; i++) {
            let caractere = valeur[i];  // Prend le i-ème caractère
            
            // Si ce n'est pas un chiffre (0-9)
            if (caractere < "0" || caractere > "9") {
                showError(fieldId, "Le CIN ne doit contenir que des chiffres");
                return false;
            }
        }
        
        return true;
    }
    
    // -------------------------------------------
    // CAS 3 : PSEUDO (seulement des lettres)
    // -------------------------------------------
    if (type === "pseudo") {
        // Liste des lettres autorisées
        let lettresAutorisees = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        
        // Parcourir chaque caractère du pseudo
        for (let i = 0; i < valeur.length; i++) {
            let caractere = valeur[i];
            
            // Vérifier si le caractère est dans la liste des lettres autorisées
            if (lettresAutorisees.indexOf(caractere) === -1) {
                showError(fieldId, "Le pseudo ne doit contenir que des lettres (pas de chiffres ni symboles)");
                return false;
            }
        }
        
        return true;
    }
    
    // -------------------------------------------
    // CAS 4 : MOT DE PASSE
    // -------------------------------------------
    if (type === "password") {
        // Vérifier 1 : longueur minimum
        if (valeur.length < 8) {
            showError(fieldId, "Le mot de passe doit avoir au moins 8 caractères");
            return false;
        }
        
        // Vérifier 2 : dernier caractère
        let dernierCaractere = valeur[valeur.length - 1];  // Prend le dernier
        
        if (dernierCaractere !== "$" && dernierCaractere !== "#") {
            showError(fieldId, "Le mot de passe doit finir par $ ou #");
            return false;
        }
        
        return true;
    }
    
    // -------------------------------------------
    // CAS 5 : TEXTE NORMAL (nom, prénom, adresse)
    // -------------------------------------------
    if (type === "text") {
        // Vérifier longueur minimum
        if (valeur.length < 2) {
            showError(fieldId, "Ce champ doit contenir au moins 2 caractères");
            return false;
        }
        
        return true;
    }
    
    // Si on arrive ici, le type n'est pas reconnu
    return false;
}// Cette fonction vérifie que la confirmation du mot de passe est correcte
function validateConfirmField(fieldId, valeur, matchId) {
    
    // Effacer l'ancienne erreur
    clearError(fieldId);
    
    // Vérifier si le champ est vide
    if (!valeur || valeur.trim() === '') {
        showError(fieldId, 'Veuillez confirmer votre mot de passe');
        return false;
    }
    
    // Récupérer la valeur du mot de passe original
    let passwordValue = document.getElementById(matchId).value;
    
    // Vérifier si les deux mots de passe sont identiques
    if (valeur !== passwordValue) {
        showError(fieldId, 'Les mots de passe ne correspondent pas');
        return false;
    }
    
    return true;
}

// Cette fonction vérifie la photo uploadée
function validatePhoto(input) {
    
    // Effacer l'ancienne erreur
    clearError('photo');
    
    // Vérifier si une photo a été sélectionnée
    if (!input.files || input.files.length === 0) {
        showError('photo', 'La photo est requise');
        return false;
    }
    
    // Récupérer le fichier
    let file = input.files[0];
    
    // Taille maximum : 2 Mo
    let maxSize = 2 * 1024 * 1024;
    
    // Vérifier la taille
    if (file.size > maxSize) {
        showError('photo', 'La photo ne doit pas dépasser 2Mo');
        return false;
    }
    
    // Types autorisés
    let allowedTypes = ['image/jpeg', 'image/png'];
    
    // Vérifier le type de fichier
    if (!allowedTypes.includes(file.type)) {
        showError('photo', 'Format accepté : JPG ou PNG uniquement');
        return false;
    }
    
    return true;
}

// ============================================================================
// 7. INITIALISATION DES FORMULAIRES (page registre.php et login.php)
// ============================================================================

function initForms() {
    
    // Si on est sur la page registre.php
    if (document.getElementById('agriculteurForm') || document.getElementById('ouvrierForm')) {
        initRegistrationForms();
    }
    
    // Si on est sur la page login.php
    if (document.getElementById('login-form')) {
        initLoginForm();
    }
}

// ============================================================================
// 8. FONCTIONS POUR LA PAGE D'INSCRIPTION (registre.php)
// ============================================================================

// Cette fonction initialise les formulaires d'inscription
function initRegistrationForms() {
    
    // Récupérer le paramètre "type" dans l'URL
    let urlParams = new URLSearchParams(window.location.search);
    let type = urlParams.get('type');
    
    // Afficher le bon formulaire selon le type
    if (type === 'ouvrier') {
        showOuvrierForm();
    } else {
        showAgriculteurForm();
    }
}

// Cette fonction affiche le formulaire Agriculteur
function showAgriculteurForm() {
    
    let agriculteurForm = document.getElementById('agriculteurForm');
    let ouvrierForm = document.getElementById('ouvrierForm');
    let btnAgriculteur = document.getElementById('btnAgriculteur');
    let btnOuvrier = document.getElementById('btnOuvrier');
    
    // Afficher le formulaire agriculteur
    if (agriculteurForm) {
        agriculteurForm.style.display = 'block';
    }
    
    // Cacher le formulaire ouvrier
    if (ouvrierForm) {
        ouvrierForm.style.display = 'none';
    }
    
    // Mettre le bouton agriculteur en actif
    if (btnAgriculteur) {
        btnAgriculteur.classList.add('active');
    }
    
    // Enlever l'actif du bouton ouvrier
    if (btnOuvrier) {
        btnOuvrier.classList.remove('active');
    }
    
    // Mettre à jour l'URL (sans recharger la page)
    let url = new URL(window.location);
    url.searchParams.set('type', 'agriculteur');
    window.history.pushState({}, '', url);
    //window.history.pushState(etat, titre, url);
}

// Cette fonction affiche le formulaire Ouvrier
function showOuvrierForm() {
    
    let agriculteurForm = document.getElementById('agriculteurForm');
    let ouvrierForm = document.getElementById('ouvrierForm');
    let btnAgriculteur = document.getElementById('btnAgriculteur');
    let btnOuvrier = document.getElementById('btnOuvrier');
    
    // Cacher le formulaire agriculteur
    if (agriculteurForm) {
        agriculteurForm.style.display = 'none';
    }
    
    // Afficher le formulaire ouvrier
    if (ouvrierForm) {
        ouvrierForm.style.display = 'block';
    }
    
    // Enlever l'actif du bouton agriculteur
    if (btnAgriculteur) {
        btnAgriculteur.classList.remove('active');
    }
    
    // Mettre le bouton ouvrier en actif
    if (btnOuvrier) {
        btnOuvrier.classList.add('active');
    }
    
    // Mettre à jour l'URL (sans recharger la page)
    let url = new URL(window.location);
    url.searchParams.set('type', 'ouvrier');
    window.history.pushState({}, '', url);
}

// Cette fonction affiche un aperçu de la photo sélectionnée
function previewPhoto(event) {
    
    let file = event.target.files[0];
    
    if (file) {
        
        // Valider la photo d'abord
        let photoValide = validatePhoto(event.target);
        
        if (!photoValide) {
            return;
        }
        
        // Créer un lecteur de fichier
        let reader = new FileReader();
        
        // Quand le fichier est chargé
        reader.onload = function(e) {
            
            let preview = document.getElementById('photo-preview');
            
            if (preview) {
                // Mettre l'image dans la prévisualisation
                preview.src = e.target.result;
                //e.target.result = le contenu du fichier converti en URL de données
                // Afficher la prévisualisation
                preview.classList.add('show');
                preview.style.display = 'block';
            }
        };
        
        // Lire le fichier
        reader.readAsDataURL(file);
        //Cette méthode lit le fichier et le convertit en URL de données (format data:image/jpeg;base64,...).
    }
    /*
    // Si l'utilisateur choisit "photo.jpg"
    file = {
        name: "photo.jpg",
        size: 1024000,
        type: "image/jpeg"
    }
    */
}

// ============================================================================
// 9. VALIDATION DU FORMULAIRE AGRICULTEUR (quand on clique sur "S'inscrire")
// ============================================================================

function validateAgriculteurForm(event) {
    
    // Empêcher l'envoi du formulaire pour l'instant
    event.preventDefault();
    
    // Effacer toutes les erreurs
    clearErrors('agriculteurForm');
    
    // Récupérer les valeurs des champs
    let nom = document.getElementById('nom_agri').value.trim();
    let prenom = document.getElementById('prenom_agri').value.trim();
    let cin = document.getElementById('cin_agri').value;
    let email = document.getElementById('email_agri').value.trim();
    let adresse = document.getElementById('adresse_agri').value.trim();
    let pseudo = document.getElementById('pseudo_agri').value;
    let password = document.getElementById('password_agri').value;
    let confirmPassword = document.getElementById('confirm_password_agri').value;
    let cgv = document.getElementById('cgv_agri').checked;
    
    let isValid = true;
    
    // Valider chaque champ
    if (!validateField('nom_agri', nom, 'text')) {
        isValid = false;
    }
    
    if (!validateField('prenom_agri', prenom, 'text')) {
        isValid = false;
    }
    
    if (!validateField('cin_agri', cin, 'cin')) {
        isValid = false;
    }
    
    if (!validateField('email_agri', email, 'email')) {
        isValid = false;
    }
    
    if (!validateField('adresse_agri', adresse, 'text')) {
        isValid = false;
    }
    
    if (!validateField('pseudo_agri', pseudo, 'pseudo')) {
        isValid = false;
    }
    
    if (!validateField('password_agri', password, 'password')) {
        isValid = false;
    }
    
    if (!validateConfirmField('confirm_password_agri', confirmPassword, 'password_agri')) {
        isValid = false;
    }
    
    // Valider les conditions générales
    if (!cgv) {
        let cgvError = document.getElementById('cgv_agri-error');
        
        if (cgvError) {
            cgvError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Vous devez accepter les conditions';
            cgvError.style.color = '#e74c3c';
            cgvError.style.fontSize = '12px';
            cgvError.style.marginTop = '5px';
            cgvError.style.display = 'block';
        }
        
        isValid = false;
    }
    
    // Si tout est valide, on envoie le formulaire
    if (isValid) {
        document.getElementById('agriculteurForm').submit();
    }
    
    return false;
}

// ============================================================================
// 10. VALIDATION DU FORMULAIRE OUVRIER (quand on clique sur "S'inscrire")
// ============================================================================

function validateOuvrierForm(event) {
    
    // Empêcher l'envoi du formulaire pour l'instant
    event.preventDefault();
    
    // Effacer toutes les erreurs
    clearErrors('ouvrierForm');
    
    // Récupérer les valeurs des champs
    let nom = document.getElementById('nom_ouv').value.trim();
    let prenom = document.getElementById('prenom_ouv').value.trim();
    let cin = document.getElementById('cin_ouv').value;
    let email = document.getElementById('email_ouv').value.trim();
    let description = document.getElementById('description').value.trim();
    let pseudo = document.getElementById('pseudo_ouv').value;
    let password = document.getElementById('password_ouv').value;
    let confirmPassword = document.getElementById('confirm_password_ouv').value;
    let cgv = document.getElementById('cgv_ouv').checked;
    let photo = document.getElementById('photo');
    
    let isValid = true;
    
    // Valider chaque champ
    if (!validateField('nom_ouv', nom, 'text')) {
        isValid = false;
    }
    
    if (!validateField('prenom_ouv', prenom, 'text')) {
        isValid = false;
    }
    
    if (!validateField('cin_ouv', cin, 'cin')) {
        isValid = false;
    }
    
    if (!validateField('email_ouv', email, 'email')) {
        isValid = false;
    }
    
    if (!validateField('description', description, 'text')) {
        isValid = false;
    }
    
    if (!validateField('pseudo_ouv', pseudo, 'pseudo')) {
        isValid = false;
    }
    
    if (!validateField('password_ouv', password, 'password')) {
        isValid = false;
    }
    
    if (!validateConfirmField('confirm_password_ouv', confirmPassword, 'password_ouv')) {
        isValid = false;
    }
    
    // Valider la photo
    if (!validatePhoto(photo)) {
        isValid = false;
    }
    
    // Valider les conditions générales
    if (!cgv) {
        let cgvError = document.getElementById('cgv_ouv-error');
        
        if (cgvError) {
            cgvError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Vous devez accepter les conditions';
            cgvError.style.color = '#e74c3c';
            cgvError.style.fontSize = '12px';
            cgvError.style.marginTop = '5px';
            cgvError.style.display = 'block';
        }
        
        isValid = false;
    }
    
    // Si tout est valide, on envoie le formulaire
    if (isValid) {
        document.getElementById('ouvrierForm').submit();
    }
    
    return false;
}

// ============================================================================
// 11. FONCTIONS POUR LA PAGE DE CONNEXION (login.php)
// ============================================================================

// Cette fonction initialise la page de connexion
function initLoginForm() {
    
    let loginForm = document.getElementById('login-form');
    
    if (!loginForm) {
        return;
    }
    
    let usernameInput = document.getElementById('username');
    let passwordInput = document.getElementById('password');
    
    // Quand on tape dans le champ pseudo, on efface l'erreur
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            clearError('username');
            hideLoginAlert();
        });
    }
    
    // Quand on tape dans le champ mot de passe, on efface l'erreur
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            clearError('password');
            hideLoginAlert();
        });
    }
}

// Cette fonction cache l'alerte de la page de connexion
function hideLoginAlert() {
    
    let alert = document.getElementById('login-alert');
    
    if (alert) {
        alert.style.display = 'none';
    }
}

// Cette fonction montre une alerte sur la page de connexion
function showLoginAlert(message, type) {
    
    let alert = document.getElementById('login-alert');
    
    if (!alert) {
        return;
    }
    
    // Choisir l'icône selon le type (succès ou erreur)
    if (type === 'success') {
        alert.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
    } else {
        alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
    }
    
    alert.className = 'alert alert-' + type;
    alert.style.display = 'block';
    
    // Cacher l'alerte après 3 secondes
    setTimeout(function() {alert.style.display = 'none';}, 3000);
}

// Cette fonction valide le formulaire de connexion
function validateLoginForm(event) {
    
    // Effacer toutes les erreurs
    clearErrors('login-form');
    
    // Récupérer les valeurs
    let username = document.getElementById('username').value.trim();
    let password = document.getElementById('password').value;
    
    let isValid = true;
    
    // Validation du pseudo
    if (!username) {
        showError('username', 'Pseudo requis');
        isValid = false;
    } else {
        // Vérifier que le pseudo ne contient que des lettres
        let reglePseudo = /^[A-Za-z]+$/;
        if (!reglePseudo.test(username)) {
            showError('username', 'Le pseudo doit contenir uniquement des lettres');
            isValid = false;
        }
    }
    
    // Validation du mot de passe
    if (!password) {
        showError('password', 'Mot de passe requis');
        isValid = false;
    } else {
        if (password.length < 8) {
            showError('password', 'Le mot de passe doit contenir au moins 8 caractères');
            isValid = false;
        } else if (!password.endsWith('$') && !password.endsWith('#')) {
            showError('password', 'Le mot de passe doit se terminer par $ ou #');
            isValid = false;
        }
    }
    
    return isValid;
}

// ============================================================================
// 12. FONCTIONS POUR LA PAGE D'ACCUEIL (index.html)
// ============================================================================

// Cette fonction ajoute la classe "active" aux liens quand on scrolle
function ajouterClassesActive() {
    
    // 1. Récupérer les sections de la page
    let section1 = document.getElementById('fonctionnement');
    let section2 = document.getElementById('avantages');
    let section3 = document.getElementById('contact');
    
    // 2. Mettre les sections dans un tableau
    let toutesLesSections = [section1, section2, section3];
    let sectionsQuiExistent = [];
    
    // 3. Garder seulement les sections qui sont sur la page
    for (let i = 0; i < toutesLesSections.length; i++) {
        if (toutesLesSections[i] !== null) { // Si la section existe
            sectionsQuiExistent.push(toutesLesSections[i]);
        }
    }
    
    // 4. Si aucune section n'existe, on arrête
    if (sectionsQuiExistent.length === 0) {
        return; // Sortir de la fonction
    }
    
    // 5. DÉTECTER LE SCROLL
    window.addEventListener('scroll', function() {
        
        let sectionVisible = ''; // Pour stocker quelle section est visible
        
        // 6. Vérifier chaque section
        for (let i = 0; i < sectionsQuiExistent.length; i++) {
            let section = sectionsQuiExistent[i];
            let positionSection = section.offsetTop; // Où commence la section
            
            // Si l'utilisateur a scrollé jusqu'à la section (ou 200px avant)
            if (window.pageYOffset >= positionSection - 200) {
                sectionVisible = section.getAttribute('id'); // On note l'ID
            }
        }
        
        // 7. Récupérer tous les liens du menu
        let liensMenu = document.querySelectorAll('.nav-menu a');
        
        // 8. Parcourir chaque lien
        for (let i = 0; i < liensMenu.length; i++) {
            // Enlever la classe "active" de tous les liens
            liensMenu[i].classList.remove('active');
            
            // Récupérer la cible du lien (ex: "#fonctionnement")
            let cible = liensMenu[i].getAttribute('href');
            
            // Si le lien correspond à la section visible
            if (cible === '#' + sectionVisible) {
                // Ajouter la classe "active" à ce lien
                liensMenu[i].classList.add('active');
            }
        }
    });
}

// Cette fonction gère les liens internes (avec #)
let anchors = document.querySelectorAll('a[href^="#"]');

for (let i = 0; i < anchors.length; i++) {
    anchors[i].addEventListener('click', function(e) {
        
        let href = this.getAttribute('href');
        
        // Ignorer les liens vides
        if (href === '#') {
            return;
        }
        
        let target = document.querySelector(href);
        
        if (target) {
            // Empêcher le comportement par défaut
            e.preventDefault();
            
            // Scroller en douceur vers la cible
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
}