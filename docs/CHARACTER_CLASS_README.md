# Classe Character - Documentation

## Vue d'ensemble

La classe `Character` encapsule toutes les fonctionnalités liées aux personnages D&D dans l'application JDR MJ. Elle fournit une interface orientée objet pour gérer les personnages, leurs statistiques, leurs capacités, et leurs interactions avec le système.

## Structure de la classe

### Propriétés principales

#### Identité du personnage
- `id` - Identifiant unique du personnage
- `user_id` - ID du propriétaire du personnage
- `name` - Nom du personnage
- `race_id` - ID de la race
- `class_id` - ID de la classe
- `background_id` - ID de l'historique
- `level` - Niveau du personnage
- `experience_points` - Points d'expérience

#### Statistiques de base
- `strength` - Force
- `dexterity` - Dextérité
- `constitution` - Constitution
- `intelligence` - Intelligence
- `wisdom` - Sagesse
- `charisma` - Charisme

#### Informations de combat
- `armor_class` - Classe d'armure
- `initiative` - Initiative
- `speed` - Vitesse
- `hit_points_max` - Points de vie maximum
- `hit_points_current` - Points de vie actuels

#### Compétences et proficiens
- `proficiency_bonus` - Bonus de compétence
- `saving_throws` - Jets de sauvegarde
- `skills` - Compétences (JSON)
- `languages` - Langues (JSON)

#### Équipement et trésor
- `equipment` - Équipement (JSON)
- `money_gold` - Or
- `money_silver` - Argent
- `money_copper` - Cuivre

#### Informations personnelles
- `background` - Historique
- `alignment` - Alignement
- `personality_traits` - Traits de personnalité
- `ideals` - Idéaux
- `bonds` - Liens
- `flaws` - Défauts

#### Sorts
- `spells_known` - Sorts connus (JSON)
- `spell_slots` - Emplacements de sorts (JSON)

#### Métadonnées
- `profile_photo` - Photo de profil
- `is_equipped` - Équipement configuré
- `equipment_locked` - Équipement verrouillé
- `character_locked` - Personnage verrouillé
- `created_at` - Date de création
- `updated_at` - Date de mise à jour

#### Relations
- `race_name` - Nom de la race
- `class_name` - Nom de la classe
- `background_name` - Nom de l'historique

## Méthodes principales

### Constructeur et hydratation

#### `__construct(PDO $pdo = null, array $data = [])`
Initialise une nouvelle instance de Character.
- `$pdo` - Instance PDO (optionnel, utilise le singleton par défaut)
- `$data` - Données pour hydrater l'objet

#### `hydrate(array $data)`
Hydrate l'objet avec les données fournies.

### CRUD (Create, Read, Update, Delete)

#### `create(array $data, PDO $pdo = null)`
Crée un nouveau personnage dans la base de données.
- `$data` - Données du personnage
- `$pdo` - Instance PDO (optionnel)
- **Retourne** : Instance Character ou false en cas d'erreur

#### `findById($id, PDO $pdo = null)`
Trouve un personnage par son ID.
- `$id` - ID du personnage
- `$pdo` - Instance PDO (optionnel)
- **Retourne** : Instance Character ou null

#### `findByUserId($userId, PDO $pdo = null)`
Trouve tous les personnages d'un utilisateur.
- `$userId` - ID de l'utilisateur
- `$pdo` - Instance PDO (optionnel)
- **Retourne** : Tableau d'instances Character

#### `update(array $data)`
Met à jour le personnage.
- `$data` - Données à mettre à jour
- **Retourne** : true en cas de succès, false sinon

#### `delete()`
Supprime le personnage.
- **Retourne** : true en cas de succès, false sinon

### Méthodes utilitaires

#### `belongsToUser($userId)`
Vérifie si le personnage appartient à un utilisateur.
- `$userId` - ID de l'utilisateur
- **Retourne** : true si le personnage appartient à l'utilisateur

#### `getProficiencyBonus()`
Calcule le bonus de compétence basé sur le niveau.
- **Retourne** : Bonus de compétence

#### `getAbilityModifier($ability)`
Calcule le modificateur d'une caractéristique.
- `$ability` - Nom de la caractéristique
- **Retourne** : Modificateur de caractéristique

#### `getStrengthModifier()`, `getDexterityModifier()`, etc.
Méthodes spécialisées pour obtenir les modificateurs de chaque caractéristique.

#### `calculateArmorClass()`
Calcule la classe d'armure du personnage.
- **Retourne** : Classe d'armure

#### `calculateMaxHitPoints()`
Calcule les points de vie maximum.
- **Retourne** : Points de vie maximum

### Gestion de l'expérience et des niveaux

#### `addExperience($amount)`
Ajoute de l'expérience au personnage.
- `$amount` - Quantité d'expérience à ajouter
- **Retourne** : true en cas de succès

#### `updateLevelFromExperience()`
Met à jour le niveau basé sur l'expérience.

#### `calculateLevelFromExperience($xp)`
Calcule le niveau basé sur l'expérience.
- `$xp` - Points d'expérience
- **Retourne** : Niveau calculé

### Gestion des sorts

#### `getSpells()`
Récupère tous les sorts du personnage.
- **Retourne** : Tableau des sorts

#### `addSpell($spellId, $prepared = false, $known = true)`
Ajoute un sort au personnage.
- `$spellId` - ID du sort
- `$prepared` - Sort préparé
- `$known` - Sort connu
- **Retourne** : true en cas de succès

#### `removeSpell($spellId)`
Retire un sort du personnage.
- `$spellId` - ID du sort
- **Retourne** : true en cas de succès

#### `getSpellSlotsUsage()`
Récupère l'utilisation des emplacements de sorts.
- **Retourne** : Tableau de l'utilisation par niveau

#### `useSpellSlot($level)`
Utilise un emplacement de sort.
- `$level` - Niveau de l'emplacement
- **Retourne** : true en cas de succès

#### `freeSpellSlot($level)`
Libère un emplacement de sort.
- `$level` - Niveau de l'emplacement
- **Retourne** : true en cas de succès

#### `resetSpellSlotsUsage()`
Réinitialise l'utilisation des emplacements de sorts.
- **Retourne** : true en cas de succès

### Gestion de l'équipement

#### `getEquippedItems()`
Récupère l'équipement équipé du personnage.
- **Retourne** : Tableau de l'équipement équipé

#### `equipItem($itemName, $itemType, $slot)`
Équipe un objet.
- `$itemName` - Nom de l'objet
- `$itemType` - Type de l'objet
- `$slot` - Emplacement
- **Retourne** : true en cas de succès

#### `unequipItem($itemName)`
Déséquipe un objet.
- `$itemName` - Nom de l'objet
- **Retourne** : true en cas de succès

### Gestion de la rage (Barbares)

#### `getRageUsage()`
Récupère l'utilisation de la rage.
- **Retourne** : Tableau avec 'used' et 'max_uses'

#### `useRage()`
Utilise la rage.
- **Retourne** : true en cas de succès

#### `freeRage()`
Libère la rage.
- **Retourne** : true en cas de succès

#### `resetRageUsage()`
Réinitialise l'utilisation de la rage.
- **Retourne** : true en cas de succès

### Gestion des capacités

#### `getCapabilities()`
Récupère les capacités du personnage.
- **Retourne** : Tableau des capacités

#### `addCapability($capabilityId, $source = 'class', $sourceId = null)`
Ajoute une capacité au personnage.
- `$capabilityId` - ID de la capacité
- `$source` - Source de la capacité
- `$sourceId` - ID de la source
- **Retourne** : true en cas de succès

#### `removeCapability($capabilityId)`
Retire une capacité du personnage.
- `$capabilityId` - ID de la capacité
- **Retourne** : true en cas de succès

### Gestion des améliorations de caractéristiques

#### `getAbilityImprovements()`
Récupère les améliorations de caractéristiques.
- **Retourne** : Tableau des améliorations par caractéristique

#### `saveAbilityImprovements($improvements)`
Sauvegarde les améliorations de caractéristiques.
- `$improvements` - Tableau des améliorations
- **Retourne** : true en cas de succès

#### `calculateFinalAbilities($abilityImprovements = null)`
Calcule les caractéristiques finales avec les améliorations.
- `$abilityImprovements` - Améliorations (optionnel)
- **Retourne** : Tableau des caractéristiques finales

### Informations de campagne

#### `getCampaignInfo()`
Récupère les informations de campagne du personnage.
- **Retourne** : Tableau des informations de campagne ou null

### Utilitaires

#### `toArray()`
Convertit l'objet en tableau pour l'affichage.
- **Retourne** : Tableau de toutes les propriétés

## Couche de compatibilité

Le fichier `includes/character_compatibility.php` fournit des fonctions de compatibilité qui encapsulent les méthodes de la classe Character pour maintenir la compatibilité avec le code existant.

### Fonctions principales de compatibilité

- `createCharacter($data)` - Créer un personnage
- `getCharacterById($id)` - Trouver un personnage par ID
- `getCharactersByUserId($userId)` - Trouver les personnages d'un utilisateur
- `updateCharacterLevelFromExperience($characterId)` - Mettre à jour le niveau
- `getCharacterSpells($characterId)` - Récupérer les sorts
- `addSpellToCharacter($characterId, $spellId, $prepared)` - Ajouter un sort
- `removeSpellFromCharacter($characterId, $spellId)` - Retirer un sort
- `getSpellSlotsUsage($characterId)` - Utilisation des emplacements de sorts
- `useSpellSlot($characterId, $level)` - Utiliser un emplacement de sort
- `freeSpellSlot($characterId, $level)` - Libérer un emplacement de sort
- `resetSpellSlotsUsage($characterId)` - Réinitialiser les emplacements de sorts
- `getCharacterEquippedItems($characterId)` - Équipement équipé
- `equipItem($characterId, $itemName, $itemType, $slot)` - Équiper un objet
- `unequipItem($characterId, $itemName)` - Déséquiper un objet
- `getRageUsage($characterId)` - Utilisation de la rage
- `useRage($characterId)` - Utiliser la rage
- `freeRage($characterId)` - Libérer la rage
- `resetRageUsage($characterId)` - Réinitialiser la rage
- `getCharacterAbilityImprovements($characterId)` - Améliorations de caractéristiques
- `saveCharacterAbilityImprovements($characterId, $improvements)` - Sauvegarder les améliorations
- `calculateFinalAbilities($character, $abilityImprovements)` - Calculer les caractéristiques finales

## Intégration dans le système

### Fichiers modifiés

1. **`classes/init.php`** - Ajout du chargement de la classe Character
2. **`includes/functions.php`** - Inclusion de la couche de compatibilité
3. **`characters.php`** - Migration vers la classe Character
4. **`view_character.php`** - Migration vers la classe Character

### Utilisation

#### Création d'un personnage
```php
$characterData = [
    'user_id' => $_SESSION['user_id'],
    'name' => 'Mon Personnage',
    'race_id' => 1,
    'class_id' => 1,
    'level' => 1,
    'strength' => 15,
    'dexterity' => 14,
    // ... autres données
];

$character = Character::create($characterData);
```

#### Récupération d'un personnage
```php
$character = Character::findById($characterId);
if ($character) {
    echo "Nom: " . $character->name;
    echo "Niveau: " . $character->level;
    echo "Force: " . $character->strength;
}
```

#### Mise à jour d'un personnage
```php
$character = Character::findById($characterId);
if ($character) {
    $character->update([
        'level' => 2,
        'experience_points' => 1000
    ]);
}
```

#### Gestion des sorts
```php
$character = Character::findById($characterId);
if ($character) {
    // Ajouter un sort
    $character->addSpell($spellId, true, true);
    
    // Utiliser un emplacement de sort
    $character->useSpellSlot(1);
    
    // Récupérer les sorts
    $spells = $character->getSpells();
}
```

#### Gestion de l'équipement
```php
$character = Character::findById($characterId);
if ($character) {
    // Équiper un objet
    $character->equipItem('Épée longue', 'arme', 'main_principale');
    
    // Récupérer l'équipement équipé
    $equippedItems = $character->getEquippedItems();
}
```

## Avantages de la classe Character

### 1. **Encapsulation**
- Toute la logique liée aux personnages est centralisée
- Interface claire et cohérente
- Gestion des erreurs centralisée

### 2. **Réutilisabilité**
- Méthodes réutilisables dans toute l'application
- Code plus maintenable
- Moins de duplication

### 3. **Sécurité**
- Validation des données centralisée
- Gestion des permissions intégrée
- Protection contre les injections SQL

### 4. **Performance**
- Requêtes optimisées
- Cache des données
- Gestion efficace des connexions

### 5. **Extensibilité**
- Facile d'ajouter de nouvelles fonctionnalités
- Architecture modulaire
- Compatibilité avec le code existant

## Tests et validation

### Syntaxe PHP
- ✅ `classes/Character.php` - Syntaxe correcte
- ✅ `includes/character_compatibility.php` - Syntaxe correcte
- ✅ `characters.php` - Syntaxe correcte
- ✅ `view_character.php` - Syntaxe correcte

### Intégration
- ✅ Chargement des classes
- ✅ Couche de compatibilité
- ✅ Migration des fichiers existants
- ✅ Gestion des erreurs

## Conclusion

La classe `Character` fournit une interface moderne et robuste pour gérer les personnages D&D dans l'application. Elle maintient la compatibilité avec le code existant tout en offrant de nouvelles fonctionnalités et une meilleure organisation du code.

La migration est **complète et fonctionnelle** !
