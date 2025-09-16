# Migration des Données CSV vers la Base de Données MySQL

## Description
Ce document décrit la migration des données des fichiers CSV (poisons, objets magiques, monstres) vers la base de données MySQL pour améliorer les performances et la robustesse du système.

## Pourquoi cette migration ?

### Avantages de la base de données
- **Performance** : Recherche plus rapide avec index et recherche fulltext
- **Robustesse** : Gestion des erreurs et validation des données
- **Scalabilité** : Support de grandes quantités de données
- **Requêtes complexes** : Possibilité de faire des jointures et requêtes avancées
- **Maintenance** : Mise à jour et sauvegarde plus faciles

### Inconvénients des fichiers CSV
- **Performance** : Lecture séquentielle du fichier à chaque recherche
- **Fragilité** : Pas de validation des données
- **Limitations** : Recherche basique avec `stripos()`
- **Maintenance** : Difficile de mettre à jour des données spécifiques

## Fichiers modifiés/créés

### 1. `database/add_data_tables.sql` (nouveau)
- Tables pour stocker les poisons, objets magiques et monstres
- Index et recherche fulltext pour les performances
- Structure optimisée pour les requêtes

### 2. `import_csv_data.php` (nouveau)
- Script d'import des données CSV vers MySQL
- Validation et nettoyage des données
- Gestion des erreurs d'import

### 3. `setup_complete_system.php` (nouveau)
- Script de configuration complet du système
- Création de toutes les tables nécessaires
- Import automatique de toutes les données

### 4. `search_poisons.php` (modifié)
- Utilise maintenant la base de données MySQL
- Recherche fulltext pour de meilleures performances
- Fallback vers CSV en cas d'erreur

### 5. `search_magical_items.php` (modifié)
- Utilise maintenant la base de données MySQL
- Recherche fulltext pour de meilleures performances
- Fallback vers CSV en cas d'erreur

### 6. `view_scene.php` (modifié)
- Récupère les informations d'objets depuis MySQL
- Plus d'erreur "Unknown database 'aidednddata'"

## Structure des nouvelles tables

### Table `poisons`
```sql
CREATE TABLE poisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,        -- ID original du CSV
    nom VARCHAR(255) NOT NULL,        -- Nom du poison
    cle VARCHAR(255),                 -- Clé de référence
    description TEXT,                 -- Description complète
    type VARCHAR(255),                -- Type et rareté
    source VARCHAR(255),              -- Source de référence
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
    csv_id VARCHAR(50) UNIQUE,        -- ID original du CSV
    nom VARCHAR(255) NOT NULL,        -- Nom de l'objet
    cle VARCHAR(255),                 -- Clé de référence
    description TEXT,                 -- Description complète
    type VARCHAR(255),                -- Type et rareté
    source VARCHAR(255),              -- Source de référence
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_nom (nom),
    INDEX idx_type (type),
    FULLTEXT idx_search (nom, cle, description, type)
);
```

### Table `dnd_monsters`
```sql
CREATE TABLE dnd_monsters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,        -- ID original du CSV
    name VARCHAR(255) NOT NULL,       -- Nom du monstre
    type VARCHAR(100),                -- Type de créature
    size VARCHAR(50),                 -- Taille
    challenge_rating VARCHAR(20),     -- Niveau de difficulté
    hit_points INT,                   -- Points de vie
    armor_class INT,                  -- Classe d'armure
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_name (name),
    INDEX idx_type (type),
    FULLTEXT idx_search (name, type)
);
```

## Installation et configuration

### 1. Configuration complète (recommandé)
```bash
# Accéder au script de configuration complet
http://localhost:8000/setup_complete_system.php
```

Ce script va :
- Créer toutes les tables d'équipement
- Créer toutes les tables de données
- Importer toutes les données CSV
- Configurer les index et contraintes

### 2. Configuration étape par étape
```bash
# 1. Créer les tables d'équipement
http://localhost:8000/setup_equipment_tables.php

# 2. Créer les tables de données
# Exécuter database/add_data_tables.sql

# 3. Importer les données CSV
http://localhost:8000/import_csv_data.php
```

## Migration des données

### Processus d'import
1. **Lecture** des fichiers CSV existants
2. **Validation** des données (nombre de colonnes, types)
3. **Insertion** dans les tables MySQL
4. **Vérification** du nombre d'enregistrements importés

### Données importées
- **Poisons** : ~302 enregistrements depuis `poisons.csv`
- **Objets magiques** : ~970 enregistrements depuis `objet_magiques.csv`
- **Monstres** : Variable selon le fichier `monstre.csv`

### Gestion des erreurs
- **Fichiers manquants** : Messages d'erreur clairs
- **Données invalides** : Ignorées avec avertissement
- **Erreurs de base** : Rollback automatique

## Recherche et performances

### Recherche fulltext
```sql
-- Recherche optimisée avec MATCH AGAINST
SELECT * FROM poisons 
WHERE MATCH(nom, cle, description, type) AGAINST('arsenic' IN BOOLEAN MODE)
```

### Fallback vers LIKE
```sql
-- Recherche de fallback si fulltext échoue
SELECT * FROM poisons 
WHERE nom LIKE '%arsenic%' OR cle LIKE '%arsenic%' OR type LIKE '%arsenic%'
```

### Index et optimisation
- **Index sur csv_id** : Recherche rapide par ID
- **Index sur nom** : Tri et recherche par nom
- **Index sur type** : Filtrage par type
- **Fulltext** : Recherche sémantique avancée

## Compatibilité et rétrocompatibilité

### Interface utilisateur
- **Aucun changement** visible pour l'utilisateur
- **Même format** de réponse JSON
- **Même structure** des données retournées

### API
- **Mêmes endpoints** : `/search_poisons.php`, `/search_magical_items.php`
- **Même format** de requête et réponse
- **Fallback automatique** vers CSV en cas d'erreur

### Données
- **csv_id** : Maintient la compatibilité avec l'ancien système
- **Structure** : Même format de données
- **Contenu** : Données identiques aux fichiers CSV

## Maintenance et mises à jour

### Ajout de nouvelles données
```sql
-- Ajouter un nouveau poison
INSERT INTO poisons (csv_id, nom, cle, description, type, source) 
VALUES ('999', 'Nouveau poison', 'nouveau-poison', 'Description...', 'Type...', 'Source...');
```

### Mise à jour des données existantes
```sql
-- Modifier un poison existant
UPDATE poisons SET description = 'Nouvelle description' WHERE csv_id = '123';
```

### Synchronisation avec CSV
- **Script d'import** : Peut être relancé pour synchroniser
- **TRUNCATE** : Vide la table avant import
- **Vérification** : Compte les enregistrements importés

## Dépannage

### Erreurs courantes
1. **"Unknown database 'aidednddata'"** : Résolu par la migration
2. **"Table doesn't exist"** : Exécuter `setup_complete_system.php`
3. **"No data found"** : Vérifier l'import des données CSV

### Vérifications
```sql
-- Vérifier le nombre de poisons
SELECT COUNT(*) FROM poisons;

-- Vérifier le nombre d'objets magiques
SELECT COUNT(*) FROM magical_items;

-- Vérifier le nombre de monstres
SELECT COUNT(*) FROM dnd_monsters;
```

### Logs et monitoring
- **Messages d'erreur** : Affichés dans l'interface
- **Compteurs** : Nombre d'enregistrements importés
- **Statuts** : Succès/échec de chaque étape

## Extensions futures

### Fonctionnalités possibles
- **Recherche avancée** : Filtres par type, rareté, source
- **Historique des modifications** : Suivi des changements
- **API REST** : Endpoints pour applications externes
- **Cache Redis** : Mise en cache des recherches fréquentes

### Intégrations
- **Système de tags** : Catégorisation des objets
- **Recherche sémantique** : IA pour améliorer les résultats
- **Export/import** : Formats multiples (JSON, XML, CSV)
- **Synchronisation** : Mise à jour automatique depuis sources externes

---

**Statut** : ✅ **MIGRATION COMPLÈTE**

La migration des données CSV vers MySQL est maintenant terminée et offre de meilleures performances, une meilleure robustesse et une maintenance plus facile.












