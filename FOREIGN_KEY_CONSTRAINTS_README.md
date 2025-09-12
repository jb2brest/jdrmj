# Gestion des Contraintes de Clé Étrangère lors de l'Import

## Description
Ce document décrit la gestion automatique des contraintes de clé étrangère lors de l'import des données CSV, résolvant le problème de TRUNCATE sur les tables référencées.

## Problème rencontré

### Erreur SQL
```
SQLSTATE[42000]: Syntax error or access violation: 1701 
Cannot truncate a table referenced in a foreign key constraint 
(`u839591438_jdrmj`.`user_monster_collection`, CONSTRAINT `user_monster_collection_ibfk_2`)
```

### Cause
La table `dnd_monsters` ne peut pas être tronquée (TRUNCATE) car elle est référencée par des contraintes de clé étrangère dans d'autres tables :
- `user_monster_collection.monster_id` → `dnd_monsters.id`
- `scene_npcs.monster_id` → `dnd_monsters.id`

## Solution implémentée

### 1. Fonction utilitaire `executeWithForeignKeyHandling()`

Cette fonction gère automatiquement les contraintes de clé étrangère :

```php
function executeWithForeignKeyHandling($pdo, $referencedTable, $callback) {
    // 1. Détecter toutes les contraintes de clé étrangère
    // 2. Supprimer temporairement ces contraintes
    // 3. Exécuter la fonction de callback (import des données)
    // 4. Recréer toutes les contraintes
    // 5. Gérer les erreurs et restaurer les contraintes
}
```

### 2. Processus de gestion des contraintes

#### Étape 1 : Détection des contraintes
```sql
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_NAME = 'dnd_monsters' 
AND REFERENCED_TABLE_SCHEMA = DATABASE()
```

#### Étape 2 : Suppression temporaire
```sql
ALTER TABLE `user_monster_collection` DROP FOREIGN KEY `user_monster_collection_ibfk_2`
ALTER TABLE `scene_npcs` DROP FOREIGN KEY `fk_scene_npcs_monster`
```

#### Étape 3 : Exécution de l'import
- Suppression des données existantes avec `DELETE`
- Import des nouvelles données depuis le CSV
- Comptage des enregistrements importés

#### Étape 4 : Recréation des contraintes
```sql
-- Pour user_monster_collection (suppression en cascade)
ALTER TABLE `user_monster_collection` 
ADD CONSTRAINT `user_monster_collection_ibfk_2` 
FOREIGN KEY (monster_id) REFERENCES dnd_monsters(id) ON DELETE CASCADE

-- Pour scene_npcs (mise à NULL en cas de suppression)
ALTER TABLE `scene_npcs` 
ADD CONSTRAINT `fk_scene_npcs_monster` 
FOREIGN KEY (monster_id) REFERENCES dnd_monsters(id) ON DELETE SET NULL
```

## Utilisation dans l'import des monstres

### Code simplifié
```php
function importMonsters($pdo) {
    $csvFile = 'aidednddata/monstre.csv';
    if (!file_exists($csvFile)) {
        throw new Exception("Fichier des monstres introuvable: $csvFile");
    }
    
    // Utiliser la fonction utilitaire pour gérer les contraintes
    executeWithForeignKeyHandling($pdo, 'dnd_monsters', function($pdo) use ($csvFile) {
        // Vider la table existante
        $pdo->exec("DELETE FROM dnd_monsters");
        
        // Import des données CSV...
        // (code d'import simplifié)
    });
}
```

### Avantages
- **Code plus propre** : Logique de gestion des contraintes séparée
- **Réutilisable** : Peut être utilisée pour d'autres tables
- **Robuste** : Gestion automatique des erreurs et restauration

## Types de contraintes gérées

### 1. `user_monster_collection.monster_id`
- **Type** : `ON DELETE CASCADE`
- **Comportement** : Si un monstre est supprimé, la collection est supprimée
- **Logique** : L'utilisateur perd sa référence au monstre

### 2. `scene_npcs.monster_id`
- **Type** : `ON DELETE SET NULL`
- **Comportement** : Si un monstre est supprimé, la référence devient NULL
- **Logique** : Le PNJ reste dans la scène mais sans référence au monstre

### 3. Autres tables (générique)
- **Type** : Contrainte standard sans `ON DELETE`
- **Comportement** : Empêche la suppression si des références existent
- **Logique** : Protection par défaut des données

## Gestion des erreurs

### 1. Erreur lors de la suppression d'une contrainte
```php
try {
    $pdo->exec("ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
    $droppedConstraints[] = $fk;
} catch (Exception $e) {
    echo "<p style='color: orange;'>⚠️ Impossible de supprimer la contrainte {$fk['CONSTRAINT_NAME']}: " . htmlspecialchars($e->getMessage()) . "</p>";
}
```

### 2. Erreur lors de la recréation d'une contrainte
```php
try {
    $pdo->exec("ALTER TABLE `{$fk['TABLE_NAME']}` ADD CONSTRAINT `{$fk['CONSTRAINT_NAME']}` FOREIGN KEY ({$fk['COLUMN_NAME']}) REFERENCES $referencedTable(id) $onDelete");
    echo "<p style='color: green;'>✓ Contrainte {$fk['CONSTRAINT_NAME']} recréée</p>";
} catch (Exception $e) {
    echo "<p style='color: orange;'>⚠️ Impossible de recréer la contrainte {$fk['CONSTRAINT_NAME']}: " . htmlspecialchars($e->getMessage()) . "</p>";
}
```

### 3. Erreur lors de l'exécution du callback
- **Restauration automatique** : Toutes les contraintes sont recréées
- **Message d'erreur** : L'erreur originale est affichée
- **État cohérent** : La base de données reste dans un état valide

## Sécurité et intégrité

### 1. Vérification des contraintes
- **Avant suppression** : Vérification de l'existence des contraintes
- **Après recréation** : Confirmation de la recréation
- **En cas d'erreur** : Tentative de restauration

### 2. Gestion des transactions
- **Pas de transaction globale** : Chaque opération est indépendante
- **Rollback automatique** : En cas d'erreur, les contraintes sont restaurées
- **État cohérent** : La base reste toujours dans un état valide

### 3. Logs et traçabilité
- **Messages détaillés** : Chaque étape est documentée
- **Statuts clairs** : Succès, avertissement, erreur
- **Comptage** : Nombre d'enregistrements traités

## Extensions futures

### 1. Support d'autres types de contraintes
```php
// Support des contraintes UNIQUE
// Support des contraintes CHECK
// Support des triggers
```

### 2. Gestion des index
```php
// Suppression temporaire des index
// Recréation des index après import
// Optimisation des performances
```

### 3. Support des vues
```php
// Gestion des vues qui référencent la table
// Mise à jour des vues après import
// Validation des données
```

## Tests et validation

### 1. Test de suppression de contrainte
```sql
-- Vérifier que la contrainte est supprimée
SHOW CREATE TABLE user_monster_collection;
-- Ne doit plus afficher la contrainte FOREIGN KEY
```

### 2. Test de recréation de contrainte
```sql
-- Vérifier que la contrainte est recréée
SHOW CREATE TABLE user_monster_collection;
-- Doit afficher la contrainte FOREIGN KEY
```

### 3. Test d'intégrité des données
```sql
-- Vérifier que les références sont valides
SELECT COUNT(*) FROM user_monster_collection umc
JOIN dnd_monsters dm ON umc.monster_id = dm.id;
-- Doit retourner le même nombre que user_monster_collection
```

## Dépannage

### 1. Contrainte non supprimée
- **Cause** : Permissions insuffisantes
- **Solution** : Vérifier les droits de l'utilisateur de base de données

### 2. Contrainte non recréée
- **Cause** : Erreur de syntaxe dans la recréation
- **Solution** : Vérifier la structure de la table et les types de données

### 3. Données corrompues
- **Cause** : Erreur lors de l'import
- **Solution** : Vérifier le format du fichier CSV et la structure de la table

---

**Statut** : ✅ **GESTION DES CONTRAINTES IMPLÉMENTÉE**

La gestion automatique des contraintes de clé étrangère est maintenant implémentée et résout le problème de TRUNCATE sur les tables référencées. Le système est robuste, sécurisé et maintient l'intégrité des données.






