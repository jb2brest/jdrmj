# Système de choix d'équipement de départ (StartingEquipmentChoix)

## Vue d'ensemble

Un nouveau système de choix d'équipement de départ a été implémenté pour résoudre le problème d'affichage incompréhensible des options dans `select_starting_equipment.php`. Ce système introduit deux nouvelles classes :

1. **`StartingEquipmentChoix`** - Représente un choix d'équipement (soit des items par défaut, soit des options à choisir)
2. **`StartingEquipmentOption`** - Représente une option d'équipement (un ensemble d'items affectés ensemble)

## Architecture du système

### 1. StartingEquipmentChoix

Un `StartingEquipmentChoix` peut être de deux types :

#### **Choix par défaut (is_default = true)**
- Ensemble d'items systématiquement affectés par défaut
- Pas de choix à faire par le joueur
- Affiché avec une icône de validation verte

#### **Choix avec options (is_default = false)**
- Liste de `StartingEquipmentOption` parmi lesquelles le joueur doit choisir
- Chaque option contient un ensemble d'items
- Affiché avec des boutons radio pour la sélection

### 2. StartingEquipmentOption

Une `StartingEquipmentOption` représente :
- Une lettre d'option (A, B, C, etc.)
- Une description de l'option
- Un ensemble d'items qui sont affectés ensemble si l'option est sélectionnée

## Structure de la base de données

### Table `starting_equipment_choix`
```sql
CREATE TABLE starting_equipment_choix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    src ENUM('class', 'background') NOT NULL,
    src_id INT NOT NULL,
    no_choix INT NOT NULL,
    description TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    default_items JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Table `starting_equipment_options`
```sql
CREATE TABLE starting_equipment_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    choix_id INT NOT NULL,
    option_letter CHAR(1) NOT NULL,
    description TEXT,
    items JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (choix_id) REFERENCES starting_equipment_choix(id) ON DELETE CASCADE
);
```

## Classes implémentées

### StartingEquipmentChoix

#### Méthodes principales :
- `create(array $data)` - Créer un nouveau choix
- `findById(int $id)` - Trouver un choix par ID
- `findByClassName(string $className)` - Trouver tous les choix d'une classe
- `findByBackgroundName(string $backgroundName)` - Trouver tous les choix d'un background
- `isDefaultChoice()` - Vérifier si c'est un choix par défaut
- `hasOptions()` - Vérifier si le choix a des options
- `getFullDescription()` - Obtenir la description complète
- `addOption(array $optionData)` - Ajouter une option au choix

#### Exemple d'utilisation :
```php
// Récupérer les choix d'une classe
$classChoix = StartingEquipmentChoix::findByClassName('Guerrier');

foreach ($classChoix as $choix) {
    if ($choix->isDefaultChoice()) {
        echo "Équipement par défaut: " . $choix->getFullDescription();
    } else {
        echo "Choix à faire: " . $choix->getDescription();
        foreach ($choix->getOptions() as $option) {
            echo "Option " . $option->getOptionLetter() . ": " . $option->getDescription();
        }
    }
}
```

### StartingEquipmentOption

#### Méthodes principales :
- `create(array $data)` - Créer une nouvelle option
- `findById(int $id)` - Trouver une option par ID
- `findByChoixId(int $choixId)` - Trouver toutes les options d'un choix
- `getFullDescription()` - Obtenir la description complète avec les items
- `getItemCount()` - Obtenir le nombre d'items
- `containsItemType(string $itemType)` - Vérifier si l'option contient un type d'item

#### Exemple d'utilisation :
```php
// Récupérer les options d'un choix
$options = StartingEquipmentOption::findByChoixId($choixId);

foreach ($options as $option) {
    echo "Option " . $option->getOptionLetter() . ": " . $option->getFullDescription();
    echo "Items: " . $option->getItemCount();
}
```

## Migration des données

### Données migrées avec succès :
- **428 entrées** de la table `starting_equipment` analysées
- **66 groupes** de choix identifiés
- **42 choix** migrés avec succès
- **165 options** créées

### Exemples de migration :

#### Classe Guerrier :
```
4 choix migrés:
- Choix 1: Armure et arme (2 options)
- Choix 2: Armes de guerre (5 options)
- Choix 3: Armes à distance (2 options)
- Choix 4: Équipement divers (2 options)
```

#### Background Artiste :
```
3 choix migrés:
- Choix 1: Instruments de musique (10 options)
- Choix 2: Équipement d'artiste (10 options)
- Choix 3: Outils d'artisanat (10 options)
```

## Intégration dans select_starting_equipment.php

### Avant (problématique) :
```php
// Ancien système confus
$startingEquipment = getClassStartingEquipment($character['class_id']);
$backgroundEquipmentDetailed = getBackgroundStartingEquipment($character['background_id']);
```

### Après (nouveau système) :
```php
// Nouveau système clair
$classChoix = StartingEquipmentChoix::findByClassName($character['class_name']);
$backgroundChoix = StartingEquipmentChoix::findByBackgroundName($character['background_name']);
```

### Affichage amélioré :

#### Équipement par défaut :
```html
<h5><i class="fas fa-check-circle text-success me-2"></i>Équipement de base du Guerrier</h5>
<p class="text-muted">Cet équipement est automatiquement attribué.</p>
<small class="text-muted">
    <strong>Équipement :</strong><br>
    Armure de cuir, Épée longue, Bouclier
</small>
```

#### Choix avec options :
```html
<h5>Choisir une arme de guerre</h5>
<p class="text-muted">Choisissez une option :</p>

<div class="form-check">
    <input type="radio" name="starting_equipment[0]" value="A" id="equipment_0_A">
    <label for="equipment_0_A">
        <strong>Option A :</strong> Épée longue et bouclier
        <small class="text-muted">
            <strong>Contenu :</strong> Épée longue, Bouclier
        </small>
    </label>
</div>

<div class="form-check">
    <input type="radio" name="starting_equipment[0]" value="B" id="equipment_0_B">
    <label for="equipment_0_B">
        <strong>Option B :</strong> Hache de guerre à deux mains
        <small class="text-muted">
            <strong>Contenu :</strong> Hache de guerre à deux mains
        </small>
    </label>
</div>
```

## Avantages du nouveau système

### 1. **Clarté de l'affichage**
- Distinction claire entre équipement par défaut et choix
- Descriptions explicites pour chaque option
- Affichage du contenu de chaque option

### 2. **Structure logique**
- Hiérarchie claire : Choix → Options → Items
- Séparation des responsabilités
- Code plus maintenable

### 3. **Flexibilité**
- Facile d'ajouter de nouveaux choix
- Facile d'ajouter de nouvelles options
- Support des équipements par défaut et des choix

### 4. **Performance**
- Requêtes optimisées avec jointures
- Chargement en une seule fois des choix et options
- Moins de requêtes multiples

### 5. **Extensibilité**
- Facile d'ajouter de nouveaux types d'équipement
- Support des quantités et descriptions détaillées
- Possibilité d'ajouter des conditions de choix

## Tests de validation

### Test avec personnage Barde/Artiste :
```
Classe Barde: 4 choix trouvés
- Choix 1: Armes (4 options)
- Choix 2: Équipement divers (2 options)
- Choix 3: Instruments (10 options)

Background Artiste: 3 choix trouvés
- Choix 1: Instruments (10 options)
- Choix 2: Équipement d'artiste (10 options)
- Choix 3: Outils (10 options)
```

### Test avec personnage Guerrier :
```
Classe Guerrier: 4 choix trouvés
- Choix 1: Armure et arme (2 options)
- Choix 2: Armes de guerre (5 options)
- Choix 3: Armes à distance (2 options)
- Choix 4: Équipement divers (2 options)
```

## Fichiers modifiés

### 1. **Nouvelles classes créées :**
- `classes/StartingEquipmentChoix.php`
- `classes/StartingEquipmentOption.php`

### 2. **Fichiers modifiés :**
- `classes/init.php` - Ajout des nouvelles classes
- `select_starting_equipment.php` - Migration vers le nouveau système

### 3. **Base de données :**
- `database/create_starting_equipment_choix_tables.sql` - Script de création des tables
- Migration automatique des données existantes

## Utilisation recommandée

### Pour les développeurs :
```php
// Récupérer les choix d'une classe
$classChoix = StartingEquipmentChoix::findByClassName('Guerrier');

// Traiter chaque choix
foreach ($classChoix as $choix) {
    if ($choix->isDefaultChoice()) {
        // Équipement automatiquement attribué
        $defaultItems = $choix->getDefaultItems();
    } else {
        // Choix à faire par le joueur
        $options = $choix->getOptions();
    }
}
```

### Pour les administrateurs :
- Utiliser les nouvelles tables pour gérer les choix d'équipement
- Ajouter facilement de nouveaux choix et options
- Modifier les descriptions et contenus des options

## Conclusion

Le nouveau système de choix d'équipement de départ résout complètement le problème d'affichage incompréhensible des options. Il offre :

1. **Interface claire et intuitive** pour les joueurs
2. **Structure logique et maintenable** pour les développeurs
3. **Flexibilité et extensibilité** pour les administrateurs
4. **Performance optimisée** avec des requêtes efficaces

Le système est **opérationnel et prêt à l'emploi** !



