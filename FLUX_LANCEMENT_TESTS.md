# 🔄 Flux de Lancement des Tests - JDR MJ

## 📋 Vue d'ensemble du processus

Voici comment les tests sont lancés après un appel à `launch_tests.sh` :

## 🚀 1. Script `launch_tests.sh` (Point d'entrée)

```
./launch_tests.sh -e staging -h
```

### Étapes du script :
1. **Parsing des arguments** : `-e staging`, `-h` (headless)
2. **Validation** : Vérification du répertoire `tests/` et de Python3
3. **Configuration des variables d'environnement** :
   - `HEADLESS=true` (si `-h`)
   - `TEST_ENVIRONMENT=staging` (si `-e staging`)
   - `KEEP_TEST_DATA=true` (si `-k`)
4. **Changement de répertoire** : `cd tests/`
5. **Lancement du menu** : `python3 advanced_test_menu.py`

## 🎯 2. Menu Avancé (`advanced_test_menu.py`)

### Initialisation :
- **Récupération de l'environnement** : `os.environ.get('TEST_ENVIRONMENT', 'local')`
- **Configuration du mode headless** : `os.environ.get('HEADLESS', 'false')`
- **Définition du PYTHONPATH** selon l'environnement :
  - `staging` → `/var/www/html/jdrmj_staging/tests`
  - `production` → `/var/www/html/jdrmj/tests`
  - `local` → `/home/jean/Documents/jdrmj/tests`

### Menu interactif :
```
1. 🗂️ Lancer par catégorie de tests
2. 🎯 Lancer un test spécifique
3. 🚀 Lancer tous les tests
4. 📊 Gérer les rapports JSON
5. ⚙️ Configuration
6. 📚 Aide
```

## ⚙️ 3. Construction de la Commande Pytest

### Variables d'environnement assemblées :
```bash
HEADLESS=true TEST_ENVIRONMENT=staging PYTHONPATH=/var/www/html/jdrmj_staging/tests
```

### Commande finale générée :
```bash
cd tests && HEADLESS=true TEST_ENVIRONMENT=staging PYTHONPATH=/var/www/html/jdrmj_staging/tests python3 -m pytest functional/classes/barbare/test_barbarian_class.py::TestBarbarianClass::test_barbarian_starting_equipment -v -p pytest_json_reporter
```

## 🧪 4. Exécution des Tests (`conftest.py`)

### Configuration des fixtures :
- **`app_url`** : `BASE_URL = os.getenv('TEST_BASE_URL', 'http://localhost/jdrmj')`
- **`driver`** : Configuration Selenium (Chrome/ChromeDriver)
- **`wait`** : WebDriverWait avec timeout
- **`test_user`** : Utilisateur de test avec nettoyage automatique

### Variables d'environnement utilisées :
- `TEST_BASE_URL` : URL de l'application (ex: `http://localhost/jdrmj_staging`)
- `HEADLESS` : Mode headless pour Selenium
- `TEST_ENVIRONMENT` : Environnement de test
- `KEEP_TEST_DATA` : Conservation des données de test

## 📊 5. Système de Rapports JSON

### `json_test_reporter.py` :
- **Capture des étapes** : Via `test_steps_capturer.py`
- **Génération de rapports** : Fichiers JSON dans `tests/reports/individual/`
- **Descriptions fonctionnelles** : Générées automatiquement via `get_functional_description()`

### Structure du rapport :
```json
{
  "test_info": {
    "name": "test_barbarian_starting_equipment",
    "file": "/var/www/html/jdrmj_staging/tests/functional/classes/barbare/test_barbarian_class.py",
    "category": "Classe - Barbare",
    "timestamp": "2025-10-18T13:12:10.685785",
    "duration_seconds": 10.0
  },
  "result": {
    "status": "PASSED",
    "success": true
  },
  "test_steps": [
    {
      "step_number": 1,
      "name": "Initialisation",
      "description": "Préparation de l'équipement de départ",
      "type": "info"
    }
  ]
}
```

## 🔄 6. Flux Complet Résumé

```
launch_tests.sh
    ↓
advanced_test_menu.py
    ↓
Construction commande pytest
    ↓
Exécution: python3 -m pytest [fichier] -v -p pytest_json_reporter
    ↓
conftest.py (fixtures)
    ↓
Test Selenium (fichier spécifique de l'environnement)
    ↓
json_test_reporter.py
    ↓
Rapport JSON généré
```

## 🎯 Points Clés

1. **Environnement dynamique** : Le PYTHONPATH change selon l'environnement
2. **Fichiers spécifiques** : Chaque environnement utilise ses propres fichiers Python
3. **Variables d'environnement** : Propagation depuis `launch_tests.sh` jusqu'aux tests
4. **Rapports automatiques** : Génération JSON avec étapes détaillées
5. **Nettoyage automatique** : Suppression des données de test (sauf si `-k`)

## 🔧 Exemple Concret

```bash
# Commande utilisateur
./launch_tests.sh -e staging -h

# Variables définies
export TEST_ENVIRONMENT=staging
export HEADLESS=true
export PYTHONPATH=/var/www/html/jdrmj_staging/tests

# Commande pytest exécutée
cd tests && HEADLESS=true TEST_ENVIRONMENT=staging PYTHONPATH=/var/www/html/jdrmj_staging/tests python3 -m pytest functional/classes/barbare/test_barbarian_class.py::TestBarbarianClass::test_barbarian_starting_equipment -v -p pytest_json_reporter

# Fichier utilisé
/var/www/html/jdrmj_staging/tests/functional/classes/barbare/test_barbarian_class.py

# URL testée
http://localhost/jdrmj_staging
```
