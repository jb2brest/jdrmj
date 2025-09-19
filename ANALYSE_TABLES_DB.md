# Analyse des Tables de Base de Données

## Résumé de l'analyse

**Total des tables :** 70 tables
**Tables à supprimer :** 8 tables
**Tables à conserver :** 62 tables

## 📊 **Tables par catégorie**

### ✅ **Tables ACTIVES (à conserver)**

#### **Tables principales**
- `users` - Utilisateurs
- `campaigns` - Campagnes
- `characters` - Personnages
- `classes` - Classes de personnages
- `races` - Races de personnages
- `spells` - Sorts
- `weapons` - Armes
- `armor` - Armures
- `magical_items` - Objets magiques
- `poisons` - Poisons

#### **Tables de gestion des campagnes**
- `campaign_members` - Membres des campagnes
- `campaign_applications` - Candidatures aux campagnes
- `campaign_journal` - Journal des campagnes
- `game_sessions` - Sessions de jeu
- `session_registrations` - Inscriptions aux sessions

#### **Tables de lieux et scènes (système actuel)**
- `places` - Lieux
- `countries` - Pays
- `regions` - Régions
- `place_players` - Joueurs dans les lieux (2 enregistrements)
- `place_npcs` - PNJ dans les lieux (4 enregistrements)
- `place_tokens` - Tokens dans les lieux (15 enregistrements)
- `place_monsters` - Monstres dans les lieux

#### **Tables de monstres**
- `dnd_monsters` - Monstres D&D (428 enregistrements)
- `user_monster_collection` - Collection de monstres des utilisateurs (2 enregistrements)
- `monster_actions` - Actions des monstres
- `monster_equipment` - Équipement des monstres
- `monster_legendary_actions` - Actions légendaires
- `monster_special_attacks` - Attaques spéciales
- `monster_spells` - Sorts des monstres

#### **Tables de personnages avancées**
- `character_equipment` - Équipement des personnages
- `character_spells` - Sorts des personnages
- `character_ability_improvements` - Améliorations de caractéristiques
- Toutes les tables `character_*` pour les archétypes de classes

#### **Tables de classes et archétypes**
- `barbarian_paths`, `bard_colleges`, `cleric_domains`, etc.
- `fighter_archetypes`, `monk_traditions`, etc.

#### **Tables de système**
- `notifications` - Notifications (9 enregistrements)
- `dice_rolls` - Lancers de dés
- `system_versions` - Versions du système (49 enregistrements)
- `database_migrations` - Migrations de base de données (2 enregistrements)

### ❌ **Tables OBSOLÈTES (à supprimer)**

#### **Tables de sauvegarde (obsolètes)**
- `characters_backup` - Sauvegarde des personnages (3 enregistrements)
- `classes_backup` - Sauvegarde des classes (12 enregistrements)
- `races_backup` - Sauvegarde des races (0 enregistrements)

#### **Système de scènes obsolète (remplacé par places)**
- `scene_npcs` - PNJ dans les scènes (0 enregistrements)
- `scene_players` - Joueurs dans les scènes (0 enregistrements)
- `scene_tokens` - Tokens dans les scènes (0 enregistrements)

#### **Table de liaison obsolète**
- `character_places` - Liaison personnages-lieux (0 enregistrements, remplacée par place_players)

#### **Table de messages inutilisée**
- `messages` - Messages (0 enregistrements, système de notifications utilisé à la place)

## 🔍 **Analyse détaillée**

### **Système de lieux/scènes**
- **Ancien système :** `scene_*` (vides, non utilisées)
- **Nouveau système :** `place_*` (utilisées, avec données)
- **Conclusion :** Supprimer les tables `scene_*`

### **Gestion des joueurs dans les lieux**
- **Ancien système :** `character_places` (vide)
- **Nouveau système :** `place_players` (utilisé)
- **Conclusion :** Supprimer `character_places`

### **Tables de sauvegarde**
- Créées lors de migrations
- Plus nécessaires une fois les migrations terminées
- **Conclusion :** Supprimer toutes les tables `*_backup`

### **Système de messages**
- `messages` : vide, non utilisé
- `notifications` : utilisé (9 enregistrements)
- **Conclusion :** Supprimer `messages`

## 📋 **Plan de nettoyage**

### **Étape 1 : Sauvegardes**
```sql
-- Vérifier que les données principales sont intactes
SELECT COUNT(*) FROM characters; -- 14
SELECT COUNT(*) FROM classes;    -- 13
SELECT COUNT(*) FROM races;      -- 14
```

### **Étape 2 : Suppression des tables obsolètes**
```sql
DROP TABLE IF EXISTS characters_backup;
DROP TABLE IF EXISTS classes_backup;
DROP TABLE IF EXISTS races_backup;
DROP TABLE IF EXISTS scene_npcs;
DROP TABLE IF EXISTS scene_players;
DROP TABLE IF EXISTS scene_tokens;
DROP TABLE IF EXISTS character_places;
DROP TABLE IF EXISTS messages;
```

### **Étape 3 : Vérification**
- Vérifier que l'application fonctionne toujours
- Tester les fonctionnalités principales
- Vérifier les relations entre tables

## ⚠️ **Précautions**

1. **Sauvegarde complète** avant suppression
2. **Test en environnement de test** d'abord
3. **Vérification des contraintes de clés étrangères**
4. **Test des fonctionnalités** après nettoyage

## 📈 **Bénéfices du nettoyage**

- **Réduction de la complexité** : -8 tables
- **Amélioration des performances** : moins de tables à interroger
- **Clarté du schéma** : suppression des doublons
- **Maintenance simplifiée** : moins de tables à gérer
