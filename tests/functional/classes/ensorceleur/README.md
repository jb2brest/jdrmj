# Tests de la classe Ensorceleur

Ce répertoire contient les tests fonctionnels pour la classe **Ensorceleur** (Sorcerer) dans le système de création de personnages D&D.

## Structure des tests

### Fichier principal
- `test_sorcerer_class.py` - Tests complets pour la classe Ensorceleur

### Tests inclus

#### 1. **test_sorcerer_character_creation**
- Test de création d'un personnage ensorceleur
- Vérification de la sélection de classe
- Validation de la redirection vers l'étape suivante

#### 2. **test_sorcerer_race_selection**
- Test de sélection de race pour un ensorceleur
- Races testées : Humain, Elfe, Nain
- Validation de la progression dans le workflow

#### 3. **test_sorcerer_origin_selection**
- Test de sélection d'origine magique
- Vérification des options d'origine disponibles
- Validation de la progression vers l'étape suivante

#### 4. **test_sorcerer_starting_equipment**
- Test de sélection d'équipement de départ
- Création complète d'un ensorceleur
- Validation du workflow complet

#### 5. **test_sorcerer_character_view**
- Test de visualisation d'un personnage ensorceleur créé
- Vérification des éléments spécifiques à l'ensorceleur
- Validation des capacités affichées

#### 6. **test_sorcerer_spell_management**
- Test de gestion des sorts pour un ensorceleur
- Accès au grimoire
- Vérification des sorts disponibles

#### 7. **test_sorcerer_level_progression**
- Test de progression par niveau
- Vérification des caractéristiques niveau 1
- Test d'évolution vers les niveaux 2 et 3

#### 8. **test_sorcerer_specific_abilities**
- Test des capacités spécifiques à l'ensorceleur
- Vérification des capacités uniques
- Validation de l'affichage des équipements

#### 9. **test_sorcerer_equipment_management**
- Test de gestion d'équipement
- Vérification des équipements typiques
- Test des boutons d'équipement/déséquipement

#### 10. **test_sorcerer_complete_creation_and_evolution**
- Test complet de création et évolution
- Workflow complet de création
- Vérification de l'accessibilité des pages

## Caractéristiques spécifiques à l'ensorceleur

### Capacités testées
- **Points de Sorcellerie** - Système de magie unique
- **Origine** - Source de la magie innée
- **Charisme** - Caractéristique principale
- **Magie** - Système de sorts
- **Métamagie** - Modification des sorts

### Équipements typiques
- Baguette
- Bâton
- Dague
- Sac à composants
- Robe
- Chapeau

### Races recommandées
- **Humain** - Polyvalent, bonus aux caractéristiques
- **Elfe** - Bonus à la dextérité, résistance au charme
- **Nain** - Bonus à la constitution, résistance au poison

### Historiques appropriés
- **Acolyte** - Connexion avec le divin
- **Sage** - Connaissance des arcanes
- **Noble** - Éducation raffinée

## Progression par niveau

### Niveau 1
- Sorts connus : 2
- Emplacements de sorts : 2 niveau 1
- Points de Sorcellerie : 2
- Capacités : Origine

### Niveau 2
- Sorts connus : 3
- Emplacements de sorts : 3 niveau 1
- Points de Sorcellerie : 2
- Capacités : Récupération d'emplacements

### Niveau 3
- Sorts connus : 4
- Emplacements de sorts : 4 niveau 1, 2 niveau 2
- Points de Sorcellerie : 3
- Capacités : Métamagie

## Fixture de test

Le fixture `test_sorcerer` est défini dans `conftest.py` :

```python
@pytest.fixture(scope="function")
def test_sorcerer():
    """Personnage ensorceleur de test"""
    return {
        'name': 'Test Sorcerer',
        'race': 'Humain',
        'class': 'Ensorceleur',
        'level': 1,
        'background': 'Acolyte',
        'origin': 'Origine Magique',
        'hit_points': 6,
        'armor_class': 12,
        'speed': 30,
        'strength': 8,
        'dexterity': 14,
        'constitution': 13,
        'intelligence': 12,
        'wisdom': 10,
        'charisma': 15
    }
```

## Exécution des tests

### Tous les tests de l'ensorceleur
```bash
python3 -m pytest tests/functional/classes/ensorceleur/test_sorcerer_class.py -v
```

### Test spécifique
```bash
python3 -m pytest tests/functional/classes/ensorceleur/test_sorcerer_class.py::TestSorcererClass::test_sorcerer_character_creation -v -s
```

### Avec rapport détaillé
```bash
python3 -m pytest tests/functional/classes/ensorceleur/test_sorcerer_class.py -v -s --tb=short
```

## Notes importantes

1. **Sélecteurs adaptés** : Les tests utilisent des sélecteurs robustes pour gérer les variations d'interface
2. **Gestion des timeouts** : Les tests incluent des attentes explicites pour les éléments dynamiques
3. **Nettoyage automatique** : Les utilisateurs de test sont automatiquement nettoyés après chaque test
4. **Rapports JSON** : Chaque test génère un rapport détaillé avec les étapes capturées
5. **Compatibilité** : Les tests sont compatibles avec l'interface existante du système

## Différences avec le Magicien

L'ensorceleur se distingue du magicien par :
- **Magie innée** vs magie apprise
- **Points de Sorcellerie** vs système de grimoire
- **Charisme** comme caractéristique principale vs Intelligence
- **Origine** vs spécialisation d'école
- **Métamagie** comme capacité unique
