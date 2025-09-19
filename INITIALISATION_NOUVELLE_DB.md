# Guide d'Initialisation d'une Nouvelle Base de Données

## 🎯 **Objectif**

Ce guide explique comment créer une nouvelle base de données JDR MJ avec toutes les données de base nécessaires (classes, races, sorts, monstres, etc.) mais **sans aucune donnée utilisateur** (pas d'utilisateurs, personnages, campagnes).

## 📋 **Fichiers fournis**

### **Scripts d'initialisation**
- `database/init_new_database.sql` - Structure complète de la base de données
- `database/init_new_database.php` - Script PHP automatisé d'initialisation
- `database/export_base_data.sql` - Script d'exportation des données de base
- `database/test_new_database.php` - Script de test et validation

### **Documentation**
- `INITIALISATION_NOUVELLE_DB.md` - Ce guide
- `ANALYSE_TABLES_DB.md` - Analyse des tables de la base existante

## 🚀 **Méthode 1 : Script PHP automatisé (Recommandé)**

### **Étape 1 : Préparation**
```bash
cd /home/robin-des-briques/Documents/jdrmj/database
```

### **Étape 2 : Exécution**
```bash
php init_new_database.php
```

### **Étape 3 : Test**
```bash
php test_new_database.php
```

## 🛠️ **Méthode 2 : Scripts SQL manuels**

### **Étape 1 : Création de la structure**
```bash
mysql -u username -p < init_new_database.sql
```

### **Étape 2 : Exportation des données de base**
```bash
mysql -u username -p < export_base_data.sql > base_data_export.sql
```

### **Étape 3 : Importation des données**
```bash
mysql -u username -p jdrmj_new < base_data_export.sql
```

## 📊 **Données incluses dans la nouvelle base**

### **✅ Données de base (incluses)**
- **Races** : 14 races D&D 5e
- **Classes** : 13 classes de personnages
- **Sorts** : 477 sorts
- **Armes** : 37 armes
- **Armures** : 13 armures
- **Objets magiques** : 323 objets
- **Poisons** : 100 poisons
- **Langues** : 16 langues
- **Historiques** : 13 historiques
- **Niveaux d'expérience** : 20 niveaux
- **Monstres** : 428 monstres D&D
- **Archétypes de classes** : Tous les archétypes

### **❌ Données utilisateur (exclues)**
- Utilisateurs (sauf admin par défaut)
- Personnages
- Campagnes
- Notifications
- Lancers de dés
- Collections de monstres

## 🔧 **Configuration**

### **Base de données cible**
- **Nom** : `jdrmj_new`
- **Charset** : `utf8mb4`
- **Collation** : `utf8mb4_unicode_ci`

### **Utilisateur admin par défaut**
- **Username** : `admin`
- **Email** : `admin@jdrmj.local`
- **Mot de passe** : `admin123`
- **Rôle** : `admin`

⚠️ **IMPORTANT** : Changez le mot de passe admin en production !

## 📋 **Structure des tables créées**

### **Tables principales (68 tables)**
- `users` - Utilisateurs
- `campaigns` - Campagnes
- `characters` - Personnages
- `campaign_members` - Membres des campagnes
- `campaign_applications` - Candidatures

### **Tables de données de base**
- `races` - Races D&D
- `classes` - Classes de personnages
- `spells` - Sorts
- `weapons` - Armes
- `armor` - Armures
- `magical_items` - Objets magiques
- `poisons` - Poisons
- `languages` - Langues
- `backgrounds` - Historiques
- `experience_levels` - Niveaux d'expérience

### **Tables d'archétypes**
- `cleric_domains` - Domaines de clerc
- `druid_circles` - Cercles de druide
- `fighter_archetypes` - Archétypes de guerrier
- `monk_traditions` - Traditions de moine
- `sorcerer_origins` - Origines de sorcier
- `warlock_pacts` - Pactes de sorcier
- `wizard_traditions` - Traditions de magicien

### **Tables de monstres**
- `dnd_monsters` - Monstres D&D
- `monster_actions` - Actions de monstres
- `monster_equipment` - Équipement de monstres
- `monster_legendary_actions` - Actions légendaires
- `monster_special_attacks` - Attaques spéciales
- `monster_spells` - Sorts de monstres

### **Tables de géographie**
- `countries` - Pays
- `regions` - Régions
- `places` - Lieux
- `place_players` - Joueurs dans les lieux
- `place_npcs` - PNJ dans les lieux
- `place_monsters` - Monstres dans les lieux
- `place_tokens` - Tokens dans les lieux

### **Tables de système**
- `notifications` - Notifications
- `dice_rolls` - Lancers de dés
- `system_versions` - Versions du système
- `database_migrations` - Migrations

## 🧪 **Tests et validation**

### **Script de test automatique**
```bash
php test_new_database.php
```

### **Tests effectués**
- ✅ Connexion à la base de données
- ✅ Vérification des tables essentielles
- ✅ Comptage des données de base
- ✅ Vérification des archétypes
- ✅ Vérification des données de monstres
- ✅ Vérification de l'utilisateur admin
- ✅ Vérification des contraintes de clés étrangères
- ✅ Tests de fonctionnalités de base

### **Résultats attendus**
- **Tables** : 68 tables créées
- **Données de base** : ~1500+ enregistrements
- **Utilisateur admin** : 1 utilisateur créé
- **Contraintes FK** : Toutes les contraintes présentes

## 🔄 **Utilisation avec l'application**

### **Configuration de l'application**
1. Modifier `config/database.php` pour pointer vers la nouvelle base
2. Ou créer un nouveau fichier de configuration
3. Tester la connexion

### **Première utilisation**
1. Se connecter avec `admin/admin123`
2. Changer le mot de passe admin
3. Créer des utilisateurs de test
4. Créer une campagne de test
5. Tester les fonctionnalités

## 🛡️ **Sécurité**

### **Recommandations**
- ✅ Changer le mot de passe admin par défaut
- ✅ Configurer les permissions de base de données
- ✅ Utiliser des mots de passe forts
- ✅ Limiter l'accès à la base de données
- ✅ Sauvegarder régulièrement

### **Permissions recommandées**
```sql
-- Utilisateur de l'application
GRANT SELECT, INSERT, UPDATE, DELETE ON jdrmj_new.* TO 'app_user'@'localhost';
-- Utilisateur admin
GRANT ALL PRIVILEGES ON jdrmj_new.* TO 'admin_user'@'localhost';
```

## 📈 **Avantages de cette approche**

### **✅ Avantages**
- **Base propre** : Aucune donnée utilisateur
- **Données complètes** : Toutes les données de base incluses
- **Prêt à l'emploi** : Structure complète et fonctionnelle
- **Testable** : Scripts de test inclus
- **Documenté** : Guide complet fourni
- **Réutilisable** : Peut être utilisé pour plusieurs environnements

### **🎯 Cas d'usage**
- **Environnement de test** : Base propre pour les tests
- **Nouvelle installation** : Installation fraîche
- **Environnement de staging** : Validation avant production
- **Développement** : Base de développement isolée

## 🆘 **Dépannage**

### **Erreurs courantes**

#### **Erreur de connexion**
```
❌ ERREUR DE CONNEXION: Access denied for user
```
**Solution** : Vérifier les paramètres de connexion dans le script

#### **Base de données existe déjà**
```
❌ ERREUR: Database 'jdrmj_new' already exists
```
**Solution** : Supprimer la base existante ou changer le nom

#### **Tables manquantes**
```
❌ Tables manquantes: users, campaigns
```
**Solution** : Vérifier que le script de structure s'est exécuté correctement

### **Logs et débogage**
- Vérifier les logs MySQL
- Utiliser le script de test pour diagnostiquer
- Vérifier les permissions de l'utilisateur

## 📞 **Support**

En cas de problème :
1. Vérifier les logs d'erreur
2. Exécuter le script de test
3. Vérifier la configuration
4. Consulter la documentation

---

**🎉 Votre nouvelle base de données JDR MJ est prête !**
