# 🚀 Script de Déploiement `push.sh`

## 📋 Description

Le script `push.sh` est un outil de déploiement automatisé pour l'application JDR 4 MJ. Il propose une interface utilisateur intuitive avec des menus colorés et des options de configuration flexibles.

## 🎮 Modes d'Utilisation

### 1. Mode Interactif (Recommandé)

Lancez le script sans paramètres pour accéder au menu interactif :

```bash
./push.sh
```

**Étapes du menu interactif :**
1. **Choix du serveur** : test, staging, ou production
2. **Configuration des tests** : avec ou sans tests
3. **Saisie du message** : personnalisé ou par défaut
4. **Confirmation** : validation des paramètres

### 2. Mode Ligne de Commande

Pour un usage automatisé ou en script :

```bash
# Déploiement basique
./push.sh test "Message de déploiement"

# Déploiement sans tests
./push.sh staging "Message" --no-tests

# Aide
./push.sh --help
```

## 🎯 Serveurs Disponibles

| Serveur | Description | Chemin | URL |
|---------|-------------|--------|-----|
| `test` | Développement | `/var/www/html/jdrmj_test` | `http://localhost/jdrmj_test` |
| `staging` | Validation | `/var/www/html/jdrmj_staging` | `http://localhost/jdrmj_staging` |
| `production` | Publication | `/var/www/html/jdrmj` | `http://localhost/jdrmj` |

## 🧪 Options de Tests

- **Avec tests** : Exécute les tests Selenium avant déploiement
- **Sans tests** : Déploie directement sans exécuter les tests

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

## 🔧 Fonctionnalités

### Interface Utilisateur
- ✅ **Menu interactif** coloré et intuitif
- ✅ **Navigation claire** avec numérotation
- ✅ **Messages informatifs** avec couleurs
- ✅ **Confirmation** avant déploiement

### Déploiement
- ✅ **Vérification des prérequis** (git, rsync, fichiers)
- ✅ **Sauvegarde automatique** de la version précédente
- ✅ **Livraison sélective** des fichiers
- ✅ **Gestion des permissions** (www-data, chmod)
- ✅ **Nettoyage automatique** des fichiers temporaires

### Tests
- ✅ **Exécution optionnelle** des tests Selenium
- ✅ **Tests de base** (authentification, disponibilité, fixtures)
- ✅ **Gestion des erreurs** de tests

### Logs et Monitoring
- ✅ **Logs colorés** (INFO, SUCCESS, WARNING, ERROR)
- ✅ **Fichier de déploiement** (`deployment.log`)
- ✅ **Commit Git** automatique
- ✅ **Sauvegardes** dans `/var/backups/jdrmj/`

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

## 📊 Exemples d'Utilisation

### Développement Quotidien
```bash
# Menu interactif pour test rapide
./push.sh
# Choisir : 1 (test), 1 (avec tests), Entrée (message par défaut), o (confirmer)
```

### Validation Staging
```bash
# Déploiement sur staging avec tests
./push.sh staging "Validation nouvelle fonctionnalité"
```

### Déploiement Rapide
```bash
# Déploiement sans tests pour correction urgente
./push.sh test "Correction bug critique" --no-tests
```

### Production
```bash
# Utiliser publish.sh pour la production
./publish.sh "1.4.15" "Nouvelle fonctionnalité validée"
```

## 🛠️ Dépannage

### Problèmes Courants

#### Permission Denied
```bash
chmod +x push.sh
```

#### Serveur Non Accessible
```bash
# Vérifier les permissions sudo
sudo -l
# Vérifier la configuration
cat deploy.conf
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

## 🔄 Workflow Recommandé

### 1. Développement
```bash
# Test local avec menu interactif
./push.sh
# Choisir : test + avec tests
```

### 2. Validation
```bash
# Staging avec tests
./push.sh staging "Validation fonctionnalité"
```

### 3. Production
```bash
# Publication officielle
./publish.sh "1.4.15" "Fonctionnalité validée"
```

## 📞 Support

En cas de problème :
1. Vérifier les logs de déploiement
2. Consulter les sauvegardes
3. Tester manuellement les prérequis
4. Utiliser `./push.sh --help` pour l'aide

---

**🎲 Bon déploiement !** 🎲
