# Système d'Attribution de Poisons

## Vue d'ensemble

Le système d'attribution de poisons permet au Maître du Jeu (MJ) d'attribuer des poisons aux personnages joueurs, PNJ et monstres lors d'une scène. Les poisons apparaissent ensuite dans la section "Équipement et Trésor" des feuilles de personnage et de monstre.

## Fonctionnalités

### 1. Attribution de Poisons
- **Interface** : Bouton "Poison" dans `view_scene.php`
- **Recherche** : Modal de recherche avec autocomplétion
- **Cibles** : Personnages joueurs, PNJ, monstres
- **Stockage** : Utilise les tables d'équipement existantes (`character_equipment`, `npc_equipment`, `monster_equipment`)

### 2. Affichage des Poisons
- **Personnages** : Section "Poisons" dans `view_character.php`
- **Monstres** : Section "Poisons" dans `view_monster_sheet.php`
- **Design** : Cartes avec bordure rouge et icône de crâne
- **Informations** : Nom, type, description, source, notes, date d'obtention

### 3. Transfert de Poisons
- **Bouton** : "Transférer à" sur chaque poison
- **Modal** : Sélection du nouveau propriétaire
- **Cibles** : Personnages, PNJ, monstres
- **Historique** : Mise à jour de `obtained_from`

## Structure de la Base de Données

### Table `poisons`
```sql
CREATE TABLE poisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,        -- ID original du CSV
    nom VARCHAR(255) NOT NULL,        -- Nom du poison
    cle VARCHAR(255),                 -- Clé de référence
    description TEXT,                 -- Description complète
    type VARCHAR(255),                -- Type et rareté
    source VARCHAR(255),              -- Source de référence
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_nom (nom),
    INDEX idx_type (type),
    FULLTEXT idx_search (nom, cle, description, type)
);
```

### Tables d'Équipement
Les poisons sont stockés dans les tables d'équipement existantes :
- `character_equipment` : Poisons des personnages joueurs
- `npc_equipment` : Poisons des PNJ
- `monster_equipment` : Poisons des monstres

Le champ `magical_item_id` contient le `csv_id` du poison.

## Fichiers Modifiés

### 1. `view_scene.php`
- **Traitement POST** : `action=assign_poison`
- **Modal de recherche** : `#poisonSearchModal`
- **Modal d'attribution** : `#assignPoisonModal`
- **JavaScript** : Recherche AJAX et gestion des modals

### 2. `view_character.php`
- **Requête** : Récupération des poisons du personnage
- **Affichage** : Section "Poisons" avec cartes stylisées
- **Transfert** : Bouton de transfert intégré

### 3. `view_monster_sheet.php`
- **Requête** : Récupération des poisons du monstre
- **Affichage** : Section "Poisons" avec cartes stylisées
- **Transfert** : Bouton de transfert intégré

### 4. `search_poisons.php` (Nouveau)
- **Recherche AJAX** : Recherche fulltext dans la table `poisons`
- **Sécurité** : Vérification des permissions et nettoyage des données
- **Performance** : Limite de 20 résultats, tri par pertinence

## Utilisation

### Pour le MJ
1. Aller sur la page d'une scène
2. Cliquer sur le bouton "Poison"
3. Rechercher un poison dans le modal
4. Cliquer sur "Attribuer ce poison"
5. Sélectionner le destinataire (personnage, PNJ, monstre)
6. Ajouter des notes optionnelles
7. Confirmer l'attribution

### Pour les Joueurs
1. Aller sur la feuille de personnage
2. Voir la section "Équipement et Trésor"
3. Consulter la sous-section "Poisons"
4. Utiliser le bouton "Transférer à" si autorisé

## Sécurité

### Contrôles d'Accès
- **Attribution** : Seul le MJ de la scène peut attribuer des poisons
- **Affichage** : Les poisons sont visibles selon les permissions existantes
- **Transfert** : Seuls les propriétaires et MJ peuvent transférer

### Validation des Données
- **Recherche** : Requêtes préparées avec échappement
- **Attribution** : Vérification de l'existence des cibles
- **Affichage** : `htmlspecialchars()` sur toutes les données

## Design

### Cartes de Poisons
- **Bordure** : Rouge (`border-danger`)
- **En-tête** : Fond rouge avec texte blanc
- **Icône** : Crâne avec os croisés (`fas fa-skull-crossbones`)
- **Couleur** : Thème rouge pour différencier des objets magiques

### Responsive
- **Grille** : `col-md-6` pour 2 colonnes sur desktop
- **Cartes** : `h-100` pour hauteur uniforme
- **Mobile** : Adaptation automatique Bootstrap

## Tests

### Script de Test
Le système a été testé avec `test_poison_system.php` qui vérifie :
1. Existence de la table `poisons` et des données
2. Existence des tables d'équipement
3. Attribution d'un poison à un personnage
4. Récupération et affichage du poison
5. Nettoyage des données de test

### Résultats
✅ Tous les tests passent avec succès
✅ 100 poisons disponibles dans la base
✅ Attribution et affichage fonctionnels
✅ Transfert intégré au système existant

## Intégration

Le système de poisons s'intègre parfaitement avec :
- **Système d'objets magiques** : Même structure de base
- **Système de transfert** : Modal et logique partagés
- **Gestion des permissions** : Utilise les contrôles existants
- **Interface utilisateur** : Cohérent avec le design existant

## Maintenance

### Ajout de Nouveaux Poisons
1. Importer les données dans la table `poisons`
2. Utiliser `update_database.php` pour l'import automatique
3. Les poisons sont immédiatement disponibles pour attribution

### Modifications
- **Interface** : Modifier les modals dans `view_scene.php`
- **Affichage** : Ajuster les cartes dans `view_character.php` et `view_monster_sheet.php`
- **Recherche** : Modifier `search_poisons.php` pour d'autres critères

Le système est maintenant pleinement fonctionnel et prêt à être utilisé ! 🧪☠️





