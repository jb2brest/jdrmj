# Menu Avancé des Tests - JDR 4 MJ

## 🎯 Vue d'ensemble

Le script `launch_tests.sh` a été modifié pour utiliser un **menu interactif avancé** qui permet de lancer les tests soit par **catégorie entière**, soit en **choisissant un test particulier**.

## 🚀 Utilisation

```bash
# Depuis le répertoire racine du projet
./launch_tests.sh
```

## 📋 Fonctionnalités

### 1. 🗂️ Lancer par catégorie de tests
- **8 catégories** organisées par fonctionnalité
- Chaque catégorie contient plusieurs fichiers de tests
- Exécution de tous les tests d'une catégorie en une fois

### 2. 🎯 Lancer un test spécifique
- **Sélection par fichier** : Choisir un fichier de test complet
- **Sélection par test** : Choisir un test précis dans un fichier
- **Navigation intuitive** : Menus hiérarchiques clairs

### 3. 🚀 Lancer tous les tests
- Exécution de l'ensemble de la suite de tests
- Confirmation requise (peut prendre plusieurs minutes)

### 4. 📊 Gérer les rapports JSON
- **Lister** les rapports existants
- **Statistiques** détaillées par catégorie
- **Nettoyage** des anciens rapports

### 5. ⚙️ Configuration
- Modification de l'URL de test
- Configuration des options d'environnement

### 6. 📚 Aide
- Documentation et guide d'utilisation

## 🗂️ Catégories de Tests

| Catégorie | Fichiers | Description |
|-----------|----------|-------------|
| 🔐 **Authentification** | 1/1 | Tests de connexion, déconnexion, inscription |
| 👤 **Personnages** | 4/4 | Tests de création et gestion des personnages |
| 🏰 **Campagnes** | 4/4 | Tests de gestion des campagnes |
| 🐉 **Bestiaire** | 1/1 | Tests du bestiaire et des monstres |
| 🎭 **Utilisateurs MJ** | 3/3 | Tests de gestion des utilisateurs MJ |
| 🌐 **Disponibilité** | 1/1 | Tests de disponibilité et d'accessibilité |
| 🔗 **Intégration** | 1/1 | Tests d'intégration complets |
| 🧪 **Fixtures** | 1/1 | Tests des données de test |

## 🎯 Tests Individuels Disponibles

### Authentification (6 tests)
- `test_user_registration`
- `test_user_login`
- `test_user_logout`
- `test_invalid_login_credentials`
- `test_registration_validation`
- `test_password_confirmation_validation`

### Personnages (13 tests)
- Tests de création complète
- Tests par classe (Guerrier, Magicien, Clerc)
- Tests par race (Humain, Elfe, Nain)
- Tests par background (Soldat, Sage, Criminel)
- Tests de validation des caractéristiques

### Campagnes (7 tests)
- Création de campagnes
- Gestion des sessions
- Gestion des scènes
- Vues publiques et joueurs

### Bestiaire (9 tests)
- Affichage du bestiaire
- Recherche de monstres
- Création de monstres
- Gestion des objets magiques
- Gestion des poisons

### Utilisateurs MJ (13 tests)
- Création et gestion des utilisateurs MJ
- Interface web complète
- Workflow complet DM

### Disponibilité (5 tests)
- Accessibilité des pages
- Responsivité
- Absence d'erreurs JavaScript

### Intégration (5 tests)
- Parcours utilisateur complet
- Workflow DM
- Workflow équipement
- Workflow bestiaire
- Workflow sorts

### Fixtures (8 tests)
- Validation des données de test
- Configuration des environnements

## 🔧 Architecture Technique

### Fichiers Modifiés
- `launch_tests.sh` : Script principal modifié
- `tests/advanced_test_menu.py` : Nouveau menu interactif

### Fichiers Créés
- `tests/advanced_test_menu.py` : Menu avancé complet
- `MENU_TESTS_AVANCE.md` : Cette documentation

### Compatibilité
- **Fallback** : Si le menu avancé n'est pas disponible, utilise le menu classique
- **Vérifications** : Contrôle de l'existence des fichiers et de Python
- **Gestion d'erreurs** : Gestion robuste des erreurs et interruptions

## 🎨 Interface Utilisateur

### Design
- **Emojis** pour une navigation intuitive
- **Menus hiérarchiques** clairs et organisés
- **Messages d'erreur** informatifs
- **Confirmations** pour les actions importantes

### Navigation
- **Numérotation** claire des options
- **Retour** facile aux menus précédents
- **Quitter** à tout moment (Ctrl+C ou option 0)

## 📊 Rapports JSON

### Génération Automatique
- Chaque test génère un rapport JSON individuel
- Stockage dans `tests/reports/individual/`
- Intégration avec l'interface admin

### Gestion des Rapports
- **Liste** des rapports existants
- **Statistiques** par catégorie et globale
- **Nettoyage** des anciens rapports

## 🚀 Avantages

1. **Flexibilité** : Choix entre catégories ou tests individuels
2. **Efficacité** : Exécution ciblée des tests nécessaires
3. **Organisation** : Tests groupés par fonctionnalité
4. **Transparence** : Visibilité complète des tests disponibles
5. **Maintenance** : Gestion facile des rapports et configuration

## 🔄 Migration

Le nouveau menu est **rétrocompatible** :
- Si `advanced_test_menu.py` n'existe pas → utilise le menu classique
- Si Python n'est pas installé → message d'erreur clair
- Si les fichiers de test sont manquants → indication claire

## 📝 Utilisation Recommandée

1. **Développement** : Utiliser les tests par catégorie
2. **Debug** : Utiliser les tests individuels
3. **CI/CD** : Utiliser "Lancer tous les tests"
4. **Maintenance** : Utiliser la gestion des rapports JSON

---

*Menu créé pour améliorer l'efficacité et la flexibilité des tests JDR 4 MJ*
