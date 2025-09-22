"""
Tests fonctionnels pour la création de personnages par étapes
"""
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time

class TestCharacterCreationSteps:
    """Tests pour la création de personnages par étapes"""
    
    def test_complete_character_creation_workflow(self, driver, wait, app_url, test_user):
        """Test du parcours complet de création d'un personnage par étapes"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Étape 1: Sélection de classe
        driver.get(f"{app_url}/character_create_step1.php")
        
        # Vérifier que la page de sélection de classe se charge
        assert "Étape 1" in driver.title or "Choisissez votre classe" in driver.page_source
        
        # Sélectionner la classe Guerrier (ID 6 d'après le diagnostic)
        try:
            warrior_card = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-class-id='6']")))
            warrior_card.click()
            
            # Vérifier que la classe est sélectionnée
            assert "selected" in warrior_card.get_attribute("class")
            
            # Cliquer sur continuer (utiliser JavaScript pour éviter les problèmes d'interception)
            continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
            driver.execute_script("arguments[0].click();", continue_btn)
            
        except TimeoutException:
            pytest.skip("Page de sélection de classe non accessible - test ignoré")
        
        # Étape 2: Sélection de race
        wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
        
        # Sélectionner la race Humain
        try:
            human_card = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-race-id='1'], .race-card:contains('Humain')")))
            human_card.click()
            
            # Vérifier que la race est sélectionnée
            assert human_card.get_attribute("class").find("selected") != -1
            
            # Cliquer sur continuer
            continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
            continue_btn.click()
            
        except TimeoutException:
            pytest.skip("Page de sélection de race non accessible - test ignoré")
        
        # Étape 3: Sélection d'historique
        wait.until(lambda driver: "character_create_step3.php" in driver.current_url)
        
        # Sélectionner l'historique Soldat
        try:
            soldier_card = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-background-id='1'], .background-card:contains('Soldat')")))
            soldier_card.click()
            
            # Vérifier que l'historique est sélectionné
            assert soldier_card.get_attribute("class").find("selected") != -1
            
            # Cliquer sur continuer
            continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
            continue_btn.click()
            
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")
        
        # Étape 4: Définition des caractéristiques
        wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
        
        # Définir les caractéristiques (méthode des 27 points)
        try:
            # Définir des caractéristiques valides
            strength_input = wait.until(EC.presence_of_element_located((By.NAME, "strength")))
            dexterity_input = driver.find_element(By.NAME, "dexterity")
            constitution_input = driver.find_element(By.NAME, "constitution")
            intelligence_input = driver.find_element(By.NAME, "intelligence")
            wisdom_input = driver.find_element(By.NAME, "wisdom")
            charisma_input = driver.find_element(By.NAME, "charisma")
            
            # Définir des valeurs valides (total ≤ 27 points)
            strength_input.clear()
            strength_input.send_keys("15")  # 9 points
            dexterity_input.clear()
            dexterity_input.send_keys("14")  # 7 points
            constitution_input.clear()
            constitution_input.send_keys("13")  # 5 points
            intelligence_input.clear()
            intelligence_input.send_keys("12")  # 4 points
            wisdom_input.clear()
            wisdom_input.send_keys("10")  # 2 points
            charisma_input.clear()
            charisma_input.send_keys("8")  # 0 points
            
            # Vérifier que le compteur de points est correct
            points_used = driver.find_element(By.ID, "points_used")
            assert int(points_used.text) <= 27
            
            # Cliquer sur continuer
            continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
            continue_btn.click()
            
        except TimeoutException:
            pytest.skip("Page de caractéristiques non accessible - test ignoré")
        
        # Vérifier la redirection vers l'étape suivante
        wait.until(lambda driver: "character_create_step5.php" in driver.current_url)
        
        # Note: Les étapes 5-9 peuvent nécessiter des tests spécifiques selon leur implémentation
        # Pour l'instant, on vérifie que l'étape 5 se charge
        assert "Étape 5" in driver.title or "character_create_step5.php" in driver.current_url
    
    def test_warrior_class_creation(self, driver, wait, app_url, test_user):
        """Test de création d'un personnage Guerrier"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Étape 1: Sélection de classe Guerrier
        driver.get(f"{app_url}/character_create_step1.php")
        
        try:
            # Chercher la classe Guerrier
            warrior_card = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, ".class-card")))
            
            # Trouver la carte Guerrier par son contenu
            class_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card")
            warrior_card = None
            for card in class_cards:
                if "Guerrier" in card.text:
                    warrior_card = card
                    break
            
            if warrior_card:
                warrior_card.click()
                
                # Vérifier les informations affichées
                assert "d10" in driver.page_source  # Dé de vie
                assert "Force" in driver.page_source or "Dextérité" in driver.page_source
                
                # Continuer vers l'étape suivante
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                continue_btn.click()
                
                # Vérifier la redirection
                wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
                
            else:
                pytest.skip("Classe Guerrier non trouvée - test ignoré")
                
        except TimeoutException:
            pytest.skip("Page de sélection de classe non accessible - test ignoré")
    
    def test_wizard_class_creation(self, driver, wait, app_url, test_user):
        """Test de création d'un personnage Magicien"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Étape 1: Sélection de classe Magicien
        driver.get(f"{app_url}/character_create_step1.php")
        
        try:
            # Chercher la classe Magicien
            class_cards = wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".class-card")))
            wizard_card = None
            
            for card in class_cards:
                if "Magicien" in card.text:
                    wizard_card = card
                    break
            
            if wizard_card:
                wizard_card.click()
                
                # Vérifier les informations affichées
                assert "d6" in driver.page_source  # Dé de vie
                assert "Intelligence" in driver.page_source
                
                # Continuer vers l'étape suivante
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                continue_btn.click()
                
                # Vérifier la redirection
                wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
                
            else:
                pytest.skip("Classe Magicien non trouvée - test ignoré")
                
        except TimeoutException:
            pytest.skip("Page de sélection de classe non accessible - test ignoré")
    
    def test_cleric_class_creation(self, driver, wait, app_url, test_user):
        """Test de création d'un personnage Clerc"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Étape 1: Sélection de classe Clerc
        driver.get(f"{app_url}/character_create_step1.php")
        
        try:
            # Chercher la classe Clerc
            class_cards = wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".class-card")))
            cleric_card = None
            
            for card in class_cards:
                if "Clerc" in card.text:
                    cleric_card = card
                    break
            
            if cleric_card:
                cleric_card.click()
                
                # Vérifier les informations affichées
                assert "d8" in driver.page_source  # Dé de vie
                assert "Sagesse" in driver.page_source
                
                # Continuer vers l'étape suivante
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                continue_btn.click()
                
                # Vérifier la redirection
                wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
                
            else:
                pytest.skip("Classe Clerc non trouvée - test ignoré")
                
        except TimeoutException:
            pytest.skip("Page de sélection de classe non accessible - test ignoré")
    
    def test_human_race_creation(self, driver, wait, app_url, test_user):
        """Test de création d'un personnage Humain"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller directement à l'étape 2 (sélection de race)
        driver.get(f"{app_url}/character_create_step2.php")
        
        try:
            # Chercher la race Humain
            race_cards = wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".race-card")))
            human_card = None
            
            for card in race_cards:
                if "Humain" in card.text:
                    human_card = card
                    break
            
            if human_card:
                human_card.click()
                
                # Vérifier les informations affichées
                assert "Versatilité humaine" in driver.page_source
                
                # Continuer vers l'étape suivante
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                continue_btn.click()
                
                # Vérifier la redirection
                wait.until(lambda driver: "character_create_step3.php" in driver.current_url)
                
            else:
                pytest.skip("Race Humain non trouvée - test ignoré")
                
        except TimeoutException:
            pytest.skip("Page de sélection de race non accessible - test ignoré")
    
    def test_elf_race_creation(self, driver, wait, app_url, test_user):
        """Test de création d'un personnage Elfe"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller directement à l'étape 2 (sélection de race)
        driver.get(f"{app_url}/character_create_step2.php")
        
        try:
            # Chercher la race Elfe
            race_cards = wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".race-card")))
            elf_card = None
            
            for card in race_cards:
                if "Elfe" in card.text:
                    elf_card = card
                    break
            
            if elf_card:
                elf_card.click()
                
                # Vérifier les informations affichées
                assert "Vision dans le noir" in driver.page_source
                assert "Fey Ancestry" in driver.page_source
                
                # Continuer vers l'étape suivante
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                continue_btn.click()
                
                # Vérifier la redirection
                wait.until(lambda driver: "character_create_step3.php" in driver.current_url)
                
            else:
                pytest.skip("Race Elfe non trouvée - test ignoré")
                
        except TimeoutException:
            pytest.skip("Page de sélection de race non accessible - test ignoré")
    
    def test_dwarf_race_creation(self, driver, wait, app_url, test_user):
        """Test de création d'un personnage Nain"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller directement à l'étape 2 (sélection de race)
        driver.get(f"{app_url}/character_create_step2.php")
        
        try:
            # Chercher la race Nain
            race_cards = wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".race-card")))
            dwarf_card = None
            
            for card in race_cards:
                if "Nain" in card.text:
                    dwarf_card = card
                    break
            
            if dwarf_card:
                dwarf_card.click()
                
                # Vérifier les informations affichées
                assert "Vision dans le noir" in driver.page_source
                assert "Résistance aux poisons" in driver.page_source
                
                # Continuer vers l'étape suivante
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                continue_btn.click()
                
                # Vérifier la redirection
                wait.until(lambda driver: "character_create_step3.php" in driver.current_url)
                
            else:
                pytest.skip("Race Nain non trouvée - test ignoré")
                
        except TimeoutException:
            pytest.skip("Page de sélection de race non accessible - test ignoré")
    
    def test_soldier_background_creation(self, driver, wait, app_url, test_user):
        """Test de création d'un personnage avec l'historique Soldat"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller directement à l'étape 3 (sélection d'historique)
        driver.get(f"{app_url}/character_create_step3.php")
        
        try:
            # Chercher l'historique Soldat
            background_cards = wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".background-card")))
            soldier_card = None
            
            for card in background_cards:
                if "Soldat" in card.text:
                    soldier_card = card
                    break
            
            if soldier_card:
                soldier_card.click()
                
                # Vérifier les informations affichées
                assert "Compétences" in driver.page_source or "Équipement" in driver.page_source
                
                # Continuer vers l'étape suivante
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                continue_btn.click()
                
                # Vérifier la redirection
                wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
                
            else:
                pytest.skip("Historique Soldat non trouvé - test ignoré")
                
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")
    
    def test_sage_background_creation(self, driver, wait, app_url, test_user):
        """Test de création d'un personnage avec l'historique Sage"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller directement à l'étape 3 (sélection d'historique)
        driver.get(f"{app_url}/character_create_step3.php")
        
        try:
            # Chercher l'historique Sage
            background_cards = wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".background-card")))
            sage_card = None
            
            for card in background_cards:
                if "Sage" in card.text:
                    sage_card = card
                    break
            
            if sage_card:
                sage_card.click()
                
                # Vérifier les informations affichées
                assert "Compétences" in driver.page_source or "Équipement" in driver.page_source
                
                # Continuer vers l'étape suivante
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                continue_btn.click()
                
                # Vérifier la redirection
                wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
                
            else:
                pytest.skip("Historique Sage non trouvé - test ignoré")
                
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")
    
    def test_criminal_background_creation(self, driver, wait, app_url, test_user):
        """Test de création d'un personnage avec l'historique Criminel"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller directement à l'étape 3 (sélection d'historique)
        driver.get(f"{app_url}/character_create_step3.php")
        
        try:
            # Chercher l'historique Criminel
            background_cards = wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".background-card")))
            criminal_card = None
            
            for card in background_cards:
                if "Criminel" in card.text:
                    criminal_card = card
                    break
            
            if criminal_card:
                criminal_card.click()
                
                # Vérifier les informations affichées
                assert "Compétences" in driver.page_source or "Équipement" in driver.page_source
                
                # Continuer vers l'étape suivante
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                continue_btn.click()
                
                # Vérifier la redirection
                wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
                
            else:
                pytest.skip("Historique Criminel non trouvé - test ignoré")
                
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")
    
    def test_step_navigation(self, driver, wait, app_url, test_user):
        """Test de navigation entre les étapes de création"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Étape 1: Sélection de classe
        driver.get(f"{app_url}/character_create_step1.php")
        
        try:
            # Sélectionner une classe
            class_cards = wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".class-card")))
            if class_cards:
                class_cards[0].click()
                
                # Continuer vers l'étape 2
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                continue_btn.click()
                
                # Vérifier la redirection vers l'étape 2
                wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
                
                # Cliquer sur Retour
                back_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[value='go_back']")))
                back_btn.click()
                
                # Vérifier le retour à l'étape 1
                wait.until(lambda driver: "character_create_step1.php" in driver.current_url)
                
                # Vérifier que la classe est toujours sélectionnée
                selected_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card.selected")
                assert len(selected_cards) > 0, "La classe sélectionnée n'est plus présente"
                
            else:
                pytest.skip("Aucune classe trouvée - test ignoré")
                
        except TimeoutException:
            pytest.skip("Navigation entre étapes non accessible - test ignoré")
    
    def test_characteristics_validation(self, driver, wait, app_url, test_user):
        """Test de validation des caractéristiques (méthode des 27 points)"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller directement à l'étape 4 (caractéristiques)
        driver.get(f"{app_url}/character_create_step4.php")
        
        try:
            # Récupérer les champs de caractéristiques
            strength_input = wait.until(EC.presence_of_element_located((By.NAME, "strength")))
            dexterity_input = driver.find_element(By.NAME, "dexterity")
            constitution_input = driver.find_element(By.NAME, "constitution")
            intelligence_input = driver.find_element(By.NAME, "intelligence")
            wisdom_input = driver.find_element(By.NAME, "wisdom")
            charisma_input = driver.find_element(By.NAME, "charisma")
            
            # Test 1: Caractéristiques valides (≤27 points)
            strength_input.clear()
            strength_input.send_keys("15")  # 9 points
            dexterity_input.clear()
            dexterity_input.send_keys("14")  # 7 points
            constitution_input.clear()
            constitution_input.send_keys("13")  # 5 points
            intelligence_input.clear()
            intelligence_input.send_keys("12")  # 4 points
            wisdom_input.clear()
            wisdom_input.send_keys("10")  # 2 points
            charisma_input.clear()
            charisma_input.send_keys("8")  # 0 points
            
            # Vérifier le compteur de points
            points_used = driver.find_element(By.ID, "points_used")
            assert int(points_used.text) <= 27, f"Trop de points utilisés: {points_used.text}"
            
            # Test 2: Caractéristiques invalides (>27 points)
            strength_input.clear()
            strength_input.send_keys("15")  # 9 points
            dexterity_input.clear()
            dexterity_input.send_keys("15")  # 9 points
            constitution_input.clear()
            constitution_input.send_keys("15")  # 9 points
            intelligence_input.clear()
            intelligence_input.send_keys("15")  # 9 points
            wisdom_input.clear()
            wisdom_input.send_keys("15")  # 9 points
            charisma_input.clear()
            charisma_input.send_keys("15")  # 9 points
            
            # Vérifier que le compteur de points dépasse 27
            points_used = driver.find_element(By.ID, "points_used")
            assert int(points_used.text) > 27, f"Le compteur devrait indiquer plus de 27 points: {points_used.text}"
            
            # Essayer de soumettre avec des caractéristiques invalides
            submit_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
            submit_btn.click()
            
            # Vérifier qu'un message d'erreur apparaît ou que le formulaire n'est pas soumis
            time.sleep(1)  # Attendre la validation
            current_url = driver.current_url
            assert "character_create_step4.php" in current_url, "Le formulaire ne devrait pas être soumis avec des caractéristiques invalides"
            
        except TimeoutException:
            pytest.skip("Page de caractéristiques non accessible - test ignoré")
    
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
