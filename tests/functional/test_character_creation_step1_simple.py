"""
Test simple pour l'étape 1 de création de personnage
"""
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time

class TestCharacterCreationStep1Simple:
    """Test simple pour l'étape 1 de création de personnage"""
    
    def test_character_creation_step1_warrior_selection(self, driver, wait, app_url, test_user):
        """Test simple: sélectionner la classe Guerrier à l'étape 1"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de création de personnage étape 1
        driver.get(f"{app_url}/character_create_step1.php")
        
        # Vérifier que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        assert "Étape 1" in driver.title or "Choisissez votre classe" in driver.page_source
        
        # Sélectionner la classe Guerrier (ID 6 d'après le diagnostic)
        warrior_card = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-class-id='6']")))
        warrior_card.click()
        
        # Vérifier que la classe est sélectionnée
        assert "selected" in warrior_card.get_attribute("class")
        
        # Cliquer sur continuer (utiliser JavaScript pour éviter les problèmes d'interception)
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", continue_btn)
        
        # Vérifier la redirection vers l'étape 2
        wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
        assert "Étape 2" in driver.title or "race" in driver.page_source.lower()
        
        print("✅ Test de sélection de classe Guerrier réussi !")
    
    def test_character_creation_step1_wizard_selection(self, driver, wait, app_url, test_user):
        """Test simple: sélectionner la classe Magicien à l'étape 1"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de création de personnage étape 1
        driver.get(f"{app_url}/character_create_step1.php")
        
        # Vérifier que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        assert "Étape 1" in driver.title or "Choisissez votre classe" in driver.page_source
        
        # Sélectionner la classe Magicien (ID 7 d'après le diagnostic)
        wizard_card = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-class-id='7']")))
        driver.execute_script("arguments[0].click();", wizard_card)
        
        # Vérifier que la classe est sélectionnée
        assert "selected" in wizard_card.get_attribute("class")
        
        # Cliquer sur continuer (utiliser JavaScript pour éviter les problèmes d'interception)
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", continue_btn)
        
        # Vérifier la redirection vers l'étape 2
        wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
        assert "Étape 2" in driver.title or "race" in driver.page_source.lower()
        
        print("✅ Test de sélection de classe Magicien réussi !")
    
    def _login_user(self, driver, wait, app_url, test_user):
        """Helper method pour se connecter"""
        driver.get(f"{app_url}/login.php")
        
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        password_field = driver.find_element(By.NAME, "password")
        
        username_field.send_keys(test_user['username'])
        password_field.send_keys(test_user['password'])
        
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_button.click()
        
        # Attendre la redirection
        wait.until(lambda driver: "index.php" in driver.current_url or "characters.php" in driver.current_url)
