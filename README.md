# 🚀 CREACO - Gestion Intelligente de Projets & Événements (Symfony Edition)

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777bb4.svg)](https://www.php.net/)
[![Symfony Version](https://img.shields.io/badge/Symfony-6.4-black.svg)](https://symfony.com/)
[![Composer](https://img.shields.io/badge/Composer-2.x-blue.svg)](https://getcomposer.org/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**NetWerkers** est une plateforme web moderne développée avec Symfony 6.4, conçue pour centraliser et simplifier la gestion des projets, des missions et des événements. En tirant parti de l'intelligence artificielle (Groq/Gemini,...) et d'intégrations tierces puissantes (Google Calendar, DocuSign, SendGrid,...), elle offre une solution robuste pour la collaboration d'équipe.

---

## 📋 Table des Matières

- [À propos du Projet](#-à-propos-du-projet)
- [Fonctionnalités Clés](#-fonctionnalités-clés)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Utilisation](#-utilisation)
- [Technologies Utilisées](#-technologies-utilisées)
- [Contribution](#-contribution)
- [Licence](#-licence)

---

## 🌟 À propos du Projet

NetWerkers Web transpose la puissance de l'application desktop originale dans un environnement web collaboratif. Que ce soit pour piloter des projets d'innovation, organiser des événements d'entreprise ou gérer des flux de travail complexes, NetWerkers centralise tout en une interface élégante et réactive.

**Le problème qu'il résout :** La fragmentation des outils. NetWerkers unifie la planification, la communication, la signature légale et l'assistance par IA dans un seul écosystème sécurisé.

---

## ✨ Fonctionnalités Clés

- **🤖 Assistant IA (Groq/Gemini) :** Analyse intelligente, génération de descriptions et aide à la décision intégrée.
- **📅 Gestion d'Événements :** Planification complète avec synchronisation via Google Calendar API.
- **🛠️ Système de Missions (TSK) :** Gestion de tickets, assignation de tâches et suivi de progression en temps réel.
- **🔐 Authentification Hybride :** Connexion sécurisée classique et intégration Google OAuth2 via `knpu/oauth2-client-bundle`.
- **📄 Signature Électronique :** Intégration native avec l'API DocuSign pour la validation de documents officiels.
- **📊 Dashboard Analytics :** Visualisation dynamique des KPI de la plateforme (Membres, Projets, Événements).
- **💬 Forum & Collaboration :** Espace d'échange moderne avec modération et filtrage de contenu.

---

## ⚙️ Installation

### Prérequis
- **PHP 8.2** ou supérieur.
- **Composer**.
- **MySQL 8.0+** ou MariaDB.
- **Symfony CLI** (recommandé).
- **Docker** (optionnel, via `compose.yaml`).

### Étapes d'installation

1. **Cloner le repository :**
   ```bash
   git clone https://github.com/votre-utilisateur/netwerkers-symfony.git
   cd netwerkers-symfony
   ```

2. **Installer les dépendances :**
   ```bash
   composer install
   ```

3. **Configurer la base de données :**
   - Créez votre fichier `.env.local` (voir section [Configuration](#-configuration)).
   - Gérez les migrations :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. **Préparer les assets :**
   ```bash
   php bin/console importmap:install
   ```

---

## 🛠️ Configuration

Éditez le fichier `.env.local` pour configurer vos services :

```env
### > doctrine/doctrine-bundle ###
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/netwerkers_db?serverVersion=8.0.32&charset=utf8mb4"
### < doctrine/doctrine-bundle ###

### > External APIs ###
GOOGLE_CLIENT_ID=votre_id
GOOGLE_CLIENT_SECRET=votre_secret
DOCUSIGN_INTEGRATION_KEY=votre_cle
GROQ_API_KEY=votre_cle_groq
MAILER_DSN=sendgrid://KEY@default
### < External APIs ###
```

---

## 🚀 Utilisation

Lancez le serveur de développement local :

```bash
symfony server:start
```

Accédez ensuite à l'application via `http://localhost:8000`.

### Commandes Utiles
- **Nettoyage du cache :** `php bin/console cache:clear`
- **Tests unitaires :** `php bin/console test:collab` ou `vendor/bin/phpunit`
- **Analyse statique :** `composer phpstan`

---

## 🛠 Technologies Utilisées

- **Framework :** Symfony 6.4 (Full-stack).
- **Frontend :** Twig, Symfony UX (Turbo, Stimulus), AssetMapper.
- **ORM :** Doctrine 3.x avec MySQL.
- **IA :** Groq PHP SDK & Gemini API.
- **Communication :** SendGrid, Mailjet.
- **Services :** DocuSign eSign API, Google OAuth2, Google Calendar.

---

## 🤝 Contribution

1. Forkez le projet.
2. Créez une branche (`git checkout -b feature/AmazingFeature`).
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`).
4. Pushez vers la branche (`git push origin feature/AmazingFeature`).
5. Ouvrez une **Pull Request**.

---

## 📄 Licence

Distribué sous la licence **MIT**. Voir le fichier `LICENSE` pour plus d'informations.

---

Développé avec ❤️ par l'équipe NetWerkers
