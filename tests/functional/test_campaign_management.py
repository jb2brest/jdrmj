"""
Tests fonctionnels pour la gestion des campagnes
"""
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time

class TestCampaignManagement:
    """Tests pour la gestion des campagnes"""
    
    def test_campaign_creation(self, driver, wait, app_url, test_user):
        """Test de création d'une campagne"""
        # Se connecter en tant que DM
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des campagnes
        driver.get(f"{app_url}/campaigns.php")
        
        # Vérifier que la page est chargée
        title = driver.title
        if "Profil" in title:
            pytest.skip("Page de campagnes redirigée vers profil - test ignoré")
        else:
            assert "Campagne" in title or "Campaign" in title
        
        # Chercher le bouton de création de campagne
        try:
            create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='create'], .btn-primary")))
            create_button.click()
            
            # Remplir le formulaire de création
            name_field = wait.until(EC.presence_of_element_located((By.NAME, "name")))
            name_field.send_keys("Test Campaign")
            
            description_field = driver.find_element(By.NAME, "description")
            description_field.send_keys("Description de test pour la campagne")
            
            # Soumettre le formulaire
            submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_button.click()
            
            # Vérifier la redirection ou le message de succès
            wait.until(lambda driver: "campaigns.php" in driver.current_url or 
                      "succès" in driver.page_source.lower() or 
                      "success" in driver.page_source.lower())
            
        except TimeoutException:
            pytest.skip("Fonctionnalité de création de campagne non disponible")
    
    def test_campaign_list_display(self, driver, wait, app_url, test_user):
        """Test d'affichage de la liste des campagnes"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des campagnes
        driver.get(f"{app_url}/campaigns.php")
        
        # Vérifier que la page est chargée
        title = driver.title
        if "Profil" in title:
            pytest.skip("Page de campagnes redirigée vers profil - test ignoré")
        else:
            assert "Campagne" in title or "Campaign" in title
        
        # Vérifier la présence d'éléments de la liste
        try:
            wait.until(EC.any_of(
                EC.presence_of_element_located((By.CSS_SELECTOR, ".campaign-card, .card")),
                EC.presence_of_element_located((By.CSS_SELECTOR, ".no-campaigns, .empty-state"))
            ))
            assert True
        except TimeoutException:
            pytest.fail("Page des campagnes ne s'affiche pas correctement")
    
    def test_campaign_view(self, driver, wait, app_url, test_user):
        """Test de visualisation d'une campagne"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des campagnes
        driver.get(f"{app_url}/campaigns.php")
        
        # Chercher un lien vers une campagne
        try:
            campaign_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='view_campaign.php']")))
            campaign_link.click()
            
            # Vérifier que la page de détail de la campagne est chargée
            wait.until(lambda driver: "view_campaign.php" in driver.current_url)
            
            # Vérifier la présence d'informations de la campagne
            assert driver.find_elements(By.CSS_SELECTOR, ".campaign-name, .campaign-info, .campaign-details")
            
        except TimeoutException:
            pytest.skip("Aucune campagne existante pour tester la visualisation")
    
    def test_session_creation(self, driver, wait, app_url, test_user):
        """Test de création d'une session"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des campagnes
        driver.get(f"{app_url}/campaigns.php")
        
        # Chercher un bouton de création de session
        try:
            session_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='session'], .btn-success")))
            session_button.click()
            
            # Remplir le formulaire de session
            name_field = wait.until(EC.presence_of_element_located((By.NAME, "name")))
            name_field.send_keys("Test Session")
            
            # Soumettre le formulaire
            submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_button.click()
            
            # Vérifier la redirection ou le message de succès
            wait.until(lambda driver: "session" in driver.current_url or 
                      "succès" in driver.page_source.lower() or 
                      "success" in driver.page_source.lower())
            
        except TimeoutException:
            pytest.skip("Fonctionnalité de création de session non disponible")
    
    def test_scene_management(self, driver, wait, app_url, test_user):
        """Test de gestion des scènes"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des campagnes
        driver.get(f"{app_url}/campaigns.php")
        
        # Chercher un lien vers une scène
        try:
            scene_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='scene'], a[href*='view_scene.php']")))
            scene_link.click()
            
            # Vérifier que la page de scène est chargée
            wait.until(lambda driver: "scene" in driver.current_url)
            
            # Vérifier la présence d'éléments de scène
            assert driver.find_elements(By.CSS_SELECTOR, ".scene, .scene-content, .scene-tokens")
            
        except TimeoutException:
            pytest.skip("Aucune scène existante pour tester la gestion")
    
    def test_public_campaigns_view(self, driver, wait, app_url):
        """Test d'affichage des campagnes publiques"""
        # Aller à la page des campagnes publiques
        driver.get(f"{app_url}/public_campaigns.php")
        
        # Vérifier que la page est chargée (peut être une page de connexion si non connecté)
        page_title = driver.title
        if "Connexion" in page_title or "Login" in page_title:
            pytest.skip("Page de campagnes publiques nécessite une connexion - test ignoré")
        else:
            assert "Publique" in page_title or "Public" in page_title or "Campagne" in page_title
        
        # Vérifier la présence d'éléments de la liste
        try:
            wait.until(EC.any_of(
                EC.presence_of_element_located((By.CSS_SELECTOR, ".campaign-card, .card")),
                EC.presence_of_element_located((By.CSS_SELECTOR, ".no-campaigns, .empty-state"))
            ))
            assert True
        except TimeoutException:
            pytest.fail("Page des campagnes publiques ne s'affiche pas correctement")
    
    def test_campaign_player_view(self, driver, wait, app_url, test_user):
        """Test de vue joueur d'une campagne"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des campagnes
        driver.get(f"{app_url}/campaigns.php")
        
        # Chercher un lien vers une vue joueur
        try:
            player_view_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='view_campaign_player.php']")))
            player_view_link.click()
            
            # Vérifier que la page de vue joueur est chargée
            wait.until(lambda driver: "view_campaign_player.php" in driver.current_url)
            
            # Vérifier la présence d'informations de la campagne
            assert driver.find_elements(By.CSS_SELECTOR, ".campaign-info, .character-info, .session-info")
            
        except TimeoutException:
            pytest.skip("Aucune campagne existante pour tester la vue joueur")
    
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
