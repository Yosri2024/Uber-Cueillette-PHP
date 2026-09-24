# 🚜 Uber Cueillette — PHP + MySQL

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](database/uber_cueillette.sql)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Plateforme web qui met en relation **agriculteurs** (publient des offres de cueillette) et **ouvriers agricoles** (postulent). Projet WEB2 2026.

---

## 📸 Rôles

| Rôle | Dossier | Accès |
|------|---------|-------|
| **Agriculteur** | `src/agriculteur/` | `dashboard.php`, `ajouter-offre.php`, `mes-offres.php`, `postulants.php`, `noter-ouvriers.php`, `profil.php` |
| **Ouvrier** | `src/ouvrier/` | `dashboard.php`, `offres-disponibles.php`, `mes-candidatures.php`, `mes-chantiers.php`, `profil.php` |
| **Public** | `public/` | `index.html`, `login.php`, `registre.php`, `logout.php` |

**Traitement** `src/traitement/` : `ajouter-offre.php`, `repondre-candidature.php`, `supprimer-offre.php` (actions POST).

---

## 🗂️ Structure

```
Uber-Cueillette-PHP/
├── public/                 # Point d'entrée web (DocumentRoot)
│   ├── index.html
│   ├── login.php
│   ├── registre.php
│   └── logout.php
├── src/
│   ├── agriculteur/        # 8 pages agriculteur
│   ├── ouvrier/            # 5 pages ouvrier
│   └── traitement/         # 3 handlers
├── config/
│   ├── database.php        # PDO MySQL (localhost, root, "")
│   └── test.php
├── assets/
│   ├── css/style.css
│   ├── js/validation.js
│   └── images/ (agric.png, olive.png, etc.)
├── database/
│   └── uber_cueillette.sql # Dump complet (3 agriculteurs, 3 ouvriers de test)
├── docs/
│   ├── rapport.pdf
│   ├── sujet.pdf
│   └── plan.md
├── .github/workflows/php.yml
└── README.md
```

**DB** `uber_cueillette` : `agriculteur`, `ouvrier`, `offre`, `candidature`, `chantier` (voir `database/uber_cueillette.sql`).

---

## 🚀 Installation

### 1. Prérequis
* PHP 8.x, MySQL 8, Apache (XAMPP / Laragon) ou `php -S`
* `pdo_mysql` activé

### 2. Base de données
```bash
# via phpMyAdmin -> Importer database/uber_cueillette.sql
# ou CLI
mysql -u root -p < database/uber_cueillette.sql
# Vérif
mysql -u root -e "USE uber_cueillette; SHOW TABLES;"
```

### 3. Config
`config/database.php` :
```php
$host = 'localhost';
$dbname = 'uber_cueillette';
$username = 'root';
$password = '';
// $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", ...)
```
Adapter si `root` a un mdp ou port différent.

### 4. Lancer

**XAMPP :** placer le projet dans `htdocs/Uber-Cueillette` -> `http://localhost/Uber-Cueillette/public/`

**PHP built-in :**
```bash
php -S localhost:8000 -t public
# Ouvrir http://localhost:8000
```

**Test connexion :** `http://localhost:8000/../config/test.php` ou `http://localhost/Uber-Cueillette/config/test.php`

---

## 🔐 Comptes de test (issus du dump)

| Pseudo | Email | MDP | Rôle |
|--------|-------|-----|------|
| `mohamedagri` | `mohamed.benali@email.com` | `agri123$` | Agriculteur |
| `samiagri` | `sami.trabelsi@email.com` | `agri456#` | Agriculteur |
| ...(voir `database/uber_cueillette.sql` `INSERT INTO ouvrier` pour ouvriers) |

> Mots de passe en clair dans le dump pour dev — en prod hasher avec `password_hash()`.

---

## 🧪 CI

`.github/workflows/php.yml` : `php 8.2` + `composer validate` + `php -l` syntax check.

---

## 📄 Licence

MIT — voir [LICENSE](LICENSE).
