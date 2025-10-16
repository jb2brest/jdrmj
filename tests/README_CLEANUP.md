# 🧹 Nettoyage des Utilisateurs de Test

Ce document explique comment nettoyer automatiquement les utilisateurs de test créés lors des tests pour éviter de surcharger la base de données.

## 🎯 Problème Résolu

Les tests créent de nombreux utilisateurs de test qui s'accumulent dans la base de données au fil du temps. Ce système de nettoyage automatique et manuel permet de :

- ✅ **Nettoyer automatiquement** après chaque test
- ✅ **Nettoyer manuellement** les utilisateurs anciens
- ✅ **Éviter la surcharge** de la base de données
- ✅ **Maintenir la performance** de l'application

## 🔧 Solutions Implémentées

### 1. Nettoyage Automatique (Fixtures Pytest)

Les fixtures `test_user` et `test_admin` dans `conftest.py` ont été modifiées pour :

- **Générer des noms uniques** avec timestamp
- **Nettoyer automatiquement** après chaque test
- **Supprimer toutes les données liées** (personnages, campagnes, etc.)

```python
@pytest.fixture(scope="function")
def test_user():
    """Utilisateur de test par défaut avec nettoyage automatique"""
    timestamp = str(int(time.time()))
    user_data = {
        'username': f'test_user_{timestamp}',
        'email': f'test_{timestamp}@example.com',
        'password': 'TestPassword123!',
        'is_dm': True
    }
    
    yield user_data
    
    # Nettoyage automatique après le test
    cleanup_test_user_from_db(user_data)
```

### 2. Hooks Pytest de Nettoyage

- **`pytest_sessionfinish`** : Nettoyage final de tous les utilisateurs créés
- **`pytest_runtest_teardown`** : Nettoyage après chaque test

### 3. Scripts de Nettoyage Manuel

#### Script Shell (Recommandé)
```bash
# Voir ce qui serait supprimé (mode dry-run)
./cleanup_tests.sh --dry-run

# Supprimer les utilisateurs > 1 jour
./cleanup_tests.sh

# Supprimer les utilisateurs > 7 jours
./cleanup_tests.sh --days=7

# Supprimer TOUS les utilisateurs de test
./cleanup_tests.sh --all

# Utiliser le script Python
./cleanup_tests.sh --python --dry-run
```

#### Script PHP Direct
```bash
# Mode dry-run
php cleanup_test_data.php --dry-run

# Supprimer les utilisateurs > 1 jour
php cleanup_test_data.php

# Supprimer les utilisateurs > 7 jours
php cleanup_test_data.php --days=7

# Supprimer TOUS les utilisateurs de test
php cleanup_test_data.php --all
```

#### Script Python Interactif
```bash
cd tests
python3 cleanup_test_users.py
```

## 📋 Utilisateurs de Test Identifiés

Le système identifie automatiquement les utilisateurs de test par ces patterns :

- `test_%` - Tous les utilisateurs commençant par "test_"
- `test_user_%` - Utilisateurs de test génériques
- `test_dm_%` - Maîtres de jeu de test
- `test_player_%` - Joueurs de test
- `test_admin_%` - Administrateurs de test
- `test_delete_%` - Utilisateurs créés pour les tests de suppression
- `%@test.com` - Emails de test
- `%@example.com` - Emails d'exemple

## 🗑️ Données Supprimées

Pour chaque utilisateur de test, le système supprime :

### Données Directes
- ✅ **Utilisateur** (`users` table)
- ✅ **Personnages** (`characters` table)
- ✅ **Campagnes** (`campaigns` table)
- ✅ **Sessions** (`campaign_sessions` table)
- ✅ **Jets de dés** (`dice_rolls` table)
- ✅ **Tokens de scène** (`scene_tokens` table)
- ✅ **Objets de lieu** (`place_objects` table)

### Données Créées
- ✅ **Monstres** (`monsters` table)
- ✅ **Objets magiques** (`magical_items` table)
- ✅ **Poisons** (`poisons` table)

### Données des Personnages
- ✅ **Sorts appris** (`character_spells` table)
- ✅ **Équipements** (`character_equipment` table)
- ✅ **Capacités** (`character_capabilities` table)
- ✅ **Langues** (`character_languages` table)
- ✅ **Sorts de classe** (`class_spells` table)
- ✅ **Emplacements de sorts** (`spell_slots` table)

## 🚀 Utilisation Recommandée

### Pour le Développement Quotidien
```bash
# Nettoyage rapide des utilisateurs anciens
./cleanup_tests.sh --days=1
```

### Pour le Nettoyage Complet
```bash
# Voir d'abord ce qui serait supprimé
./cleanup_tests.sh --dry-run

# Puis supprimer si tout semble correct
./cleanup_tests.sh --all
```

### Pour les Tests Automatisés
Le nettoyage automatique fonctionne sans intervention :
```bash
# Les tests nettoient automatiquement après chaque exécution
pytest tests/functional/
```

## ⚠️ Précautions

1. **Sauvegarde** : Toujours faire une sauvegarde avant un nettoyage massif
2. **Vérification** : Utiliser `--dry-run` avant de supprimer
3. **Confirmation** : Le script demande confirmation avant suppression
4. **Transactions** : Toutes les suppressions sont dans des transactions

## 🔍 Vérification

Pour vérifier qu'il n'y a plus d'utilisateurs de test :

```bash
# Afficher tous les utilisateurs de test restants
./cleanup_tests.sh --dry-run
```

## 📊 Statistiques

Le système affiche des statistiques détaillées :
- Nombre d'utilisateurs trouvés
- Nombre d'utilisateurs supprimés
- Détail des données supprimées par table
- Temps d'exécution

## 🛠️ Dépannage

### Erreur de Connexion à la Base de Données
```bash
# Vérifier la configuration
cat config/database.test.php
```

### Erreur de Permissions
```bash
# Rendre le script exécutable
chmod +x cleanup_tests.sh
```

### Dépendances Python Manquantes
```bash
# Installer pymysql
pip3 install pymysql
```

## 📝 Logs

Les scripts génèrent des logs détaillés :
- ✅ Utilisateurs supprimés avec succès
- ⚠️ Erreurs de suppression
- 📊 Statistiques finales
- 🧹 Nettoyage automatique

## 🎉 Résultat

Avec ce système, la base de données reste propre et performante, même avec de nombreux tests automatisés !
