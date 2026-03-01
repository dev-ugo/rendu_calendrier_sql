# Calendrier

Un calendrier PHP simple avec gestion d'événements, adossé à une base de données MySQL.

## Fonctionnalités

- Vue mensuelle avec navigation entre les mois
- Créer, modifier et supprimer des événements
- Tous les événements sont visibles, mais chaque utilisateur ne peut gérer que les siens

## Prérequis

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Un serveur web (Apache, Nginx, ou le serveur intégré PHP)

## Installation

**1. Créer la base de données**

```sql
CREATE DATABASE calendar_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**2. Importer le schéma et les données de démonstration**

```bash
mysql -u root -p calendar_db < dump-calendar_db.sql
```

**3. Configurer la connexion à la base de données**

Copier le fichier d'exemple et renseigner vos identifiants :

```bash
cp calendar_db_example.php calendar_db.php
```

Modifier `calendar_db.php` avec vos propres valeurs :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'calendar_db');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

**4. Lancer le serveur**

```bash
php -S localhost:8000
```

Puis ouvrir [http://localhost:8000/calendar.php](http://localhost:8000/calendar.php).

## Structure du projet

```
calendar/
├── calendar.php            # Application principale (UI + logique)
├── calendar_db.php         # Connexion à la base de données (non versionné)
├── calendar_db.example.php # Fichier de connexion à copier comme point de départ
└── calendar_db.sql         # Schéma et données de démonstration
```

## Identification des utilisateurs

Aucune connexion n'est requise. Chaque visiteur reçoit un identifiant aléatoire stocké dans un cookie (`calendar_user_id`, valable 2 ans). Cet identifiant est utilisé pour associer les événements à leur propriétaire et restreindre les droits de modification et de suppression.
