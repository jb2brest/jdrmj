# Intégration de la classe Campaign

## Vue d'ensemble

La classe `Campaign` a été créée et intégrée dans le système JDR MJ pour centraliser et améliorer la gestion des campagnes. Cette classe remplace les requêtes SQL dispersées par une approche orientée objet plus maintenable.

## Fichiers créés

### Fichiers principaux
- `classes/Campaign.php` - Classe Campaign principale
- `includes/campaign_compatibility.php` - Fonctions de compatibilité
- `docs/CAMPAIGN_CLASS_INTEGRATION.md` - Documentation

### Fichiers modifiés
- `includes/functions.php` - Inclut le fichier de compatibilité
- `campaigns.php` - Utilise maintenant la classe Campaign

## Fonctionnalités de la classe Campaign

### Création et gestion
```php
// Création d'une campagne
$campaign = Campaign::create($pdo, [
    'dm_id' => $userId,
    'title' => 'Ma Campagne',
    'description' => 'Description de la campagne',
    'game_system' => 'D&D 5e',
    'is_public' => true
]);

// Mise à jour
$campaign->update(['description' => 'Nouvelle description']);

// Suppression
$campaign->delete();
```

### Recherche et accès
```php
// Recherche par ID
$campaign = Campaign::findById($pdo, $campaignId);

// Recherche par code d'invitation
$campaign = Campaign::findByInviteCode($pdo, $inviteCode);

// Campagnes accessibles par un utilisateur
$campaigns = Campaign::getAccessibleCampaigns($pdo, $userId, $userRole);

// Campagnes créées par un DM
$campaigns = Campaign::getCampaignsByDM($pdo, $dmId);
```

### Gestion des membres
```php
// Ajouter un membre
$campaign->addMember($userId, 'player');

// Retirer un membre
$campaign->removeMember($userId);

// Obtenir les membres
$members = $campaign->getMembers();

// Vérifier l'appartenance
$isMember = $campaign->isMember($userId);

// Obtenir le rôle d'un utilisateur
$role = $campaign->getUserRole($userId);
```

### Gestion des lieux
```php
// Associer un lieu
$campaign->associatePlace($placeId);

// Dissocier un lieu
$campaign->dissociatePlace($placeId);

// Obtenir les lieux associés
$places = $campaign->getAssociatedPlaces();

// Obtenir les lieux disponibles
$availablePlaces = $campaign->getAvailablePlaces();
```

### Vérifications de permissions
```php
// Vérifier l'accès
$canAccess = $campaign->canAccess($userId, $userRole);

// Vérifier les droits de modification
$canModify = $campaign->canModify($userId, $userRole);
```

## Compatibilité

### Fonctions de compatibilité
Les anciennes fonctions continuent de fonctionner grâce au fichier `includes/campaign_compatibility.php` :

```php
// Ces fonctions utilisent maintenant la classe Campaign en arrière-plan
generateInviteCode($length)
associatePlaceToCampaign($placeId, $campaignId)
dissociatePlaceFromCampaign($placeId, $campaignId)
getCampaignsForPlace($placeId)
getPlacesForCampaign($campaignId)
getAvailablePlacesForCampaign($campaignId)
getAvailableCampaignsForPlace($placeId)
updateCampaignPlaceAssociations($campaignId, $placeIds)
updatePlaceCampaignAssociations($placeId, $campaignIds)
```

### Nouvelles fonctions utilitaires
```php
// Obtenir un objet Campaign
$campaign = getCampaignObject($campaignId);

// Créer une campagne
$campaign = createCampaign($data);

// Obtenir les campagnes accessibles
$campaigns = getAccessibleCampaigns($userId, $userRole);

// Obtenir les campagnes d'un DM
$campaigns = getCampaignsByDM($dmId);
```

## Avantages

### 1. Encapsulation
- Toute la logique de campagne est centralisée
- Code plus organisé et maintenable

### 2. Sécurité
- Validation centralisée des données
- Gestion des permissions intégrée

### 3. Réutilisabilité
- Méthodes réutilisables dans tout le projet
- API cohérente

### 4. Extensibilité
- Facile d'ajouter de nouvelles fonctionnalités
- Structure orientée objet

## Utilisation recommandée

### Pour les nouveaux développements
```php
// Utiliser directement la classe Campaign
$campaign = Campaign::findById($pdo, $campaignId);
if ($campaign && $campaign->canAccess($userId, $userRole)) {
    // Logique d'accès à la campagne
}
```

### Pour le code existant
```php
// Continuer à utiliser les fonctions de compatibilité
$campaigns = getAccessibleCampaigns($userId, $userRole);
```

## Tests

L'intégration a été testée avec succès :
- ✅ Création de campagnes
- ✅ Recherche et accès
- ✅ Gestion des membres
- ✅ Gestion des lieux
- ✅ Vérifications de permissions
- ✅ Fonctions de compatibilité
- ✅ Intégration avec campaigns.php

## Prochaines étapes

1. **Migration progressive** : Remplacer progressivement les appels aux fonctions de compatibilité par l'utilisation directe de la classe Campaign
2. **Tests supplémentaires** : Tester l'intégration avec tous les fichiers du projet
3. **Documentation** : Mettre à jour la documentation des API
4. **Optimisation** : Améliorer les performances si nécessaire

## Support

Pour toute question ou problème lié à l'intégration de la classe Campaign, consultez :
- Le code source de `classes/Campaign.php`
- Les tests d'intégration
- Ce fichier de documentation
