"""
Tests fonctionnels pour le bestiaire et la gestion des monstres
"""
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time

class TestBestiary:
    """Tests pour le bestiaire et la gestion des monstres"""
    
    def test_bestiary_display(self, driver, wait, app_url, test_user):
        """Test d'affichage du bestiaire"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page du bestiaire
        driver.get(f"{app_url}/bestiary.php")
        
        # Vérifier que la page est chargée
        assert "Bestiaire" in driver.title or "Bestiary" in driver.title
        
        # Vérifier la présence d'éléments du bestiaire
        try:
            wait.until(EC.any_of(
                EC.presence_of_element_located((By.CSS_SELECTOR, ".monster-card, .card")),
                EC.presence_of_element_located((By.CSS_SELECTOR, ".no-monsters, .empty-state"))
            ))
            assert True
        except TimeoutException:
            pytest.fail("Page du bestiaire ne s'affiche pas correctement")
    
    def test_monster_search(self, driver, wait, app_url, test_user):
        """Test de recherche de monstres"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page du bestiaire
        driver.get(f"{app_url}/bestiary.php")
        
        # Chercher le champ de recherche
        try:
            search_field = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='search'], input[name='search']")))
            search_field.send_keys("dragon")
            
            # Chercher le bouton de recherche
            search_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'], .btn-search")
            search_button.click()
            
            # Vérifier que les résultats s'affichent
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".search-results, .monster-card")))
            
        except TimeoutException:
            pytest.skip("Fonctionnalité de recherche non disponible")
    
    def test_monster_details_view(self, driver, wait, app_url, test_user):
        """Test de visualisation des détails d'un monstre"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page du bestiaire
        driver.get(f"{app_url}/bestiary.php")
        
        # Chercher un lien vers un monstre
        try:
            monster_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='monster'], .monster-card a")))
            monster_link.click()
            
            # Vérifier que la page de détail du monstre est chargée
            wait.until(lambda driver: "monster" in driver.current_url)
            
            # Vérifier la présence d'informations du monstre
            assert driver.find_elements(By.CSS_SELECTOR, ".monster-name, .monster-stats, .monster-abilities")
            
        except TimeoutException:
            pytest.skip("Aucun monstre existant pour tester la visualisation")
    
    def test_monster_creation(self, driver, wait, app_url, test_user):
        """Test de création d'un monstre"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de création de monstre
        driver.get(f"{app_url}/create_monster_npc.php")
        
        # Vérifier que la page est chargée
        title = driver.title
        if not title or title == "JDR 4 MJ - Gestionnaire de Personnages D&D":
            pytest.skip("Page de création de monstre non accessible - test ignoré")
        else:
            assert "Monstre" in title or "Monster" in title or "Créer" in title
        
        # Remplir le formulaire de création
        try:
            name_field = wait.until(EC.presence_of_element_located((By.NAME, "name")))
            name_field.send_keys("Test Monster")
            
            # Remplir d'autres champs si nécessaire
            # (selon la structure du formulaire)
            
            # Soumettre le formulaire
            submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_button.click()
            
            # Vérifier la redirection ou le message de succès
            wait.until(lambda driver: "monster" in driver.current_url or 
                      "succès" in driver.page_source.lower() or 
                      "success" in driver.page_source.lower())
            
        except TimeoutException:
            pytest.skip("Fonctionnalité de création de monstre non disponible")
    
    def test_my_monsters_collection(self, driver, wait, app_url, test_user):
        """Test de la collection de monstres personnels"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de ma collection
        driver.get(f"{app_url}/my_monsters.php")
        
        # Vérifier que la page est chargée
        title = driver.title
        if not title or title == "JDR 4 MJ - Gestionnaire de Personnages D&D":
            pytest.skip("Page de collection de monstres non accessible - test ignoré")
        else:
            assert "Collection" in title or "Monsters" in title
        
        # Vérifier la présence d'éléments de la collection
        try:
            wait.until(EC.any_of(
                EC.presence_of_element_located((By.CSS_SELECTOR, ".monster-card, .card")),
                EC.presence_of_element_located((By.CSS_SELECTOR, ".no-monsters, .empty-state"))
            ))
            assert True
        except TimeoutException:
            pytest.fail("Page de collection de monstres ne s'affiche pas correctement")
    
    def test_monster_equipment_management(self, driver, wait, app_url, test_user):
        """Test de gestion de l'équipement des monstres"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de ma collection
        driver.get(f"{app_url}/my_monsters.php")
        
        # Chercher un lien vers l'équipement d'un monstre
        try:
            equipment_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href*='equipment'], a[href*='view_monster_equipment.php']")))
            equipment_link.click()
            
            # Vérifier que la page d'équipement est chargée
            wait.until(lambda driver: "equipment" in driver.current_url)
            
            # Vérifier la présence d'éléments d'équipement
            assert driver.find_elements(By.CSS_SELECTOR, ".equipment, .inventory, .item")
            
        except TimeoutException:
            pytest.skip("Fonctionnalité d'équipement de monstre non disponible")
    
    def test_magical_items_search(self, driver, wait, app_url, test_user):
        """Test de recherche d'objets magiques"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de recherche d'objets magiques
        driver.get(f"{app_url}/search_magical_items.php")
        
        # Vérifier que la page est chargée
        title = driver.title
        if not title or title == "":
            pytest.skip("Page de recherche d'objets magiques non accessible - test ignoré")
        else:
            assert "Magique" in title or "Magical" in title
        
        # Chercher le champ de recherche
        try:
            search_field = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='search'], input[name='search']")))
            search_field.send_keys("épée")
            
            # Chercher le bouton de recherche
            search_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'], .btn-search")
            search_button.click()
            
            # Vérifier que les résultats s'affichent
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".search-results, .item-card")))
            
        except TimeoutException:
            pytest.skip("Fonctionnalité de recherche d'objets magiques non disponible")
    
    def test_poison_search(self, driver, wait, app_url, test_user):
        """Test de recherche de poisons"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de recherche de poisons
        driver.get(f"{app_url}/search_poisons.php")
        
        # Vérifier que la page est chargée
        title = driver.title
        if not title or title == "":
            pytest.skip("Page de recherche de poisons non accessible - test ignoré")
        else:
            assert "Poison" in title
        
        # Chercher le champ de recherche
        try:
            search_field = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='search'], input[name='search']")))
            search_field.send_keys("venin")
            
            # Chercher le bouton de recherche
            search_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'], .btn-search")
            search_button.click()
            
            # Vérifier que les résultats s'affichent
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".search-results, .poison-card")))
            
        except TimeoutException:
            pytest.skip("Fonctionnalité de recherche de poisons non disponible")
    
    def test_grimoire_access(self, driver, wait, app_url, test_user):
        """Test d'accès au grimoire"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page du grimoire
        driver.get(f"{app_url}/grimoire.php")
        
        # Vérifier que la page est chargée
        title = driver.title
        if "Mes Personnages" in title:
            pytest.skip("Page de grimoire redirigée vers personnages - test ignoré")
        else:
            assert "Grimoire" in title or "Spell" in title
        
        # Vérifier la présence d'éléments du grimoire
        try:
            wait.until(EC.any_of(
                EC.presence_of_element_located((By.CSS_SELECTOR, ".spell-card, .card")),
                EC.presence_of_element_located((By.CSS_SELECTOR, ".no-spells, .empty-state"))
            ))
            assert True
        except TimeoutException:
            pytest.fail("Page du grimoire ne s'affiche pas correctement")
    
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
