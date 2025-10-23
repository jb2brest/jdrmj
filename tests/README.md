# Tests Fonctionnels Selenium - JDR 4 MJ

Ce répertoire contient tous les tests fonctionnels automatisés pour l'application JDR 4 MJ utilisant Selenium WebDriver.

## 📁 Structure

```
tests/
├── functional/           # Tests fonctionnels
│   ├── test_authentication.py      # Tests d'authentification
│   ├── test_character_management.py # Tests de gestion des personnages
│   ├── test_campaign_management.py  # Tests de gestion des campagnes
│   └── test_bestiary.py            # Tests du bestiaire
├── fixtures/             # Données de test
│   └── test_data.py      # Fixtures et données de test
├── reports/              # Rapports de tests
├── conftest.py          # Configuration pytest
├── pytest.ini           # Configuration pytest
├── requirements.txt     # Dépendances Python
├── run_tests.py         # Script de lancement
└── README.md            # Ce fichier
```

## 🚀 Installation

### Prérequis

- Python 3.8+
- Chrome/Chromium installé
- Application JDR 4 MJ en cours d'exécution

### Installation des dépendances

```bash
# Depuis le répertoire tests/
pip install -r requirements.txt

# Ou utiliser le script d'installation
python run_tests.py --install
```

## 🧪 Exécution des tests

### Lancement rapide

```bash
# Tous les tests
python run_tests.py

# Tests en mode headless
python run_tests.py --headless

# Tests en parallèle
python run_tests.py --parallel
```

### Types de tests disponibles

```bash
# Tests de fumée (rapides et critiques)
python run_tests.py --type smoke

# Tests d'authentification
python run_tests.py --type authentication

# Tests de gestion des personnages
python run_tests.py --type character

# Tests de gestion des campagnes
python run_tests.py --type campaign

# Tests du bestiaire
python run_tests.py --type bestiary

# Tous les tests fonctionnels
python run_tests.py --type functional
```

### Options avancées

```bash
# URL personnalisée
python run_tests.py --url http://localhost:8080/jdrmj

# Mode verbeux
python run_tests.py --verbose

# Installation + exécution
python run_tests.py --install --type smoke
```

## 📊 Rapports

Les rapports de tests sont générés automatiquement dans `tests/reports/` :

- `report.html` : Rapport HTML détaillé
- `screenshot_*.png` : Captures d'écran des échecs

## 🔧 Configuration

### Variables d'environnement

```bash
# URL de base de l'application
export TEST_BASE_URL="http://localhost/jdrmj"

# Mode headless
export HEADLESS="true"
```

### Configuration pytest

Le fichier `pytest.ini` contient la configuration par défaut. Vous pouvez le modifier pour :

- Changer les marqueurs de tests
- Modifier les options de rapport
- Ajuster les paramètres de test

## 🎯 Tests disponibles

### Authentification (`test_authentication.py`)

- ✅ Inscription d'utilisateur
- ✅ Connexion utilisateur
- ✅ Déconnexion utilisateur
- ✅ Validation des identifiants invalides
- ✅ Validation du formulaire d'inscription
- ✅ Confirmation de mot de passe

### Gestion des personnages (`test_character_management.py`)

- ✅ Création de personnage
- ✅ Affichage de la liste des personnages
- ✅ Visualisation des détails d'un personnage
- ✅ Édition de personnage
- ✅ Suppression de personnage
- ✅ Gestion de l'équipement

### Gestion des campagnes (`test_campaign_management.py`)

- ✅ Création de campagne
- ✅ Affichage de la liste des campagnes
- ✅ Visualisation des détails d'une campagne
- ✅ Création de session
- ✅ Gestion des scènes
- ✅ Campagnes publiques
- ✅ Vue joueur des campagnes

### Bestiaire (`test_bestiary.py`)

- ✅ Affichage du bestiaire
- ✅ Recherche de monstres
- ✅ Visualisation des détails d'un monstre
- ✅ Création de monstre
- ✅ Collection de monstres personnels
- ✅ Gestion de l'équipement des monstres
- ✅ Recherche d'objets magiques
- ✅ Recherche de poisons
- ✅ Accès au grimoire

## 🐛 Dépannage

### Problèmes courants

1. **ChromeDriver non trouvé**
   ```bash
   # Le webdriver-manager s'occupe automatiquement du téléchargement
   pip install --upgrade webdriver-manager
   ```

2. **Tests qui échouent sur la connexion**
   ```bash
   # Vérifier que l'application est accessible
   curl http://localhost/jdrmj
   ```

3. **Tests lents**
   ```bash
   # Utiliser le mode headless
   python run_tests.py --headless
   ```

4. **Tests qui échouent sur les éléments**
   - Vérifier que l'application est à jour
   - Vérifier les sélecteurs CSS dans les tests
   - Consulter les captures d'écran dans `tests/reports/`

### Logs et débogage

```bash
# Mode verbeux pour plus de détails
python run_tests.py --verbose

# Exécuter un test spécifique
python -m pytest tests/functional/test_authentication.py::TestAuthentication::test_user_login -v
```

## 🔄 Intégration CI/CD

### GitHub Actions

```yaml
name: Tests Selenium
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
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
        python run_tests.py --headless --parallel
```

### Jenkins

```groovy
pipeline {
    agent any
    stages {
        stage('Test') {
            steps {
                sh 'cd tests && python run_tests.py --headless --parallel'
            }
        }
    }
    post {
        always {
            publishHTML([
                allowMissing: false,
                alwaysLinkToLastBuild: true,
                keepAll: true,
                reportDir: 'tests/reports',
                reportFiles: 'report.html',
                reportName: 'Test Report'
            ])
        }
    }
}
```

## 📝 Ajout de nouveaux tests

1. Créer un nouveau fichier dans `tests/functional/`
2. Importer les fixtures nécessaires depuis `conftest.py`
3. Utiliser les données de test depuis `fixtures/test_data.py`
4. Ajouter des marqueurs appropriés (`@pytest.mark.smoke`, etc.)

Exemple :

```python
import pytest
from selenium.webdriver.common.by import By

@pytest.mark.smoke
def test_new_feature(driver, wait, app_url, test_user):
    """Test d'une nouvelle fonctionnalité"""
    # Votre code de test ici
    pass
```

## 🤝 Contribution

Pour contribuer aux tests :

1. Suivre les conventions de nommage
2. Ajouter des docstrings descriptives
3. Utiliser les fixtures existantes quand possible
4. Tester sur différents navigateurs si nécessaire
5. Mettre à jour ce README si vous ajoutez de nouveaux types de tests

---

**Développé avec ❤️ pour JDR 4 MJ**
