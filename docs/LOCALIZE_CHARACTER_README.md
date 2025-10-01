# Méthode localizeCharacter - Documentation

## Vue d'ensemble

La méthode `localizeCharacter` de la classe `Monde` a été créée pour centraliser la logique de localisation des personnages/joueurs dans le monde. Cette méthode remplace la logique complexe qui était précédemment dupliquée dans `view_scene_player.php`.

## Signature de la méthode

```php
public static function localizeCharacter(int $user_id, ?int $campaign_id = null): array
```

### Paramètres

- `$user_id` (int) : ID de l'utilisateur à localiser
- `$campaign_id` (int|null) : ID de la campagne spécifique (optionnel)

### Valeur de retour

La méthode retourne un tableau associatif avec les clés suivantes :

- `status` (string) : Statut de la localisation
- `message` (string) : Message descriptif
- `place` (array|null) : Informations sur le lieu (si trouvé)
- `campaign_id` (int|null) : ID de la campagne (pour certains statuts)

## Statuts possibles

### 1. `found`
Le personnage a été trouvé dans un lieu.

```php
[
    'status' => 'found',
    'place' => [
        'id' => 1,
        'name' => 'Taverne du Dragon',
        'description' => 'Une taverne accueillante...',
        'campaign_title' => 'Aventure Épique',
        'campaign_id' => 1,
        'dm_id' => 2
    ],
    'message' => 'Personnage localisé avec succès'
]
```

### 2. `member_no_place`
Le joueur est membre de la campagne spécifiée mais n'est pas assigné à un lieu.

```php
[
    'status' => 'member_no_place',
    'campaign_id' => 1,
    'message' => 'Vous êtes membre de cette campagne mais n\'êtes pas encore assigné à un lieu spécifique.'
]
```

### 3. `not_member`
Le joueur n'est pas membre de la campagne spécifiée.

```php
[
    'status' => 'not_member',
    'campaign_id' => 1,
    'message' => 'Vous n\'êtes pas membre de cette campagne.'
]
```

### 4. `member_no_place_any`
Le joueur est membre d'au moins une campagne mais n'est assigné à aucun lieu.

```php
[
    'status' => 'member_no_place_any',
    'message' => 'Vous êtes membre d\'une campagne mais n\'êtes pas encore assigné à un lieu spécifique.'
]
```

### 5. `no_campaigns`
Le joueur n'est membre d'aucune campagne.

```php
[
    'status' => 'no_campaigns',
    'message' => 'Vous n\'êtes membre d\'aucune campagne.'
]
```

### 6. `error`
Erreur lors de la localisation.

```php
[
    'status' => 'error',
    'message' => 'Erreur lors de la localisation du personnage.'
]
```

## Utilisation

### Exemple basique

```php
<?php
require_once 'classes/init.php';

$user_id = $_SESSION['user_id'];
$localization = Monde::localizeCharacter($user_id);

switch ($localization['status']) {
    case 'found':
        $place = $localization['place'];
        echo "Vous êtes dans : " . $place['name'];
        break;
        
    case 'member_no_place_any':
        echo $localization['message'];
        break;
        
    case 'no_campaigns':
        echo "Rejoignez une campagne pour commencer à jouer.";
        break;
        
    default:
        echo "Erreur : " . $localization['message'];
}
?>
```

### Exemple avec campagne spécifique

```php
<?php
$user_id = $_SESSION['user_id'];
$campaign_id = $_GET['campaign_id'] ?? null;

$localization = Monde::localizeCharacter($user_id, $campaign_id);

switch ($localization['status']) {
    case 'found':
        // Afficher le lieu
        break;
        
    case 'member_no_place':
        // Rediriger vers la campagne
        header("Location: view_campaign.php?id=" . $localization['campaign_id']);
        break;
        
    case 'not_member':
        // Rediriger vers les campagnes
        header('Location: campaigns.php');
        break;
}
?>
```

## Migration depuis l'ancien système

### Avant (logique dupliquée)

```php
// Logique complexe dupliquée dans view_scene_player.php
if ($requested_campaign_id) {
    $stmt = $pdo->prepare("SELECT ...");
    $stmt->execute([$user_id, $requested_campaign_id]);
    $place = $stmt->fetch();
    
    if (!$place) {
        $stmt = $pdo->prepare("SELECT cm.role FROM campaign_members...");
        $stmt->execute([$requested_campaign_id, $user_id]);
        $membership = $stmt->fetch();
        
        if ($membership) {
            // Afficher message "membre mais pas de lieu"
        } else {
            // Rediriger
        }
    }
} else {
    // Autre logique complexe...
}
```

### Après (méthode centralisée)

```php
// Logique centralisée et réutilisable
$localization = Monde::localizeCharacter($user_id, $requested_campaign_id);

switch ($localization['status']) {
    case 'found':
        $place = $localization['place'];
        break;
        
    case 'member_no_place':
        // Gestion centralisée
        break;
        
    // ... autres cas
}
```

## Avantages

### 1. **Centralisation**
- Logique de localisation centralisée dans une seule méthode
- Évite la duplication de code
- Facilite la maintenance

### 2. **Réutilisabilité**
- Peut être utilisée dans d'autres fichiers
- Interface claire et cohérente
- Facilite les tests unitaires

### 3. **Maintenabilité**
- Modifications centralisées
- Code plus lisible
- Gestion d'erreurs unifiée

### 4. **Performance**
- Requêtes optimisées
- Cache possible (futur)
- Moins de code dupliqué

## Intégration dans view_scene_player.php

Le fichier `view_scene_player.php` a été migré pour utiliser cette nouvelle méthode :

```php
<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';

$page_title = "Lieu - Vue Joueur";
$current_page = "view_scene_player";

requireLogin();

$user_id = $_SESSION['user_id'];
$requested_campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : null;

// Localiser le personnage/joueur dans le monde
$localization = Monde::localizeCharacter($user_id, $requested_campaign_id);

// Traiter les différents statuts de localisation
switch ($localization['status']) {
    case 'found':
        $place = $localization['place'];
        // Continuer avec l'affichage du lieu
        break;
        
    case 'member_no_place':
        // Afficher message spécifique
        break;
        
    // ... autres cas
}
?>
```

## Tests

La méthode a été testée avec différents scénarios :

- ✅ Localisation d'un utilisateur existant
- ✅ Localisation avec campagne spécifique
- ✅ Gestion des utilisateurs inexistants
- ✅ Gestion des campagnes inexistantes
- ✅ Tests de performance
- ✅ Vérification de la structure de retour

## Conclusion

La méthode `localizeCharacter` améliore significativement la structure du code en centralisant la logique de localisation des personnages. Elle offre une interface claire et cohérente, facilite la maintenance et améliore la réutilisabilité du code.
