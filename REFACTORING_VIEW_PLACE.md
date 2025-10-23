# Refactorisation de view_place.php

## Résumé des modifications

Le fichier `view_place.php` a été refactorisé selon les règles suivantes :
- Le SQL est maintenant géré dans des méthodes de classes
- Le JavaScript a été extrait vers un fichier global
- Les appels de modification en base sont codés en AJAX et font appel à des endpoints PHP dans le répertoire `api`

## Structure des fichiers

### Fichiers principaux
- `view_place_refactored.php` - Version refactorisée du fichier principal
- `templates/view_place_template.php` - Template HTML principal
- `templates/view_place_modals.php` - Modals séparés
- `js/view_place.js` - JavaScript global extrait
- `css/view_place.css` - Styles CSS

### Endpoints API créés
- `api/get_token_positions.php` - Récupérer les positions des tokens
- `api/update_token_position.php` - Mettre à jour la position d'un token
- `api/reset_token_positions.php` - Réinitialiser les positions des tokens
- `api/search_monsters.php` - Rechercher des monstres
- `api/search_poisons.php` - Rechercher des poisons
- `api/search_magical_items.php` - Rechercher des objets magiques
- `api/update_object_position.php` - Mettre à jour la position d'un objet
- `api/save_dice_roll.php` - Sauvegarder un lancer de dés
- `api/toggle_dice_roll_hidden.php` - Basculer la visibilité d'un lancer de dés
- `api/delete_dice_roll.php` - Supprimer un lancer de dés
- `api/get_regions_by_country.php` - Récupérer les régions d'un pays
- `api/get_places_by_region.php` - Récupérer les lieux d'une région
- `api/get_player_characters.php` - Récupérer les personnages d'un joueur
- `api/add_monster.php` - Ajouter un monstre à un lieu
- `api/add_npc.php` - Ajouter un PNJ à un lieu
- `api/add_player.php` - Ajouter un joueur à un lieu
- `api/update_place.php` - Mettre à jour un lieu

## Classes utilisées

### Lieu.php (déjà existante)
La classe `Lieu` contient déjà toutes les méthodes nécessaires pour :
- Gérer les requêtes SQL liées aux lieux
- Récupérer les joueurs, PNJ, monstres et objets
- Gérer les positions des tokens
- Mettre à jour les informations du lieu

### Autres classes
- `Access.php` - Gestion des accès entre lieux
- `User.php` - Gestion des utilisateurs
- `Campaign.php` - Gestion des campagnes
- `Character.php` - Gestion des personnages

## Améliorations apportées

### 1. Séparation des responsabilités
- **PHP** : Logique métier et gestion des données
- **HTML** : Structure et présentation
- **JavaScript** : Interactions côté client
- **CSS** : Styles et apparence

### 2. API RESTful
- Tous les endpoints suivent une convention REST
- Réponses JSON standardisées
- Gestion d'erreurs cohérente
- Authentification et autorisation

### 3. Code JavaScript modulaire
- Fonctions organisées par fonctionnalité
- Variables globales centralisées
- Gestion d'erreurs améliorée
- Code réutilisable

### 4. Template système
- HTML séparé du PHP
- Modals réutilisables
- Structure claire et maintenable

## Utilisation

### Remplacer l'ancien fichier
```bash
# Sauvegarder l'ancien fichier
mv view_place.php view_place_old.php

# Utiliser la nouvelle version
mv view_place_refactored.php view_place.php
```

### Vérifier les permissions
Assurez-vous que le répertoire `api` est accessible en lecture/écriture :
```bash
chmod 755 api/
chmod 644 api/*.php
```

### Tester les fonctionnalités
1. Vérifier que les tokens se déplacent correctement
2. Tester l'ajout de monstres, PNJ et joueurs
3. Vérifier les lancers de dés
4. Tester la recherche en temps réel
5. Vérifier les modals et formulaires

## Avantages de la refactorisation

1. **Maintenabilité** : Code plus facile à maintenir et déboguer
2. **Réutilisabilité** : Composants réutilisables dans d'autres pages
3. **Performance** : Chargement asynchrone des données
4. **Sécurité** : Validation centralisée des données
5. **Évolutivité** : Structure modulaire pour ajouter de nouvelles fonctionnalités

## Notes importantes

- Tous les endpoints API vérifient l'authentification
- Les permissions sont vérifiées côté serveur
- Les erreurs sont loggées pour le débogage
- Le code est compatible avec l'architecture existante
