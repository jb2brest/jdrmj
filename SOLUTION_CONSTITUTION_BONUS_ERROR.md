# Solution - Erreur "Undefined array key constitution_bonus"

## 🚨 Problème Identifié

```
PHP Warning: Undefined array key "constitution_bonus" in /var/www/html/jdrmj/classes/Character.php on line 1365
```

## 🔍 Cause du Problème

Le code dans `Character.php` ligne 1365 tentait d'accéder à `$character['constitution_bonus']` qui n'existe pas dans le tableau `$character`. Les bonus raciaux sont stockés dans la table `races` et doivent être récupérés séparément.

### Code Problématique
```php
// AVANT (ligne 1365)
$tempChar->constitution = $character['constitution'] + $character['constitution_bonus'];
```

## ✅ Solution Appliquée

### 1. Correction du Code
**Fichier**: `classes/Character.php` (lignes 1362-1378)

**AVANT**:
```php
if ($isBarbarian) {
    // Pour les barbares sans armure : CA = 10 + modificateur de Dextérité + modificateur de Constitution
    $tempChar = new Character();
    $tempChar->constitution = $character['constitution'] + $character['constitution_bonus'];
    $constitutionModifier = $tempChar->getAbilityModifier('constitution');
    $ac = 10 + $dexterityModifier + $constitutionModifier;
}
```

**APRÈS**:
```php
if ($isBarbarian) {
    // Pour les barbares sans armure : CA = 10 + modificateur de Dextérité + modificateur de Constitution
    // Récupérer le bonus racial de constitution
    $constitutionBonus = 0;
    if (isset($character['race_id'])) {
        $stmt = $this->pdo->prepare("SELECT constitution_bonus FROM races WHERE id = ?");
        $stmt->execute([$character['race_id']]);
        $raceData = $stmt->fetch();
        if ($raceData) {
            $constitutionBonus = (int)$raceData['constitution_bonus'];
        }
    }
    
    $tempChar = new Character();
    $tempChar->constitution = $character['constitution'] + $constitutionBonus;
    $constitutionModifier = $tempChar->getAbilityModifier('constitution');
    $ac = 10 + $dexterityModifier + $constitutionModifier;
}
```

### 2. Logique de la Correction

1. **Récupération du bonus racial** : Le code récupère maintenant le `constitution_bonus` depuis la table `races` en utilisant le `race_id` du personnage
2. **Vérification de sécurité** : Vérification que `race_id` existe avant d'exécuter la requête
3. **Calcul correct** : La constitution totale = constitution de base + bonus racial
4. **Calcul de CA** : Pour les barbares sans armure, CA = 10 + modificateur Dex + modificateur Con

## 🧪 Test de Validation

Le problème a été testé avec succès :
- ✅ Personnage barbare trouvé (Barbarus, Demi-orc)
- ✅ Constitution de base : 13
- ✅ Bonus racial constitution : 1
- ✅ Constitution totale : 14
- ✅ Modificateur constitution : 2
- ✅ CA finale calculée : 14 (10 + 2 + 2)

## 📋 Contexte Technique

### Pourquoi cette erreur ?
- Les bonus raciaux sont stockés dans la table `races`
- Le tableau `$character` contient les statistiques de base du personnage
- Les bonus raciaux doivent être récupérés séparément et ajoutés aux statistiques de base

### Calcul de CA pour les Barbares
Les barbares sans armure bénéficient d'une CA spéciale :
- **CA = 10 + modificateur de Dextérité + modificateur de Constitution**
- Cela reflète leur capacité naturelle à esquiver et leur endurance

## 🔧 Impact de la Correction

- ✅ Plus d'erreur PHP "Undefined array key"
- ✅ Calcul correct de la CA pour les barbares
- ✅ Prise en compte des bonus raciaux
- ✅ Code plus robuste avec vérifications de sécurité

---

**Date de résolution**: 2025-10-13  
**Statut**: ✅ Résolu  
**Fichier modifié**: `classes/Character.php`
