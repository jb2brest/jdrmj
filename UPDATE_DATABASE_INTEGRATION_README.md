# Intégration des Nouvelles Tables dans update_database.php

## Description
Ce document décrit l'intégration complète des nouvelles tables d'équipement et de données dans le script `update_database.php` existant, permettant une mise à jour automatique et transparente de la base de données.

## Pourquoi cette intégration ?

### Avantages de l'intégration
- **Mise à jour automatique** : Les nouvelles tables sont créées automatiquement
- **Import automatique** : Les données CSV sont importées sans intervention manuelle
- **Cohérence** : Toutes les mises à jour sont centralisées dans un seul script
- **Maintenance** : Plus besoin de scripts séparés pour la configuration

### Avant l'intégration
- **Scripts séparés** : `setup_equipment_tables.php`, `setup_complete_system.php`
- **Configuration manuelle** : Nécessitait plusieurs étapes
- **Risque d'oubli** : Possibilité de manquer certaines tables

### Après l'intégration
- **Script unique** : `update_database.php` gère tout
- **Configuration automatique** : Une seule exécution suffit
- **Vérification complète** : Toutes les tables sont vérifiées et créées

## Nouvelles fonctionnalités ajoutées

### 1. Tables d'équipement
- **`character_equipment`** : Équipement des personnages joueurs
- **`npc_equipment`** : Équipement des PNJ dans les scènes
- **`monster_equipment`** : Équipement des monstres dans les scènes

### 2. Tables de données
- **`poisons`** : Catalogue des poisons avec recherche fulltext
- **`magical_items`** : Catalogue des objets magiques avec recherche fulltext
- **`dnd_monsters`** : Catalogue des monstres (mise à jour de la table existante)

### 3. Import automatique des données
- **Poisons** : Import depuis `aidednddata/poisons.csv`
- **Objets magiques** : Import depuis `aidednddata/objet_magiques.csv`
- **Monstres** : Import depuis `aidednddata/monstre.csv`

## Structure des nouvelles tables

### Table `character_equipment`
```sql
CREATE TABLE character_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    magical_item_id VARCHAR(50),
    item_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(100),
    item_description TEXT,
    item_source VARCHAR(100),
    quantity INT DEFAULT 1,
    equipped BOOLEAN DEFAULT FALSE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    obtained_from VARCHAR(100) DEFAULT 'Attribution MJ',
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    INDEX idx_character_id (character_id),
    INDEX idx_item_name (item_name),
    INDEX idx_magical_item_id (magical_item_id)
);
```

### Table `npc_equipment`
```sql
CREATE TABLE npc_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    npc_id INT NOT NULL,
    scene_id INT NOT NULL,
    magical_item_id VARCHAR(50),
    item_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(100),
    item_description TEXT,
    item_source VARCHAR(100),
    quantity INT DEFAULT 1,
    equipped BOOLEAN DEFAULT FALSE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    obtained_from VARCHAR(100) DEFAULT 'Attribution MJ',
    INDEX idx_npc_id (npc_id),
    INDEX idx_scene_id (scene_id),
    INDEX idx_item_name (item_name),
    INDEX idx_magical_item_id (magical_item_id)
);
```

### Table `monster_equipment`
```sql
CREATE TABLE monster_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    monster_id INT NOT NULL,
    scene_id INT NOT NULL,
    magical_item_id VARCHAR(50),
    item_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(100),
    item_description TEXT,
    item_source VARCHAR(100),
    quantity INT DEFAULT 1,
    equipped BOOLEAN DEFAULT FALSE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    obtained_from VARCHAR(100) DEFAULT 'Attribution MJ',
    INDEX idx_monster_id (monster_id),
    INDEX idx_scene_id (scene_id),
    INDEX idx_item_name (item_name),
    INDEX idx_magical_item_id (magical_item_id)
);
```

### Table `poisons`
```sql
CREATE TABLE poisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,
    nom VARCHAR(255) NOT NULL,
    cle VARCHAR(255),
    description TEXT,
    type VARCHAR(255),
    source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_nom (nom),
    INDEX idx_type (type),
    FULLTEXT idx_search (nom, cle, description, type)
);
```

### Table `magical_items`
```sql
CREATE TABLE magical_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,
    nom VARCHAR(255) NOT NULL,
    cle VARCHAR(255),
    description TEXT,
    type VARCHAR(255),
    source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_nom (nom),
    INDEX idx_type (type),
    FULLTEXT idx_search (nom, cle, description, type)
);
```

## Processus de mise à jour

### 1. Vérification des tables existantes
```php
$stmt = $pdo->query("SHOW TABLES LIKE 'character_equipment'");
if ($stmt->rowCount() == 0) {
    // Créer la table
} else {
    // Table existe déjà
}
```

### 2. Création des tables manquantes
- **Vérification** : Si la table n'existe pas
- **Création** : Exécution du CREATE TABLE
- **Confirmation** : Message de succès ou d'information

### 3. Mise à jour des tables existantes
- **Ajout de colonnes** : Nouvelles colonnes si nécessaire
- **Ajout d'index** : Index fulltext et autres optimisations
- **Contraintes** : Clés étrangères et contraintes d'intégrité

### 4. Import automatique des données
- **Vérification des fichiers** : Existence des fichiers CSV
- **Import des données** : Lecture et insertion en base
- **Gestion des erreurs** : Messages d'erreur clairs
- **Comptage** : Nombre d'enregistrements importés

## Fonctions d'import ajoutées

### `importPoisons($pdo)`
- **Fichier source** : `aidednddata/poisons.csv`
- **Processus** : TRUNCATE + INSERT
- **Validation** : Vérification du nombre de colonnes
- **Comptage** : Retour du nombre d'enregistrements

### `importMagicalItems($pdo)`
- **Fichier source** : `aidednddata/objet_magiques.csv`
- **Processus** : TRUNCATE + INSERT
- **Validation** : Vérification du nombre de colonnes
- **Comptage** : Retour du nombre d'enregistrements

### `importMonsters($pdo)`
- **Fichier source** : `aidednddata/monstre.csv`
- **Processus** : TRUNCATE + INSERT
- **Validation** : Vérification du nombre de colonnes
- **Comptage** : Retour du nombre d'enregistrements

## Utilisation

### Exécution simple
```bash
# Accéder au script de mise à jour
http://localhost:8000/update_database.php
```

### Ce qui se passe automatiquement
1. **Vérification** de toutes les tables existantes
2. **Création** des nouvelles tables manquantes
3. **Mise à jour** des tables existantes si nécessaire
4. **Import** automatique de toutes les données CSV
5. **Rapport** détaillé de toutes les opérations

### Messages affichés
- **✅ Succès** : Opération réussie (vert)
- **ℹ Information** : Élément déjà existant (bleu)
- **⚠️ Avertissement** : Erreur non critique (orange)
- **✗ Erreur** : Problème critique (rouge)

## Gestion des erreurs

### Erreurs de création de table
- **Table existe déjà** : Affichage d'un message informatif
- **Erreur de syntaxe** : Affichage de l'erreur SQL
- **Problème de permissions** : Message d'erreur clair

### Erreurs d'import CSV
- **Fichier manquant** : Message d'avertissement
- **Erreur de lecture** : Message d'erreur détaillé
- **Données invalides** : Ignorées avec avertissement

### Fallbacks et robustesse
- **Continuation** : Le script continue même en cas d'erreur
- **Rapport complet** : Toutes les erreurs sont affichées
- **État final** : Résumé de la situation finale

## Compatibilité

### Versions antérieures
- **Tables existantes** : Pas de modification
- **Nouvelles fonctionnalités** : Ajoutées automatiquement
- **Données existantes** : Préservées

### Évolutions futures
- **Nouvelles tables** : Facilement ajoutables
- **Nouvelles colonnes** : Support des ALTER TABLE
- **Nouveaux imports** : Fonctions réutilisables

## Maintenance

### Ajout de nouvelles tables
1. **Vérification** : `SHOW TABLES LIKE 'nouvelle_table'`
2. **Création** : `CREATE TABLE nouvelle_table (...)`
3. **Confirmation** : Message de succès

### Ajout de nouvelles colonnes
1. **Vérification** : `SHOW COLUMNS FROM table LIKE 'colonne'`
2. **Ajout** : `ALTER TABLE table ADD COLUMN colonne ...`
3. **Confirmation** : Message de succès

### Ajout de nouveaux imports
1. **Nouvelle fonction** : `importNouvelleDonnee($pdo)`
2. **Appel** : Dans la section d'import automatique
3. **Gestion d'erreur** : Try-catch avec messages

## Tests et validation

### Vérification des tables
```sql
-- Vérifier que toutes les tables existent
SHOW TABLES LIKE 'character_equipment';
SHOW TABLES LIKE 'npc_equipment';
SHOW TABLES LIKE 'monster_equipment';
SHOW TABLES LIKE 'poisons';
SHOW TABLES LIKE 'magical_items';
```

### Vérification des données
```sql
-- Compter les enregistrements importés
SELECT COUNT(*) FROM poisons;
SELECT COUNT(*) FROM magical_items;
SELECT COUNT(*) FROM dnd_monsters;
```

### Vérification des index
```sql
-- Vérifier les index fulltext
SHOW INDEX FROM poisons WHERE Key_name = 'idx_search';
SHOW INDEX FROM magical_items WHERE Key_name = 'idx_search';
SHOW INDEX FROM dnd_monsters WHERE Key_name = 'idx_search';
```

## Dépannage

### Problèmes courants
1. **"Table doesn't exist"** : Exécuter `update_database.php`
2. **"No data found"** : Vérifier l'existence des fichiers CSV
3. **"Permission denied"** : Vérifier les droits de la base de données

### Solutions
1. **Relancer le script** : `update_database.php` gère les erreurs
2. **Vérifier les fichiers** : S'assurer que les CSV existent
3. **Vérifier les permissions** : Droits d'écriture sur la base

---

**Statut** : ✅ **INTÉGRATION COMPLÈTE**

L'intégration des nouvelles tables dans `update_database.php` est maintenant terminée. Le script gère automatiquement la création de toutes les tables nécessaires et l'import de toutes les données CSV, offrant une solution complète et transparente pour la mise à jour de la base de données.
