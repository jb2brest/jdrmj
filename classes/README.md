# 🏗️ Système de Classes - Documentation

## 📋 **Vue d'ensemble**

Ce répertoire contient le système de classes orienté objet pour l'application JDR MJ. Le système est conçu pour encapsuler les fonctionnalités métier et améliorer la maintenabilité du code.

---

## 📁 **Structure des fichiers**

```
classes/
├── Autoloader.php      # Système d'autoloading automatique
├── Database.php        # Gestion des connexions à la base de données
├── Monde.php          # Classe pour la gestion des mondes
├── init.php           # Fichier d'initialisation
└── README.md          # Cette documentation
```

---

## 🚀 **Utilisation**

### **Initialisation**

```php
// Inclure l'initialisation des classes
require_once 'classes/init.php';

// Les classes sont maintenant disponibles automatiquement
```

### **Exemple d'utilisation de la classe Monde**

```php
// Créer un nouveau monde
$monde = new Monde(getPDO());
$monde->setName("Monde de Test")
      ->setDescription("Description du monde")
      ->setCreatedBy($user_id);

// Valider et sauvegarder
$errors = $monde->validate();
if (empty($errors)) {
    $monde->save();
}

// Récupérer un monde existant
$monde = Monde::findById(getPDO(), $world_id);

// Récupérer tous les mondes d'un utilisateur
$mondes = Monde::findByUser(getPDO(), $user_id);
```

---

## 🏛️ **Architecture des classes**

### **1. Classe Database**

**Responsabilités:**
- Gestion des connexions PDO (pattern Singleton)
- Méthodes utilitaires pour les requêtes SQL
- Gestion des transactions

**Méthodes principales:**
- `getInstance()` - Obtient l'instance unique
- `getPdo()` - Retourne l'instance PDO
- `selectAll()`, `selectOne()` - Requêtes SELECT
- `execute()`, `insert()` - Requêtes INSERT/UPDATE/DELETE
- `beginTransaction()`, `commit()`, `rollback()` - Gestion des transactions

### **2. Classe Monde**

**Responsabilités:**
- Encapsulation des données d'un monde
- Validation des données
- Persistance en base de données
- Gestion des relations (pays, etc.)

**Propriétés:**
- `id` - Identifiant unique
- `name` - Nom du monde
- `description` - Description du monde
- `map_url` - URL de la carte
- `created_by` - ID du créateur
- `created_at`, `updated_at` - Timestamps

**Méthodes principales:**
- **Getters/Setters** - Accès aux propriétés
- `validate()` - Validation des données
- `save()` - Sauvegarde en base
- `delete()` - Suppression
- `getCountryCount()` - Nombre de pays
- `getCountries()` - Liste des pays
- `toArray()` - Conversion en tableau

**Méthodes statiques:**
- `findById()` - Recherche par ID
- `findByUser()` - Recherche par utilisateur
- `nameExists()` - Vérification d'existence

### **3. Classe Autoloader**

**Responsabilités:**
- Chargement automatique des classes
- Gestion des répertoires de recherche
- Support des namespaces

---

## 🔧 **Configuration**

### **Base de données**

La classe `Database` charge automatiquement la configuration depuis:
1. `config/database.php`
2. `config/database.test.php`

### **Autoloader**

L'autoloader est configuré pour chercher dans:
- `classes/` (répertoire principal)
- `includes/` (répertoire des includes)

---

## 📝 **Bonnes pratiques**

### **1. Utilisation des classes**

```php
// ✅ Bon
$monde = new Monde(getPDO());
$monde->setName("Mon Monde")->save();

// ❌ Éviter
$monde = new Monde($pdo); // Utiliser getPDO() pour la cohérence
```

### **2. Gestion des erreurs**

```php
try {
    $monde = new Monde(getPDO());
    $monde->save();
} catch (Exception $e) {
    // Gérer l'erreur appropriée
    $error_message = $e->getMessage();
}
```

### **3. Validation**

```php
$errors = $monde->validate();
if (!empty($errors)) {
    // Afficher les erreurs
    foreach ($errors as $error) {
        echo $error;
    }
}
```

---

## 🧪 **Tests**

### **Fichier de test**

Utilisez `test_monde_class.php` pour tester les fonctionnalités:

```bash
# Accéder au fichier via le navigateur
http://votre-domaine/test_monde_class.php
```

### **Tests recommandés**

1. **Création d'un monde**
2. **Validation des données**
3. **Récupération par ID**
4. **Récupération par utilisateur**
5. **Vérification d'existence**
6. **Conversion en tableau**

---

## 🔄 **Migration depuis le code existant**

### **Avant (code procédural)**

```php
$stmt = $pdo->prepare("INSERT INTO worlds (name, description, created_by) VALUES (?, ?, ?)");
$stmt->execute([$name, $description, $user_id]);
```

### **Après (orienté objet)**

```php
$monde = new Monde(getPDO());
$monde->setName($name)
      ->setDescription($description)
      ->setCreatedBy($user_id)
      ->save();
```

---

## 🚀 **Évolutions futures**

### **Classes à créer**

1. **Classe Pays** - Gestion des pays
2. **Classe Région** - Gestion des régions
3. **Classe Lieu** - Gestion des lieux
4. **Classe Campagne** - Gestion des campagnes
5. **Classe Personnage** - Gestion des personnages

### **Améliorations**

1. **Interface commune** pour toutes les entités
2. **Repository pattern** pour la persistance
3. **Service layer** pour la logique métier
4. **Validation centralisée**

---

## 📚 **Ressources**

- [Documentation PHP OOP](https://www.php.net/manual/en/language.oop5.php)
- [Pattern Singleton](https://refactoring.guru/design-patterns/singleton)
- [Autoloading PSR-4](https://www.php-fig.org/psr/psr-4/)
