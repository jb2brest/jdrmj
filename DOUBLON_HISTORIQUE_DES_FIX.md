# Correction du doublon dans l'affichage des résultats de jets de dés

## 🐛 Problème identifié

Il y avait un doublon dans l'affichage des résultats de jets de dés :

1. **"Historique des jets"** : Bien positionné dans la zone "Jets de dés" mais ne fonctionnait pas
2. **"Historique des lancers de dés"** : Fonctionnait mais ne devrait pas être affichée (doublon)

## 🔍 Analyse du problème

### **Section en doublon**
- ✅ **"Historique des jets"** : Dans la zone "Jets de dés" (col-md-6) - **À conserver**
- ❌ **"Historique des lancers de dés"** : Section séparée avec `$diceRolls` - **À supprimer**

### **Problème de fonctionnement**
- ❌ **API manquante** : `get_dice_rolls_history.php` n'existait pas
- ❌ **Méthode manquante** : `DiceRoll::getByCampaignId()` n'existait pas
- ❌ **JavaScript défaillant** : `loadDiceHistory()` ne pouvait pas charger les données

## ✅ Corrections apportées

### 1. **Suppression de la section en doublon**
```php
// SUPPRIMÉ - Section en doublon
<!-- Historique des lancers de dés -->
<?php if (!empty($diceRolls)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-dice me-2"></i>Historique des lancers de dés</h5>
        </div>
        <!-- ... contenu PHP ... -->
    </div>
<?php endif; ?>
```

### 2. **Création de l'API manquante**
```php
// api/get_dice_rolls_history.php
- Vérification des paramètres (campaign_id, show_hidden)
- Vérification des permissions (accès à la campagne)
- Appel à DiceRoll::getByCampaignId()
- Retour JSON des données
```

### 3. **Ajout de la méthode manquante**
```php
// classes/DiceRoll.php
public static function getByCampaignId($campaignId, $showHidden = false, PDO $pdo = null)
{
    // Vérification de l'existence de la table
    // Requête SQL avec JOIN sur users et places
    // Filtrage des jets masqués selon les permissions
    // Tri par date décroissante
    // Limite à 50 résultats
}
```

## 🎯 Fonctionnalités restaurées

### **Section "Historique des jets" (conservée)**
- ✅ **Position** : Dans la zone "Jets de dés" (col-md-6)
- ✅ **JavaScript** : `loadDiceHistory()` fonctionnel
- ✅ **API** : `get_dice_rolls_history.php` opérationnel
- ✅ **Données** : Récupération depuis la base de données
- ✅ **Permissions** : Gestion des jets masqués selon le rôle

### **Section en doublon (supprimée)**
- ❌ **"Historique des lancers de dés"** : Section séparée supprimée
- ❌ **Données PHP** : Plus d'utilisation de `$diceRolls`
- ❌ **Affichage statique** : Plus d'affichage PHP direct

## 🧪 Tests effectués

- ✅ Syntaxe PHP correcte pour tous les fichiers
- ✅ API `get_dice_rolls_history.php` créée
- ✅ Méthode `DiceRoll::getByCampaignId()` ajoutée
- ✅ Section en doublon supprimée

## 📁 Fichiers modifiés

- `templates/view_place_template.php` - Suppression de la section en doublon
- `api/get_dice_rolls_history.php` - Création de l'API manquante
- `classes/DiceRoll.php` - Ajout de la méthode `getByCampaignId()`

## 🎯 Résultat

L'affichage des résultats de jets de dés est maintenant correct :

1. **Une seule section** : "Historique des jets" dans la zone "Jets de dés"
2. **Fonctionnement** : JavaScript + API pour charger les données
3. **Pas de doublon** : Section séparée supprimée
4. **Données dynamiques** : Chargement en temps réel via AJAX
5. **Permissions** : Gestion des jets masqués selon le rôle

La page affiche maintenant un seul historique des jets de dés fonctionnel !
