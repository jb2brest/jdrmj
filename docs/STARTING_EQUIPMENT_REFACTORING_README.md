# 🔄 Refactorisation des Tables Starting Equipment

## 📋 Vue d'ensemble

La structure des tables `starting_equipment_options` et `starting_equipment_choix` a été refactorisée selon les nouvelles spécifications. La table `starting_equipment` reste la source de référence.

## 🗂️ Nouvelle Structure

### **Table `starting_equipment` (Source)**
La table `starting_equipment` reste inchangée et sert de référence pour la structure des autres tables.

### **Table `starting_equipment_choix`**
Reprend la structure de base avec ajout de `type_choix` :

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

### **Table `starting_equipment_options`**
Reprend les colonnes de `starting_equipment` + ajoute `id_choix` :

| Colonne | Type | Description |
|---------|------|-------------|
| **id** | INT AUTO_INCREMENT | Identifiant unique |
| **id_choix** | INT | ID du choix parent (FK vers starting_equipment_choix) |
| **src** | ENUM('class', 'background', 'race') | Source d'origine |
| **src_id** | INT | ID de la source d'origine |
| **type** | VARCHAR(20) | Type d'équipement |
| **type_id** | INT | ID de l'équipement précis |
| **type_filter** | VARCHAR(50) | Filtre de type |
| **nb** | INT | Nombre d'objets |
| **groupe_id** | INT | ID de groupe |
| **created_at** | TIMESTAMP | Date de création |
| **updated_at** | TIMESTAMP | Date de modification |

## 🔄 Changements Apportés

### **1. Suppression des Colonnes**
- ❌ `no_choix` et `option_letter` dans `starting_equipment_options`
- ❌ `is_default` et `default_items` dans `starting_equipment_choix`

### **2. Ajout des Colonnes**
- ✅ `id_choix` dans `starting_equipment_options` (remplace `no_choix` et `option_letter`)
- ✅ `type_choix` dans `starting_equipment_choix`
- ✅ Toutes les colonnes de `starting_equipment` dans `starting_equipment_options`

### **3. Relations**
- `starting_equipment_options.id_choix` → `starting_equipment_choix.id`
- Contrainte de clé étrangère avec `ON DELETE CASCADE`

## 📁 Fichiers Créés/Modifiés

### **Nouveaux Fichiers**
- `database/refactor_starting_equipment_tables.sql` - Script de refactorisation
- `database/migrate_starting_equipment_data.sql` - Script de migration des données
- `docs/STARTING_EQUIPMENT_REFACTORING_README.md` - Cette documentation

### **Fichiers Modifiés**
- `classes/StartingEquipmentChoix.php` - Mise à jour de la classe
- `classes/StartingEquipmentOption.php` - Mise à jour de la classe

## 🚀 Instructions de Migration

### **1. Exécuter la Refactorisation**
```sql
-- Exécuter le script de refactorisation
SOURCE database/refactor_starting_equipment_tables.sql;
```

### **2. Migrer les Données**
```sql
-- Exécuter le script de migration
SOURCE database/migrate_starting_equipment_data.sql;
```

### **3. Vérifier la Migration**
Le script de migration inclut des requêtes de vérification pour s'assurer que toutes les données ont été correctement migrées.

## 🔧 Classes PHP Mises à Jour

### **StartingEquipmentChoix**
- ✅ Suppression des propriétés `isDefault` et `defaultItems`
- ✅ Ajout des propriétés `typeChoix` et `groupeId`
- ✅ Mise à jour des méthodes CRUD
- ✅ Simplification de la logique métier

### **StartingEquipmentOption**
- ✅ Remplacement de `choixId` par `idChoix`
- ✅ Ajout de toutes les propriétés de `starting_equipment`
- ✅ Suppression des propriétés `optionLetter`, `description`, `items`
- ✅ Mise à jour des méthodes CRUD

## 📊 Impact sur les Données

### **Avant la Migration**
- Structure complexe avec JSON et lettres d'option
- Logique de choix dispersée entre les tables
- Difficulté de maintenance

### **Après la Migration**
- Structure normalisée basée sur `starting_equipment`
- Relations claires entre choix et options
- Facilité de maintenance et d'extension

## ⚠️ Points d'Attention

1. **Sauvegarde** : Toujours faire une sauvegarde avant la migration
2. **Tests** : Tester la migration sur un environnement de développement
3. **Compatibilité** : Vérifier que les applications utilisant ces tables fonctionnent toujours
4. **Performance** : Les nouvelles relations peuvent impacter les performances des requêtes

## 🔍 Vérifications Post-Migration

1. Compter les enregistrements dans chaque table
2. Vérifier l'intégrité des relations
3. Tester les fonctionnalités d'équipement de départ
4. Valider l'affichage dans l'interface utilisateur

