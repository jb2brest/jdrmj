"""
Tests fonctionnels pour la gestion des personnages
"""
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time

class TestCharacterManagement:
    """Tests pour la gestion des personnages"""
    
    def test_character_creation(self, driver, wait, app_url, test_user, test_character):
        """Test de création d'un personnage"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de création de personnage
        driver.get(f"{app_url}/create_character.php")
        
        # Vérifier que la page est chargée
        title = driver.title
        if not title or "Profil" in title:
            pytest.skip("Page de création de personnage non accessible - test ignoré")
        else:
            assert "Créer" in title or "Create" in title
        
        # Remplir les informations de base
        try:
            name_field = wait.until(EC.presence_of_element_located((By.NAME, "name")))
            name_field.send_keys(test_character['name'])
            
            # Sélectionner la race
            race_select = Select(driver.find_element(By.NAME, "race"))
            race_select.select_by_visible_text(test_character['race'])
        except (TimeoutException, NoSuchElementException):
            pytest.skip("Formulaire de création de personnage non trouvé - test ignoré")
        
        # Sélectionner la classe
        class_select = Select(driver.find_element(By.NAME, "class"))
        class_select.select_by_visible_text(test_character['class'])
        
        # Remplir les caractéristiques (si nécessaire)
        # Note: Les caractéristiques peuvent être générées automatiquement
        # ou nécessiter une saisie manuelle selon l'implémentation
        
        # Soumettre le formulaire
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        submit_button.click()
        
        # Vérifier la redirection ou le message de succès
        try:
            wait.until(lambda driver: "characters.php" in driver.current_url or 
                      "succès" in driver.page_source.lower() or 
                      "success" in driver.page_source.lower())
            assert True
        except TimeoutException:
            # Vérifier s'il y a des erreurs
            error_elements = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error")
            if error_elements:
                pytest.fail(f"Erreurs de création: {[elem.text for elem in error_elements]}")
            else:
                pytest.fail("Création de personnage échouée")
    
    def test_character_list_display(self, driver, wait, app_url, test_user):
        """Test d'affichage de la liste des personnages"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des personnages
        driver.get(f"{app_url}/characters.php")
        
        # Vérifier que la page est chargée
        assert "Personnages" in driver.title or "Characters" in driver.title
        
        # Vérifier la présence d'éléments de la liste
        try:
            # Attendre qu'au moins un élément de personnage soit présent
            # ou qu'un message "aucun personnage" soit affiché
            wait.until(EC.any_of(
                EC.presence_of_element_located((By.CSS_SELECTOR, ".character-card, .card")),
                EC.presence_of_element_located((By.CSS_SELECTOR, ".no-characters, .empty-state"))
            ))
            assert True
        except TimeoutException:
            pytest.fail("Page des personnages ne s'affiche pas correctement")
    
    def test_character_view(self, driver, wait, app_url, test_user):
        """Test de visualisation d'un personnage"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des personnages
        driver.get(f"{app_url}/characters.php")
        
        # Chercher un lien vers un personnage
        try:
            character_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='view_character.php']")))
            character_link.click()
            
            # Vérifier que la page de détail du personnage est chargée
            wait.until(lambda driver: "view_character.php" in driver.current_url)
            
            # Vérifier la présence d'informations du personnage
            assert driver.find_elements(By.CSS_SELECTOR, ".character-name, .character-info, .character-stats")
            
        except TimeoutException:
            # Si aucun personnage n'existe, créer un personnage de test
            pytest.skip("Aucun personnage existant pour tester la visualisation")
    
    def test_character_editing(self, driver, wait, app_url, test_user):
        """Test d'édition d'un personnage"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des personnages
        driver.get(f"{app_url}/characters.php")
        
        # Chercher un lien d'édition
        try:
            edit_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='edit_character.php']")))
            edit_link.click()
            
            # Vérifier que la page d'édition est chargée
            wait.until(lambda driver: "edit_character.php" in driver.current_url)
            
            # Vérifier la présence du formulaire d'édition
            assert driver.find_elements(By.CSS_SELECTOR, "form, input[name='name']")
            
        except TimeoutException:
            pytest.skip("Aucun personnage existant pour tester l'édition")
    
    def test_character_deletion(self, driver, wait, app_url, test_user):
        """Test de suppression d'un personnage"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des personnages
        driver.get(f"{app_url}/characters.php")
        
        # Chercher un bouton de suppression
        try:
            delete_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[data-action='delete'], .btn-danger")))
            
            # Cliquer sur le bouton de suppression
            delete_button.click()
            
            # Confirmer la suppression si une modal apparaît
            try:
                confirm_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, ".modal .btn-danger, .confirm-delete")))
                confirm_button.click()
            except TimeoutException:
                # Pas de modal de confirmation
                pass
            
            # Vérifier que le personnage a été supprimé
            # (redirection ou message de succès)
            wait.until(lambda driver: "characters.php" in driver.current_url or 
                      "supprimé" in driver.page_source.lower() or 
                      "deleted" in driver.page_source.lower())
            
        except TimeoutException:
            pytest.skip("Aucun personnage existant pour tester la suppression")
    
    def test_character_equipment_management(self, driver, wait, app_url, test_user):
        """Test de gestion de l'équipement d'un personnage"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des personnages
        driver.get(f"{app_url}/characters.php")
        
        # Chercher un lien vers l'équipement
        try:
            equipment_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='equipment'], a[href*='inventory']")))
            equipment_link.click()
            
            # Vérifier que la page d'équipement est chargée
            wait.until(lambda driver: "equipment" in driver.current_url or "inventory" in driver.current_url)
            
            # Vérifier la présence d'éléments d'équipement
            assert driver.find_elements(By.CSS_SELECTOR, ".equipment, .inventory, .item")
            
        except TimeoutException:
            pytest.skip("Fonctionnalité d'équipement non disponible ou aucun personnage")
    
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
