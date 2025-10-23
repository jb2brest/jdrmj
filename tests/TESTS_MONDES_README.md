# 🌍 Tests de Création des Mondes, Pays, Régions et Lieux

Ce document décrit les tests automatisés pour la fonctionnalité de création et gestion des mondes, pays, régions et lieux dans l'application JDR MJ.

## 📋 Vue d'ensemble

Les tests couvrent les fonctionnalités suivantes :
- **Mondes** : Création, validation, affichage et gestion
- **Pays** : Création dans un monde, validation, affichage et gestion
- **Régions** : Création dans un pays, validation, affichage et gestion
- **Lieux** : Création dans une région, validation, affichage et gestion

## 🗂️ Structure des fichiers

```
tests/
├── functional/
│   ├── test_world_creation.py      # Tests de création des mondes
│   ├── test_country_creation.py    # Tests de création des pays
│   ├── test_region_creation.py     # Tests de création des régions
│   └── test_place_creation.py      # Tests de création des lieux
├── cleanup_world_test_data.py      # Script de nettoyage des données de test
├── run_world_creation_tests.sh     # Script de lancement des tests
└── TESTS_MONDES_README.md          # Ce fichier de documentation
```

## 🚀 Lancement des tests

### Option 1 : Script dédié (Recommandé)

```bash
# Depuis le répertoire racine du projet
./tests/run_world_creation_tests.sh

# Avec options
./tests/run_world_creation_tests.sh -h -k  # Mode headless + conserver les données
./tests/run_world_creation_tests.sh --help # Aide
```

### Option 2 : Script principal avec menu

```bash
# Depuis le répertoire racine du projet
./launch_tests.sh

# Puis sélectionner les catégories :
# - 🌍 Tests des Mondes
# - 🏰 Tests des Pays
# - 🗺️ Tests des Régions
# - 📍 Tests des Lieux
```

### Option 3 : Pytest direct

```bash
cd tests
python3 -m pytest functional/test_world_creation.py -v
python3 -m pytest functional/test_country_creation.py -v
python3 -m pytest functional/test_region_creation.py -v
python3 -m pytest functional/test_place_creation.py -v
```

## ⚙️ Options de configuration

### Variables d'environnement

- `HEADLESS=true` : Mode headless (sans interface graphique)
- `TEST_ENVIRONMENT=local|staging|production` : Environnement de test
- `KEEP_TEST_DATA=true` : Conserver les données de test après exécution
- `TEST_BASE_URL=http://localhost/jdrmj` : URL de base de l'application

### Options des scripts

- `-h, --headless` : Activer le mode headless
- `-e, --env ENV` : Spécifier l'environnement
- `-k, --keep` : Conserver les données de test
- `--help` : Afficher l'aide

## 🧪 Types de tests

### Tests des Mondes (`test_world_creation.py`)

1. **test_create_world_success** : Création d'un monde avec succès
2. **test_create_world_empty_name** : Validation du nom requis
3. **test_create_world_duplicate_name** : Validation des noms uniques
4. **test_view_world_details** : Affichage des détails d'un monde
5. **test_world_list_display** : Affichage de la liste des mondes

### Tests des Pays (`test_country_creation.py`)

1. **test_create_country_success** : Création d'un pays avec succès
2. **test_create_country_empty_name** : Validation du nom requis
3. **test_create_country_duplicate_name** : Validation des noms uniques par monde
4. **test_view_country_details** : Affichage des détails d'un pays
5. **test_country_list_display** : Affichage de la liste des pays

### Tests des Régions (`test_region_creation.py`)

1. **test_create_region_success** : Création d'une région avec succès
2. **test_create_region_empty_name** : Validation du nom requis
3. **test_create_region_duplicate_name** : Validation des noms uniques par pays
4. **test_view_region_details** : Affichage des détails d'une région
5. **test_region_list_display** : Affichage de la liste des régions

### Tests des Lieux (`test_place_creation.py`)

1. **test_create_place_success** : Création d'un lieu avec succès
2. **test_create_place_empty_title** : Validation du titre requis
3. **test_create_place_duplicate_title** : Validation des titres uniques par région
4. **test_view_place_details** : Affichage des détails d'un lieu
5. **test_place_list_display** : Affichage de la liste des lieux

## 🧹 Gestion des données de test

### Nettoyage automatique

Par défaut, toutes les données de test sont automatiquement supprimées après l'exécution des tests. Cela inclut :
- Utilisateurs de test créés
- Mondes créés
- Pays créés
- Régions créées
- Lieux créés

### Conservation des données

Pour conserver les données de test (utile pour le débogage) :

```bash
# Avec le script dédié
./tests/run_world_creation_tests.sh -k

# Avec le script principal
./launch_tests.sh -k

# Avec pytest direct
KEEP_TEST_DATA=true python3 -m pytest functional/test_world_creation.py -v
```

### Nettoyage manuel

```bash
# Nettoyer toutes les données de test
cd tests
python3 cleanup_world_test_data.py

# Nettoyer les données d'un utilisateur spécifique
python3 cleanup_world_test_data.py --user test_user_1234567890

# Aide du script de nettoyage
python3 cleanup_world_test_data.py --help
```

## 🔧 Configuration requise

### Prérequis

- Python 3.7+
- Selenium WebDriver
- Chrome/Chromium installé
- Base de données MySQL/MariaDB accessible
- Application JDR MJ déployée et accessible

### Dépendances Python

```bash
pip install pytest selenium pymysql webdriver-manager
```

### Configuration de la base de données

Le fichier `config/database.test.php` doit être configuré avec les bonnes informations de connexion à la base de données de test.

## 📊 Rapports de test

Les tests génèrent des rapports JSON détaillés dans le répertoire `tests/reports/` :
- Rapports individuels par test
- Screenshots en cas d'échec
- Statistiques de performance

## 🐛 Dépannage

### Problèmes courants

1. **Erreur de connexion à la base de données**
   - Vérifier la configuration dans `config/database.test.php`
   - S'assurer que la base de données est accessible

2. **Erreur de WebDriver**
   - Installer Chrome/Chromium
   - Vérifier que ChromeDriver est dans le PATH

3. **Tests qui échouent**
   - Vérifier que l'application est accessible à l'URL configurée
   - Consulter les logs dans `tests/reports/`

### Logs et débogage

```bash
# Mode verbeux
python3 -m pytest functional/test_world_creation.py -v -s

# Avec logs détaillés
python3 -m pytest functional/test_world_creation.py -v --log-cli-level=DEBUG

# Conserver les données pour inspection
KEEP_TEST_DATA=true python3 -m pytest functional/test_world_creation.py -v
```

## 🔄 Intégration continue

Ces tests peuvent être intégrés dans un pipeline CI/CD :

```yaml
# Exemple pour GitHub Actions
- name: Run World Creation Tests
  run: |
    ./tests/run_world_creation_tests.sh -h
  env:
    TEST_BASE_URL: ${{ secrets.TEST_BASE_URL }}
    KEEP_TEST_DATA: false
```

## 📝 Notes importantes

- Les tests utilisent des utilisateurs temporaires avec des noms uniques basés sur des timestamps
- Tous les éléments créés sont automatiquement nettoyés sauf si l'option `-k` est utilisée
- Les tests sont conçus pour être indépendants et peuvent être exécutés dans n'importe quel ordre
- Les données de test utilisent des préfixes spécifiques pour faciliter l'identification et le nettoyage

## 🤝 Contribution

Pour ajouter de nouveaux tests :

1. Créer un nouveau fichier de test dans `tests/functional/`
2. Suivre la structure existante avec les méthodes utilitaires
3. Ajouter la nouvelle catégorie dans `advanced_test_menu.py`
4. Mettre à jour ce README si nécessaire
5. Tester le nettoyage automatique des données
