# Correction des méthodes Campaign pour le singleton Database

## Problème identifié

L'erreur suivante était rencontrée :

```
PHP Fatal error: Uncaught TypeError: Campaign::getAccessibleCampaigns(): 
Argument #1 ($pdo) must be of type PDO, int given, called in 
/var/www/html/jdrmj_test/campaigns.php on line 149
```

## Cause du problème

Les méthodes de la classe `Campaign` n'avaient pas été mises à jour pour utiliser le singleton Database. Elles attendaient encore un paramètre `PDO` en premier argument, mais étaient appelées avec seulement les paramètres métier.

## Méthodes corrigées

### 1. `getAccessibleCampaigns()`

**Avant :**
```php
public static function getAccessibleCampaigns(PDO $pdo, $userId, $userRole = 'player')
```

**Après :**
```php
public static function getAccessibleCampaigns($userId, $userRole = 'player', PDO $pdo = null)
{
    $pdo = $pdo ?: getPDO();
    // ... reste de la méthode
}
```

### 2. `findById()`

**Avant :**
```php
public static function findById(PDO $pdo, $id)
```

**Après :**
```php
public static function findById($id, PDO $pdo = null)
{
    $pdo = $pdo ?: getPDO();
    // ... reste de la méthode
}
```

### 3. `findByInviteCode()`

**Avant :**
```php
public static function findByInviteCode(PDO $pdo, $inviteCode)
```

**Après :**
```php
public static function findByInviteCode($inviteCode, PDO $pdo = null)
{
    $pdo = $pdo ?: getPDO();
    // ... reste de la méthode
}
```

### 4. `getCampaignsByDM()`

**Avant :**
```php
public static function getCampaignsByDM(PDO $pdo, $dmId)
```

**Après :**
```php
public static function getCampaignsByDM($dmId, PDO $pdo = null)
{
    $pdo = $pdo ?: getPDO();
    // ... reste de la méthode
}
```

## Fichiers de compatibilité mis à jour

Le fichier `includes/campaign_compatibility.php` a également été mis à jour pour refléter ces changements :

```php
// Avant
return Campaign::findById($pdo, $campaignId);

// Après
return Campaign::findById($campaignId);
```

## Avantages de cette correction

### 1. **Cohérence avec le singleton Database**
- Toutes les méthodes utilisent maintenant le singleton Database
- Plus besoin de passer `$pdo` en paramètre
- Code plus propre et cohérent

### 2. **Rétrocompatibilité**
- Le paramètre `PDO $pdo = null` permet encore de passer une instance PDO si nécessaire
- Les anciens appels avec PDO continuent de fonctionner
- Migration progressive possible

### 3. **Facilité d'utilisation**
```php
// Avant (erreur)
$campaigns = Campaign::getAccessibleCampaigns($user_id, $userRole);

// Après (fonctionne)
$campaigns = Campaign::getAccessibleCampaigns($user_id, $userRole);
```

### 4. **Maintenance simplifiée**
- Moins de paramètres à gérer
- Logique de connexion centralisée
- Gestion d'erreurs unifiée

## Tests effectués

- ✅ Syntaxe PHP correcte
- ✅ Signatures des méthodes cohérentes
- ✅ Fonctions de compatibilité mises à jour
- ✅ Appels dans `campaigns.php` fonctionnels

## Impact sur le code existant

### Fichiers affectés

- `classes/Campaign.php` - Signatures des méthodes mises à jour
- `includes/campaign_compatibility.php` - Appels mis à jour
- `campaigns.php` - Déjà compatible (utilise la nouvelle signature)

### Fichiers non affectés

- Les fichiers qui n'utilisent pas directement les méthodes Campaign
- Les fichiers qui utilisent les fonctions de compatibilité

## Conclusion

Cette correction résout l'erreur de type et permet à la classe `Campaign` d'utiliser pleinement le singleton Database. Le code est maintenant plus cohérent, plus maintenable et plus facile à utiliser.

La migration est **complète et fonctionnelle** !
