# UnivThèqueFs — Archives Pédagogiques Numériques
> Système de gestion des archives pédagogiques pour la Filière Informatique  
> Faculté des Sciences — Université de Ngaoundéré, Cameroun

![PHP](https://img.shields.io/badge/PHP-8.2-blue) ![MySQL](https://img.shields.io/badge/MySQL-8.0-orange) ![JavaScript](https://img.shields.io/badge/JavaScript-ES6-yellow) ![License](https://img.shields.io/badge/license-MIT-green)

---

## 📋 Description

**UnivThèqueFs** est une plateforme web d'archivage et de consultation des documents pédagogiques (Cours, TD, TP, Contrôles, Examens, TPE) pour le cycle Licence en Informatique (L1, L2, L3) — 36 UEs sur 6 semestres.

## ✨ Fonctionnalités

- 📚 Consultation des archives par niveau (L1/L2/L3), semestre et UE
- 🔍 Recherche avancée multi-critères
- ⬇️ Téléchargement direct des documents (PDF, DOCX)
- 🔐 Authentification JWT (Admin / Étudiant)
- 📤 Upload de documents avec validation
- ⚙️ Tableau de bord administrateur complet
- 📱 Interface responsive

## 🛠️ Technologies

| Couche | Technologies |
|--------|-------------|
| Frontend | HTML5, CSS3, JavaScript ES6, Font Awesome |
| Backend | PHP 8.2, PDO, JWT maison |
| Base de données | MySQL 8.0 — 9 tables |
| Serveur | Apache XAMPP |

## 🚀 Installation rapide (XAMPP)

### Prérequis
- XAMPP ≥ 8.0 (Apache + MySQL)
- PHP ≥ 8.0

### Étapes

**1. Cloner le repository**
```bash
git clone https://github.com/TON_USERNAME/UnivThequeFs.git
```

**2. Copier dans htdocs**
```
C:\xampp\htdocs\UnivThequeFs\
```

**3. Configurer Apache** — dans `httpd.conf` :
```apache
LoadModule rewrite_module modules/mod_rewrite.so

<Directory "C:/xampp/htdocs">
    AllowOverride All
</Directory>
```

**4. Lancer l'installation automatique**

Démarrer Apache + MySQL dans XAMPP, puis ouvrir :
```
http://localhost/UnivThequeFs/setup.php
```
→ Crée la base, les tables, les 36 UEs et les comptes. **Supprimer setup.php après !**

**5. Accéder au site**
```
http://localhost/UnivThequeFs/
```

## 🔑 Comptes par défaut

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@univtheque.cm | Admin@2024 |
| Étudiant | etudiant@univtheque.cm | Etudiant@2024 |

> ⚠️ Changer ces mots de passe en production !

## 📁 Structure

```
UnivThequeFs/
├── .htaccess                  # Config Apache (CORS, Authorization)
├── index.html                 # Redirection vers frontend/
├── setup.php                  # Installation auto (supprimer après)
├── backend/
│   ├── api/index.php          # Routeur REST API
│   ├── config/database.php    # Config BDD + JWT
│   ├── controllers/           # AuthController, DocumentController
│   ├── models/                # User, Document
│   ├── database/univtheque.sql
│   └── uploads/               # Fichiers déposés
└── frontend/
    ├── index.html             # Page d'accueil
    ├── css/                   # Styles
    ├── js/                    # Scripts (main.js, search.js)
    └── pages/
        ├── licence1.html      # L1 — Semestres 1 & 2
        ├── licence2.html      # L2 — Semestres 3 & 4
        ├── licence3.html      # L3 — Semestres 5 & 6
        ├── document.html      # Fiche document
        └── admin.html         # Tableau de bord admin
```

## 🔌 API REST

Base URL : `/backend/api/index.php`

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| POST | `/auth/login` | — | Connexion → JWT |
| POST | `/auth/register` | — | Inscription |
| GET | `/documents` | — | Liste des documents |
| POST | `/documents` | Admin | Upload document |
| GET | `/documents/{id}/download` | — | Télécharger |
| PATCH | `/documents/{id}/statut` | Admin | Changer statut |
| GET | `/stats` | — | Statistiques |
| GET | `/ues` | — | Liste des UEs |
| GET | `/professeurs` | — | Liste des profs |

## 👨‍💻 Auteur

Projet académique — Filière Informatique  
Faculté des Sciences — Université de Ngaoundéré  
Année 2024-2025

## 📄 Licence

MIT License
