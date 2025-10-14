# 📊 Système de Rapports JSON - JDR 4 MJ

## 🎯 Vue d'ensemble

Le système de rapports JSON remplace l'ancien système CSV par un système plus granulaire où **chaque test génère automatiquement son propre rapport JSON**. Cela permet un suivi détaillé et une analyse fine des résultats de tests.

## 🏗️ Architecture

### Structure des répertoires
```
tests/
├── reports/
│   ├── individual/          # Rapports individuels par test
│   │   ├── test_name1.json
│   │   ├── test_name2.json
│   │   └── ...
│   └── aggregated/          # Rapports agrégés
│       ├── session_*.json   # Rapports de session
│       └── summary_*.json   # Rapports de résumé
├── json_test_reporter.py    # Générateur de rapports
├── pytest_json_reporter.py # Plugin pytest
└── demo_json_reports.py     # Démonstration
```

## 📄 Format des Rapports

### Rapport Individuel (nom_du_test.json)
```json
{
  "test_info": {
    "name": "test_user_login",
    "file": "functional/test_authentication.py",
    "category": "Authentification",
    "priority": "Basse",
    "timestamp": "2025-10-14T22:03:11.304459",
    "date": "2025-10-14",
    "time": "22:03:11",
    "timezone": "",
    "duration_seconds": 0.1
  },
  "result": {
    "status": "PASSED",
    "success": true,
    "error_message": "",
    "error_line": "",
    "stack_trace": ""
  },
  "execution": {
    "start_time": "2025-10-14T22:03:11.204220",
    "end_time": "2025-10-14T22:03:11.304406",
    "start_date": "2025-10-14",
    "start_time_formatted": "22:03:11",
    "end_date": "2025-10-14",
    "end_time_formatted": "22:03:11",
    "duration_formatted": "100ms"
  },
  "environment": {
    "python_version": "3.12.3",
    "platform": "linux",
    "working_directory": "/path/to/tests"
  },
  "version_info": {
    "detection_timestamp": "2025-10-14T22:03:11.304459",
    "application": {
      "version": "2.9.2"
    },
    "system": {
      "os_name": "Ubuntu 24.04.3 LTS",
      "os_version": "24.04.3 LTS (Noble Numbat)",
      "architecture": "x86_64",
      "python_version": "3.12.3"
    },
    "web_server": {
      "server_status": "online",
      "http_status": 200,
      "server_header": "Apache/2.4.58 (Ubuntu)"
    },
    "git": {
      "git_version": "2.43.0",
      "commit_hash": "f1923cb6",
      "branch": "Classes",
      "last_commit_date": "2025-10-14 20:48:59 +0200"
    },
    "database": {
      "mysql": "10.11.13"
    },
    "web_server_software": {
      "php": "8.3.6",
      "apache": "2.4.58"
    }
  },
  "additional_data": {}
}
```

### Rapport de Session
```json
{
  "session_info": {
    "name": "pytest_session_1234567890",
    "timestamp": "2025-10-14T22:03:23.194796",
    "duration_seconds": 30.0
  },
  "summary": {
    "total_tests": 5,
    "passed_tests": 3,
    "failed_tests": 2,
    "success_rate": 60.0
  },
  "categories": {
    "Authentification": {
      "total": 2,
      "passed": 1,
      "failed": 1
    }
  }
}
```

## 🚀 Utilisation

### 1. Exécution Automatique
Les rapports JSON sont générés automatiquement lors de l'exécution des tests :

```bash
# Avec rapports JSON (par défaut)
python3 run_tests.py --type smoke

# Sans rapports JSON
python3 run_tests.py --type smoke --no-json
```

### 2. Menu Interactif
```bash
./launch_tests.sh
# Choisir l'option 18: "Générer des rapports JSON des tests"
```

### 3. Utilisation Directe
```bash
# Créer un rapport manuel
python3 json_test_reporter.py --action create --test-name "mon_test" --status PASSED

# Lister les rapports
python3 json_test_reporter.py --action list

# Générer un résumé
python3 json_test_reporter.py --action summary
```

## 🔧 Intégration avec pytest

### Plugin Automatique
Le plugin `pytest_json_reporter.py` s'active automatiquement et génère un rapport pour chaque test :

```python
# Dans conftest.py ou pytest.ini
pytest_plugins = ["pytest_json_reporter"]
```

### Décorateur pour Tests Personnalisés
```python
from pytest_json_reporter import json_report

@json_report(test_name="test_login_custom", category="Auth", priority="High")
def test_user_login():
    # Votre test ici
    pass
```

## 📊 Analyse des Rapports

### 1. Rapports Individuels
Chaque test génère un fichier JSON avec :
- **Informations du test** : nom, fichier, catégorie, priorité
- **Résultat** : statut, message d'erreur, ligne d'erreur
- **Exécution** : durée, timestamps
- **Environnement** : version Python, plateforme

### 2. Rapports de Session
Agrégation de tous les tests d'une session avec :
- Statistiques globales
- Répartition par catégorie
- Liste des rapports individuels

### 3. Rapports de Résumé
Analyse des tests récents (24h) avec :
- Statistiques globales
- Tendances par catégorie
- Tests les plus récents

## 🎨 Catégories et Priorités

### Catégories Automatiques
- `Authentification` : Tests de connexion/déconnexion
- `Gestion_Personnages` : Tests de personnages
- `Gestion_Campagnes` : Tests de campagnes
- `Bestiaire` : Tests du bestiaire
- `Utilisateurs_MJ` : Tests d'utilisateurs MJ
- `Tests_Fumee` : Tests de fumée
- `Integration` : Tests d'intégration
- `Autres` : Tests non catégorisés

### Priorités Automatiques
- `Haute` : Erreurs de timeout, base de données
- `Moyenne` : Erreurs Selenium, assertions
- `Basse` : Tests réussis
- `Inconnue` : Statuts non reconnus

## 🛠️ API du Générateur

### JSONTestReporter
```python
from json_test_reporter import JSONTestReporter

reporter = JSONTestReporter()

# Créer un rapport individuel
report_path = reporter.create_test_report(
    test_name="mon_test",
    test_file="test_file.py",
    status="PASSED",
    start_time=time.time(),
    end_time=time.time(),
    error_message="",
    category="Authentification"
)

# Créer un rapport de session
session_report = reporter.create_test_session_report(
    session_name="ma_session",
    test_reports=[report_path],
    start_time=start_time,
    end_time=end_time
)

# Générer un résumé
summary_report = reporter.generate_summary_report("Mon résumé")
```

## 📈 Avantages du Système JSON

### ✅ Avantages
1. **Granularité** : Un rapport par test
2. **Traçabilité** : Historique complet des exécutions
3. **Flexibilité** : Format JSON facilement analysable
4. **Automatisation** : Génération automatique
5. **Intégration** : Compatible avec pytest
6. **Extensibilité** : Facile d'ajouter de nouveaux champs
7. **📅 Suivi Temporel** : Date et heure détaillées pour chaque test
8. **📱 Détection de Version** : Versions automatiques de tous les logiciels
9. **🔍 Traçabilité Complète** : Informations Git, serveur, base de données

### 🔄 Migration depuis CSV
- Les anciens scripts CSV sont remplacés
- Les rapports JSON sont plus détaillés
- Meilleure intégration avec les outils modernes
- Analyse plus fine des résultats

## 🎯 Exemples d'Utilisation

### Démonstration Complète
```bash
# Démonstration générale
python3 demo_json_reports.py

# Démonstration des nouvelles fonctionnalités (date/heure et version)
python3 demo_version_date.py
```

### Tests avec Rapports
```bash
# Tests DM avec rapports JSON
python3 run_dm_user_tests.py --type selenium

# Tests généraux avec rapports
python3 run_tests.py --type smoke --verbose
```

### Analyse des Résultats
```bash
# Via le menu interactif
./launch_tests.sh
# Option 18 → Option 2 (Lister les rapports)
# Option 18 → Option 4 (Statistiques)
```

## 🔍 Dépannage

### Problèmes Courants
1. **Rapports non générés** : Vérifier que `json_test_reporter.py` est disponible
2. **Plugin pytest non actif** : Vérifier l'import dans `conftest.py`
3. **Permissions** : S'assurer que le répertoire `reports/` est accessible en écriture

### Logs et Debug
```bash
# Mode verbeux pour voir la génération des rapports
python3 run_tests.py --type smoke --verbose
```

## 📚 Ressources

- **Script principal** : `json_test_reporter.py`
- **Plugin pytest** : `pytest_json_reporter.py`
- **Détecteur de version** : `version_detector.py`
- **Démonstration générale** : `demo_json_reports.py`
- **Démonstration date/version** : `demo_version_date.py`
- **Menu interactif** : Option 18 du menu principal

## 🆕 Nouvelles Fonctionnalités

### 📅 Suivi Temporel Détaillé
- **Date et heure** de chaque test
- **Timestamps** de début et fin d'exécution
- **Durée** formatée (ms, s, m)
- **Fuseau horaire** local

### 📱 Détection Automatique de Version
- **Version de l'application** (depuis VERSION, composer.json, etc.)
- **Versions des logiciels** : PHP, MySQL, Apache, Python
- **Informations système** : OS, architecture
- **Informations Git** : commit, branche, dernière modification
- **Statut du serveur web** : connectivité, headers HTTP

### 🔍 Traçabilité Complète
Chaque rapport contient maintenant :
- Quand le test a été exécuté (date/heure précise)
- Quelle version du logiciel était testée
- Dans quel environnement (OS, versions des dépendances)
- Quel commit Git était actif
- Si le serveur web était accessible

---

**🎉 Le système de rapports JSON est maintenant opérationnel avec suivi temporel et détection de version !**
