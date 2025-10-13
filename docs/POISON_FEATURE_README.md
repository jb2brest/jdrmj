# Fonctionnalité de Recherche de Poisons

## Description
Cette fonctionnalité ajoute un bouton "Poison" à côté du titre de la session et du nom du MJ dans l'écran de détail d'une scène. Ce bouton ouvre une fenêtre modale permettant de rechercher des poisons dans la base de données.

## Fichiers modifiés/créés

### 1. `search_poisons.php` (nouveau)
- API PHP qui lit le fichier CSV des poisons
- Recherche dans le nom, la clé, la description et le type
- Retourne les résultats au format JSON
- Limite les résultats à 50 pour éviter la surcharge

### 2. `view_scene.php` (modifié)
- Ajout du bouton "Poison" à côté des informations de session
- Ajout de la modale de recherche de poisons
- Ajout du JavaScript pour la gestion de la recherche

### 3. `test_poisons.php` (nouveau)
- Page de test pour vérifier le bon fonctionnement de l'API
- Interface simple pour tester la recherche

## Structure de la base de données
Les poisons sont stockés dans le fichier `aidednddata/poisons.csv` avec les colonnes suivantes :
- Id : Identifiant unique
- Nom : Nom du poison
- Cle : Clé de référence
- Description : Description détaillée du poison
- Type : Type d'administration et prix
- Source : Source de la référence

## Fonctionnalités

### Bouton Poison
- Apparence : Bouton rouge avec icône de crâne et os croisés
- Position : À côté du titre de la session et du nom du MJ
- Action : Ouvre la modale de recherche

### Modale de Recherche
- Interface de recherche en temps réel
- Recherche après 2 caractères minimum
- Délai de 300ms pour éviter les requêtes excessives
- Affichage des résultats avec :
  - Nom du poison (en rouge)
  - Type d'administration
  - Source de référence
  - Description complète
  - Clé de référence

### Recherche
- Recherche dans tous les champs (nom, clé, description, type)
- Résultats limités à 50 pour les performances
- Gestion des erreurs et états de chargement
- Réinitialisation automatique à l'ouverture de la modale

## Utilisation

1. **Accéder à une scène** : Naviguer vers `view_scene.php?id=X`
2. **Cliquer sur le bouton Poison** : Situé à côté des informations de session
3. **Rechercher** : Taper au moins 2 caractères dans le champ de recherche
4. **Consulter les résultats** : Les poisons correspondants s'affichent en temps réel

## Tests

### Test de l'API
```bash
curl -H "X-Requested-With: XMLHttpRequest" "http://localhost:8000/search_poisons.php?q=arsenic"
```

### Test de l'interface
Ouvrir `http://localhost:8000/test_poisons.php` dans un navigateur

## Sécurité
- Vérification des requêtes AJAX avec l'en-tête `X-Requested-With`
- Validation des paramètres d'entrée
- Limitation des résultats de recherche

## Compatibilité
- Fonctionne avec Bootstrap 5.3.0
- Compatible avec les navigateurs modernes supportant ES6
- Utilise Font Awesome pour les icônes

## Maintenance
- Les poisons sont automatiquement mis à jour via le fichier CSV
- Aucune base de données SQL requise pour cette fonctionnalité
- Fichier de configuration facilement modifiable
