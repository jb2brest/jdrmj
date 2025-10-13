# 🚀 Guide de Déploiement - JDR 4 MJ

## 📋 Scripts de Déploiement

### `push.sh` - Livraison sur Serveur de Test

Script automatisé pour livrer l'application sur les serveurs de test et staging.

#### Usage

```bash
# Mode interactif (recommandé)
./push.sh

# Mode ligne de commande
./push.sh test "Message de livraison"
./push.sh staging "Message de livraison"
./push.sh test "Message" --no-tests

# Aide
./push.sh --help
```

#### Fonctionnalités

- ✅ **Menu interactif** coloré et intuitif
- ✅ **Choix du serveur** (test/staging/production)
- ✅ **Option avec ou sans tests** avant déploiement
- ✅ **Saisie du message** de déploiement
- ✅ **Confirmation** avant déploiement
- ✅ **Mode ligne de commande** conservé
- ✅ **Vérification des prérequis** (git, rsync, fichiers)
- ✅ **Sauvegarde automatique** de la version précédente
- ✅ **Livraison sélective** (exclusion des fichiers de développement)
- ✅ **Gestion des permissions** (www-data, chmod)
- ✅ **Logs colorés** et informatifs
- ✅ **Nettoyage automatique** des fichiers temporaires

### `publish.sh` - Publication en Production

Script pour la publication officielle en production (avec tags Git).

#### Usage

```bash
./publish.sh "1.4.14" "Description de la version"
```

## 🎮 Menu Interactif

### Interface Utilisateur

Le script `push.sh` propose une interface utilisateur intuitive avec des menus colorés :

#### Menu Principal
```
╔══════════════════════════════════════════════════════════════╗
║                    🚀 JDR 4 MJ - Déploiement                ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  1. 🧪 Serveur de TEST (développement)                    ║
║  2. 🎭 Serveur de STAGING (validation)                    ║
║  3. 🏭 Serveur de PRODUCTION (publication)               ║
║                                                              ║
║  4. 📋 Afficher l'aide                                ║
║  5. ❌ Quitter                                          ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
```

#### Menu des Tests
```
╔══════════════════════════════════════════════════════════════╗
║                    🧪 Configuration des Tests                ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  1. ✅ Exécuter les tests avant déploiement              ║
║  2. ⚡ Déployer sans exécuter les tests                 ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
```

#### Confirmation
```
╔══════════════════════════════════════════════════════════════╗
║                    📋 Confirmation de Déploiement             ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  Serveur : 🧪 TEST                                    ║
║  Tests : ✅ Avec tests                                      ║
║  Message : Livraison automatique                                        ║
║  Timestamp : 2025-09-17 11:21:08                                    ║
║                                                              ║
║  Voulez-vous continuer ? (o/N) :                        ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
```

## 🎯 Serveurs Configurés

### Serveur de Test (`test`)
- **Chemin** : `/var/www/html/jdrmj_test`
- **URL** : `http://localhost/jdrmj_test`
- **Sauvegarde** : `/var/backups/jdrmj/`
- **Permissions** : `www-data:www-data`

### Serveur de Staging (`staging`)
- **Chemin** : `/var/www/html/jdrmj_staging`
- **URL** : `http://localhost/jdrmj_staging`
- **Sauvegarde** : `/var/backups/jdrmj/`
- **Permissions** : `www-data:www-data`

### Serveur de Production (`production`)
- **Chemin** : `/var/www/html/jdrmj`
- **URL** : `http://localhost/jdrmj`
- **Protection** : Nécessite approbation manuelle
- **Script** : Utiliser `publish.sh` uniquement

## 📁 Fichiers Inclus/Exclus

### ✅ Fichiers Inclus
- **PHP** : `*.php`
- **Configuration** : `*.htaccess`, `*.ini`, `*.env`
- **Styles** : `*.css`, `*.js`
- **Images** : `*.jpg`, `*.png`, `*.gif`, `*.svg`
- **Base de données** : `*.sql`
- **Documentation** : `*.md`, `*.txt`
- **Répertoires** : `config/`, `includes/`, `css/`, `images/`, `database/`

### ❌ Fichiers Exclus
- **Tests** : `tests/`, `testenv/`, `monenv/`
- **Développement** : `__pycache__/`, `.git/`, `.gitignore`
- **Scripts** : `publish.sh`, `push.sh`, `deploy.conf`

## 🧪 Tests Automatiques

Le script exécute automatiquement les tests avant la livraison :

```bash
# Tests de base (toujours exécutés)
../testenv/bin/python -m pytest functional/test_authentication.py functional/test_application_availability.py functional/test_fixtures.py -v
```

### Résultats des Tests
- ✅ **Tests réussis** : Livraison continue
- ⚠️ **Tests échoués** : Avertissement mais livraison continue
- ❌ **Tests critiques** : Arrêt de la livraison

## 🔧 Configuration

### Fichier `deploy.conf`
Configuration des serveurs et paramètres de déploiement.

```ini
[test]
path=/var/www/html/jdrmj_test
user=www-data
group=www-data
permissions=755
backup_enabled=true
```

### Variables d'Environnement
```bash
# Serveur par défaut
DEFAULT_SERVER="test"

# Message par défaut
DEFAULT_MESSAGE="Livraison automatique"
```

## 📊 Logs et Monitoring

### Fichiers de Log
- **Déploiement** : `deployment.log`
- **Sauvegardes** : `/var/backups/jdrmj/`
- **Tests** : `tests/reports/`

### Messages Colorés
- 🔵 **INFO** : Informations générales
- 🟢 **SUCCESS** : Opérations réussies
- 🟡 **WARNING** : Avertissements
- 🔴 **ERROR** : Erreurs critiques

## 🚨 Gestion d'Erreurs

### Arrêt Automatique
Le script s'arrête automatiquement en cas d'erreur critique :
- Prérequis manquants
- Erreurs de syntaxe
- Échecs de déploiement

### Nettoyage Automatique
Les fichiers temporaires sont automatiquement supprimés :
- Répertoires temporaires
- Fichiers de test
- Logs temporaires

## 🔄 Workflow de Déploiement

### 1. Développement
```bash
# Développement local
git checkout -b feature/nouvelle-fonctionnalite
# ... développement ...
git commit -m "Ajout nouvelle fonctionnalité"
```

### 2. Test
```bash
# Livraison sur serveur de test
./push.sh test "Test nouvelle fonctionnalité"
```

### 3. Staging
```bash
# Livraison sur serveur de staging
./push.sh staging "Validation staging"
```

### 4. Production
```bash
# Publication officielle
./publish.sh "1.4.15" "Nouvelle fonctionnalité validée"
```

## 🛠️ Dépannage

### Problèmes Courants

#### Permission Denied
```bash
# Vérifier les permissions
ls -la push.sh
chmod +x push.sh
```

#### Serveur Non Accessible
```bash
# Vérifier la configuration
cat deploy.conf
# Vérifier les permissions sudo
sudo -l
```

#### Tests Échouent
```bash
# Exécuter les tests manuellement
cd tests
../testenv/bin/python -m pytest functional/test_authentication.py -v
```

### Logs de Débogage
```bash
# Exécution avec logs détaillés
bash -x ./push.sh test "Debug message"
```

## 📞 Support

En cas de problème :
1. Vérifier les logs de déploiement
2. Consulter les sauvegardes
3. Tester manuellement les prérequis
4. Contacter l'équipe de développement

---

**🎲 Bon déploiement !** 🎲
