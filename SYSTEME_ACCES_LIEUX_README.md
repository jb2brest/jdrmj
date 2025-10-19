# Système d'Accès entre Lieux

## Vue d'ensemble

Le système d'accès permet de créer des connexions entre différents lieux avec des propriétés configurables. Un accès peut être visible ou caché des joueurs, ouvert ou fermé, et piégé ou non.

## Fonctionnalités

### Propriétés des Accès

- **Nom** : Nom descriptif de l'accès (ex: "Porte principale", "Passage secret")
- **Description** : Description détaillée de l'accès
- **Visibilité** : Visible ou caché des joueurs
- **État** : Ouvert ou fermé
- **Piège** : Optionnel, avec détails configurables

### Détails des Pièges

- **Description** : Description du piège
- **Difficulté** : Classe de difficulté (DD) de 1 à 30
- **Dégâts** : Dégâts infligés (ex: "2d6+3")

## Structure de la Base de Données

### Table `accesses`

```sql
CREATE TABLE accesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_place_id INT NOT NULL,           -- Lieu de départ
    to_place_id INT NOT NULL,             -- Lieu de destination
    name VARCHAR(255) NOT NULL,           -- Nom de l'accès
    description TEXT NULL,                -- Description
    is_visible BOOLEAN NOT NULL DEFAULT TRUE,    -- Visible des joueurs
    is_open BOOLEAN NOT NULL DEFAULT TRUE,       -- Ouvert/fermé
    is_trapped BOOLEAN NOT NULL DEFAULT FALSE,   -- Piégé ou non
    trap_description TEXT NULL,           -- Description du piège
    trap_difficulty INT NULL,             -- Difficulté du piège (1-20)
    trap_damage VARCHAR(100) NULL,        -- Dégâts du piège
    position_x INT DEFAULT 0,             -- Position X sur la carte
    position_y INT DEFAULT 0,             -- Position Y sur la carte
    is_on_map BOOLEAN DEFAULT FALSE,      -- Affiché sur la carte
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (from_place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (to_place_id) REFERENCES places(id) ON DELETE CASCADE,
    UNIQUE KEY unique_access (from_place_id, to_place_id, name)
);
```

## Classes PHP

### Classe `Access`

La classe `Access` gère toutes les opérations CRUD sur les accès :

#### Méthodes principales

- `save()` : Sauvegarde (création ou mise à jour)
- `delete()` : Suppression
- `findById($id)` : Récupération par ID
- `getFromPlace($place_id)` : Accès sortants d'un lieu
- `getToPlace($place_id)` : Accès entrants vers un lieu
- `getAllForPlace($place_id)` : Tous les accès d'un lieu
- `getAccessiblePlaces($place_id)` : Lieux accessibles depuis un lieu
- `existsBetween($from_id, $to_id, $name)` : Vérifier l'existence d'un accès

#### Méthodes d'affichage

- `getStatusText()` : Texte du statut
- `getStatusClass()` : Classe CSS pour l'affichage
- `getStatusIcon()` : Icône FontAwesome

## Interface Utilisateur

### Page de Gestion des Accès

**URL** : `manage_place_accesses.php?place_id={id}`

#### Fonctionnalités

- **Liste des accès** : Affichage de tous les accès du lieu
- **Création d'accès** : Modal pour créer un nouvel accès
- **Modification d'accès** : Modal pour modifier un accès existant
- **Suppression d'accès** : Confirmation et suppression
- **Gestion des pièges** : Interface pour configurer les détails des pièges

#### Interface de Création

1. **Nom de l'accès** (requis)
2. **Lieu de destination** (sélection dans une liste)
3. **Description** (optionnel)
4. **Propriétés** :
   - ☑️ Visible des joueurs
   - ☑️ Ouvert
   - ☐ Piégé
5. **Détails du piège** (si piégé) :
   - Description du piège
   - Difficulté (DD)
   - Dégâts

### Intégration dans la Vue des Lieux

#### Bouton de Gestion

Un bouton "Gérer les accès" est ajouté dans la page `view_place.php` pour les utilisateurs ayant les droits d'édition.

#### Section d'Affichage

Une nouvelle section "Accès disponibles" affiche :
- Liste des accès sortants
- Statut de chaque accès (visible, ouvert, piégé)
- Détails des pièges
- Liens vers les lieux de destination

## Utilisation

### Pour les Maîtres de Jeu

1. **Accéder à un lieu** via `view_place.php`
2. **Cliquer sur "Gérer les accès"** (bouton vert)
3. **Créer des accès** vers d'autres lieux
4. **Configurer les propriétés** (visibilité, ouverture, pièges)
5. **Tester les accès** en naviguant entre les lieux

### Pour les Joueurs

1. **Voir les accès disponibles** dans la section "Accès disponibles"
2. **Comprendre le statut** via les badges colorés
3. **Naviguer vers d'autres lieux** via les liens

## Exemples d'Utilisation

### Accès Normal
- **Nom** : "Porte principale"
- **Description** : "Une grande porte en bois"
- **Visible** : Oui
- **Ouvert** : Oui
- **Piégé** : Non

### Passage Secret
- **Nom** : "Passage secret"
- **Description** : "Un passage dissimulé derrière une tapisserie"
- **Visible** : Non
- **Ouvert** : Oui
- **Piégé** : Non

### Accès Piégé
- **Nom** : "Pont de pierre"
- **Description** : "Un pont ancien en pierre"
- **Visible** : Oui
- **Ouvert** : Oui
- **Piégé** : Oui
  - **Description du piège** : "Piège à flèches mortelles"
  - **Difficulté** : 15
  - **Dégâts** : "2d6+3"

## Tests

### Tests Unitaires

Le script `test_access_system.php` teste :
- Création de lieux et d'accès
- Récupération des accès
- Méthodes de statut
- Modification et suppression
- Nettoyage automatique

### Tests Fonctionnels

Le test `test_access_system_fixed.py` vérifie :
- Interface de gestion des accès
- Création d'accès via l'interface web
- Configuration des pièges
- Affichage des statuts

## Sécurité

- **Contrôle d'accès** : Seuls les DM propriétaires peuvent gérer les accès
- **Validation** : Vérification des données d'entrée
- **Contraintes** : Un lieu ne peut pas avoir d'accès vers lui-même
- **Unicité** : Pas de doublons d'accès entre les mêmes lieux

## Évolutions Futures

### Fonctionnalités Possibles

1. **Accès conditionnels** : Basés sur des conditions (niveau, objet, etc.)
2. **Accès temporaires** : Avec durée limitée
3. **Accès à sens unique** : Direction unique
4. **Accès avec coût** : Nécessitant des ressources
5. **Accès magiques** : Avec sorts ou objets magiques
6. **Position sur carte** : Affichage des accès sur les cartes
7. **Historique** : Traçabilité des passages
8. **Notifications** : Alertes lors de l'utilisation d'accès

### Améliorations Techniques

1. **API REST** : Pour l'intégration avec d'autres systèmes
2. **Cache** : Optimisation des performances
3. **Export/Import** : Sauvegarde des configurations
4. **Templates** : Modèles d'accès prédéfinis
5. **Statistiques** : Utilisation des accès

## Conclusion

Le système d'accès entre lieux est maintenant entièrement fonctionnel et permet aux maîtres de jeu de créer des connexions riches et interactives entre les différents lieux de leurs campagnes. L'interface est intuitive et les fonctionnalités couvrent les besoins principaux tout en restant extensibles pour de futures améliorations.

