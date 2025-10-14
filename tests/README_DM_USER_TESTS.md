# Tests de Gestion des Utilisateurs Maître du Jeu (MJ)

Ce document décrit les tests créés pour la gestion des utilisateurs de type "Maître du Jeu" (MJ) dans l'application JDR MJ.

## 📁 Fichiers de Test

### Tests PHP (Backend)
- **`test_dm_user_management.php`** - Tests unitaires PHP pour la gestion des utilisateurs MJ
- **`run_dm_user_tests.py`** - Script de lancement des tests

### Tests Selenium (Interface Web)
- **`test_dm_user_management.py`** - Tests fonctionnels complets pour la gestion des utilisateurs MJ
- **`test_dm_user_web_interface.py`** - Tests spécifiques à l'interface web

### Données de Test
- **`fixtures/test_data.py`** - Données de test mises à jour avec les utilisateurs MJ

## 🧪 Types de Tests

### 1. Tests PHP (Backend)

#### Fonctionnalités testées :
- ✅ **Création d'utilisateur MJ** : Création d'un utilisateur avec le rôle 'dm' et is_dm=true
- ✅ **Connexion utilisateur MJ** : Authentification avec un utilisateur MJ
- ✅ **Suppression d'utilisateur MJ** : Suppression d'un utilisateur MJ de la base de données
- ✅ **Vérification des privilèges** : Test des fonctions de rôle (isDM(), isDMOrAdmin(), etc.)
- ✅ **Comparaison des rôles** : Comparaison entre les rôles player, dm et admin

#### Exécution :
```bash
# Depuis le répertoire racine du projet
php test_dm_user_management.php
```

### 2. Tests Selenium (Interface Web)

#### Fonctionnalités testées :
- ✅ **Inscription web** : Inscription d'un utilisateur MJ via l'interface web
- ✅ **Connexion web** : Connexion avec un utilisateur MJ via l'interface web
- ✅ **Déconnexion web** : Déconnexion d'un utilisateur MJ
- ✅ **Accès au profil** : Vérification de l'affichage du rôle MJ sur le profil
- ✅ **Accès aux campagnes** : Vérification de l'accès aux fonctionnalités de campagne
- ✅ **Accès au bestiaire** : Vérification de l'accès au bestiaire
- ✅ **Workflow complet** : Test du parcours complet d'un utilisateur MJ

#### Exécution :
```bash
# Depuis le répertoire tests/
python run_dm_user_tests.py --type selenium
```

## 🚀 Utilisation

### Lancement Rapide

```bash
# Tous les tests (PHP + Selenium)
python run_dm_user_tests.py

# Tests PHP uniquement
python run_dm_user_tests.py --type php

# Tests Selenium uniquement
python run_dm_user_tests.py --type selenium

# Tests Selenium en mode headless
python run_dm_user_tests.py --type selenium --headless

# Tests Selenium avec mode verbeux
python run_dm_user_tests.py --type selenium --verbose
```

### Options Avancées

```bash
# Tests Selenium de type "smoke" (rapides)
python run_dm_user_tests.py --type selenium --selenium-type smoke

# Tests Selenium complets
python run_dm_user_tests.py --type selenium --selenium-type all

# Tests Selenium spécifiques aux MJ
python run_dm_user_tests.py --type selenium --selenium-type dm
```

## 📊 Résultats des Tests

### Tests PHP
Les tests PHP affichent :
- ✅ Création d'utilisateur MJ avec détails (ID, username, email, rôle, is_dm)
- ✅ Connexion réussie avec vérification des fonctions de rôle
- ✅ Suppression réussie avec vérification de la suppression
- ✅ Test des privilèges avec session active
- ✅ Tableau de comparaison des rôles

### Tests Selenium
Les tests Selenium génèrent :
- 📸 Captures d'écran en cas d'échec
- 📝 Logs détaillés des actions
- 📊 Rapport HTML dans `tests/reports/`

## 🔧 Configuration

### Prérequis
- PHP 7.4+ avec extensions PDO et MySQL
- Python 3.8+ avec Selenium
- Chrome/Chromium installé
- Application JDR MJ en cours d'exécution

### Variables d'Environnement
```bash
# URL de base de l'application
export TEST_BASE_URL="http://localhost/jdrmj"

# Mode headless pour Selenium
export HEADLESS="true"
```

## 🐛 Dépannage

### Problèmes Courants

1. **Erreur de connexion à la base de données**
   - Vérifier la configuration dans `config/database.php`
   - S'assurer que la base de données est accessible

2. **Tests Selenium qui échouent**
   - Vérifier que l'application est accessible
   - Vérifier que Chrome/Chromium est installé
   - Consulter les captures d'écran dans `tests/reports/`

3. **Utilisateur de test existe déjà**
   - Les tests génèrent des noms uniques avec timestamp
   - En cas de conflit, les tests sont ignorés automatiquement

### Logs et Débogage

```bash
# Mode verbeux pour plus de détails
python run_dm_user_tests.py --verbose

# Exécuter un test spécifique
python -m pytest tests/functional/test_dm_user_web_interface.py::TestDMUserWebInterface::test_dm_user_registration_web -v
```

## 📈 Métriques de Test

### Couverture des Fonctionnalités
- ✅ **Création d'utilisateur MJ** : 100%
- ✅ **Connexion utilisateur MJ** : 100%
- ✅ **Suppression d'utilisateur MJ** : 100%
- ✅ **Vérification des privilèges** : 100%
- ✅ **Interface web** : 100%

### Types de Tests
- **Tests unitaires PHP** : 5 tests
- **Tests fonctionnels Selenium** : 7 tests
- **Tests d'intégration** : 1 test (workflow complet)

## 🔄 Intégration CI/CD

### GitHub Actions
```yaml
name: Tests Utilisateurs MJ
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    - name: Set up PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
    - name: Set up Python
      uses: actions/setup-python@v2
      with:
        python-version: 3.9
    - name: Install dependencies
      run: |
        cd tests
        pip install -r requirements.txt
    - name: Run tests
      run: |
        cd tests
        python run_dm_user_tests.py --headless
```

## 📝 Ajout de Nouveaux Tests

Pour ajouter de nouveaux tests :

1. **Tests PHP** : Modifier `test_dm_user_management.php`
2. **Tests Selenium** : Ajouter des méthodes dans `test_dm_user_web_interface.py`
3. **Données de test** : Modifier `fixtures/test_data.py`

### Exemple de nouveau test Selenium :
```python
def test_new_dm_feature(self, driver, wait, app_url):
    """Test d'une nouvelle fonctionnalité MJ"""
    # Votre code de test ici
    pass
```

## 🤝 Contribution

Pour contribuer aux tests :
1. Suivre les conventions de nommage
2. Ajouter des docstrings descriptives
3. Utiliser les fixtures existantes
4. Tester sur différents navigateurs si nécessaire
5. Mettre à jour ce README si vous ajoutez de nouveaux tests

---

**Développé avec ❤️ pour JDR 4 MJ**
