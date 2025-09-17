# 🗄️ Configuration Multi-Environnement des Bases de Données

## 📋 Vue d'Ensemble

Le système de configuration des bases de données permet de gérer automatiquement différents environnements (Test, Staging, Production) avec des configurations spécifiques pour chaque environnement.

## 🏗️ Architecture

### Structure des Fichiers

```
config/
├── database.php              # Configuration principale (détection automatique)
├── database.test.php         # Configuration pour l'environnement TEST
├── database.staging.php      # Configuration pour l'environnement STAGING
└── database.production.php   # Configuration pour l'environnement PRODUCTION
```

### Fichiers de Configuration

- **`config/database.php`** : Configuration principale avec détection automatique de l'environnement
- **`config/database.{env}.php`** : Configuration spécifique à chaque environnement
- **`config.env.example`** : Exemple de configuration d'environnement
- **`setup_databases.sh`** : Script de configuration des bases de données

## 🎯 Environnements Configurés

### 🧪 Environnement TEST
- **Base de données** : `jdrmj_test`
- **Utilisateur** : `jdrmj_test_user`
- **Mot de passe** : `test_password_123`
- **URL** : `http://localhost/jdrmj_test`
- **Debug** : Activé
- **Logs** : Debug

### 🎭 Environnement STAGING
- **Base de données** : `jdrmj_staging`
- **Utilisateur** : `jdrmj_staging_user`
- **Mot de passe** : `staging_password_456`
- **URL** : `http://localhost/jdrmj_staging`
- **Debug** : Activé
- **Logs** : Info

### 🏭 Environnement PRODUCTION
- **Base de données** : `u839591438_jdrmj`
- **Utilisateur** : `u839591438_jdrmj`
- **Mot de passe** : `M8jbsYJUj6FE$;C`
- **URL** : `http://localhost/jdrmj`
- **Debug** : Désactivé
- **Logs** : Error

## 🔧 Configuration

### 1. Détection Automatique de l'Environnement

Le système détecte automatiquement l'environnement en vérifiant :

1. **Variable d'environnement** : `$_ENV['APP_ENV']`
2. **Nom du serveur** : `$_SERVER['SERVER_NAME']`
3. **Chemin du script** : `$_SERVER['SCRIPT_NAME']`
4. **Host HTTP** : `$_SERVER['HTTP_HOST']`
5. **Par défaut** : Production

### 2. Configuration par Fichier

Chaque environnement a son propre fichier de configuration :

```php
// config/database.test.php
return [
    'host' => 'localhost',
    'dbname' => 'jdrmj_test',
    'username' => 'jdrmj_test_user',
    'password' => 'test_password_123',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
```

### 3. Configuration par Variable d'Environnement

Vous pouvez forcer un environnement en définissant la variable :

```bash
# Dans le fichier .env
APP_ENV=test

# Ou en ligne de commande
export APP_ENV=staging
```

## 🚀 Utilisation

### Configuration des Bases de Données

```bash
# Configurer un environnement spécifique
./setup_databases.sh test
./setup_databases.sh staging
./setup_databases.sh production

# Configurer tous les environnements
./setup_databases.sh all

# Afficher l'aide
./setup_databases.sh help
```

### Déploiement avec Configuration

```bash
# Le script push.sh configure automatiquement l'environnement
./push.sh test "Message de test"
./push.sh staging "Message de staging"
```

### Utilisation dans le Code PHP

```php
// La configuration est automatiquement chargée
require_once 'config/database.php';

// Les constantes sont disponibles
echo DB_HOST;    // localhost
echo DB_NAME;    // jdrmj_test (selon l'environnement)
echo DB_USER;    // jdrmj_test_user
echo DB_ENV;     // test

// La connexion PDO est prête
$stmt = $pdo->prepare("SELECT * FROM users");
$stmt->execute();
```

## 🔒 Sécurité

### Fichiers de Configuration

- **Permissions** : `600` (lecture/écriture pour le propriétaire uniquement)
- **Propriétaire** : `www-data:www-data`
- **Exclusion Git** : Les fichiers `.env` sont exclus du versioning

### Gestion des Mots de Passe

- **Production** : Mots de passe complexes et uniques
- **Test/Staging** : Mots de passe simples pour le développement
- **Rotation** : Changement régulier des mots de passe

## 🛠️ Maintenance

### Ajout d'un Nouvel Environnement

1. **Créer le fichier de configuration** :
   ```bash
   cp config/database.test.php config/database.dev.php
   ```

2. **Modifier la configuration** :
   ```php
   // config/database.dev.php
   return [
       'host' => 'localhost',
       'dbname' => 'jdrmj_dev',
       'username' => 'jdrmj_dev_user',
       'password' => 'dev_password_789',
       // ...
   ];
   ```

3. **Ajouter la logique de détection** dans `config/database.php`

4. **Configurer la base de données** :
   ```bash
   ./setup_databases.sh dev
   ```

### Modification des Mots de Passe

1. **Modifier le fichier de configuration** approprié
2. **Mettre à jour la base de données** :
   ```sql
   ALTER USER 'username'@'host' IDENTIFIED BY 'new_password';
   ```
3. **Redéployer l'application** si nécessaire

## 📊 Monitoring

### Logs de Connexion

```php
// Les logs sont automatiquement enregistrés en mode debug
if (defined('DEBUG') && DEBUG) {
    error_log("Connexion DB - Environnement: " . DB_ENV . " - Host: " . DB_HOST);
}
```

### Vérification de l'Environnement

```php
// Vérifier l'environnement actuel
if (DB_ENV === 'production') {
    // Logique spécifique à la production
} elseif (DB_ENV === 'test') {
    // Logique spécifique aux tests
}
```

## 🚨 Dépannage

### Problèmes Courants

#### Erreur de Connexion
```bash
# Vérifier la configuration
php -r "require 'config/database.php'; echo 'Connexion OK';"

# Vérifier les logs
tail -f /var/log/apache2/error.log
```

#### Environnement Non Détecté
```bash
# Forcer un environnement
export APP_ENV=test
php -r "require 'config/database.php'; echo DB_ENV;"
```

#### Permissions de Fichier
```bash
# Corriger les permissions
sudo chown www-data:www-data config/database.*.php
sudo chmod 600 config/database.*.php
```

### Tests de Configuration

```bash
# Tester la configuration
./setup_databases.sh test
./push.sh test "Test de configuration"
```

## 📚 Exemples

### Configuration Personnalisée

```php
// config/database.custom.php
return [
    'host' => 'db.example.com',
    'dbname' => 'jdrmj_custom',
    'username' => 'custom_user',
    'password' => 'custom_password',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]
];
```

### Utilisation Avancée

```php
// Détection manuelle de l'environnement
$env = loadDatabaseConfig('staging');
$pdo = new PDO(
    "mysql:host={$env['host']};dbname={$env['dbname']};charset={$env['charset']}",
    $env['username'],
    $env['password'],
    $env['options']
);
```

---

**🎲 Configuration des bases de données terminée !** 🎲
