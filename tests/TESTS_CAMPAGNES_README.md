# 🏰 Tests de Gestion des Campagnes

Ce document décrit les tests automatisés pour la fonctionnalité de gestion des campagnes dans l'application JDR MJ.

## 📋 Vue d'ensemble

Les tests couvrent les fonctionnalités suivantes :
- **Création de campagnes** : Création, validation, affichage et gestion
- **Sessions de campagne** : Création, gestion et affichage des sessions
- **Membres de campagne** : Gestion des membres, invitations et permissions

## 🗂️ Structure des fichiers

```
tests/
├── functional/
│   ├── test_campaign_creation.py      # Tests de création des campagnes
│   ├── test_campaign_sessions.py      # Tests de gestion des sessions
│   └── test_campaign_members.py       # Tests de gestion des membres
├── cleanup_campaign_test_data.py      # Script de nettoyage des données de test
├── run_campaign_tests.sh              # Script de lancement des tests
└── TESTS_CAMPAGNES_README.md          # Ce fichier de documentation
```

## 🚀 Lancement des tests

### Option 1 : Script dédié (Recommandé)

```bash
# Depuis le répertoire racine du projet
./tests/run_campaign_tests.sh

# Avec options
./tests/run_campaign_tests.sh -h -k  # Mode headless + conserver les données
./tests/run_campaign_tests.sh --help # Aide
```

### Option 2 : Script principal avec menu

```bash
# Depuis le répertoire racine du projet
./launch_tests.sh

# Puis sélectionner les catégories :
# - 🏰 Gestion des Campagnes
#   - 🏗️ Création de Campagnes
#   - 📅 Sessions de Campagne
#   - 👥 Membres de Campagne
```

### Option 3 : Pytest direct

```bash
cd tests
python3 -m pytest functional/test_campaign_creation.py -v
python3 -m pytest functional/test_campaign_sessions.py -v
python3 -m pytest functional/test_campaign_members.py -v
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

### Tests de Création de Campagnes (`test_campaign_creation.py`)

1. **test_create_campaign_success** : Création d'une campagne avec succès
2. **test_create_campaign_empty_title** : Validation du titre requis
3. **test_create_campaign_short_title** : Validation de la longueur minimale du titre
4. **test_create_campaign_private** : Création d'une campagne privée
5. **test_view_campaign_details** : Affichage des détails d'une campagne
6. **test_campaign_list_display** : Affichage de la liste des campagnes
7. **test_campaign_invite_code_display** : Affichage du code d'invitation
8. **test_campaign_different_game_systems** : Création avec différents systèmes de jeu

### Tests de Sessions de Campagne (`test_campaign_sessions.py`)

1. **test_create_campaign_session_success** : Création d'une session avec succès
2. **test_create_campaign_session_empty_title** : Validation du titre requis
3. **test_view_campaign_session_details** : Affichage des détails d'une session
4. **test_campaign_session_list_display** : Affichage de la liste des sessions
5. **test_campaign_session_notes** : Ajout de notes à une session

### Tests de Membres de Campagne (`test_campaign_members.py`)

1. **test_join_campaign_with_invite_code** : Rejoindre une campagne avec un code d'invitation
2. **test_join_campaign_invalid_invite_code** : Validation des codes d'invitation invalides
3. **test_view_campaign_members** : Affichage des membres d'une campagne
4. **test_remove_campaign_member** : Suppression d'un membre de campagne
5. **test_campaign_member_permissions** : Test des permissions des membres

## 🧹 Gestion des données de test

### Nettoyage automatique

Par défaut, toutes les données de test sont automatiquement supprimées après l'exécution des tests. Cela inclut :
- Utilisateurs de test créés
- Campagnes créées
- Sessions créées
- Membres ajoutés
- Codes d'invitation générés

### Conservation des données

Pour conserver les données de test (utile pour le débogage) :

```bash
# Avec le script dédié
./tests/run_campaign_tests.sh -k

# Avec le script principal
./launch_tests.sh -k

# Avec pytest direct
KEEP_TEST_DATA=true python3 -m pytest functional/test_campaign_creation.py -v
```

### Nettoyage manuel

```bash
# Nettoyer toutes les données de test de campagne
cd tests
python3 cleanup_campaign_test_data.py

# Nettoyer les données d'un utilisateur spécifique
python3 cleanup_campaign_test_data.py --user test_user_1234567890

# Aide du script de nettoyage
python3 cleanup_campaign_test_data.py --help
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

4. **Problèmes de codes d'invitation**
   - Vérifier que la génération de codes d'invitation fonctionne
   - S'assurer que les codes sont uniques

### Logs et débogage

```bash
# Mode verbeux
python3 -m pytest functional/test_campaign_creation.py -v -s

# Avec logs détaillés
python3 -m pytest functional/test_campaign_creation.py -v --log-cli-level=DEBUG

# Conserver les données pour inspection
KEEP_TEST_DATA=true python3 -m pytest functional/test_campaign_creation.py -v
```

## 🔄 Intégration continue

Ces tests peuvent être intégrés dans un pipeline CI/CD :

```yaml
# Exemple pour GitHub Actions
- name: Run Campaign Tests
  run: |
    ./tests/run_campaign_tests.sh -h
  env:
    TEST_BASE_URL: ${{ secrets.TEST_BASE_URL }}
    KEEP_TEST_DATA: false
```

## 📝 Notes importantes

- Les tests utilisent des utilisateurs temporaires avec des noms uniques basés sur des timestamps
- Tous les éléments créés sont automatiquement nettoyés sauf si l'option `-k` est utilisée
- Les tests sont conçus pour être indépendants et peuvent être exécutés dans n'importe quel ordre
- Les données de test utilisent des préfixes spécifiques pour faciliter l'identification et le nettoyage
- Les codes d'invitation sont générés automatiquement et testés pour leur unicité

## 🎯 Fonctionnalités testées

### Création de campagnes
- ✅ Création avec succès
- ✅ Validation des champs requis
- ✅ Gestion des campagnes publiques/privées
- ✅ Affichage des détails
- ✅ Affichage des listes
- ✅ Génération de codes d'invitation
- ✅ Support de différents systèmes de jeu

### Sessions de campagne
- ✅ Création avec succès
- ✅ Validation des champs requis
- ✅ Affichage des détails
- ✅ Affichage des listes
- ✅ Ajout de notes

### Membres de campagne
- ✅ Rejoindre avec un code d'invitation
- ✅ Validation des codes invalides
- ✅ Affichage des membres
- ✅ Suppression de membres
- ✅ Gestion des permissions

## 🤝 Contribution

Pour ajouter de nouveaux tests :

1. Créer un nouveau fichier de test dans `tests/functional/`
2. Suivre la structure existante avec les méthodes utilitaires
3. Ajouter la nouvelle catégorie dans `advanced_test_menu.py`
4. Mettre à jour ce README si nécessaire
5. Tester le nettoyage automatique des données
6. S'assurer que les tests sont indépendants et reproductibles

## 🔗 Liens utiles

- [Documentation des tests de mondes](./TESTS_MONDES_README.md)
- [Configuration des tests](./conftest.py)
- [Script de lancement principal](../launch_tests.sh)
- [Menu avancé des tests](./advanced_test_menu.py)
