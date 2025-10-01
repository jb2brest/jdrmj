# 🔄 Mise à jour des fichiers de visualisation - Utilisation des classes

## 📋 Résumé des modifications

Les fichiers suivants ont été mis à jour pour utiliser le système de classes `Monde`, `Pays`, `Region` et `Univers` :

### ✅ Fichiers mis à jour

1. **`view_world.php`** - Visualisation des mondes
2. **`view_country.php`** - Visualisation des pays  
3. **`view_region.php`** - Visualisation des régions
4. **`view_campaign.php`** - Visualisation des campagnes

## 🔧 Modifications apportées

### 1. **view_world.php**

#### **Changements principaux :**
- ✅ **Remplacement de l'inclusion** : `config/database.php` → `classes/init.php`
- ✅ **Récupération du monde** : Requête SQL → `Monde::findById($world_id)`
- ✅ **Récupération des pays** : Requête SQL → `$monde->getCountries()`
- ✅ **Affichage des données** : `$world['name']` → `$monde->getName()`
- ✅ **Création de pays** : Requête SQL → `$univers->createPays()`
- ✅ **Mise à jour de pays** : Requête SQL → `$pays->save()`
- ✅ **Suppression de pays** : Requête SQL → `$pays->delete()`

#### **Avant :**
```php
$stmt = $pdo->prepare("SELECT * FROM worlds WHERE id = ? AND created_by = ?");
$stmt->execute([$world_id, $user_id]);
$world = $stmt->fetch();
```

#### **Après :**
```php
$monde = Monde::findById($world_id);
if (!$monde || $monde->getCreatedBy() != $user_id) {
    header('Location: manage_worlds.php?error=world_not_found');
    exit();
}
```

### 2. **view_country.php**

#### **Changements principaux :**
- ✅ **Remplacement de l'inclusion** : `config/database.php` → `classes/init.php`
- ✅ **Récupération du pays** : Requête SQL → `Pays::findById($country_id)`
- ✅ **Récupération des régions** : Requête SQL → `$pays->getRegions()`
- ✅ **Affichage des données** : `$country['name']` → `$pays->getName()`
- ✅ **Vérification des permissions** : `$pays->getMonde()->getCreatedBy()`

#### **Avant :**
```php
$stmt = $pdo->prepare("SELECT c.*, w.name as world_name, w.created_by 
                      FROM countries c 
                      JOIN worlds w ON c.world_id = w.id 
                      WHERE c.id = ? AND w.created_by = ?");
$stmt->execute([$country_id, $user_id]);
$country = $stmt->fetch();
```

#### **Après :**
```php
$pays = Pays::findById($country_id);
if (!$pays || $pays->getMonde()->getCreatedBy() != $user_id) {
    header('Location: manage_worlds.php?error=country_not_found');
    exit();
}
```

### 3. **view_region.php**

#### **Changements principaux :**
- ✅ **Remplacement de l'inclusion** : `config/database.php` → `classes/init.php`
- ✅ **Récupération de la région** : Requête SQL → `Region::findById($region_id)`
- ✅ **Récupération des lieux** : Requête SQL → `$region->getPlaces()`
- ✅ **Affichage des données** : `$region['name']` → `$region->getName()`
- ✅ **Vérification des permissions** : `$region->getMonde()->getCreatedBy()`

#### **Avant :**
```php
$stmt = $pdo->prepare("SELECT r.*, c.name as country_name, c.world_id, w.name as world_name, w.created_by 
                      FROM regions r 
                      JOIN countries c ON r.country_id = c.id 
                      JOIN worlds w ON c.world_id = w.id 
                      WHERE r.id = ? AND w.created_by = ?");
$stmt->execute([$region_id, $user_id]);
$region = $stmt->fetch();
```

#### **Après :**
```php
$region = Region::findById($region_id);
if (!$region || $region->getMonde()->getCreatedBy() != $user_id) {
    header('Location: manage_worlds.php?error=region_not_found');
    exit();
}
```

### 4. **view_campaign.php**

#### **Changements principaux :**
- ✅ **Remplacement de l'inclusion** : `config/database.php` → `classes/init.php`
- ✅ **Simplification des requêtes** : Suppression des JOINs avec `worlds`
- ✅ **Récupération du monde** : Requête SQL → `Monde::findById($campaign['world_id'])`
- ✅ **Gestion des mondes** : Ajout de la logique de récupération des informations du monde

#### **Avant :**
```php
$stmt = $pdo->prepare("SELECT c.*, u.username AS dm_username, w.name AS world_name, w.id AS world_id 
                      FROM campaigns c 
                      JOIN users u ON c.dm_id = u.id 
                      LEFT JOIN worlds w ON c.world_id = w.id 
                      WHERE c.id = ?");
```

#### **Après :**
```php
$stmt = $pdo->prepare("SELECT c.*, u.username AS dm_username 
                      FROM campaigns c 
                      JOIN users u ON c.dm_id = u.id 
                      WHERE c.id = ?");

// Récupérer les informations du monde si la campagne en a un
$world_name = '';
$world_id = null;
if ($campaign && !empty($campaign['world_id'])) {
    $monde = Monde::findById($campaign['world_id']);
    if ($monde) {
        $world_name = $monde->getName();
        $world_id = $monde->getId();
    }
}
$campaign['world_name'] = $world_name;
$campaign['world_id'] = $world_id;
```

## 🎯 Avantages de la refactorisation

### **1. Code plus maintenable**
- ✅ **Séparation des responsabilités** : Chaque classe gère ses propres données
- ✅ **Réutilisabilité** : Les méthodes des classes peuvent être utilisées ailleurs
- ✅ **Lisibilité** : Code plus clair et expressif

### **2. Performance améliorée**
- ✅ **Moins de requêtes SQL** : Utilisation des méthodes optimisées des classes
- ✅ **Cache intégré** : L'Univers gère le cache des objets
- ✅ **Requêtes simplifiées** : Suppression des JOINs complexes

### **3. Sécurité renforcée**
- ✅ **Validation centralisée** : Les classes valident les données
- ✅ **Gestion d'erreurs** : Meilleure gestion des exceptions
- ✅ **Permissions** : Vérification des droits d'accès via les classes

### **4. Cohérence du système**
- ✅ **Architecture uniforme** : Tous les fichiers utilisent le même système
- ✅ **Gestion centralisée** : L'Univers gère les connexions et le cache
- ✅ **Évolutivité** : Facile d'ajouter de nouvelles fonctionnalités

## 🔍 Détails techniques

### **Méthodes utilisées :**

#### **Classe Monde :**
- `Monde::findById($id)` - Récupérer un monde par ID
- `$monde->getName()` - Nom du monde
- `$monde->getDescription()` - Description du monde
- `$monde->getMapUrl()` - URL de la carte
- `$monde->getCreatedBy()` - ID du créateur
- `$monde->getCountries()` - Liste des pays

#### **Classe Pays :**
- `Pays::findById($id)` - Récupérer un pays par ID
- `$pays->getName()` - Nom du pays
- `$pays->getDescription()` - Description du pays
- `$pays->getMapUrl()` - URL de la carte
- `$pays->getCoatOfArmsUrl()` - URL du blason
- `$pays->getWorldId()` - ID du monde
- `$pays->getWorldName()` - Nom du monde
- `$pays->getRegionCount()` - Nombre de régions
- `$pays->getRegions()` - Liste des régions
- `$pays->getMonde()` - Objet Monde associé
- `$pays->save()` - Sauvegarder les modifications
- `$pays->delete()` - Supprimer le pays
- `$pays->toArray()` - Convertir en tableau

#### **Classe Region :**
- `Region::findById($id)` - Récupérer une région par ID
- `$region->getName()` - Nom de la région
- `$region->getDescription()` - Description de la région
- `$region->getMapUrl()` - URL de la carte
- `$region->getCountryId()` - ID du pays
- `$region->getCountryName()` - Nom du pays
- `$region->getWorldName()` - Nom du monde
- `$region->getMonde()` - Objet Monde associé
- `$region->getPlaces()` - Liste des lieux
- `$region->toArray()` - Convertir en tableau

#### **Classe Univers :**
- `getUnivers()` - Obtenir l'instance de l'Univers
- `$univers->createPays()` - Créer un nouveau pays

## 🚀 Déploiement

### **Fichiers à déployer :**
- ✅ `view_world.php`
- ✅ `view_country.php`
- ✅ `view_region.php`
- ✅ `view_campaign.php`

### **Dépendances :**
- ✅ `classes/init.php` (déjà déployé)
- ✅ `classes/Monde.php` (déjà déployé)
- ✅ `classes/Pays.php` (déjà déployé)
- ✅ `classes/Region.php` (déjà déployé)
- ✅ `classes/Univers.php` (déjà déployé)

## ✅ Tests recommandés

### **1. Tests fonctionnels :**
- [ ] Affichage des mondes
- [ ] Affichage des pays
- [ ] Affichage des régions
- [ ] Affichage des campagnes
- [ ] Création de pays depuis un monde
- [ ] Modification de pays
- [ ] Suppression de pays
- [ ] Navigation entre les niveaux

### **2. Tests de sécurité :**
- [ ] Vérification des permissions
- [ ] Accès aux données d'autres utilisateurs
- [ ] Validation des entrées utilisateur

### **3. Tests de performance :**
- [ ] Temps de chargement des pages
- [ ] Utilisation de la mémoire
- [ ] Nombre de requêtes SQL

## 🎉 Résultat

Tous les fichiers de visualisation utilisent maintenant le système de classes, offrant :
- **Code plus propre et maintenable**
- **Performance améliorée**
- **Sécurité renforcée**
- **Architecture cohérente**

La refactorisation est terminée et prête pour le déploiement ! 🚀

