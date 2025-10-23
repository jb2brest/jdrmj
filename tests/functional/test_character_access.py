#!/usr/bin/env python3
"""
Test d'accès à view_character.php avec Selenium
"""
import pytest
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
import os

class TestCharacterAccess:
    """Test d'accès à view_character.php"""
    
    @pytest.fixture(scope="class")
    def driver(self):
        """Configuration du driver Selenium"""
        chrome_options = Options()
        
        # Mode headless si variable d'environnement définie
        if os.getenv('HEADLESS', 'false').lower() == 'true':
            chrome_options.add_argument("--headless")
        
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=1920,1080")
        chrome_options.add_argument("--disable-web-security")
        chrome_options.add_argument("--allow-running-insecure-content")
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option('useAutomationExtension', False)
        
        # Utiliser Chrome 131 et ChromeDriver 131
        chrome_options.binary_location = "./chrome"
        service = Service('./chromedriver')
        driver = webdriver.Chrome(service=service, options=chrome_options)
        
        yield driver
        driver.quit()
    
    def test_character_access_flow(self, driver):
        """Test complet d'accès à view_character.php"""
        print("\n=== Test d'accès à view_character.php ===")
        
        # 1. Aller à la page de connexion
        print("1. Accès à la page de connexion...")
        driver.get("http://localhost/jdrmj/login.php")
        time.sleep(2)
        
        # Vérifier si on est sur la page de connexion
        assert "Connexion" in driver.title, f"Page inattendue: {driver.title}"
        print("✓ Page de connexion chargée")
        
        # 2. Se connecter avec Jean
        print("2. Connexion avec Jean...")
        username_field = driver.find_element(By.NAME, "username")
        password_field = driver.find_element(By.NAME, "password")
        
        username_field.send_keys("Jean")
        password_field.send_keys("marion")
        
        # Cliquer sur le bouton de connexion
        login_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        login_button.click()
        time.sleep(3)
        
        # Vérifier la connexion
        print(f"Page après connexion: {driver.title}")
        assert "Connexion" not in driver.title, "Échec de la connexion"
        print("✓ Connexion réussie")
        
        # 3. Essayer d'accéder à view_character.php?id=81
        print("3. Accès à view_character.php?id=81...")
        driver.get("http://localhost/jdrmj/view_character.php?id=81")
        time.sleep(3)
        
        # Analyser la page
        print(f"Titre de la page: {driver.title}")
        print(f"URL actuelle: {driver.current_url}")
        
        # Vérifier le contenu
        page_source = driver.page_source
        
        if "Connexion" in driver.title:
            print("❌ Redirection vers la page de connexion")
            assert False, "Session non maintenue - redirection vers connexion"
        elif "characters.php" in driver.current_url:
            print("❌ Redirection vers characters.php")
            assert False, "Permissions insuffisantes - redirection vers characters.php"
        elif "Cassegrin" in page_source:
            print("✓ Page de personnage chargée avec succès")
            assert True
        else:
            print("? Page inattendue")
            print(f"Contenu de la page: {page_source[:500]}...")
            assert False, "Page inattendue"
        
        # 4. Tester les liens depuis view_place.php
        print("\n4. Test depuis view_place.php...")
        driver.get("http://localhost/jdrmj/view_place.php?id=154")
        time.sleep(3)
        
        print(f"Page view_place: {driver.title}")
        
        # Chercher les liens "Voir la fiche"
        try:
            view_links = driver.find_elements(By.CSS_SELECTOR, "a[title='Voir la fiche']")
            print(f"Liens 'Voir la fiche' trouvés: {len(view_links)}")
            
            if view_links:
                print("Test du premier lien...")
                view_links[0].click()
                time.sleep(3)
                print(f"Page après clic: {driver.title}")
                print(f"URL: {driver.current_url}")
            else:
                print("Aucun lien 'Voir la fiche' trouvé")
                
        except Exception as e:
            print(f"Erreur lors de la recherche des liens: {e}")

if __name__ == "__main__":
    # Exécution directe du test
    import sys
    sys.path.append(os.path.dirname(os.path.abspath(__file__)))
    
    # Créer une instance du test
    test_instance = TestCharacterAccess()
    
    # Configurer le driver
    driver = None
    try:
        chrome_options = Options()
        chrome_options.add_argument("--headless")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=1920,1080")
        chrome_options.add_argument("--disable-web-security")
        chrome_options.add_argument("--allow-running-insecure-content")
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option('useAutomationExtension', False)
        
        chrome_options.binary_location = "./chrome"
        service = Service('./chromedriver')
        driver = webdriver.Chrome(service=service, options=chrome_options)
        
        # Exécuter le test
        test_instance.test_character_access_flow(driver)
        
    except Exception as e:
        print(f"Erreur: {e}")
    finally:
        if driver:
            driver.quit()
