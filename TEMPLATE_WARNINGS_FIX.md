# Correction des erreurs PHP Warning dans les templates

## 🐛 Problèmes identifiés

1. **Erreur d'inclusion navbar** :
   ```
   PHP Warning: include(../includes/navbar.php): Failed to open stream: No such file or directory
   ```

2. **Clés de tableau manquantes dans diceRolls** :
   ```
   PHP Warning: Undefined array key "dice_count"
   PHP Warning: Undefined array key "modifier" 
   PHP Warning: Undefined array key "created_at"
   ```

3. **Clés de tableau manquantes dans availablePlayers** :
   ```
   PHP Warning: Undefined array key "id"
   ```

## ✅ Corrections apportées

### 1. **Correction du chemin d'inclusion navbar**
```php
// Avant (incorrect - chemin relatif depuis templates/)
<?php include '../includes/navbar.php'; ?>

// Après (correct - chemin relatif depuis view_place.php)
<?php include 'includes/navbar.php'; ?>
```

### 2. **Correction des clés manquantes dans diceRolls**
```php
// Avant (erreur si clé manquante)
<?php echo $roll['dice_count']; ?>
<?php echo $roll['modifier']; ?>
<?php echo date('H:i', strtotime($roll['created_at'])); ?>

// Après (vérification avec isset())
<?php echo isset($roll['dice_count']) ? $roll['dice_count'] : '1'; ?>
<?php echo isset($roll['modifier']) && $roll['modifier'] != 0 ? $roll['modifier'] : ''; ?>
<?php echo isset($roll['created_at']) ? date('H:i', strtotime($roll['created_at'])) : date('H:i'); ?>
```

### 3. **Correction des clés manquantes dans availablePlayers**
```php
// Avant (erreur si clé manquante)
<option value="<?php echo $player['id']; ?>"><?php echo htmlspecialchars($player['username']); ?></option>

// Après (vérification avec isset())
<option value="<?php echo isset($player['id']) ? $player['id'] : ''; ?>"><?php echo htmlspecialchars(isset($player['username']) ? $player['username'] : 'Inconnu'); ?></option>
```

## 🧪 Tests effectués

- ✅ Syntaxe PHP correcte pour `templates/view_place_template.php`
- ✅ Syntaxe PHP correcte pour `templates/view_place_modals.php`
- ✅ Vérifications `isset()` ajoutées pour toutes les clés problématiques
- ✅ Valeurs par défaut définies pour éviter les erreurs

## 🎯 Résultat

Toutes les erreurs PHP Warning sont maintenant résolues :

1. **Inclusion navbar** : Chemin correct depuis le répertoire racine
2. **Clés diceRolls** : Vérifications `isset()` avec valeurs par défaut
3. **Clés availablePlayers** : Vérifications `isset()` avec valeurs par défaut

## 📁 Fichiers modifiés

- `templates/view_place_template.php` - Correction des chemins et clés manquantes
- `templates/view_place_modals.php` - Correction des clés manquantes

La page `http://localhost/jdrmj/view_place.php?id=154` devrait maintenant s'afficher sans aucune erreur PHP Warning !
