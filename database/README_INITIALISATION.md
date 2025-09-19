# Scripts d'Initialisation de Base de Données

## 🎯 **Objectif**

Ces scripts permettent de créer une nouvelle base de données JDR MJ avec toutes les données de base nécessaires (classes, races, sorts, monstres, etc.) mais **sans aucune donnée utilisateur** (pas d'utilisateurs, personnages, campagnes).

## 📋 **Scripts disponibles**

### **1. Script simplifié (Recommandé)**
```bash
php simple_init_database.php
```
- **Base créée** : `jdrmj_new`
- **Tables** : 35 tables (données de base uniquement)
- **Données** : ~4000 enregistrements
- **Utilisateur admin** : admin/admin123

### **2. Script complet**
```bash
php complete_init_database.php
```
- **Base créée** : `jdrmj_complete`
- **Tables** : Toutes les tables (structure complète)
- **Données** : Données de base + structure complète
- **Utilisateur admin** : admin/admin123

### **3. Script de test**
```bash
php test_new_database.php
```
- **Fonction** : Teste et valide la base créée
- **Vérifications** : Tables, données, contraintes, fonctionnalités

## 🚀 **Utilisation rapide**

### **Étape 1 : Initialisation**
```bash
cd /home/robin-des-briques/Documents/jdrmj/database
php simple_init_database.php
```

### **Étape 2 : Test**
```bash
php test_new_database.php
```

### **Étape 3 : Configuration de l'application**
Modifier `config/database.php` pour pointer vers la nouvelle base :
```php
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'jdrmj_new',  // Nouvelle base
    'username' => 'u839591438_jdrmj',
    'password' => 'M8jbsYJUj6FE$;C'
];
```

## 📊 **Données incluses**

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
- **Archétypes** : Tous les archétypes de classes
- **Actions de monstres** : 1227 actions
- **Actions légendaires** : 105 actions
- **Attaques spéciales** : 760 attaques
- **Sorts de monstres** : 517 sorts
- **Géographie** : 6 pays, 16 régions

### **❌ Données utilisateur (exclues)**
- Utilisateurs (sauf admin par défaut)
- Personnages
- Campagnes
- Notifications
- Lancers de dés
- Collections de monstres

## 🔧 **Configuration**

### **Utilisateur admin par défaut**
- **Username** : `admin`
- **Email** : `admin@jdrmj.local`
- **Mot de passe** : `admin123`
- **Rôle** : `admin`

⚠️ **IMPORTANT** : Changez le mot de passe admin en production !

### **Paramètres de connexion**
Les scripts utilisent les paramètres de connexion de la base source :
- **Host** : localhost
- **Username** : u839591438_jdrmj
- **Password** : M8jbsYJUj6FE$;C

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
- **Tables** : 35+ tables créées
- **Données de base** : 4000+ enregistrements
- **Utilisateur admin** : 1 utilisateur créé
- **Contraintes FK** : Contraintes présentes

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

## 📈 **Avantages**

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
**Solution** : Le script supprime automatiquement la base existante

#### **Tables manquantes**
```
❌ Tables manquantes: users, campaigns
```
**Solution** : Utiliser le script complet au lieu du script simplifié

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
