"""
Données de test pour les tests fonctionnels
"""
import pytest
from faker import Faker

fake = Faker('fr_FR')

@pytest.fixture
def test_users():
    """Génère des utilisateurs de test"""
    return {
        'dm_user': {
            'username': 'test_dm',
            'email': 'dm@test.com',
            'password': 'TestPassword123!',
            'is_dm': True
        },
        'player_user': {
            'username': 'test_player',
            'email': 'player@test.com',
            'password': 'TestPassword123!',
            'is_dm': False
        },
        'random_user': {
            'username': fake.user_name(),
            'email': fake.email(),
            'password': 'TestPassword123!',
            'is_dm': False
        }
    }

@pytest.fixture
def test_characters():
    """Génère des personnages de test"""
    return {
        'warrior': {
            'name': 'Test Warrior',
            'race': 'Humain',
            'class': 'Guerrier',
            'level': 1,
            'background': 'Soldat'
        },
        'wizard': {
            'name': 'Test Wizard',
            'race': 'Elfe',
            'class': 'Magicien',
            'level': 3,
            'background': 'Sage'
        },
        'random_character': {
            'name': fake.name(),
            'race': fake.random_element(elements=('Humain', 'Elfe', 'Nain', 'Halfelin')),
            'class': fake.random_element(elements=('Guerrier', 'Magicien', 'Clerc', 'Voleur')),
            'level': fake.random_int(min=1, max=20),
            'background': fake.random_element(elements=('Soldat', 'Sage', 'Acolyte', 'Criminel'))
        }
    }

@pytest.fixture
def test_campaigns():
    """Génère des campagnes de test"""
    return {
        'main_campaign': {
            'name': 'Test Campaign',
            'description': 'Description de test pour la campagne',
            'is_public': False
        },
        'public_campaign': {
            'name': 'Public Test Campaign',
            'description': 'Campagne publique de test',
            'is_public': True
        },
        'random_campaign': {
            'name': fake.catch_phrase(),
            'description': fake.text(max_nb_chars=200),
            'is_public': fake.boolean()
        }
    }

@pytest.fixture
def test_monsters():
    """Génère des monstres de test"""
    return {
        'dragon': {
            'name': 'Dragon Rouge',
            'type': 'Dragon',
            'challenge_rating': 8,
            'hit_points': 200,
            'armor_class': 18
        },
        'goblin': {
            'name': 'Gobelin',
            'type': 'Humanoïde',
            'challenge_rating': 0.25,
            'hit_points': 7,
            'armor_class': 15
        },
        'random_monster': {
            'name': fake.word().title() + ' ' + fake.word().title(),
            'type': fake.random_element(elements=('Dragon', 'Humanoïde', 'Bête', 'Élémentaire')),
            'challenge_rating': fake.random_int(min=0, max=20),
            'hit_points': fake.random_int(min=1, max=500),
            'armor_class': fake.random_int(min=10, max=25)
        }
    }

@pytest.fixture
def test_magical_items():
    """Génère des objets magiques de test"""
    return {
        'sword': {
            'name': 'Épée Magique',
            'type': 'Arme',
            'rarity': 'Rare',
            'description': 'Une épée enchantée'
        },
        'potion': {
            'name': 'Potion de Soin',
            'type': 'Potion',
            'rarity': 'Commune',
            'description': 'Restaure des points de vie'
        },
        'random_item': {
            'name': fake.word().title() + ' ' + fake.word().title(),
            'type': fake.random_element(elements=('Arme', 'Armure', 'Potion', 'Anneau')),
            'rarity': fake.random_element(elements=('Commune', 'Peu Commune', 'Rare', 'Très Rare')),
            'description': fake.text(max_nb_chars=100)
        }
    }

@pytest.fixture
def test_poisons():
    """Génère des poisons de test"""
    return {
        'basic_poison': {
            'name': 'Poison de Base',
            'type': 'Poison',
            'dc': 10,
            'damage': '1d4 dégâts de poison'
        },
        'deadly_poison': {
            'name': 'Poison Mortel',
            'type': 'Poison',
            'dc': 15,
            'damage': '3d6 dégâts de poison'
        },
        'random_poison': {
            'name': fake.word().title() + ' ' + fake.word().title(),
            'type': 'Poison',
            'dc': fake.random_int(min=8, max=20),
            'damage': f"{fake.random_int(min=1, max=6)}d{fake.random_int(min=4, max=12)} dégâts de poison"
        }
    }
