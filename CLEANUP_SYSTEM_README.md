# 🧹 Système de Nettoyage Automatique des Tests

Ce document explique le système de nettoyage automatique des données de test pour maintenir la base de données propre.

## 🎯 Problème Résolu

Les tests créent de nombreux éléments (utilisateurs, mondes, pays, régions, lieux, personnages, etc.) qui s'accumulent dans la base de données au fil du temps. Ce système permet de :

- ✅ **Nettoyer automatiquement** après chaque test
- ✅ **Nettoyer manuellement** les données anciennes
- ✅ **Éviter la surcharge** de la base de données
- ✅ **Maintenir la performance** de l'application

## 🔧 Solutions Implémentées

### 1. Nettoyage Automatique (Fixtures Pytest)

Les fixtures `test_user` et `test_admin` dans `tests/conftest.py` nettoient automatiquement :

- **Utilisateurs de test** avec noms uniques (timestamp)
- **Toutes les données liées** (personnages, campagnes, mondes, etc.)
- **Éléments géographiques** (pays, régions, lieux)
- **Données stockées** dans `test_user['created_worlds']`

### 2. Scripts de Nettoyage Manuel

#### Script Shell Principal
```bash
# Nettoyage standard (> 1 jour)
./cleanup_tests.sh

# Nettoyage avec paramètres
./cleanup_tests.sh --days=7
./cleanup_tests.sh --all
./cleanup_tests.sh --dry-run

# Utiliser le script Python
./cleanup_tests.sh --python --dry-run
```

#### Script Python Direct
```bash
# Mode dry-run
python3 tests/cleanup_test_users.py --dry-run

# Nettoyage standard
python3 tests/cleanup_test_users.py --days=1

# Nettoyage complet
python3 tests/cleanup_test_users.py --all
```

### 3. Nettoyage Automatique (Cron)

#### Configuration Cron
```bash
# Nettoyage quotidien à 2h du matin
0 2 * * * /home/jean/Documents/jdrmj/cleanup_auto.sh

# Nettoyage hebdomadaire le dimanche à 3h
0 3 * * 0 /home/jean/Documents/jdrmj/cleanup_auto.sh --days=7
```

#### Script Automatique
```bash
# Exécuter le nettoyage automatique
./cleanup_auto.sh
```

## 📋 Données Nettoyées

### Utilisateurs de Test
- `test_%` - Tous les utilisateurs commençant par "test_"
- `test_user_%` - Utilisateurs de test génériques
- `test_dm_%` - Maîtres de jeu de test
- `test_player_%` - Joueurs de test
- `test_admin_%` - Administrateurs de test
- `%@test.com` - Emails de test
- `%@example.com` - Emails d'exemple

### Données Supprimées par Utilisateur

#### Données Directes
- ✅ **Utilisateur** (`users` table)
- ✅ **Personnages** (`characters` table)
- ✅ **Campagnes** (`campaigns` table)
- ✅ **Jets de dés** (`dice_rolls` table)

#### Données Géographiques
- ✅ **Lieux** (`places` table)
- ✅ **Régions** (`regions` table)
- ✅ **Pays** (`countries` table)
- ✅ **Mondes** (`worlds` table)

#### Données Créées
- ✅ **Monstres** (`monsters` table)
- ✅ **Objets magiques** (`magical_items` table)
- ✅ **Poisons** (`poisons` table)

#### Données des Personnages
- ✅ **Sorts appris** (`character_spells` table)
- ✅ **Équipements** (`character_equipment` table)
- ✅ **Capacités** (`character_capabilities` table)
- ✅ **Langues** (`character_languages` table)
- ✅ **Sorts de classe** (`class_spells` table)
- ✅ **Emplacements de sorts** (`spell_slots` table)

## 🚀 Utilisation Recommandée

### Pour le Développement Quotidien
```bash
# Nettoyage rapide des données anciennes
./cleanup_tests.sh --days=1
```

### Pour le Nettoyage Complet
```bash
# Voir d'abord ce qui serait supprimé
./cleanup_tests.sh --python --dry-run

# Supprimer toutes les données de test
./cleanup_tests.sh --all
```

### Pour la Production
```bash
# Configurer le nettoyage automatique
crontab -e

# Ajouter cette ligne pour un nettoyage quotidien
0 2 * * * /home/jean/Documents/jdrmj/cleanup_auto.sh
```

## 🔍 Vérification

### Vérifier les Données Restantes
```bash
# Mode dry-run pour voir ce qui serait supprimé
./cleanup_tests.sh --python --dry-run
```

### Logs de Nettoyage
```bash
# Voir les logs de nettoyage automatique
tail -f /home/jean/Documents/jdrmj/cleanup.log
```

## ⚠️ Notes Importantes

1. **Sauvegarde** : Toujours faire une sauvegarde avant un nettoyage complet
2. **Tests en cours** : Ne pas exécuter pendant que des tests sont en cours
3. **Données légitimes** : Vérifier que les données à supprimer sont bien des données de test
4. **Performance** : Le nettoyage peut prendre du temps sur de grandes bases de données

## 🛠️ Dépannage

### Erreurs de Tables Manquantes
Le système gère automatiquement les tables qui n'existent pas.

### Erreurs de Permissions
```bash
# Vérifier les permissions
ls -la cleanup_tests.sh
chmod +x cleanup_tests.sh
```

### Logs d'Erreur
```bash
# Voir les erreurs de nettoyage
grep "ERROR" /home/jean/Documents/jdrmj/cleanup.log
```
