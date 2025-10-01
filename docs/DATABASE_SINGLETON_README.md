# Singleton Database - Documentation

## Vue d'ensemble

Le singleton Database a été implémenté pour centraliser la gestion des connexions PDO et éliminer les dépendances multiples à la variable `$pdo` globale. Cette approche améliore la maintenabilité du code et facilite la gestion des connexions à la base de données.

## Architecture

### Classe Database (Singleton)

La classe `Database` implémente le pattern Singleton et gère :
- La connexion unique à la base de données
- La configuration automatique selon l'environnement
- Les méthodes utilitaires pour les requêtes

### Fichiers impliqués

- `classes/Database.php` - Classe singleton principale
- `config/database.php` - Configuration mise à jour pour utiliser le singleton
- `classes/init.php` - Fonction `getPDO()` mise à jour
- `includes/database_compatibility.php` - Fonctions de compatibilité
- `classes/User.php` - Mise à jour pour utiliser le singleton
- `classes/Campaign.php` - Mise à jour pour utiliser le singleton

## Utilisation

### 1. Accès à l'instance PDO

```php
// Via la fonction utilitaire (recommandé)
$pdo = getPDO();

// Via la classe Database directement
$pdo = Database::getInstance()->getPdo();

// Via la variable globale (compatibilité)
global $pdo; // Toujours disponible
```

### 2. Requêtes via le singleton

```php
// Méthodes statiques de la classe Database
$result = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
$results = Database::fetchAll("SELECT * FROM campaigns WHERE dm_id = ?", [$dmId]);

// Fonctions de compatibilité
$result = fetchQuery("SELECT * FROM users WHERE id = ?", [$userId]);
$results = fetchAllQuery("SELECT * FROM campaigns WHERE dm_id = ?", [$dmId]);
```

### 3. Création d'objets

```php
// Les classes User et Campaign utilisent automatiquement le singleton
$user = new User(); // PDO automatiquement injecté
$campaign = new Campaign(); // PDO automatiquement injecté

// Création via méthodes statiques
$user = User::create($userData); // PDO automatiquement injecté
$campaign = Campaign::create($campaignData); // PDO automatiquement injecté
```

## Migration depuis l'ancien système

### Avant (avec $pdo global)

```php
require_once 'config/database.php'; // Crée $pdo global

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
```

### Après (avec singleton)

```php
require_once 'classes/init.php'; // Inclut Database et User

// Option 1: Méthodes statiques
$user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);

// Option 2: Fonctions de compatibilité
$user = fetchQuery("SELECT * FROM users WHERE id = ?", [$userId]);

// Option 3: Variable globale (toujours disponible)
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
```

## Avantages

### 1. **Centralisation**
- Une seule instance de connexion PDO
- Configuration centralisée
- Gestion d'erreurs unifiée

### 2. **Performance**
- Évite la création de multiples connexions
- Réutilisation de la même instance
- Optimisation des ressources

### 3. **Maintenabilité**
- Code plus propre et organisé
- Facilite les tests unitaires
- Réduction des dépendances

### 4. **Compatibilité**
- La variable `$pdo` globale reste disponible
- Migration progressive possible
- Pas de rupture du code existant

## Exemples d'utilisation

### Fichier view_scene_player.php

```php
<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';

// Au lieu de require_once 'config/database.php';

$user_id = $_SESSION['user_id'];

// Récupération des données via le singleton
$place = Database::fetch("
    SELECT p.*, c.title as campaign_title
    FROM places p 
    INNER JOIN place_campaigns pc ON p.id = pc.place_id
    JOIN campaigns c ON pc.campaign_id = c.id 
    JOIN place_players pp ON p.id = pp.place_id 
    WHERE pp.player_id = ? AND c.id = ?
    LIMIT 1
", [$user_id, $campaign_id]);

$players = Database::fetchAll("
    SELECT u.username, pp.arrival_time
    FROM place_players pp
    JOIN users u ON pp.player_id = u.id
    WHERE pp.place_id = ?
    ORDER BY pp.arrival_time ASC
", [$place_id]);
?>
```

### Création d'utilisateur

```php
<?php
require_once 'classes/init.php';

$userData = [
    'username' => 'nouveau_joueur',
    'email' => 'joueur@example.com',
    'password' => 'motdepasse123',
    'role' => 'player'
];

// Création via la classe User (utilise automatiquement le singleton)
$user = User::create($userData);
?>
```

## Tests

Le fichier `test_database_singleton.php` contient des tests complets pour vérifier :
- Le fonctionnement du singleton
- L'intégration avec les classes User et Campaign
- La compatibilité avec la variable `$pdo` globale
- Les méthodes statiques de requête

```bash
php test_database_singleton.php
```

## Migration recommandée

### Étape 1: Mise à jour des includes
```php
// Remplacer
require_once 'config/database.php';

// Par
require_once 'classes/init.php';
```

### Étape 2: Utilisation des méthodes statiques
```php
// Remplacer
$stmt = $pdo->prepare("SELECT * FROM table WHERE id = ?");
$stmt->execute([$id]);
$result = $stmt->fetch();

// Par
$result = Database::fetch("SELECT * FROM table WHERE id = ?", [$id]);
```

### Étape 3: Création d'objets
```php
// Remplacer
$user = new User($pdo, $data);

// Par
$user = new User(null, $data); // PDO automatiquement injecté
```

## Compatibilité

Le système est entièrement rétrocompatible :
- La variable `$pdo` globale reste disponible
- Les anciens fichiers continuent de fonctionner
- Migration progressive possible
- Aucune rupture du code existant

## Conclusion

Le singleton Database offre une solution élégante pour centraliser la gestion des connexions PDO tout en maintenant la compatibilité avec le code existant. Il facilite la maintenance et améliore la structure du code sans casser les fonctionnalités existantes.
