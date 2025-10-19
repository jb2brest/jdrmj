# 📁 Récupération des Fichiers de Test (.py) depuis launch_tests.sh

## 🔄 Processus de Découverte des Fichiers

Voici comment les fichiers de test (.py) sont récupérés et organisés :

## 🚀 1. Point de Départ : `launch_tests.sh`

```bash
./launch_tests.sh -e staging -h
```

Le script :
1. **Change de répertoire** : `cd tests/`
2. **Lance le menu** : `python3 advanced_test_menu.py`

## 🎯 2. Initialisation : `AdvancedTestMenu.__init__()`

```python
def __init__(self):
    self.base_dir = Path(__file__).parent          # /home/jean/Documents/jdrmj/tests
    self.parent_dir = self.base_dir.parent         # /home/jean/Documents/jdrmj
    self.functional_dir = self.base_dir / "functional"  # /home/jean/Documents/jdrmj/tests/functional
    
    # Récupération de l'environnement
    self.test_environment = os.environ.get('TEST_ENVIRONMENT', 'local')
```

## 📂 3. Découverte Dynamique des Fichiers

### Méthode `print_individual_test_menu()` :

```python
# Récupération de TOUS les fichiers de test
test_files = list(self.functional_dir.glob("test_*.py"))
test_files.sort()
```

**Résultat** : Liste de tous les fichiers `test_*.py` dans le répertoire `functional/`

### Exemple de fichiers découverts :
```
/home/jean/Documents/jdrmj/tests/functional/
├── test_authentication.py
├── test_barbarian_class.py
├── test_bard_class.py
├── test_campaign_creation.py
├── test_character_creation_steps.py
├── test_dm_user_management.py
├── test_world_creation.py
└── ...
```

## 🗂️ 4. Organisation par Catégories

### Catégories prédéfinies dans `test_categories` :

```python
self.test_categories = {
    "authentification": {
        "name": "🔐 Authentification et Utilisateurs",
        "files": ["test_authentication.py"],
        "description": "Tests de connexion, déconnexion, inscription"
    },
    "classes": {
        "name": "⚔️ Tests des Classes de Personnage",
        "subcategories": {
            "barbare": {
                "name": "🪓 Classe Barbare",
                "files": ["test_barbarian_class.py"]
            },
            "barde": {
                "name": "🎭 Classe Barde", 
                "files": ["test_bard_class.py"]
            }
        }
    }
}
```

## 🔍 5. Extraction des Noms de Tests

### Méthode `extract_test_names(file_path)` :

```python
def extract_test_names(self, file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    test_names = []
    lines = content.split('\n')
    
    for line in lines:
        line = line.strip()
        # Chercher les définitions de tests
        if line.startswith('def test_') and '(' in line:
            test_name = line.split('(')[0].replace('def ', '')
            test_names.append(test_name)
    
    return test_names
```

**Exemple** : Pour `test_barbarian_class.py` :
```python
def test_barbarian_character_creation(self, driver, wait, app_url):
def test_barbarian_race_selection(self, driver, wait, app_url):
def test_barbarian_starting_equipment(self, driver, wait, app_url):
```

**Résultat** :
```
['test_barbarian_character_creation', 'test_barbarian_race_selection', 'test_barbarian_starting_equipment']
```

## 🎯 6. Affichage du Menu

### Structure du menu généré :

```
📁 ⚔️ Tests des Classes de Personnage:
   📂 🪓 Classe Barbare:
      📄 test_barbarian_class.py:
         • test_barbarian_character_creation
         • test_barbarian_race_selection
         • test_barbarian_starting_equipment
         • test_barbarian_character_view
         • test_barbarian_rage_mechanism
         • ...
```

## ⚙️ 7. Sélection et Exécution

### Quand l'utilisateur sélectionne un test :

1. **Fichier sélectionné** : `test_barbarian_class.py`
2. **Test sélectionné** : `test_barbarian_starting_equipment`
3. **Chemin complet** : `functional/classes/barbare/test_barbarian_class.py::TestBarbarianClass::test_barbarian_starting_equipment`

### Commande pytest générée :

```bash
cd tests && HEADLESS=true TEST_ENVIRONMENT=staging PYTHONPATH=/var/www/html/jdrmj_staging/tests python3 -m pytest functional/classes/barbare/test_barbarian_class.py::TestBarbarianClass::test_barbarian_starting_equipment -v -p pytest_json_reporter
```

## 🔄 8. Flux Complet de Récupération

```
launch_tests.sh
    ↓
cd tests/
    ↓
python3 advanced_test_menu.py
    ↓
AdvancedTestMenu.__init__()
    ↓
self.functional_dir = Path(__file__).parent / "functional"
    ↓
test_files = list(self.functional_dir.glob("test_*.py"))
    ↓
Pour chaque fichier : extract_test_names(file_path)
    ↓
Organisation par catégories prédéfinies
    ↓
Affichage du menu interactif
    ↓
Sélection utilisateur
    ↓
Construction de la commande pytest
    ↓
Exécution du test
```

## 🎯 Points Clés

1. **Découverte automatique** : `glob("test_*.py")` trouve tous les fichiers de test
2. **Parsing des fonctions** : Extraction des noms de tests via regex `def test_`
3. **Organisation hiérarchique** : Catégories et sous-catégories prédéfinies
4. **Environnement dynamique** : Le PYTHONPATH change selon l'environnement
5. **Chemin relatif** : Les fichiers sont référencés par `functional/fichier.py`

## 📍 Exemple Concret

```python
# Découverte
test_files = list(self.functional_dir.glob("test_*.py"))
# Résultat : [Path('test_barbarian_class.py'), Path('test_bard_class.py'), ...]

# Extraction des tests
test_names = self.extract_test_names(Path('test_barbarian_class.py'))
# Résultat : ['test_barbarian_character_creation', 'test_barbarian_starting_equipment', ...]

# Commande finale
cmd = f"python3 -m pytest functional/classes/barbare/test_barbarian_class.py::TestBarbarianClass::test_barbarian_starting_equipment"
```

Le système découvre automatiquement tous les fichiers de test et extrait dynamiquement les noms des fonctions de test pour créer un menu interactif complet.

