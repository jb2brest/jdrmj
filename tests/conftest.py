"""
Configuration globale pour les tests Selenium
"""
import pytest
import os
import time
import pymysql
import subprocess
import json
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By

# Import optionnel de webdriver_manager
try:
    from webdriver_manager.chrome import ChromeDriverManager
    WEBDRIVER_MANAGER_AVAILABLE = True
except ImportError:
    WEBDRIVER_MANAGER_AVAILABLE = False
    print("⚠️ webdriver_manager non disponible - ChromeDriver doit être installé manuellement")

# from pytest_html import html  # Non disponible dans cette version

# Configuration de l'URL de base de l'application
BASE_URL = os.getenv('TEST_BASE_URL', 'http://localhost/jdrmj')

# Liste globale pour tracker les utilisateurs de test créés
created_test_users = []

def get_database_config():
    """Récupère la configuration de la base de données de test"""
    try:
        # Essayer d'importer la configuration PHP
        result = subprocess.run([
            'php', '-r', 
            'include "config/database.test.php"; $config = include "config/database.test.php"; echo json_encode($config);'
        ], capture_output=True, text=True, cwd=os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
        
        if result.returncode == 0:
            config = json.loads(result.stdout)
            return config
    except Exception as e:
        print(f"Erreur lors de la lecture de la config PHP: {e}")
    
    # Configuration par défaut si la lecture PHP échoue
    return {
        'host': 'localhost',
        'dbname': 'u839591438_jdrmj',
        'username': 'u839591438_jdrmj',
        'password': 'M8jbsYJUj6FE$;C',
        'charset': 'utf8mb4'
    }

def cleanup_test_user_from_db(user_data):
    """Nettoie un utilisateur de test spécifique de la base de données"""
    if not user_data or not user_data.get('username'):
        return
    
    try:
        config = get_database_config()
        connection = pymysql.connect(
            host=config['host'],
            user=config['username'],
            password=config['password'],
            database=config['dbname'],
            charset=config.get('charset', 'utf8mb4'),
            autocommit=False
        )
        
        cursor = connection.cursor()
        
        # Trouver l'utilisateur par username ou email
        cursor.execute("SELECT id FROM users WHERE username = %s OR email = %s", 
                      (user_data['username'], user_data.get('email', '')))
        result = cursor.fetchone()
        
        if result:
            user_id = result[0]
            
            # Supprimer les données liées
            cursor.execute("DELETE FROM characters WHERE user_id = %s", (user_id,))
            cursor.execute("DELETE FROM campaigns WHERE dm_id = %s", (user_id,))
            cursor.execute("DELETE FROM campaign_sessions WHERE dm_id = %s", (user_id,))
            cursor.execute("DELETE FROM dice_rolls WHERE user_id = %s", (user_id,))
            cursor.execute("DELETE FROM scene_tokens WHERE user_id = %s", (user_id,))
            cursor.execute("DELETE FROM place_objects WHERE user_id = %s", (user_id,))
            cursor.execute("DELETE FROM monsters WHERE created_by = %s", (user_id,))
            cursor.execute("DELETE FROM magical_items WHERE created_by = %s", (user_id,))
            cursor.execute("DELETE FROM poisons WHERE created_by = %s", (user_id,))
            
            # Supprimer l'utilisateur
            cursor.execute("DELETE FROM users WHERE id = %s", (user_id,))
            
            connection.commit()
            print(f"✅ Utilisateur de test {user_data['username']} nettoyé de la base de données")
        
        connection.close()
        
    except Exception as e:
        print(f"⚠️ Erreur lors du nettoyage de l'utilisateur {user_data.get('username', 'inconnu')}: {e}")

@pytest.fixture(scope="session")
def browser_config():
    """Configuration du navigateur pour tous les tests"""
    return {
        'headless': os.getenv('HEADLESS', 'false').lower() == 'true',
        'window_size': (1920, 1080),
        'implicit_wait': 2
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
    chrome_options.add_argument('--ignore-certificate-errors')
    chrome_options.add_argument('--ignore-ssl-errors')
    chrome_options.add_argument('--ignore-certificate-errors-spki-list')
    chrome_options.add_argument('--disable-features=VizDisplayCompositor')
    chrome_options.add_argument('--remote-debugging-port=9222')
    
    # Utiliser webdriver_manager si disponible, sinon chemin fixe
    if WEBDRIVER_MANAGER_AVAILABLE:
        try:
            service = Service(ChromeDriverManager().install())
        except Exception as e:
            print(f"⚠️ Erreur avec webdriver_manager: {e}")
            print("🔄 Utilisation du chemin fixe pour ChromeDriver")
            service = Service("/usr/bin/chromedriver")
    else:
        service = Service("/usr/bin/chromedriver")
    
    driver = webdriver.Chrome(service=service, options=chrome_options)
    driver.implicitly_wait(browser_config['implicit_wait'])
    driver.set_window_size(*browser_config['window_size'])
    
    yield driver
    
    driver.quit()

@pytest.fixture(scope="function")
def wait(driver):
    """WebDriverWait pour les attentes explicites"""
    return WebDriverWait(driver, 2)  # Timeout réduit à 2 secondes

@pytest.fixture(scope="function")
def app_url():
    """URL de base de l'application"""
    return BASE_URL

@pytest.fixture(scope="function")
def test_user():
    """Utilisateur de test par défaut avec nettoyage automatique"""
    # Générer un nom d'utilisateur unique avec timestamp
    timestamp = str(int(time.time()))
    user_data = {
        'username': f'test_user_{timestamp}',
        'email': f'test_{timestamp}@example.com',
        'password': 'TestPassword123!',
        'is_dm': True
    }
    
    # Ajouter à la liste des utilisateurs créés
    created_test_users.append(user_data)
    
    yield user_data
    
    # Nettoyage automatique après le test
    cleanup_test_user_from_db(user_data)
    if user_data in created_test_users:
        created_test_users.remove(user_data)

@pytest.fixture(scope="function")
def test_admin():
    """Utilisateur admin de test avec nettoyage automatique"""
    # Générer un nom d'utilisateur unique avec timestamp
    timestamp = str(int(time.time()))
    user_data = {
        'username': f'test_admin_{timestamp}',
        'email': f'admin_{timestamp}@test.com',
        'password': 'TestPassword123!',
        'is_dm': True,
        'role': 'admin'
    }
    
    # Ajouter à la liste des utilisateurs créés
    created_test_users.append(user_data)
    
    yield user_data
    
    # Nettoyage automatique après le test
    cleanup_test_user_from_db(user_data)
    if user_data in created_test_users:
        created_test_users.remove(user_data)

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

@pytest.hookimpl(trylast=True)
def pytest_sessionfinish(session, exitstatus):
    """Nettoyage final de tous les utilisateurs de test créés"""
    print(f"\n🧹 Nettoyage final: {len(created_test_users)} utilisateur(s) de test à nettoyer")
    
    for user_data in created_test_users.copy():
        cleanup_test_user_from_db(user_data)
    
    created_test_users.clear()
    print("✅ Nettoyage final terminé")

@pytest.hookimpl(tryfirst=True)
def pytest_runtest_teardown(item, nextitem):
    """Nettoyage après chaque test"""
    # Le nettoyage individuel est géré par les fixtures
    pass
