# Système Homogène de Gestion des Capacités

## Vue d'ensemble

Le système homogène de capacités remplace l'ancien système hybride (fonctions PHP + base de données) par une approche entièrement basée sur la base de données. Toutes les capacités (classe, race, historique) sont maintenant stockées de manière cohérente et peuvent être gérées dynamiquement.

## Structure de la base de données

### Tables principales

#### `capability_types`
Définit les types de capacités avec leurs propriétés d'affichage.

```sql
CREATE TABLE capability_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(20)
);
```

**Types prédéfinis :**
- Combat (fas fa-sword, danger)
- Magie (fas fa-magic, purple)
- Défense (fas fa-shield-alt, primary)
- Mouvement (fas fa-running, info)
- Social (fas fa-users, success)
- Exploration (fas fa-compass, warning)
- Spécial (fas fa-star, secondary)
- Racial (fas fa-dragon, dark)
- Classe (fas fa-shield-alt, primary)
- Historique (fas fa-scroll, info)

#### `capabilities`
Stocke toutes les capacités avec leurs métadonnées.

```sql
CREATE TABLE capabilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    type_id INT NOT NULL,
    source_type ENUM('class', 'race', 'background', 'feat', 'item') NOT NULL,
    source_id INT,
    level_requirement INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Champs :**
- `name` : Nom de la capacité
- `description` : Description détaillée
- `type_id` : Référence vers `capability_types`
- `source_type` : Type de source (classe, race, historique, etc.)
- `source_id` : ID de la source (ID de classe, race, etc.)
- `level_requirement` : Niveau requis pour obtenir la capacité

#### `character_capabilities`
Liaison entre personnages et capacités.

```sql
CREATE TABLE character_capabilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    capability_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Fonctions PHP

### Récupération des capacités

#### `getCharacterCapabilities($character_id)`
Récupère toutes les capacités actives d'un personnage.

```php
$capabilities = getCharacterCapabilities(123);
```

#### `getClassCapabilities($class_id, $level)`
Récupère les capacités d'une classe pour un niveau donné.

```php
$barbarianCapabilities = getClassCapabilities(6, 5); // Barbare niveau 5
```

#### `getRaceCapabilities($race_id)`
Récupère les capacités raciales d'une race.

```php
$humanCapabilities = getRaceCapabilities(1); // Humain
```

#### `getBackgroundCapabilities($background_id)`
Récupère les capacités d'un historique.

```php
$nobleCapabilities = getBackgroundCapabilities(3); // Noble
```

### Gestion des capacités

#### `updateCharacterCapabilities($character_id)`
Met à jour automatiquement toutes les capacités d'un personnage selon sa classe, race, niveau et historique.

```php
if (updateCharacterCapabilities(123)) {
    echo "Capacités mises à jour avec succès";
}
```

#### `addCharacterCapability($character_id, $capability_id, $notes = '')`
Ajoute une capacité spécifique à un personnage.

```php
addCharacterCapability(123, 45, "Obtenu par don spécial");
```

#### `removeCharacterCapability($character_id, $capability_id)`
Retire une capacité d'un personnage.

```php
removeCharacterCapability(123, 45);
```

### Recherche et exploration

#### `searchCapabilities($search)`
Recherche des capacités par nom ou description.

```php
$results = searchCapabilities('rage');
```

#### `getAllCapabilitiesByType()`
Récupère toutes les capacités groupées par type.

```php
$groupedCapabilities = getAllCapabilitiesByType();
```

## Fonctions de compatibilité

Pour maintenir la compatibilité avec l'ancien système, les fonctions suivantes sont disponibles :

```php
// Ces fonctions utilisent maintenant la base de données
getBarbarianCapabilities($level)
getFighterCapabilities($level)
getWizardCapabilities($level)
getClericCapabilities($level)
getRangerCapabilities($level)
getPaladinCapabilities($level)
getBardCapabilities($level)
getDruidCapabilities($level)
getSorcererCapabilities($level)
getRogueCapabilities($level)
getMonkCapabilities($level)
getWarlockCapabilities($level)
```

## Installation et migration

### 1. Exécuter le script de configuration

```bash
php setup_capabilities_system.php
```

Ce script :
- Crée les tables nécessaires
- Insère les types de capacités
- Migre les capacités de base
- Met à jour tous les personnages existants

### 2. Tester le système

```bash
php test_capabilities_system.php
```

### 3. Vérifier l'intégration

Le fichier `view_character.php` a été modifié pour utiliser le nouveau système. Les capacités sont maintenant affichées avec :
- Icônes personnalisées par type
- Couleurs différenciées
- Informations de source (Classe, Race, Historique)
- Descriptions détaillées

## Avantages du nouveau système

### 1. **Homogénéité**
- Toutes les capacités sont stockées de la même manière
- Gestion cohérente des métadonnées (icônes, couleurs, types)

### 2. **Flexibilité**
- Ajout facile de nouvelles capacités
- Modification des descriptions sans redéploiement
- Gestion dynamique des niveaux requis

### 3. **Extensibilité**
- Support pour de nouveaux types de capacités (dons, objets magiques)
- Possibilité d'ajouter des capacités personnalisées
- Système de notes pour les capacités spéciales

### 4. **Performance**
- Requêtes optimisées avec index
- Cache possible au niveau application
- Évite le parsing de texte complexe

### 5. **Maintenance**
- Centralisation de la logique métier
- Facilité de correction et d'amélioration
- Historique des modifications

## Migration depuis l'ancien système

### Capacités de classe
- **Avant** : Fonctions PHP codées en dur
- **Après** : Stockées en base avec niveau requis

### Capacités raciales
- **Avant** : Champ `traits` TEXT avec parsing complexe
- **Après** : Entrées individuelles dans `capabilities`

### Capacités d'historique
- **Avant** : Champ `feature` dans `backgrounds`
- **Après** : Entrées dans `capabilities` avec `source_type = 'background'`

## Exemples d'utilisation

### Ajouter une nouvelle capacité de classe

```sql
INSERT INTO capabilities (name, description, type_id, source_type, source_id, level_requirement) 
VALUES (
    'Nouvelle capacité',
    'Description de la nouvelle capacité',
    1, -- ID du type (Combat)
    'class',
    6, -- ID de la classe (Barbare)
    10 -- Niveau requis
);
```

### Ajouter une capacité raciale personnalisée

```sql
INSERT INTO capabilities (name, description, type_id, source_type, source_id) 
VALUES (
    'Résistance spéciale',
    'Résistance aux dégâts de froid',
    3, -- ID du type (Défense)
    'race',
    1 -- ID de la race (Humain)
);
```

### Rechercher des capacités

```php
// Rechercher toutes les capacités de combat
$combatCapabilities = searchCapabilities('combat');

// Rechercher les capacités de niveau 5
$stmt = $pdo->prepare("
    SELECT * FROM capabilities 
    WHERE level_requirement = 5 AND is_active = 1
");
$stmt->execute();
$level5Capabilities = $stmt->fetchAll();
```

## Maintenance et évolutions

### Ajout de nouveaux types de capacités

```sql
INSERT INTO capability_types (name, description, icon, color) 
VALUES ('Temporel', 'Capacités liées au temps', 'fas fa-clock', 'info');
```

### Désactivation d'une capacité

```sql
UPDATE capabilities SET is_active = 0 WHERE id = 123;
```

### Modification d'une description

```sql
UPDATE capabilities 
SET description = 'Nouvelle description' 
WHERE id = 123;
```

Le système homogène de capacités offre une base solide et extensible pour la gestion des capacités de personnages D&D, tout en maintenant la compatibilité avec l'existant.
