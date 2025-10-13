# Intégration de la classe User

## Vue d'ensemble

La classe `User` a été intégrée dans le système JDR MJ pour centraliser et améliorer la gestion des utilisateurs. Cette intégration remplace les fonctions dispersées dans `includes/functions.php` par une approche orientée objet plus maintenable.

## Fichiers modifiés

### Fichiers principaux
- `classes/User.php` - Classe User principale
- `includes/user_compatibility.php` - Fonctions de compatibilité
- `includes/functions.php` - Anciennes fonctions commentées
- `login.php` - Utilise maintenant la classe User
- `logout.php` - Utilise maintenant la classe User
- `register.php` - Utilise maintenant la classe User
- `profile.php` - Utilise maintenant la classe User
- `campaigns.php` - Utilise maintenant la classe User
- `view_campaign.php` - Corrigé pour inclure la configuration DB

## Fonctionnalités de la classe User

### Authentification
```php
// Authentification
$user = new User($pdo);
$authenticatedUser = $user->authenticate($username, $password);

// Vérification de connexion
if (User::isLoggedIn()) {
    // Utilisateur connecté
}

// Déconnexion
User::logout();
```

### Gestion des rôles
```php
// Vérification des rôles
if (User::isDM()) {
    // Accès MJ
}

if (User::isAdmin()) {
    // Accès admin
}

if (User::isDMOrAdmin()) {
    // Accès privilégié
}
```

### CRUD des utilisateurs
```php
// Création
$newUser = User::create($pdo, [
    'username' => 'nouveau_joueur',
    'email' => 'email@example.com',
    'password' => 'motdepasse',
    'role' => 'player'
]);

// Recherche
$user = User::findById($pdo, $id);
$user = User::findByUsername($pdo, $username);
$user = User::findByEmail($pdo, $email);

// Mise à jour
$user->update(['role' => 'dm']);

// Suppression
$user->delete();
```

## Compatibilité

### Fonctions de compatibilité
Les anciennes fonctions continuent de fonctionner grâce au fichier `includes/user_compatibility.php` :

```php
// Ces fonctions utilisent maintenant la classe User en arrière-plan
isLoggedIn()
getUserRole()
isDM()
isAdmin()
isPlayer()
isDMOrAdmin()
getUserInfo($user_id)
getExperienceLevelLabel($level)
```

### Migration progressive
- Les anciennes fonctions sont commentées dans `includes/functions.php`
- Les nouvelles fonctions de compatibilité utilisent la classe User
- Aucun changement requis dans le code existant
- Migration possible vers l'utilisation directe de la classe User

## Avantages

### 1. Encapsulation
- Toute la logique utilisateur est centralisée
- Code plus organisé et maintenable

### 2. Sécurité
- Gestion sécurisée des mots de passe
- Validation centralisée des données

### 3. Réutilisabilité
- Méthodes réutilisables dans tout le projet
- API cohérente

### 4. Extensibilité
- Facile d'ajouter de nouvelles fonctionnalités
- Structure orientée objet

## Utilisation recommandée

### Pour les nouveaux développements
```php
// Utiliser directement la classe User
$user = User::findById($pdo, $userId);
if ($user && $user->isDmInstance()) {
    // Logique MJ
}
```

### Pour le code existant
```php
// Continuer à utiliser les fonctions de compatibilité
if (isDM()) {
    // Logique MJ
}
```

## Tests

L'intégration a été testée avec succès :
- ✅ Authentification
- ✅ Gestion des rôles
- ✅ CRUD des utilisateurs
- ✅ Fonctions de compatibilité
- ✅ Intégration avec le système existant

## Prochaines étapes

1. **Migration progressive** : Remplacer progressivement les appels aux fonctions de compatibilité par l'utilisation directe de la classe User
2. **Tests supplémentaires** : Tester l'intégration avec tous les fichiers du projet
3. **Documentation** : Mettre à jour la documentation des API
4. **Optimisation** : Améliorer les performances si nécessaire

## Support

Pour toute question ou problème lié à l'intégration de la classe User, consultez :
- Le code source de `classes/User.php`
- Les tests d'intégration
- Ce fichier de documentation
