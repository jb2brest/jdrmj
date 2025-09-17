# Script d'Initialisation de la Base de Données

## 📋 Description

Ce document décrit les scripts d'initialisation de la base de données pour l'application JDR MJ - D&D 5e.

## 📁 Fichiers Disponibles

### 1. `init_database.sql`
**Script principal d'initialisation complète de la base de données**

- ✅ Crée la base de données `dnd_characters`
- ✅ Crée toutes les tables nécessaires
- ✅ Configure les contraintes et index
- ✅ Insère les données initiales D&D 5e
- ✅ Vérifie l'intégrité de l'installation

### 2. `verify_database.sql`
**Script de vérification et de test de la base de données**

- ✅ Vérifie la structure des tables
- ✅ Contrôle les contraintes de clés étrangères
- ✅ Teste l'intégrité des données
- ✅ Effectue des tests de fonctionnalité
- ✅ Génère un rapport de statut

## 🚀 Utilisation

### ⚠️ IMPORTANT - Ne pas exécuter en local !

Ces scripts sont destinés à l'initialisation des serveurs de **production**, **staging** et **test**.

### Initialisation Complète

```bash
# Sur le serveur de production
mysql -u u839591438 -p u839591438_jdrmj < database/init_database.sql

# Sur le serveur de staging  
mysql -u u839591438 -p u839591438_jdrmj_s < database/init_database.sql

# Sur le serveur de test
mysql -u u839591438 -p u839591438_jdrmj < database/init_database.sql
```

### Vérification

```bash
# Vérifier l'installation
mysql -u u839591438 -p u839591438_jdrmj < database/verify_database.sql
```

## 🏗️ Structure Créée

### Tables Principales

| Table | Description | Relations |
|-------|-------------|-----------|
| `users` | Utilisateurs de l'application | - |
| `characters` | Personnages des joueurs | users, races, classes, backgrounds |
| `races` | Races D&D 5e | - |
| `classes` | Classes D&D 5e | - |
| `backgrounds` | Historiques D&D 5e | - |
| `languages` | Langues D&D 5e | - |
| `experience_levels` | Niveaux et XP D&D 5e | - |

### Tables de Campagnes

| Table | Description | Relations |
|-------|-------------|-----------|
| `campaigns` | Campagnes créées par les MJ | users (dm_id) |
| `campaign_members` | Membres des campagnes | campaigns, users |
| `campaign_applications` | Candidatures aux campagnes | campaigns, users |
| `game_sessions` | Sessions de jeu | users (dm_id), campaigns |
| `session_registrations` | Inscriptions aux sessions | game_sessions, users, characters |

### Tables de Scènes

| Table | Description | Relations |
|-------|-------------|-----------|
| `scenes` | Scènes dans les sessions | game_sessions |
| `scene_players` | Joueurs dans les scènes | scenes, users, characters |
| `scene_npcs` | PNJ dans les scènes | scenes, characters, dnd_monsters |
| `scene_tokens` | Positions des tokens | scenes |

### Tables de Données D&D

| Table | Description | Relations |
|-------|-------------|-----------|
| `spells` | Sorts D&D 5e | - |
| `character_spells` | Sorts des personnages | characters, spells |
| `dnd_monsters` | Monstres D&D 5e | - |
| `magical_items` | Objets magiques | - |
| `poisons` | Poisons | - |

### Tables d'Équipement

| Table | Description | Relations |
|-------|-------------|-----------|
| `weapons` | Armes D&D 5e | - |
| `armor` | Armures D&D 5e | - |
| `character_equipment` | Équipement des personnages | characters |
| `npc_equipment` | Équipement des PNJ | scene_npcs |
| `monster_equipment` | Équipement des monstres | dnd_monsters |

### Tables Système

| Table | Description | Relations |
|-------|-------------|-----------|
| `notifications` | Notifications utilisateurs | users |

## 📊 Données Initiales

### Races D&D 5e (8 races)
- Humain, Elfe, Nain, Halfelin
- Demi-elfe, Demi-orc, Gnome, Tieffelin

### Classes D&D 5e (12 classes)
- Barbare, Barde, Clerc, Druide
- Guerrier, Moine, Paladin, Rôdeur
- Roublard, Ensorceleur, Magicien, Occultiste

### Historiques D&D 5e (10 backgrounds)
- Acolyte, Artisan, Charlatan, Criminel, Ermite
- Folk Hero, Noble, Sage, Soldat, Vagabond

### Langues D&D 5e (10 langues)
- Commun, Elfe, Nain, Gnomique, Halfelin
- Orc, Draconique, Céleste, Infernal, Primordial

### Niveaux d'Expérience (20 niveaux)
- De 1 à 20 avec les points d'XP et bonus de compétence corrects

## 🔧 Configuration Multi-Environnement

### Production
```php
// config/database.production.php
return [
    'host' => 'localhost',
    'dbname' => 'u839591438_jdrmj',
    'username' => 'u839591438_jdrmj',
    'password' => 'M8jbsYJUj6FE$;C',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
];
```

### Staging
```php
// config/database.staging.php
return [
    'host' => 'localhost',
    'dbname' => 'u839591438_jdrmj_s',
    'username' => 'u839591438_jdrmj',
    'password' => 'M8jbsYJUj6FE$;C',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
];
```

### Test
```php
// config/database.test.php
return [
    'host' => 'localhost',
    'dbname' => 'u839591438_jdrmj',
    'username' => 'u839591438_jdrmj',
    'password' => 'M8jbsYJUj6FE$;C',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
];
```

## 🚀 Déploiement Automatique

Le script `push.sh` peut être utilisé pour déployer automatiquement sur les différents environnements :

```bash
# Déploiement sur test
./push.sh test "Initialisation base de données"

# Déploiement sur staging
./push.sh staging "Initialisation base de données"

# Déploiement sur production
./push.sh production "Initialisation base de données"
```

## 📋 Checklist d'Initialisation

### Avant l'Initialisation
- [ ] Vérifier les accès à la base de données
- [ ] Sauvegarder les données existantes (si applicable)
- [ ] Confirmer l'environnement cible

### Pendant l'Initialisation
- [ ] Exécuter `init_database.sql`
- [ ] Vérifier les messages d'erreur
- [ ] Exécuter `verify_database.sql`
- [ ] Contrôler le rapport de vérification

### Après l'Initialisation
- [ ] Tester la connexion depuis l'application
- [ ] Vérifier l'affichage des données initiales
- [ ] Tester la création d'un utilisateur
- [ ] Tester la création d'un personnage

## 🔍 Dépannage

### Erreurs Courantes

#### Erreur de Permissions
```sql
ERROR 1044 (42000): Access denied for user 'user'@'host' to database 'dnd_characters'
```
**Solution :** Vérifier les permissions de l'utilisateur sur la base de données.

#### Erreur de Contrainte
```sql
ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails
```
**Solution :** Vérifier que les données de référence existent avant l'insertion.

#### Erreur de Caractères
```sql
ERROR 1366 (22007): Incorrect string value: '\xF0\x9F\x8E\xB2' for column 'name'
```
**Solution :** Vérifier que la base utilise `utf8mb4` et non `utf8`.

### Vérifications

```sql
-- Vérifier l'encodage de la base
SHOW VARIABLES LIKE 'character_set_database';

-- Vérifier les permissions
SHOW GRANTS FOR 'user'@'host';

-- Vérifier les tables créées
SHOW TABLES;

-- Vérifier les données initiales
SELECT COUNT(*) FROM races;
SELECT COUNT(*) FROM classes;
SELECT COUNT(*) FROM experience_levels;
```

## 📞 Support

En cas de problème :

1. **Vérifier les logs** : Consulter les logs MySQL pour les erreurs détaillées
2. **Exécuter la vérification** : Utiliser `verify_database.sql` pour diagnostiquer
3. **Contrôler les permissions** : Vérifier les accès utilisateur
4. **Tester la connexion** : Utiliser `test_database_config.php`

## 🎯 Prochaines Étapes

Après l'initialisation de la base de données :

1. **Import des données CSV** : Utiliser les scripts d'import pour les monstres, objets magiques, etc.
2. **Configuration de l'application** : Vérifier que l'application se connecte correctement
3. **Tests fonctionnels** : Tester la création d'utilisateurs et de personnages
4. **Déploiement de l'application** : Utiliser `push.sh` pour déployer l'application

---

**⚠️ Rappel Important :** Ces scripts ne doivent PAS être exécutés en local. Ils sont destinés uniquement aux serveurs de déploiement (test, staging, production).
