# Migration du Système d'Équipement de Départ

## 📋 Vue d'ensemble

Ce document décrit la migration du système d'équipement de départ vers une nouvelle architecture basée sur une table dédiée `starting_equipment`.

## 🗄️ Nouvelle Structure de Base de Données

### Table `starting_equipment`

```sql
CREATE TABLE starting_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    src VARCHAR(20) NOT NULL,           -- Source: class, background, race
    src_id INT NOT NULL,                -- ID de la source
    type VARCHAR(20) NOT NULL,          -- Type: Outils, Armure, Bouclier, Arme, Accessoire, Sac
    type_id INT,                        -- ID de l'équipement précis
    option_indice CHAR(1),              -- Indice d'option: a, b, c
    groupe_id INT,                      -- ID de groupe pour les items groupés
    type_choix ENUM('obligatoire', 'à_choisir') DEFAULT 'obligatoire',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 📁 Fichiers Créés

### 1. `database/create_starting_equipment_table.sql`
- Script de création de la table `starting_equipment`
- Définit la structure avec tous les champs requis

### 2. `database/migrate_starting_equipment_data.sql`
- Exemples de données pour les classes et backgrounds
- Montre comment structurer les données dans la nouvelle table

### 3. `database/parse_existing_starting_equipment.php`
- Script de migration automatique
- Parse les données existantes des champs `starting_equipment` et `equipment`
- Convertit vers la nouvelle structure

### 4. `includes/starting_equipment_functions.php`
- Nouvelles fonctions PHP pour gérer l'équipement de départ
- Remplace les anciennes fonctions basées sur le parsing de texte

### 5. `select_starting_equipment_new.php`
- Nouvelle interface de sélection d'équipement
- Utilise la nouvelle structure de données
- Interface plus claire et modulaire

## 🔄 Processus de Migration

### Étape 1: Créer la table
```bash
mysql -u username -p database_name < database/create_starting_equipment_table.sql
```

### Étape 2: Migrer les données existantes
```bash
php database/parse_existing_starting_equipment.php
```

### Étape 3: Tester le nouveau système
- Utiliser `select_starting_equipment_new.php` pour tester
- Vérifier que l'équipement est correctement généré

## 🎯 Avantages de la Nouvelle Architecture

### 1. **Flexibilité**
- Ajout facile de nouveaux types d'équipement
- Gestion des groupes d'équipement
- Support des choix multiples

### 2. **Maintenabilité**
- Structure claire et normalisée
- Relations explicites avec les tables d'équipement
- Code plus lisible et modulaire

### 3. **Extensibilité**
- Support des races avec équipement de départ
- Possibilité d'ajouter des équipements conditionnels
- Gestion des équipements magiques de départ

### 4. **Performance**
- Requêtes SQL optimisées
- Index sur les colonnes importantes
- Moins de parsing de texte

## 📊 Types d'Équipement Supportés

| Type | Description | Table de référence |
|------|-------------|-------------------|
| Arme | Armes de toutes sortes | `weapons` |
| Armure | Armures et protections | `armor` |
| Bouclier | Boucliers | `armor` (type = 'Bouclier') |
| Outils | Outils d'artisan | `tools` |
| Accessoire | Objets divers | Table générique |
| Sac | Sacs d'équipement | Données codées |

## 🔧 Fonctions Principales

### `getStartingEquipmentBySource($src, $srcId)`
Récupère l'équipement de départ pour une source donnée.

### `structureStartingEquipmentByGroups($equipment)`
Structure l'équipement par groupes pour l'affichage.

### `generateFinalEquipmentNew($classId, $backgroundId, $raceId, $equipmentChoices)`
Génère l'équipement final basé sur les choix du joueur.

### `addStartingEquipmentToCharacterNew($characterId, $equipmentData)`
Ajoute l'équipement de départ à un personnage.

## 🚀 Prochaines Étapes

1. **Tester la migration** avec des données réelles
2. **Mettre à jour les références** dans le code existant
3. **Ajouter les type_id** pour lier aux tables d'équipement
4. **Implémenter l'équipement de race** si nécessaire
5. **Optimiser les performances** si besoin

## ⚠️ Notes Importantes

- Les anciens champs `starting_equipment` et `equipment` restent pour la compatibilité
- La migration est réversible si nécessaire
- Tester avec des personnages existants avant de déployer
- Sauvegarder la base de données avant la migration
