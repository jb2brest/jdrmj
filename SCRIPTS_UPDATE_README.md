# 🔄 Mise à jour des Scripts de Déploiement

Ce document décrit les modifications apportées aux scripts `push.sh` et `publish.sh` pour intégrer le déploiement des classes PHP.

## 📋 Modifications Apportées

### 🚀 Script `push.sh`

#### **Nouvelles Fonctionnalités :**

1. **Fonction `deploy_classes()`** :
   - Vérifie l'existence du répertoire `classes`
   - Copie les classes vers l'environnement de déploiement
   - Configure les permissions appropriées
   - Vérifie la syntaxe PHP de toutes les classes
   - Teste l'initialisation des classes

2. **Intégration dans les environnements :**
   - **Test** : Déploiement local avec vérifications complètes
   - **Staging** : Déploiement avec vérifications
   - **Production** : Déploiement via FTP avec permissions

3. **Amélioration de `prepare_files()`** :
   - Inclusion du répertoire `classes/` dans la synchronisation
   - Copie de tous les fichiers de classes

4. **Amélioration du déploiement FTP** :
   - Ajout des permissions pour le répertoire `classes/`
   - Synchronisation des classes via FTP

#### **Fonctionnalités de Vérification :**

```bash
# Vérification de la syntaxe PHP
for file in "$deploy_path/classes"/*.php; do
    php -l "$file"
done

# Test d'initialisation des classes
php -r "
    require_once '$deploy_path/classes/init.php';
    \$univers = getUnivers();
    \$pdo = getPDO();
"
```

### 📦 Script `publish.sh`

#### **Nouvelles Fonctionnalités :**

1. **Déploiement automatique après publication** :
   - Option de déploiement sur le serveur de test
   - Option de déploiement sur le serveur de staging
   - Possibilité de ne pas déployer

2. **Interface interactive** :
   - Menu de choix pour le déploiement
   - Messages d'erreur en cas d'échec
   - Confirmation des actions

3. **Commandes utiles étendues** :
   - Ajout des commandes de déploiement
   - Documentation des options disponibles

## 🎯 Utilisation

### Déploiement avec `push.sh`

```bash
# Déploiement interactif
./push.sh

# Déploiement direct sur le serveur de test
./push.sh test "Message de déploiement"

# Déploiement sur staging sans tests
./push.sh staging "Message" --no-tests
```

### Publication avec `publish.sh`

```bash
# Publication d'un correctif
./publish.sh patch "Correction bug affichage"

# Publication d'une fonctionnalité
./publish.sh minor "Ajout gestion des classes"

# Publication majeure
./publish.sh major "Refonte complète avec classes"
```

## 🔧 Fonctionnalités de Déploiement des Classes

### ✅ Vérifications Automatiques

1. **Syntaxe PHP** : Vérification de tous les fichiers `.php` dans `classes/`
2. **Initialisation** : Test de l'initialisation de l'Univers et du PDO
3. **Permissions** : Configuration automatique des permissions
4. **Intégrité** : Vérification de la présence de tous les fichiers nécessaires

### 📁 Structure des Classes Déployées

```
classes/
├── init.php          # Fichier d'initialisation
├── Autoloader.php    # Autoloader des classes
├── Database.php      # Gestionnaire de base de données
├── Univers.php       # Classe Univers (Singleton)
├── Monde.php         # Classe Monde
├── Pays.php          # Classe Pays
├── Region.php        # Classe Region
└── README.md         # Documentation
```

### 🔐 Permissions Configurées

- **Répertoire** : `755` (rwxr-xr-x)
- **Fichiers PHP** : `644` (rw-r--r--)
- **Propriétaire** : `www-data:www-data`

## 🚨 Gestion des Erreurs

### Erreurs de Syntaxe PHP
- Arrêt du déploiement si erreurs détectées
- Affichage des fichiers en erreur
- Logs détaillés

### Erreurs d'Initialisation
- Test de l'Univers et du PDO
- Vérification des dépendances
- Messages d'erreur explicites

### Erreurs de Permissions
- Configuration automatique des permissions
- Vérification des droits d'accès
- Correction automatique si possible

## 📊 Avantages de l'Intégration

### 🔒 Sécurité
- Vérification automatique de la syntaxe
- Test de l'initialisation avant déploiement
- Permissions correctes configurées

### ⚡ Performance
- Déploiement optimisé des classes
- Vérifications rapides
- Gestion des erreurs efficace

### 🛠️ Maintenance
- Processus de déploiement unifié
- Logs détaillés
- Gestion d'erreurs centralisée

## 🔄 Workflow de Déploiement

1. **Préparation** : Copie des fichiers et classes
2. **Vérification** : Syntaxe PHP et initialisation
3. **Déploiement** : Synchronisation vers l'environnement
4. **Configuration** : Permissions et paramètres
5. **Validation** : Tests finaux et confirmation

## 📝 Notes Importantes

- Les classes sont automatiquement incluses dans tous les déploiements
- Les vérifications sont obligatoires pour tous les environnements
- Le déploiement s'arrête en cas d'erreur critique
- Les permissions sont configurées automatiquement
- Les logs sont détaillés pour faciliter le débogage

## 🆘 Support

En cas de problème :

1. Vérifier les logs de déploiement
2. Tester la syntaxe PHP manuellement
3. Vérifier les permissions des fichiers
4. Consulter la documentation des classes
5. Utiliser les options de debug des scripts

