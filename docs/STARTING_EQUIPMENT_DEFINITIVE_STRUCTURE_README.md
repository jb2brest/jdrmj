# 🎯 Structure Définitive des Tables Starting Equipment

## 📋 Vue d'ensemble

La structure des tables `starting_equipment_options` et `starting_equipment_choix` a été finalisée selon les spécifications exactes. La table `starting_equipment` reste la source de référence.

## 🗂️ Structure Définitive

### **Table `starting_equipment_choix`**

| Colonne | Type | Description |
|---------|------|-------------|
| **id** | INT AUTO_INCREMENT | Identifiant unique |
| **src** | ENUM('class', 'background') | Source: class ou background |
| **src_id** | INT | ID de la classe ou du background concerné |
| **no_choix** | INT | Le numéro du choix |
| **option_letter** | CHAR(1) | La lettre d'option du package |
| **created_at** | TIMESTAMP | Date de création |
| **updated_at** | TIMESTAMP | Date de modification |

### **Table `starting_equipment_options`**

| Colonne | Type | Description |
|---------|------|-------------|
| **id** | INT AUTO_INCREMENT | Identifiant unique |
| **starting_equipment_choix_id** | INT | ID du choix dont fait partie l'option |
| **src** | ENUM('class', 'background') | Source: class ou background |
| **src_id** | INT | ID de la classe ou du background concerné |
| **type** | ENUM | Type d'équipement: armor, bouclier, instrument, nourriture, outils, sac, weapon |
| **type_id** | INT | ID de l'équipement dans la table correspondant au type |
| **type_filter** | VARCHAR(100) | Filtre pour sélectionner des armes dans une liste |
| **nb** | INT | Le nombre d'item (défaut: 1) |
| **created_at** | TIMESTAMP | Date de création |
| **updated_at** | TIMESTAMP | Date de modification |

### **Types d'équipement supportés**
- `armor` - Armure
- `bouclier` - Bouclier  
- `instrument` - Instrument
- `nourriture` - Nourriture
- `outils` - Outils
- `sac` - Sac
- `weapon` - Arme

### **Filtres de type d'arme**
- "Armes de guerre de corps à corps"
- "Armes courantes à distance"
- "Armes courantes de corps à corps"
- "Armes de guerre à distance"

## 🔗 Relations

### **Relation entre les tables**
- `starting_equipment_options.starting_equipment_choix_id` → `starting_equipment_choix.id`
- Contrainte de clé étrangère avec `ON DELETE CASCADE`

### **Logique métier**
1. **Choix d'équipement** : Un choix peut avoir plusieurs options
2. **Options d'équipement** : Chaque option appartient à un choix spécifique
3. **Lettres d'option** : Permettent de distinguer les différents packages d'options

## 🏗️ Classe PHP `StartingEquipmentChoix`

### **Propriétés**
```php
private $id;           // Identifiant unique
private $src;          // Source (class/background)
private $srcId;        // ID de la source
private $noChoix;      // Numéro du choix
private $optionLetter; // Lettre d'option du package
private $options;      // Options associées
```

### **Méthodes principales**
- `create()` - Créer un nouveau choix
- `findById()` - Trouver par ID
- `findBySource()` - Trouver par source
- `addOption()` - Ajouter une option
- `hasOptions()` - Vérifier si a des options
- `getFullDescription()` - Description complète

## 🏗️ Classe PHP `StartingEquipmentOption`

### **Propriétés**
```php
private $id;                           // Identifiant unique
private $startingEquipmentChoixId;     // ID du choix parent
private $src;                          // Source (class/background)
private $srcId;                        // ID de la source
private $type;                         // Type d'équipement
private $typeId;                       // ID de l'équipement
private $typeFilter;                   // Filtre de type
private $nb;                           // Nombre d'items
```

### **Méthodes principales**
- `create()` - Créer une nouvelle option
- `findById()` - Trouver par ID
- `findByStartingEquipmentChoixId()` - Trouver par choix
- `findBySource()` - Trouver par source
- `update()` - Mettre à jour
- `delete()` - Supprimer
- `getTypeLabel()` - Obtenir le label en français
- `getFullDescription()` - Description complète

## 📁 Fichiers de Migration

### **Scripts SQL**
1. **`refactor_starting_equipment_tables.sql`** - Refactorisation des tables
2. **`migrate_starting_equipment_data.sql`** - Migration des données
3. **`validate_final_structure.sql`** - Validation de la structure

### **Classes PHP**
1. **`StartingEquipmentOption.php`** - Classe des options
2. **`StartingEquipmentChoix.php`** - Classe des choix

## 🚀 Instructions de Déploiement

### **1. Sauvegarde**
```bash
mysqldump -u username -p database_name > backup_before_migration.sql
```

### **2. Refactorisation**
```sql
SOURCE database/refactor_starting_equipment_tables.sql;
```

### **3. Migration des données**
```sql
SOURCE database/migrate_starting_equipment_data.sql;
```

### **4. Vérification**
```sql
SOURCE database/validate_final_structure.sql;
```

## ✅ Avantages de la Structure Définitive

### **Simplicité**
- Structure claire et cohérente
- Relation directe entre choix et options
- Types d'équipement standardisés

### **Flexibilité**
- Support des filtres d'armes
- Gestion des lettres d'option
- Gestion des quantités

### **Maintenabilité**
- Code PHP simplifié
- Requêtes SQL optimisées
- Documentation complète

## 🔍 Exemples d'Utilisation

### **Créer un choix d'équipement**
```php
$choix = StartingEquipmentChoix::create([
    'src' => 'class',
    'src_id' => 5,
    'no_choix' => 1,
    'option_letter' => 'A'
]);
```

### **Créer une option d'équipement**
```php
$option = StartingEquipmentOption::create([
    'starting_equipment_choix_id' => $choix->getId(),
    'src' => 'class',
    'src_id' => 5,
    'type' => 'weapon',
    'type_filter' => 'Armes de guerre de corps à corps',
    'nb' => 1
]);
```

### **Récupérer les options d'un choix**
```php
$options = StartingEquipmentOption::findByStartingEquipmentChoixId($choix->getId());
```

### **Récupérer tous les choix d'une classe**
```php
$choix = StartingEquipmentChoix::findBySource('class', 5);
```

## ⚠️ Points d'Attention

1. **Types d'équipement** : Utiliser uniquement les types définis dans l'ENUM
2. **Filtres d'armes** : Respecter les libellés exacts des filtres
3. **Relations** : S'assurer de la cohérence des `starting_equipment_choix_id`
4. **Sources** : Limiter aux valeurs 'class' et 'background'
5. **Lettres d'option** : Utiliser des lettres uniques par choix

## 🔄 Migration depuis l'Ancienne Structure

### **Changements Majeurs**
1. **Suppression** de `groupe_id` dans les options
2. **Ajout** de `starting_equipment_choix_id` dans les options
3. **Simplification** de `starting_equipment_choix`
4. **Ajout** de `option_letter` dans les choix

### **Impact sur le Code**
- Mise à jour des requêtes SQL
- Modification des méthodes de recherche
- Adaptation des relations entre objets
- Mise à jour des interfaces utilisateur

## 📊 Statistiques de Migration

Le script de migration inclut des vérifications pour s'assurer que :
- Tous les équipements sont migrés
- Les relations sont cohérentes
- Les contraintes sont respectées
- Les performances sont maintenues

