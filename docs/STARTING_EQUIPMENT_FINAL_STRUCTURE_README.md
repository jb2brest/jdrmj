# 🎯 Structure Finale des Tables Starting Equipment

## 📋 Vue d'ensemble

La structure des tables `starting_equipment_options` et `starting_equipment_choix` a été finalisée selon les spécifications exactes. La table `starting_equipment` reste la source de référence.

## 🗂️ Structure Finale

### **Table `starting_equipment_options`**

| Colonne | Type | Description |
|---------|------|-------------|
| **id** | INT AUTO_INCREMENT | Identifiant unique |
| **groupe_id** | INT | ID de l'ensemble de pièce qui constitue l'option |
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

### **Table `starting_equipment_choix`**

| Colonne | Type | Description |
|---------|------|-------------|
| **id** | INT AUTO_INCREMENT | Identifiant unique |
| **src** | ENUM('class', 'background', 'race') | Source du choix |
| **src_id** | INT | ID de la source |
| **no_choix** | INT | Numéro du choix |
| **description** | TEXT | Description du choix |
| **type_choix** | ENUM('obligatoire', 'à_choisir') | Type de choix |
| **groupe_id** | INT | ID de groupe |
| **created_at** | TIMESTAMP | Date de création |
| **updated_at** | TIMESTAMP | Date de modification |

## 🔗 Relations

### **Relation entre les tables**
- `starting_equipment_options.groupe_id` → `starting_equipment_choix.groupe_id`
- Les options sont regroupées par `groupe_id` pour former des ensembles cohérents

### **Logique métier**
1. **Choix obligatoire** : Les équipements sont automatiquement attribués
2. **Choix à faire** : Le joueur doit choisir parmi les options disponibles
3. **Groupement** : Les options avec le même `groupe_id` forment un ensemble

## 🏗️ Classe PHP `StartingEquipmentOption`

### **Propriétés**
```php
private $id;           // Identifiant unique
private $groupeId;     // ID de l'ensemble de pièce
private $src;          // Source (class/background)
private $srcId;        // ID de la source
private $type;         // Type d'équipement
private $typeId;       // ID de l'équipement
private $typeFilter;   // Filtre de type
private $nb;           // Nombre d'items
```

### **Méthodes principales**
- `create()` - Créer une nouvelle option
- `findById()` - Trouver par ID
- `findByGroupeId()` - Trouver par groupe
- `findBySource()` - Trouver par source
- `update()` - Mettre à jour
- `delete()` - Supprimer
- `getTypeLabel()` - Obtenir le label en français
- `getFullDescription()` - Description complète

## 🏗️ Classe PHP `StartingEquipmentChoix`

### **Propriétés**
```php
private $id;           // Identifiant unique
private $src;          // Source
private $srcId;        // ID de la source
private $noChoix;      // Numéro du choix
private $description;  // Description
private $typeChoix;    // Type de choix
private $groupeId;     // ID de groupe
private $options;      // Options associées
```

### **Méthodes principales**
- `create()` - Créer un nouveau choix
- `findById()` - Trouver par ID
- `findBySource()` - Trouver par source
- `addOption()` - Ajouter une option
- `isObligatory()` - Vérifier si obligatoire
- `hasOptions()` - Vérifier si a des options

## 📁 Fichiers de Migration

### **Scripts SQL**
1. **`refactor_starting_equipment_tables.sql`** - Refactorisation des tables
2. **`migrate_starting_equipment_data.sql`** - Migration des données
3. **`test_starting_equipment_migration.sql`** - Tests de vérification

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
SOURCE database/test_starting_equipment_migration.sql;
```

## ✅ Avantages de la Nouvelle Structure

### **Simplicité**
- Structure claire et cohérente
- Relations simples entre les tables
- Types d'équipement standardisés

### **Flexibilité**
- Support des filtres d'armes
- Groupement des options
- Gestion des quantités

### **Maintenabilité**
- Code PHP simplifié
- Requêtes SQL optimisées
- Documentation complète

## 🔍 Exemples d'Utilisation

### **Créer une option d'équipement**
```php
$option = StartingEquipmentOption::create([
    'groupe_id' => 1,
    'src' => 'class',
    'src_id' => 5,
    'type' => 'weapon',
    'type_filter' => 'Armes de guerre de corps à corps',
    'nb' => 1
]);
```

### **Récupérer les options d'un groupe**
```php
$options = StartingEquipmentOption::findByGroupeId(1);
```

### **Créer un choix d'équipement**
```php
$choix = StartingEquipmentChoix::create([
    'src' => 'class',
    'src_id' => 5,
    'no_choix' => 1,
    'description' => 'Choix d\'arme de guerre',
    'type_choix' => 'à_choisir',
    'groupe_id' => 1
]);
```

## ⚠️ Points d'Attention

1. **Types d'équipement** : Utiliser uniquement les types définis dans l'ENUM
2. **Filtres d'armes** : Respecter les libellés exacts des filtres
3. **Groupes** : S'assurer de la cohérence des `groupe_id`
4. **Sources** : Limiter aux valeurs 'class' et 'background' pour les options

