# Correction du message "Aucune campagne associée à ce lieu"

## 🐛 Problème identifié

Le message "Aucune campagne associée à ce lieu" s'affichait incorrectement alors qu'une campagne était bien associée au lieu (ID: 120).

## 🔍 Analyse du problème

Le problème venait du fait que la variable `currentCampaignId` dans le JavaScript n'était pas correctement définie :

1. **Dans `view_place.php`** : La campagne était bien récupérée via `$lieu->getCampaigns()`
2. **Dans le JavaScript** : `currentCampaignId` était définie comme `window.placeId` au lieu de `window.campaignId`
3. **Variables manquantes** : `campaignId` n'était pas passée dans `$js_vars`

## ✅ Corrections apportées

### 1. **Ajout de `campaignId` dans les variables JavaScript**
```php
// Variables JavaScript pour l'API
$js_vars = [
    'placeId' => $place_id,
    'canEdit' => $canEdit,
    'isOwnerDM' => $isOwnerDM,
    'tokenPositions' => $tokenPositions,
    'campaignId' => $place['campaign_id']  // ← Ajouté
];
```

### 2. **Ajout de `window.campaignId` dans le template**
```javascript
// Variables JavaScript globales
window.placeId = <?php echo json_encode($js_vars['placeId']); ?>;
window.canEdit = <?php echo json_encode($js_vars['canEdit']); ?>;
window.isOwnerDM = <?php echo json_encode($js_vars['isOwnerDM']); ?>;
window.tokenPositions = <?php echo json_encode($js_vars['tokenPositions']); ?>;
window.campaignId = <?php echo json_encode($js_vars['campaignId']); ?>;  // ← Ajouté
```

### 3. **Correction de `currentCampaignId` dans le JavaScript**
```javascript
// Avant (incorrect)
let currentCampaignId = window.placeId ? window.placeId : 0;

// Après (corrigé)
let currentCampaignId = window.campaignId ? window.campaignId : 0;
```

## 🧪 Tests effectués

### **Test de récupération de la campagne**
- ✅ **Lieu trouvé** : "Salle de garde" (ID: 154)
- ✅ **Campagne trouvée** : "Les chroniques d'Ignis" (ID: 120)
- ✅ **DM ID** : 2
- ✅ **hasCampaignId()** : true

### **Variables JavaScript**
- ✅ **window.placeId** : 154 (ID du lieu)
- ✅ **window.campaignId** : 120 (ID de la campagne)
- ✅ **Différenciation** : Les deux variables sont maintenant distinctes

## 🎯 Résultat

Le message "Aucune campagne associée à ce lieu" ne s'affiche plus car :

1. **Campagne récupérée** : ID 120 "Les chroniques d'Ignis"
2. **Variable JavaScript** : `currentCampaignId = 120`
3. **Système de dés** : Fonctionne maintenant avec la bonne campagne
4. **Historique** : Peut charger les jets de dés de la campagne

## 📁 Fichiers modifiés

- `view_place.php` - Ajout de `campaignId` dans `$js_vars`
- `templates/view_place_template.php` - Ajout de `window.campaignId`
- `js/view_place.js` - Correction de `currentCampaignId`

## 🎯 Résultat final

La page `http://localhost/jdrmj/view_place.php?id=154` affiche maintenant :

- ✅ **Système de dés** : Fonctionnel avec la campagne ID 120
- ✅ **Historique** : Chargement des jets de dés de la campagne
- ✅ **Sauvegarde** : Enregistrement des jets dans la bonne campagne
- ✅ **Permissions** : Gestion des droits selon la campagne

Le message d'erreur est maintenant résolu !
