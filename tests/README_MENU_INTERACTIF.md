# 🎲 Menu Interactif des Tests - JDR 4 MJ

Ce document explique comment utiliser le menu interactif pour lancer les tests de l'application JDR MJ.

## 🚀 Lancement Rapide

### Depuis le répertoire racine du projet :
```bash
./launch_tests.sh
```

### Depuis le répertoire tests/ :
```bash
./launch_menu.sh
```

### Directement avec Python :
```bash
python3 tests/test_menu.py
```

## 📋 Menu Principal

Le menu interactif propose les catégories suivantes :

### 🔐 AUTHENTIFICATION ET UTILISATEURS
- **1. Tests d'authentification** - Connexion/déconnexion des utilisateurs
- **2. Tests de gestion des utilisateurs MJ** - Création, connexion, suppression des MJ
- **3. Tests d'inscription d'utilisateurs** - Inscription de nouveaux utilisateurs

### 👤 GESTION DES PERSONNAGES
- **4. Tests de création de personnages** - Création de nouveaux personnages
- **5. Tests de gestion des personnages** - Édition, visualisation des personnages
- **6. Tests d'équipement des personnages** - Gestion de l'équipement

### 🏰 GESTION DES CAMPAGNES
- **7. Tests de gestion des campagnes** - Gestion générale des campagnes
- **8. Tests de création de campagnes** - Création de nouvelles campagnes
- **9. Tests de sessions de campagne** - Gestion des sessions

### 🐉 BESTIAIRE ET MONSTRES
- **10. Tests du bestiaire** - Affichage et recherche dans le bestiaire
- **11. Tests de création de monstres** - Création de nouveaux monstres
- **12. Tests de gestion des monstres** - Gestion des monstres existants

### 🧪 TESTS SPÉCIALISÉS
- **13. Tests de fumée** - Tests rapides et critiques
- **14. Tests d'intégration complets** - Tous les tests
- **15. Tests de diagnostic** - Tests de diagnostic du système

### ⚙️ OPTIONS ET CONFIGURATION
- **16. Installation des dépendances** - Installation automatique des dépendances
- **17. Configuration de l'environnement** - Configuration des paramètres
- **18. Aide et documentation** - Aide et liens vers la documentation

## 🎯 Utilisation

1. **Lancez le menu** avec une des commandes ci-dessus
2. **Choisissez une option** en tapant le numéro correspondant
3. **Suivez les instructions** affichées à l'écran
4. **Consultez les résultats** des tests

## 🔧 Fonctionnalités du Menu

### Navigation Intuitive
- Interface claire et colorée
- Navigation par numéros
- Retour automatique au menu principal

### Gestion des Erreurs
- Gestion des interruptions (Ctrl+C)
- Messages d'erreur clairs
- Pause entre les tests pour consultation

### Configuration Flexible
- Configuration de l'URL de test
- Mode headless automatique
- Installation automatique des dépendances

## 📊 Types de Tests Disponibles

### Tests Selenium (Interface Web)
- Tests fonctionnels complets
- Simulation d'utilisateur réel
- Captures d'écran en cas d'échec
- Rapports HTML détaillés

### Tests PHP (Backend)
- Tests unitaires de la logique métier
- Tests de la classe User
- Tests de gestion des rôles
- Tests de base de données

### Tests Spécialisés
- Tests de fumée (rapides)
- Tests d'intégration
- Tests de diagnostic
- Tests de performance

## 🌐 Configuration

### URL de Test
Par défaut : `http://localhost/jdrmj`
Modifiable via l'option 17 du menu ou la variable d'environnement `TEST_BASE_URL`

### Variables d'Environnement
```bash
export TEST_BASE_URL="http://localhost:8080/jdrmj"
export HEADLESS="true"
export PARALLEL="true"
export VERBOSE="true"
```

## 📁 Structure des Fichiers

```
tests/
├── test_menu.py              # Menu interactif principal
├── launch_menu.sh            # Script de lancement
├── run_tests.py              # Lanceur de tests Selenium
├── run_dm_user_tests.py      # Tests utilisateurs MJ
├── functional/               # Tests fonctionnels
├── fixtures/                 # Données de test
├── reports/                  # Rapports de tests
└── README_MENU_INTERACTIF.md # Ce fichier
```

## 🐛 Dépannage

### Problèmes Courants

1. **"Python 3 n'est pas installé"**
   ```bash
   sudo apt install python3 python3-pip  # Ubuntu/Debian
   brew install python3                   # macOS
   ```

2. **"Permission denied"**
   ```bash
   chmod +x tests/test_menu.py
   chmod +x tests/launch_menu.sh
   chmod +x launch_tests.sh
   ```

3. **"Module not found"**
   ```bash
   cd tests
   python3 -m pip install -r requirements.txt
   ```

4. **"ChromeDriver not found"**
   - Le menu propose l'installation automatique
   - Ou installez manuellement ChromeDriver

### Logs et Débogage

- Les tests affichent des messages détaillés
- Les rapports sont sauvegardés dans `tests/reports/`
- Les captures d'écran sont dans `tests/screenshots/`

## 🚀 Exemples d'Utilisation

### Test Rapide
```bash
./launch_tests.sh
# Choisir option 13 (Tests de fumée)
```

### Test Complet
```bash
./launch_tests.sh
# Choisir option 14 (Tests d'intégration complets)
```

### Test des Utilisateurs MJ
```bash
./launch_tests.sh
# Choisir option 2 (Tests de gestion des utilisateurs MJ)
```

### Installation des Dépendances
```bash
./launch_tests.sh
# Choisir option 16 (Installation des dépendances)
```

## 🔄 Intégration CI/CD

Le menu peut être utilisé dans des scripts d'automatisation :

```bash
# Test automatique en mode headless
echo "13" | ./launch_tests.sh

# Test avec configuration personnalisée
TEST_BASE_URL="http://test.example.com" ./launch_tests.sh
```

## 📝 Personnalisation

### Ajout de Nouveaux Tests
1. Créer le fichier de test dans `tests/functional/`
2. Ajouter l'option dans `test_menu.py`
3. Implémenter la méthode `handle_choice()`

### Modification du Menu
Éditez `tests/test_menu.py` pour :
- Ajouter de nouvelles options
- Modifier l'affichage
- Changer les commandes exécutées

## 🤝 Contribution

Pour contribuer au menu interactif :
1. Suivre les conventions de nommage
2. Ajouter des docstrings
3. Tester sur différents systèmes
4. Mettre à jour cette documentation

---

**🎲 Menu Interactif des Tests - Développé avec ❤️ pour JDR 4 MJ**
