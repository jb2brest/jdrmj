"""
Configuration globale pour les tests Selenium
"""
import pytest
import os
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import time
# from pytest_html import html  # Non disponible dans cette version

# Configuration de l'URL de base de l'application
BASE_URL = os.getenv('TEST_BASE_URL', 'http://localhost/jdrmj')

@pytest.fixture(scope="session")
def browser_config():
    """Configuration du navigateur pour tous les tests"""
    return {
        'headless': os.getenv('HEADLESS', 'false').lower() == 'true',
        'window_size': (1920, 1080),
        'implicit_wait': 10
    }

@pytest.fixture(scope="function")
def driver(browser_config):
    """Création d'une instance de navigateur pour chaque test"""
    chrome_options = Options()
    
    if browser_config['headless']:
        chrome_options.add_argument('--headless')
    
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--window-size=1920,1080')
    chrome_options.add_argument('--disable-extensions')
    chrome_options.add_argument('--disable-logging')
    chrome_options.add_argument('--disable-web-security')
    chrome_options.add_argument('--allow-running-insecure-content')
    
    service = Service("/usr/local/bin/chromedriver")
    driver = webdriver.Chrome(service=service, options=chrome_options)
    driver.implicitly_wait(browser_config['implicit_wait'])
    driver.set_window_size(*browser_config['window_size'])
    
    yield driver
    
    driver.quit()

@pytest.fixture(scope="function")
def wait(driver):
    """WebDriverWait pour les attentes explicites"""
    return WebDriverWait(driver, 15)  # Augmenté à 15 secondes

@pytest.fixture(scope="function")
def app_url():
    """URL de base de l'application"""
    return BASE_URL

@pytest.fixture(scope="function")
def test_user():
    """Utilisateur de test par défaut"""
    return {
        'username': 'test_user',
        'email': 'test@example.com',
        'password': 'TestPassword123!',
        'is_dm': True
    }

@pytest.fixture(scope="function")
def test_character():
    """Personnage de test par défaut"""
    return {
        'name': 'Test Character',
        'race': 'Humain',
        'class': 'Guerrier',
        'level': 1,
        'background': 'Soldat'
    }

@pytest.fixture(scope="function")
def test_campaigns():
    """Campagnes de test"""
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
        }
    }

@pytest.fixture(scope="function")
def test_monsters():
    """Monstres de test"""
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
        }
    }

@pytest.fixture(scope="function")
def test_magical_items():
    """Objets magiques de test"""
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
        }
    }

@pytest.fixture(scope="function")
def test_poisons():
    """Poisons de test"""
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
        }
    }

def pytest_configure(config):
    """Configuration globale de pytest"""
    # Créer le répertoire de rapports s'il n'existe pas
    os.makedirs('tests/reports', exist_ok=True)

# Hooks HTML désactivés pour compatibilité
# def pytest_html_report_title(report):
#     """Titre du rapport HTML"""
#     report.title = "Tests Fonctionnels JDR 4 MJ"

# def pytest_html_results_table_header(cells):
#     """En-têtes du tableau de résultats HTML"""
#     cells.insert(2, html.th('Description'))
#     cells.insert(3, html.th('Screenshot'))

# def pytest_html_results_table_row(report, cells):
#     """Lignes du tableau de résultats HTML"""
#     cells.insert(2, html.td(report.description))
#     cells.insert(3, html.td(html.img(src=report.screenshot) if hasattr(report, 'screenshot') else ''))

@pytest.hookimpl(tryfirst=True)
def pytest_runtest_makereport(item, call):
    """Capture des screenshots en cas d'échec"""
    if call.when == "call" and call.excinfo is not None:
        driver = item.funcargs.get('driver')
        if driver:
            screenshot_path = f"tests/reports/screenshot_{item.name}_{call.when}.png"
            driver.save_screenshot(screenshot_path)
            # Ajouter le screenshot au rapport
            if hasattr(item, 'user_properties'):
                item.user_properties.append(("screenshot", screenshot_path))
