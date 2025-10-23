# Configuration des Environnements

## Problème résolu

L'erreur `Configuration de base de données non trouvée pour l'environnement: test` a été résolue en créant le fichier manquant `config/database.test.php`.

## Environnements disponibles

### 1. **Production** (par défaut)
- Fichier : `config/database.production.php`
- Base de données : `u839591438_jdrmj`
- Détection automatique : quand aucune variable d'environnement n'est définie

### 2. **Staging**
- Fichier : `config/database.staging.php`
- Base de données : `u839591438_jdrmj_s`
- Activation : `APP_ENV=staging`

### 3. **Test**
- Fichier : `config/database.test.php`
- Base de données : `u839591438_jdrmj` (même que production pour simplifier)
- Activation : `APP_ENV=test`

## Comment utiliser

### En ligne de commande
```bash
# Environnement de test
APP_ENV=test php script.php

# Environnement de staging
APP_ENV=staging php script.php

# Environnement de production (par défaut)
php script.php
```

### Dans un serveur web
Définir la variable d'environnement dans la configuration du serveur web ou dans un fichier `.env`.

### Détection automatique
Le système détecte automatiquement l'environnement basé sur :
1. Variable d'environnement `APP_ENV`
2. Nom du serveur (contient "test" ou "localhost" → test)
3. Nom du serveur (contient "staging" → staging)
4. Chemin du script
5. Host HTTP (localhost → test)
6. Par défaut : production

## Fichiers de configuration

Chaque environnement a son propre fichier de configuration :
- `config/database.production.php`
- `config/database.staging.php`
- `config/database.test.php`

## Résolution du problème

Le fichier `config/database.test.php` avait été supprimé lors du nettoyage des fichiers inutiles. Il a été recréé avec la même configuration que la production pour simplifier la gestion des environnements de test.
