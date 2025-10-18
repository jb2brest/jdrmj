# Système de Capture d'Étapes de Tests

## Vue d'ensemble

Le système de capture d'étapes permet de capturer et d'afficher les détails détaillés des tests dans l'interface web `admin_versions.php`. Chaque test peut maintenant enregistrer ses étapes d'exécution, qui sont ensuite visibles en cliquant sur le nom du test dans l'onglet "Tests".

## Fonctionnalités

### 1. Capture d'Étapes
- **Actions** : Clics, saisies, navigations
- **Assertions** : Vérifications et validations
- **Informations** : Messages informatifs
- **Erreurs** : Gestion des erreurs
- **Avertissements** : Messages d'avertissement
- **Captures d'écran** : Screenshots automatiques

### 2. Interface Web
- **Modal détaillée** : Affichage des étapes dans une modal
- **Timeline visuelle** : Représentation chronologique des étapes
- **Export** : Possibilité d'exporter le rapport en HTML
- **Navigation intuitive** : Clic sur le nom du test pour voir les détails

## Utilisation

### Dans les Tests

```python
from test_steps_capturer import (
    add_action, add_assertion, add_info, add_error, add_warning,
    step_context
)

def test_example(driver):
    """Test avec capture d'étapes"""
    
    # Étape simple
    add_action("Navigation", "Accès à la page de connexion")
    driver.get("http://localhost/jdrmj/login.php")
    
    # Étape avec contexte (gestion automatique de la durée)
    with step_context("Connexion", "Processus de connexion"):
        username_field = driver.find_element("name", "username")
        username_field.send_keys("test_user")
        add_action("Saisie nom d'utilisateur", "test_user saisi")
        
        # Assertion
        add_assertion("Champ présent", "Le champ username est présent", 
                     expected="présent", actual="présent", passed=True)
    
    # Information
    add_info("Test terminé", "Test de connexion terminé avec succès")
```

### Types d'Étapes

#### Actions
```python
add_action("Nom de l'action", "Description de l'action", {"détail": "valeur"})
```

#### Assertions
```python
add_assertion("Nom de l'assertion", "Description", 
             expected="valeur attendue", actual="valeur réelle", passed=True)
```

#### Informations
```python
add_info("Nom de l'info", "Description de l'information")
```

#### Erreurs
```python
add_error("Nom de l'erreur", "Description de l'erreur", {"code": "ERROR_001"})
```

#### Avertissements
```python
add_warning("Nom de l'avertissement", "Description de l'avertissement")
```

### Contexte d'Étape

```python
with step_context("Nom de l'étape", "Description", "action"):
    # Code de l'étape
    # La durée est calculée automatiquement
    pass
```

## Structure des Données

### Rapport JSON

```json
{
  "test_info": {
    "name": "nom_du_test",
    "file": "chemin/vers/test.py",
    "category": "Catégorie",
    "priority": "Priorité",
    "timestamp": "2025-10-18T10:32:25.772560",
    "date": "2025-10-18",
    "time": "10:32:25",
    "duration_seconds": 2.0
  },
  "result": {
    "status": "PASSED",
    "success": true,
    "error_message": "",
    "stack_trace": ""
  },
  "test_steps": [
    {
      "step_number": 1,
      "name": "Nom de l'étape",
      "description": "Description de l'étape",
      "type": "action",
      "timestamp": 1697623945.772560,
      "datetime": "2025-10-18T10:32:25.772560",
      "duration_seconds": 0.1,
      "details": {},
      "screenshot_path": null
    }
  ]
}
```

### Types d'Étapes

- **action** : Action utilisateur (clic, saisie, navigation)
- **assertion** : Vérification/validation
- **info** : Information générale
- **error** : Erreur rencontrée
- **warning** : Avertissement
- **screenshot** : Capture d'écran

## Interface Web

### Accès aux Détails

1. Aller sur `http://localhost/jdrmj_staging/admin_versions.php`
2. Cliquer sur l'onglet "Tests"
3. Cliquer sur le nom d'un test (souligné et cliquable)
4. Une modal s'ouvre avec les détails du test

### Affichage des Étapes

- **Timeline visuelle** : Représentation chronologique
- **Icônes colorées** : Différentes couleurs selon le type d'étape
- **Durées** : Temps d'exécution de chaque étape
- **Détails** : Informations supplémentaires si disponibles

### Export

- Bouton "Exporter" dans la modal
- Génère un fichier HTML avec tous les détails
- Inclut les styles Bootstrap pour un affichage optimal

## Configuration

### Intégration Automatique

Le système est automatiquement intégré via `conftest.py` :

```python
@pytest.hookimpl(tryfirst=True)
def pytest_runtest_setup(item):
    """Démarre la capture des étapes au début du test"""
    if TEST_STEPS_AVAILABLE:
        start_test(item.name, item.function.__doc__)

@pytest.hookimpl(tryfirst=True)
def pytest_runtest_teardown(item, nextitem):
    """Termine la capture des étapes à la fin du test"""
    if TEST_STEPS_AVAILABLE:
        end_test("completed")
```

### Création de Rapports

Les rapports JSON sont automatiquement créés avec les étapes capturées :

```python
@pytest.hookimpl(tryfirst=True)
def pytest_runtest_makereport(item, call):
    """Export des étapes dans le rapport JSON"""
    if TEST_STEPS_AVAILABLE and call.when == "call":
        test_steps = export_test_steps()
        # Création du rapport JSON avec les étapes
```

## Exemples

### Test Simple

```python
def test_login_simple(driver):
    """Test de connexion simple"""
    
    add_action("Navigation", "Accès à la page de connexion")
    driver.get("http://localhost/jdrmj/login.php")
    
    add_action("Saisie identifiants", "Saisie des identifiants de test")
    driver.find_element("name", "username").send_keys("test_user")
    driver.find_element("name", "password").send_keys("test_password")
    
    add_action("Connexion", "Clic sur le bouton de connexion")
    driver.find_element("type", "submit").click()
    
    add_assertion("Redirection", "Vérification de la redirection", 
                 expected="dashboard", actual=driver.current_url, 
                 passed="dashboard" in driver.current_url)
```

### Test avec Gestion d'Erreur

```python
def test_error_handling(driver):
    """Test de gestion d'erreur"""
    
    with step_context("Test page inexistante", "Tentative d'accès à une page inexistante"):
        try:
            driver.get("http://localhost/jdrmj/page_inexistante.php")
            add_action("Accès page", "Tentative d'accès à une page inexistante")
            
            if "404" in driver.title:
                add_assertion("Page 404", "Page d'erreur 404 affichée", 
                             expected="404", actual="404", passed=True)
            else:
                add_warning("Pas de 404", "Aucune page d'erreur 404 détectée")
                
        except Exception as e:
            add_error("Erreur navigation", str(e), {"exception": str(e)})
```

## Avantages

1. **Traçabilité** : Suivi détaillé de l'exécution des tests
2. **Debugging** : Identification rapide des problèmes
3. **Documentation** : Auto-documentation des tests
4. **Interface intuitive** : Visualisation claire dans l'interface web
5. **Export** : Possibilité d'exporter les rapports
6. **Intégration** : Fonctionne automatiquement avec pytest

## Fichiers Impliqués

- `tests/test_steps_capturer.py` : Capteur d'étapes
- `tests/json_test_reporter.py` : Générateur de rapports JSON
- `tests/conftest.py` : Intégration pytest
- `admin_versions.php` : Interface web
- `tests/functional/test_example_with_steps.py` : Exemple d'utilisation

## Démonstration

Pour tester le système :

```bash
cd /home/jean/Documents/jdrmj/tests
python3 test_steps_demo.py
```

Puis consulter `http://localhost/jdrmj_staging/admin_versions.php` et cliquer sur l'onglet "Tests" pour voir les détails du test de démonstration.
