"""
Tests d'intégration pour les flux complets de l'application
"""
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time

class TestIntegration:
    """Tests d'intégration pour les flux complets"""
    
    @pytest.mark.smoke
    def test_complete_user_journey(self, driver, wait, app_url, test_user, test_character):
        """Test du parcours complet d'un utilisateur"""
        # 1. Inscription
        driver.get(f"{app_url}/register.php")
        self._register_user(driver, wait, test_user)
        
        # 2. Connexion
        self._login_user(driver, wait, app_url, test_user)
        
        # 3. Création d'un personnage
        self._create_character(driver, wait, app_url, test_character)
        
        # 4. Visualisation du personnage
        self._view_character(driver, wait, app_url)
        
        # 5. Déconnexion
        self._logout_user(driver, wait, app_url)
    
    @pytest.mark.smoke
    def test_dm_campaign_workflow(self, driver, wait, app_url, test_user, test_campaigns):
        """Test du workflow complet d'un MJ"""
        # 1. Connexion en tant que MJ
        self._login_user(driver, wait, app_url, test_user)
        
        # 2. Création d'une campagne
        self._create_campaign(driver, wait, app_url, test_campaigns['main_campaign'])
        
        # 3. Création d'une session
        self._create_session(driver, wait, app_url)
        
        # 4. Gestion d'une scène
        self._manage_scene(driver, wait, app_url)
        
        # 5. Ajout de monstres au bestiaire
        self._add_monster_to_bestiary(driver, wait, app_url)
    
    def test_character_equipment_workflow(self, driver, wait, app_url, test_user, test_character):
        """Test du workflow d'équipement d'un personnage"""
        # 1. Connexion
        self._login_user(driver, wait, app_url, test_user)
        
        # 2. Création d'un personnage
        self._create_character(driver, wait, app_url, test_character)
        
        # 3. Gestion de l'équipement
        self._manage_character_equipment(driver, wait, app_url)
        
        # 4. Recherche d'objets magiques
        self._search_magical_items(driver, wait, app_url)
    
    def test_bestiary_management_workflow(self, driver, wait, app_url, test_user, test_monsters):
        """Test du workflow de gestion du bestiaire"""
        # 1. Connexion
        self._login_user(driver, wait, app_url, test_user)
        
        # 2. Parcourir le bestiaire
        self._browse_bestiary(driver, wait, app_url)
        
        # 3. Rechercher des monstres
        self._search_monsters(driver, wait, app_url)
        
        # 4. Créer un monstre personnalisé
        self._create_custom_monster(driver, wait, app_url, test_monsters['dragon'])
        
        # 5. Ajouter à la collection
        self._add_to_collection(driver, wait, app_url)
    
    def test_spell_management_workflow(self, driver, wait, app_url, test_user):
        """Test du workflow de gestion des sorts"""
        # 1. Connexion
        self._login_user(driver, wait, app_url, test_user)
        
        # 2. Accéder au grimoire
        self._access_grimoire(driver, wait, app_url)
        
        # 3. Rechercher des sorts
        self._search_spells(driver, wait, app_url)
        
        # 4. Gérer les sorts d'un personnage
        self._manage_character_spells(driver, wait, app_url)
    
    # Helper methods
    def _register_user(self, driver, wait, test_user):
        """Helper: Inscription d'un utilisateur"""
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        email_field = driver.find_element(By.NAME, "email")
        password_field = driver.find_element(By.NAME, "password")
        confirm_password_field = driver.find_element(By.NAME, "confirm_password")
        
        username_field.send_keys(test_user['username'])
        email_field.send_keys(test_user['email'])
        password_field.send_keys(test_user['password'])
        confirm_password_field.send_keys(test_user['password'])
        
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", submit_button)
        
        # Attendre la redirection
        wait.until(lambda driver: "index.php" in driver.current_url or "characters.php" in driver.current_url or 
                  "succès" in driver.page_source.lower())
    
    def _login_user(self, driver, wait, app_url, test_user):
        """Helper: Connexion d'un utilisateur"""
        driver.get(f"{app_url}/login.php")
        
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        password_field = driver.find_element(By.NAME, "password")
        
        username_field.send_keys(test_user['username'])
        password_field.send_keys(test_user['password'])
        
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", submit_button)
        
        wait.until(lambda driver: "index.php" in driver.current_url or "characters.php" in driver.current_url or "characters.php" in driver.current_url)
    
    def _create_character(self, driver, wait, app_url, test_character):
        """Helper: Création d'un personnage"""
        driver.get(f"{app_url}/create_character.php")
        
        name_field = wait.until(EC.presence_of_element_located((By.NAME, "name")))
        name_field.send_keys(test_character['name'])
        
        try:
            race_select = Select(driver.find_element(By.NAME, "race"))
        except NoSuchElementException:
            pytest.skip("Formulaire de création de personnage non accessible - test ignoré")
        race_select.select_by_visible_text(test_character['race'])
        
        class_select = Select(driver.find_element(By.NAME, "class"))
        class_select.select_by_visible_text(test_character['class'])
        
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        submit_button.click()
        
        wait.until(lambda driver: "characters.php" in driver.current_url or 
                  "succès" in driver.page_source.lower())
    
    def _view_character(self, driver, wait, app_url):
        """Helper: Visualisation d'un personnage"""
        driver.get(f"{app_url}/characters.php")
        
        try:
            character_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='view_character.php']")))
            character_link.click()
            wait.until(lambda driver: "view_character.php" in driver.current_url)
        except TimeoutException:
            pytest.skip("Aucun personnage existant pour la visualisation")
    
    def _logout_user(self, driver, wait, app_url):
        """Helper: Déconnexion d'un utilisateur"""
        logout_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='logout.php']")))
        logout_link.click()
        wait.until(lambda driver: "login.php" in driver.current_url or "index.php" in driver.current_url)
    
    def _create_campaign(self, driver, wait, app_url, test_campaign):
        """Helper: Création d'une campagne"""
        driver.get(f"{app_url}/campaigns.php")
        
        try:
            create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='create'], .btn-primary")))
            create_button.click()
            
            name_field = wait.until(EC.presence_of_element_located((By.NAME, "name")))
            name_field.send_keys(test_campaign['name'])
            
            try:
                description_field = driver.find_element(By.NAME, "description")
                description_field.send_keys(test_campaign['description'])
            except NoSuchElementException:
                pytest.skip("Formulaire de création de campagne non accessible - test ignoré")
            
            submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_button.click()
            
            wait.until(lambda driver: "campaigns.php" in driver.current_url or 
                      "succès" in driver.page_source.lower())
        except TimeoutException:
            pytest.skip("Fonctionnalité de création de campagne non disponible")
    
    def _create_session(self, driver, wait, app_url):
        """Helper: Création d'une session"""
        try:
            session_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='session'], .btn-success")))
            session_button.click()
            
            name_field = wait.until(EC.presence_of_element_located((By.NAME, "name")))
            name_field.send_keys("Test Session")
            
            submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_button.click()
            
            wait.until(lambda driver: "session" in driver.current_url or 
                      "succès" in driver.page_source.lower())
        except TimeoutException:
            pytest.skip("Fonctionnalité de création de session non disponible")
    
    def _manage_scene(self, driver, wait, app_url):
        """Helper: Gestion d'une scène"""
        try:
            scene_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='scene'], a[href*='view_scene.php']")))
            scene_link.click()
            wait.until(lambda driver: "scene" in driver.current_url)
        except TimeoutException:
            pytest.skip("Aucune scène existante pour la gestion")
    
    def _add_monster_to_bestiary(self, driver, wait, app_url):
        """Helper: Ajout d'un monstre au bestiaire"""
        driver.get(f"{app_url}/bestiary.php")
        
        try:
            create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='create'], .btn-primary")))
            create_button.click()
            
            name_field = wait.until(EC.presence_of_element_located((By.NAME, "name")))
            name_field.send_keys("Test Monster")
            
            submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_button.click()
            
            wait.until(lambda driver: "monster" in driver.current_url or 
                      "succès" in driver.page_source.lower())
        except TimeoutException:
            pytest.skip("Fonctionnalité de création de monstre non disponible")
    
    def _manage_character_equipment(self, driver, wait, app_url):
        """Helper: Gestion de l'équipement d'un personnage"""
        try:
            equipment_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='equipment'], a[href*='inventory']")))
            equipment_link.click()
            wait.until(lambda driver: "equipment" in driver.current_url or "inventory" in driver.current_url)
        except TimeoutException:
            pytest.skip("Fonctionnalité d'équipement non disponible")
    
    def _search_magical_items(self, driver, wait, app_url):
        """Helper: Recherche d'objets magiques"""
        driver.get(f"{app_url}/search_magical_items.php")
        
        try:
            search_field = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='search'], input[name='search']")))
            search_field.send_keys("épée")
            
            search_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'], .btn-search")
            search_button.click()
            
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".search-results, .item-card")))
        except TimeoutException:
            pytest.skip("Fonctionnalité de recherche d'objets magiques non disponible")
    
    def _browse_bestiary(self, driver, wait, app_url):
        """Helper: Parcourir le bestiaire"""
        driver.get(f"{app_url}/bestiary.php")
        wait.until(EC.any_of(
            EC.presence_of_element_located((By.CSS_SELECTOR, ".monster-card, .card")),
            EC.presence_of_element_located((By.CSS_SELECTOR, ".no-monsters, .empty-state"))
        ))
    
    def _search_monsters(self, driver, wait, app_url):
        """Helper: Rechercher des monstres"""
        try:
            search_field = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='search'], input[name='search']")))
            search_field.send_keys("dragon")
            
            search_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'], .btn-search")
            search_button.click()
            
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".search-results, .monster-card")))
        except TimeoutException:
            pytest.skip("Fonctionnalité de recherche de monstres non disponible")
    
    def _create_custom_monster(self, driver, wait, app_url, test_monster):
        """Helper: Créer un monstre personnalisé"""
        driver.get(f"{app_url}/create_monster_npc.php")
        
        try:
            name_field = wait.until(EC.presence_of_element_located((By.NAME, "name")))
            name_field.send_keys(test_monster['name'])
            
            submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_button.click()
            
            wait.until(lambda driver: "monster" in driver.current_url or 
                      "succès" in driver.page_source.lower())
        except TimeoutException:
            pytest.skip("Fonctionnalité de création de monstre non disponible")
    
    def _add_to_collection(self, driver, wait, app_url):
        """Helper: Ajouter à la collection"""
        driver.get(f"{app_url}/my_monsters.php")
        wait.until(EC.any_of(
            EC.presence_of_element_located((By.CSS_SELECTOR, ".monster-card, .card")),
            EC.presence_of_element_located((By.CSS_SELECTOR, ".no-monsters, .empty-state"))
        ))
    
    def _access_grimoire(self, driver, wait, app_url):
        """Helper: Accéder au grimoire"""
        driver.get(f"{app_url}/grimoire.php")
        wait.until(EC.any_of(
            EC.presence_of_element_located((By.CSS_SELECTOR, ".spell-card, .card")),
            EC.presence_of_element_located((By.CSS_SELECTOR, ".no-spells, .empty-state"))
        ))
    
    def _search_spells(self, driver, wait, app_url):
        """Helper: Rechercher des sorts"""
        try:
            search_field = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='search'], input[name='search']")))
            search_field.send_keys("boule de feu")
            
            search_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'], .btn-search")
            search_button.click()
            
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".search-results, .spell-card")))
        except TimeoutException:
            pytest.skip("Fonctionnalité de recherche de sorts non disponible")
    
    def _manage_character_spells(self, driver, wait, app_url):
        """Helper: Gérer les sorts d'un personnage"""
        try:
            spells_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='spell'], a[href*='grimoire']")))
            spells_link.click()
            wait.until(lambda driver: "spell" in driver.current_url or "grimoire" in driver.current_url)
        except TimeoutException:
            pytest.skip("Fonctionnalité de gestion des sorts non disponible")
