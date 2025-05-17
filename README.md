### 🛒 `README.md` – Projet E-commerce Symfony

# 🛍️ Application E-commerce Symfony

Ce projet est un site e-commerce complet développé avec **Symfony v6.4** pour le backend et **Twig** pour le frontend, intégrant **Stripe** pour le traitement sécurisé des paiements.

---

## 🚀 Mise en route

### Prérequis

- PHP ^8.1
- Composer  
- Symfony CLI  
- MySQL  
- Stripe CLI (pour les tests de webhook)  
- Service SMTP (pour l’envoi des e-mails)

#### 📦 Installation de Stripe CLI

Stripe CLI permet d’écouter les événements Webhook localement pendant le développement.

➡️ [Voir ma vidéo explicative sur YouTube](https://youtu.be/jJu8vQH7hLY?t=10)  
➡️ [Documentation officielle Stripe CLI](https://stripe.com/docs/stripe-cli#install)

````markdown
### Installation

```bash
# Cloner le dépôt
git clone https://github.com/younestalibi/symfony-ecommerce.git
cd symfony-ecommerce

# Installer les dépendances
composer install

# Configurer les variables d'environnement
cp .env .env.local
# Modifier DATABASE_URL et MAILER_DSN et les clés STRIPE dans .env.local

# Créer la base de données et exécuter les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Créer un administrateur
php bin/console app:create-admin

# Vider le cache (optionnel)
php bin/console cache:clear

# Lancer le serveur de développement
symfony serve

# Écouter les événements Stripe via webhook
stripe listen --forward-to localhost:8000/payment/webhook

# Activer le worker Messenger
php bin/console messenger:consume async
````

---

## 🎯 Fonctionnalités

### ✅ Frontend (côté client)

* 🛒 Liste des produits avec recherche et filtres
* 🔍 Détail d’un produit
* 🧺 Gestion du panier : ajout, modification, suppression
* 📦 Passage de commande et validation
* 🏠 Gestion des adresses : création, modification, suppression, adresse par défaut
* 💳 Intégration de Stripe pour les paiements
* 📜 Historique des commandes et détails de chaque commande

### ✅ Backend (espace administrateur)

* 🛒 Gestion des produits (ajout, modification, suppression)
* 🗂️ Gestion des catégories (CRUD)
* 📋 Visualisation des commandes avec leurs détails
* 🔄 Mise à jour du statut des commandes

### ✅ Authentification

* 🔐 Inscription et connexion
* 🔁 Fonctionnalité "Mot de passe oublié" et réinitialisation

### ✅ Notifications par e-mail

* 📧 Confirmation de commande
* 📧 Confirmation de paiement
* 📧 Notification au back-office lors d’une nouvelle commande
* ⚠️ Alerte sur le stock faible
* 🔄 Notification lors du changement de statut d'une commande

---

## 📁 Structure du projet

```
src/
├── Controller/
├── Entity/
├── Repository/
├── Service/
├── Form/
├── Security/
├── Enum/
└── Twig/

templates/
├── Admin/
├── Frontend/
├── Layout/
├── Components/
└── email/
```
