# Système d'Expérience et de Niveaux

## Vue d'ensemble

Le système d'expérience a été intégré avec succès dans l'application JDR 4 MJ. Les personnages commencent maintenant avec 0 point d'expérience et leur niveau est calculé automatiquement en fonction de leurs points d'expérience.

## Fonctionnalités implémentées

### 1. Table de données d'expérience
- **Fichier**: `database/add_experience_table.sql`
- **Table**: `experience_levels`
- **Contenu**: 20 niveaux avec les seuils d'expérience et bonus de maîtrise correspondants
- **Source**: Données importées depuis `aidednddata/Aidedd - experience.csv`

### 2. Fonctions de calcul automatique
- **Fichier**: `includes/functions.php`
- **Fonctions ajoutées**:
  - `calculateLevelFromExperience($experiencePoints)` - Calcule le niveau basé sur l'XP
  - `calculateProficiencyBonusFromExperience($experiencePoints)` - Calcule le bonus de maîtrise
  - `getExperienceRequiredForNextLevel($currentLevel)` - XP requis pour le niveau suivant
  - `updateCharacterLevelFromExperience($characterId)` - Met à jour un personnage

### 3. Interface utilisateur mise à jour

#### Création de personnage (`create_character.php`)
- Remplacement du champ "Niveau" par "Points d'Expérience"
- Calcul automatique du niveau en temps réel (JavaScript)
- Affichage du niveau calculé
- Validation des points d'expérience (minimum 0)

#### Édition de personnage (`edit_character.php`)
- Interface similaire à la création
- Calcul automatique du niveau et bonus de maîtrise
- Mise à jour automatique lors de la modification de l'XP

#### Gestion de l'expérience (`manage_experience.php`)
- Page dédiée pour ajouter de l'expérience aux personnages
- Affichage des personnages avec barres de progression XP
- Tableau des seuils d'expérience
- Interface intuitive pour les MJ

### 4. Scripts d'importation et de mise à jour
- **`import_experience_data.php`**: Importe les données du CSV en base
- **`update_characters_experience.php`**: Met à jour tous les personnages existants

## Seuils d'expérience (D&D 5e)

| Niveau | XP Requis | Bonus Maîtrise | XP pour Niveau Suivant |
|--------|-----------|----------------|------------------------|
| 1      | 0         | +2             | 300                    |
| 2      | 300       | +2             | 600                    |
| 3      | 900       | +2             | 1,800                  |
| 4      | 2,700     | +2             | 3,800                  |
| 5      | 6,500     | +3             | 7,500                  |
| 6      | 14,000    | +3             | 9,000                  |
| 7      | 23,000    | +3             | 11,000                 |
| 8      | 34,000    | +3             | 14,000                 |
| 9      | 48,000    | +4             | 16,000                 |
| 10     | 64,000    | +4             | 21,000                 |
| 11     | 85,000    | +4             | 15,000                 |
| 12     | 100,000   | +4             | 20,000                 |
| 13     | 120,000   | +5             | 20,000                 |
| 14     | 140,000   | +5             | 25,000                 |
| 15     | 165,000   | +5             | 30,000                 |
| 16     | 195,000   | +5             | 30,000                 |
| 17     | 225,000   | +6             | 40,000                 |
| 18     | 265,000   | +6             | 40,000                 |
| 19     | 305,000   | +6             | 50,000                 |
| 20     | 355,000   | +6             | Niveau maximum         |

## Utilisation

### Pour les joueurs
1. **Créer un personnage**: Saisir les points d'expérience (0 par défaut)
2. **Modifier un personnage**: Changer les points d'expérience, le niveau se met à jour automatiquement
3. **Voir la progression**: Utiliser la page "Gestion Expérience" pour suivre l'évolution

### Pour les MJ
1. **Attribuer de l'expérience**: Utiliser la page "Gestion Expérience"
2. **Suivre la progression**: Voir les barres de progression et les niveaux des personnages
3. **Consulter les seuils**: Tableau de référence des niveaux d'expérience

## Avantages du système

1. **Automatisation**: Plus besoin de calculer manuellement les niveaux
2. **Cohérence**: Respect des règles D&D 5e officielles
3. **Flexibilité**: Facile d'ajouter de l'expérience aux personnages
4. **Interface intuitive**: Calcul en temps réel et barres de progression
5. **Compatibilité**: Fonctionne avec tous les personnages existants

## Fichiers modifiés

- `includes/functions.php` - Nouvelles fonctions de calcul
- `create_character.php` - Interface de création mise à jour
- `edit_character.php` - Interface d'édition mise à jour
- `database/schema.sql` - Déjà contient le champ `experience_points`

## Fichiers créés

- `database/add_experience_table.sql` - Structure de la table d'expérience
- `import_experience_data.php` - Script d'importation
- `update_characters_experience.php` - Script de mise à jour
- `manage_experience.php` - Page de gestion de l'expérience
- `EXPERIENCE_SYSTEM_README.md` - Cette documentation

Le système est maintenant entièrement fonctionnel et prêt à être utilisé !

