# 🚀 Démarrage Rapide - Tests Selenium

## ✅ Installation terminée !

Votre système de tests Selenium est maintenant configuré et prêt à être utilisé.

## 🎯 Commandes essentielles

### **Démarrage rapide (recommandé)**
```bash
cd tests
./start_tests.sh
```

### **Test simple pour vérifier l'installation**
```bash
cd tests
../testenv/bin/python test_simple.py
```

### **Tests de fumée (rapides)**
```bash
cd tests
../testenv/bin/python run_tests.py --type smoke --headless
```

### **Tests d'authentification**
```bash
cd tests
../testenv/bin/python run_tests.py --type authentication --headless
```

### **Tous les tests**
```bash
cd tests
../testenv/bin/python run_tests.py --type all --headless
```

## 📁 Structure créée

```
tests/
├── functional/                    # Tests fonctionnels
│   ├── test_authentication.py    # Tests d'authentification
│   ├── test_character_management.py # Tests de gestion des personnages
│   ├── test_campaign_management.py  # Tests de gestion des campagnes
│   ├── test_bestiary.py          # Tests du bestiaire
│   └── test_integration.py       # Tests d'intégration
├── fixtures/
│   └── test_data.py              # Données de test
├── reports/                      # Rapports de tests
├── conftest.py                   # Configuration pytest/Selenium
├── pytest.ini                   # Configuration pytest
├── requirements.txt              # Dépendances Python
├── run_tests.py                 # Script principal
├── test_simple.py               # Test simple de vérification
├── start_tests.sh               # Script de démarrage interactif
├── activate_testenv.sh          # Script d'activation
├── install.sh                   # Script d'installation
├── Makefile                     # Commandes make
└── README.md                    # Documentation complète
```

## 🔧 Configuration

- **Environnement virtuel** : `../testenv/`
- **ChromeDriver** : `/usr/local/bin/chromedriver`
- **URL par défaut** : `http://localhost/jdrmj`
- **Mode headless** : Activé par défaut

## 📊 Rapports

Les rapports sont générés dans `tests/reports/` :
- `report.html` : Rapport HTML détaillé
- `screenshot_*.png` : Captures d'écran des échecs

## 🌐 Configuration de l'URL

Pour tester une autre URL :
```bash
export TEST_BASE_URL="http://votre-serveur/jdrmj"
../testenv/bin/python run_tests.py --type smoke
```

## 🐛 Dépannage

### Problème : "python command not found"
**Solution** : Utilisez `python3` au lieu de `python`

### Problème : "ChromeDriver not found"
**Solution** : 
```bash
wget https://storage.googleapis.com/chrome-for-testing-public/140.0.7339.82/linux64/chromedriver-linux64.zip
unzip chromedriver-linux64.zip
sudo mv chromedriver-linux64/chromedriver /usr/local/bin/
sudo chmod +x /usr/local/bin/chromedriver
```

### Problème : "Application not accessible"
**Solution** : Vérifiez que votre application JDR 4 MJ est en cours d'exécution

## 📝 Prochaines étapes

1. **Tester l'installation** : `./start_tests.sh`
2. **Adapter les sélecteurs** selon votre interface
3. **Ajouter de nouveaux tests** dans `functional/`
4. **Intégrer dans votre workflow** de développement

## 🎲 Types de tests disponibles

- **smoke** : Tests de fumée (rapides et critiques)
- **authentication** : Tests d'authentification
- **character** : Tests de gestion des personnages
- **campaign** : Tests de gestion des campagnes
- **bestiary** : Tests du bestiaire
- **functional** : Tous les tests fonctionnels
- **all** : Tous les tests

## 🚀 Exemples d'utilisation

```bash
# Test simple
../testenv/bin/python test_simple.py

# Tests de fumée
../testenv/bin/python run_tests.py --type smoke --headless

# Tests avec interface graphique
../testenv/bin/python run_tests.py --type authentication

# Tests en parallèle
../testenv/bin/python run_tests.py --type all --parallel

# Tests avec URL personnalisée
TEST_BASE_URL="http://localhost:8080/jdrmj" ../testenv/bin/python run_tests.py --type smoke
```

---

**🎉 Votre système de tests Selenium est prêt !**

Pour plus de détails, consultez `README.md`
