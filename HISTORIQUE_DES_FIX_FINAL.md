# Correction de l'historique des jets de dés - Résumé final

## Problème initial
L'historique des jets de dés n'était pas récupéré dans `view_place.php`.

## Corrections effectuées

### 1. Correction de la classe DiceRoll
**Fichier**: `/home/jean/Documents/jdrmj/classes/DiceRoll.php`

**Problème 1**: Colonne `place_id` inexistante
```sql
-- AVANT (ligne 115-120)
SELECT dr.*, u.username, p.title as place_title
FROM dice_rolls dr 
JOIN users u ON dr.user_id = u.id 
LEFT JOIN places p ON dr.place_id = p.id  -- ❌ colonne inexistante
WHERE dr.campaign_id = ?

-- APRÈS
SELECT dr.*, u.username
FROM dice_rolls dr 
JOIN users u ON dr.user_id = u.id 
WHERE dr.campaign_id = ?  -- ✅ correction
```

**Problème 2**: Colonne `created_at` au lieu de `rolled_at`
```sql
-- AVANT (ligne 128)
ORDER BY dr.created_at DESC LIMIT 50  -- ❌ colonne inexistante

-- APRÈS
ORDER BY dr.rolled_at DESC LIMIT 50  -- ✅ correction
```

### 2. Correction de l'API get_dice_rolls_history.php
**Fichier**: `/home/jean/Documents/jdrmj/api/get_dice_rolls_history.php`

**Problème**: Chemins relatifs incorrects
```php
// AVANT
require_once '../includes/functions.php';
require_once '../classes/DiceRoll.php';

// APRÈS
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/DiceRoll.php';
require_once __DIR__ . '/../classes/Campaign.php';
require_once __DIR__ . '/../classes/User.php';
```

**Ajout**: Démarrage de la session
```php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

## Tests effectués

### Test 1: Vérification de la table dice_rolls
```bash
✅ Table dice_rolls existe
📋 Colonnes: id, campaign_id, user_id, username, dice_type, dice_sides, 
             quantity, results, total, max_result, min_result, has_crit, 
             has_fumble, rolled_at, is_hidden
📊 Nombre de jets: 2
```

### Test 2: Test de la méthode getByCampaignId()
```bash
✅ Méthode fonctionne
📊 Nombre de jets récupérés: 2
```

### Test 3: Test de l'API en local
```bash
✅ API fonctionne
📄 Response: {"success":true,"rolls":[...]}
```

## Résultats
- ✅ La table `dice_rolls` existe et contient des données
- ✅ La méthode `DiceRoll::getByCampaignId()` fonctionne correctement
- ✅ L'API retourne les jets de dés en local
- ⚠️  L'API nécessite une session active pour fonctionner via HTTP

## Fichiers modifiés
1. `/home/jean/Documents/jdrmj/classes/DiceRoll.php` - Correction des requêtes SQL
2. `/home/jean/Documents/jdrmj/api/get_dice_rolls_history.php` - Correction des chemins et ajout de session

## Fichiers de test créés
1. `test_dice_history_debug.php` - Test complet de l'API
2. `test_api_direct.php` - Test direct de l'API
3. `test_api_with_session.php` - Test avec session
4. `test_api_debug.php` - Test de débogage
5. `test_api_simple.php` - Test simple
6. `test_selenium_simple.py` - Test Selenium avec requests
7. `test_api_http.php` - Test HTTP

## Nettoyage nécessaire
Les fichiers de test peuvent être supprimés après vérification :
- `test_*.php`
- `test_*.py`

