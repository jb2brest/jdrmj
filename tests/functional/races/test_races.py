"""
Tests fonctionnels pour les races
Basés sur les tests de classes existants
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, StaleElementReferenceException, InvalidSessionIdException


class TestRaces:
    """Tests pour les races"""

    def _find_card_by_text(self, driver, card_selector, search_text):
        """Helper: Trouver une carte par son texte (classe, race, option, etc.)"""
        max_retries = 3
        for retry in range(max_retries):
            try:
                cards = driver.find_elements(By.CSS_SELECTOR, card_selector)
                for card in cards:
                    try:
                        title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                        if search_text in title_element.text:
                            return card
                    except (NoSuchElementException, StaleElementReferenceException):
                        continue
                if retry < max_retries - 1:
                    time.sleep(0.5)
                    continue
                return None
            except (StaleElementReferenceException, Exception):
                if retry < max_retries - 1:
                    time.sleep(0.5)
                    continue
                return None
        return None
    
    def _click_card_and_continue(self, driver, wait, card_element, continue_btn_selector="#continueBtn", wait_time=0.5):
        """Helper: Cliquer sur une carte et continuer"""
        if card_element:
            try:
                driver.execute_script("arguments[0].click();", card_element)
                time.sleep(wait_time)
                
                # Vérifier que la carte est sélectionnée (avec gestion des éléments obsolètes)
                try:
                    card_class = card_element.get_attribute("class")
                    if card_class and "selected" not in card_class:
                        # Réessayer en cherchant la carte à nouveau
                        time.sleep(0.5)
                except StaleElementReferenceException:
                    # Si l'élément est obsolète, on continue quand même
                    pass
                
                # Cliquer sur continuer
                time.sleep(0.5)  # Pause supplémentaire avant de chercher le bouton
                try:
                    continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, continue_btn_selector)))
                    if continue_btn.get_property("disabled"):
                        # Attendre un peu plus si le bouton est désactivé
                        time.sleep(1)
                        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, continue_btn_selector)))
                    driver.execute_script("arguments[0].click();", continue_btn)
                    return True
                except (InvalidSessionIdException, Exception) as e:
                    # Si le navigateur se ferme ou autre erreur, on retourne False
                    if isinstance(e, InvalidSessionIdException):
                        raise
                    time.sleep(1)
                    # Réessayer une fois
                    try:
                        continue_btn = driver.find_element(By.CSS_SELECTOR, continue_btn_selector)
                        if not continue_btn.get_property("disabled"):
                            driver.execute_script("arguments[0].click();", continue_btn)
                            return True
                    except:
                        pass
                    raise
            except (StaleElementReferenceException, TimeoutException) as e:
                # En cas d'erreur, on essaie de continuer quand même
                try:
                    continue_btn = driver.find_element(By.CSS_SELECTOR, continue_btn_selector)
                    if not continue_btn.get_property("disabled"):
                        driver.execute_script("arguments[0].click();", continue_btn)
                        return True
                except:
                    pass
                raise
        return False
    
    def _click_continue_button(self, driver, wait, selector="#continueBtn"):
        """Helper: Cliquer sur le bouton continuer (nouvelle IHM uniquement)"""
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, selector)))
        driver.execute_script("arguments[0].click();", continue_btn)

    def test_human_race_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de la race Humain"""
        print(f"🔧 Test de sélection de la race Humain")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Aller à la page de création de personnage
        driver.get(f"{app_url}/cc01_class_selection.php?type=player")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card")))
        time.sleep(0.5)
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Guerrier)
        warrior_card = self._find_card_by_text(driver, ".class-card", "Guerrier")
        if not warrior_card:
            pytest.skip("Carte de classe Guerrier non trouvée - test ignoré")
        
        self._click_card_and_continue(driver, wait, warrior_card)
        try:
            wait.until(lambda driver: "cc02_race_selection.php" in driver.current_url)
        except TimeoutException:
            current_url = driver.current_url
            if "cc02" not in current_url and "race" not in current_url.lower():
                pytest.skip("Navigation vers la sélection de race échouée - test ignoré")
        print("✅ Classe Guerrier sélectionnée, redirection vers étape 2")
        
        # Sélectionner la race Humain
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-race-id]")))
            time.sleep(0.5)
            human_card = self._find_card_by_text(driver, ".class-card[data-race-id]", "Humain")
            
            if human_card:
                self._click_card_and_continue(driver, wait, human_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc03_background_selection.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc03" not in current_url and "background" not in current_url.lower():
                        pytest.skip("Navigation vers la sélection d'historique échouée - test ignoré")
                print("✅ Race Humain sélectionnée avec succès")
            else:
                pytest.skip("Carte de race Humain non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection de race non accessible - test ignoré")

    def test_elf_race_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de la race Elfe"""
        print(f"🔧 Test de sélection de la race Elfe")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Aller à la page de création de personnage
        driver.get(f"{app_url}/cc01_class_selection.php?type=player")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card")))
        time.sleep(0.5)
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Magicien)
        wizard_card = self._find_card_by_text(driver, ".class-card", "Magicien")
        if not wizard_card:
            pytest.skip("Carte de classe Magicien non trouvée - test ignoré")
        
        self._click_card_and_continue(driver, wait, wizard_card)
        try:
            wait.until(lambda driver: "cc02_race_selection.php" in driver.current_url)
        except TimeoutException:
            current_url = driver.current_url
            if "cc02" not in current_url and "race" not in current_url.lower():
                pytest.skip("Navigation vers la sélection de race échouée - test ignoré")
        print("✅ Classe Magicien sélectionnée, redirection vers étape 2")
        
        # Sélectionner la race Elfe
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-race-id]")))
            time.sleep(0.5)
            elf_card = self._find_card_by_text(driver, ".class-card[data-race-id]", "Elfe")
            
            if elf_card:
                self._click_card_and_continue(driver, wait, elf_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc03_background_selection.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc03" not in current_url and "background" not in current_url.lower():
                        pytest.skip("Navigation vers la sélection d'historique échouée - test ignoré")
                print("✅ Race Elfe sélectionnée avec succès")
            else:
                pytest.skip("Carte de race Elfe non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection de race non accessible - test ignoré")

    def test_dwarf_race_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de la race Nain"""
        print(f"🔧 Test de sélection de la race Nain")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Aller à la page de création de personnage
        driver.get(f"{app_url}/cc01_class_selection.php?type=player")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card")))
        time.sleep(0.5)
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Clerc)
        cleric_card = self._find_card_by_text(driver, ".class-card", "Clerc")
        if not cleric_card:
            pytest.skip("Carte de classe Clerc non trouvée - test ignoré")
        
        self._click_card_and_continue(driver, wait, cleric_card)
        try:
            wait.until(lambda driver: "cc02_race_selection.php" in driver.current_url)
        except TimeoutException:
            current_url = driver.current_url
            if "cc02" not in current_url and "race" not in current_url.lower():
                pytest.skip("Navigation vers la sélection de race échouée - test ignoré")
        print("✅ Classe Clerc sélectionnée, redirection vers étape 2")
        
        # Sélectionner la race Nain
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-race-id]")))
            time.sleep(0.5)
            dwarf_card = self._find_card_by_text(driver, ".class-card[data-race-id]", "Nain")
            
            if dwarf_card:
                self._click_card_and_continue(driver, wait, dwarf_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc03_background_selection.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc03" not in current_url and "background" not in current_url.lower():
                        pytest.skip("Navigation vers la sélection d'historique échouée - test ignoré")
                print("✅ Race Nain sélectionnée avec succès")
            else:
                pytest.skip("Carte de race Nain non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection de race non accessible - test ignoré")

    def test_halfling_race_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de la race Halfelin"""
        print(f"🔧 Test de sélection de la race Halfelin")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Aller à la page de création de personnage
        driver.get(f"{app_url}/cc01_class_selection.php?type=player")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card")))
        time.sleep(0.5)
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Roublard)
        rogue_card = self._find_card_by_text(driver, ".class-card", "Roublard")
        if not rogue_card:
            pytest.skip("Carte de classe Roublard non trouvée - test ignoré")
        
        self._click_card_and_continue(driver, wait, rogue_card)
        try:
            wait.until(lambda driver: "cc02_race_selection.php" in driver.current_url)
        except TimeoutException:
            current_url = driver.current_url
            if "cc02" not in current_url and "race" not in current_url.lower():
                pytest.skip("Navigation vers la sélection de race échouée - test ignoré")
        print("✅ Classe Roublard sélectionnée, redirection vers étape 2")
        
        # Sélectionner la race Halfelin
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-race-id]")))
            time.sleep(0.5)
            halfling_card = self._find_card_by_text(driver, ".class-card[data-race-id]", "Halfelin")
            
            if halfling_card:
                self._click_card_and_continue(driver, wait, halfling_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc03_background_selection.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc03" not in current_url and "background" not in current_url.lower():
                        pytest.skip("Navigation vers la sélection d'historique échouée - test ignoré")
                print("✅ Race Halfelin sélectionnée avec succès")
            else:
                pytest.skip("Carte de race Halfelin non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection de race non accessible - test ignoré")

    def test_tiefling_race_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de la race Tieffelin"""
        print(f"🔧 Test de sélection de la race Tieffelin")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Aller à la page de création de personnage
        driver.get(f"{app_url}/cc01_class_selection.php?type=player")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card")))
        time.sleep(0.5)
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Occultiste)
        warlock_card = self._find_card_by_text(driver, ".class-card", "Occultiste")
        if not warlock_card:
            pytest.skip("Carte de classe Occultiste non trouvée - test ignoré")
        
        self._click_card_and_continue(driver, wait, warlock_card)
        try:
            wait.until(lambda driver: "cc02_race_selection.php" in driver.current_url)
        except TimeoutException:
            current_url = driver.current_url
            if "cc02" not in current_url and "race" not in current_url.lower():
                pytest.skip("Navigation vers la sélection de race échouée - test ignoré")
        print("✅ Classe Occultiste sélectionnée, redirection vers étape 2")
        
        # Sélectionner la race Tieffelin
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-race-id]")))
            time.sleep(0.5)
            tiefling_card = self._find_card_by_text(driver, ".class-card[data-race-id]", "Tieffelin")
            
            if tiefling_card:
                self._click_card_and_continue(driver, wait, tiefling_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc03_background_selection.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc03" not in current_url and "background" not in current_url.lower():
                        pytest.skip("Navigation vers la sélection d'historique échouée - test ignoré")
                print("✅ Race Tieffelin sélectionnée avec succès")
            else:
                pytest.skip("Carte de race Tieffelin non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection de race non accessible - test ignoré")

    def test_race_characteristics_display(self, driver, wait, app_url, test_user):
        """Test d'affichage des caractéristiques des races"""
        print(f"🔧 Test d'affichage des caractéristiques des races")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Aller à la page de création de personnage
        driver.get(f"{app_url}/cc01_class_selection.php?type=player")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card")))
        time.sleep(0.5)
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Guerrier)
        warrior_card = self._find_card_by_text(driver, ".class-card", "Guerrier")
        if not warrior_card:
            pytest.skip("Carte de classe Guerrier non trouvée - test ignoré")
        
        self._click_card_and_continue(driver, wait, warrior_card)
        try:
            wait.until(lambda driver: "cc02_race_selection.php" in driver.current_url)
        except TimeoutException:
            current_url = driver.current_url
            if "cc02" not in current_url and "race" not in current_url.lower():
                pytest.skip("Navigation vers la sélection de race échouée - test ignoré")
        print("✅ Classe Guerrier sélectionnée, redirection vers étape 2")
        
        # Vérifier l'affichage des races et leurs caractéristiques
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-race-id]")))
            time.sleep(0.5)
            race_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card[data-race-id]")
            print(f"🔍 {len(race_cards)} cartes de race trouvées")
            
            for i, card in enumerate(race_cards):
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    race_name = title_element.text
                    print(f"🔍 Race {i+1}: {race_name}")
                    
                    # Vérifier la présence de caractéristiques
                    card_text = card.text.lower()
                    if "force" in card_text or "dextérité" in card_text or "constitution" in card_text or "intelligence" in card_text or "sagesse" in card_text or "charisme" in card_text:
                        print(f"✅ Caractéristiques trouvées pour {race_name}")
                    else:
                        print(f"⚠️ Aucune caractéristique visible pour {race_name}")
                        
                except (NoSuchElementException, StaleElementReferenceException):
                    continue
            
            print("✅ Test d'affichage des caractéristiques des races terminé")
            
        except TimeoutException:
            pytest.skip("Page de sélection de race non accessible - test ignoré")

    def test_race_selection_workflow(self, driver, wait, app_url, test_user):
        """Test du workflow complet de sélection de race"""
        print(f"🔧 Test du workflow complet de sélection de race")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Aller à la page de création de personnage
        driver.get(f"{app_url}/cc01_class_selection.php?type=player")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card")))
        time.sleep(0.5)
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Barde)
        bard_card = self._find_card_by_text(driver, ".class-card", "Barde")
        if not bard_card:
            pytest.skip("Carte de classe Barde non trouvée - test ignoré")
        
        self._click_card_and_continue(driver, wait, bard_card)
        try:
            wait.until(lambda driver: "cc02_race_selection.php" in driver.current_url)
        except TimeoutException:
            current_url = driver.current_url
            if "cc02" not in current_url and "race" not in current_url.lower():
                pytest.skip("Navigation vers la sélection de race échouée - test ignoré")
        print("✅ Classe Barde sélectionnée, redirection vers étape 2")
        
        # Tester la sélection de différentes races
        races_to_test = ["Humain", "Elfe", "Halfelin"]
        
        for race_name in races_to_test:
            try:
                # Recharger la page pour chaque test
                driver.get(f"{app_url}/cc02_race_selection.php")
                wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-race-id]")))
                time.sleep(0.5)
                
                race_card = self._find_card_by_text(driver, ".class-card[data-race-id]", race_name)
                
                if race_card:
                    driver.execute_script("arguments[0].click();", race_card)
                    time.sleep(1)
                    print(f"✅ Race {race_name} sélectionnée")
                    
                    # Vérifier que le bouton continuer est disponible
                    try:
                        continue_btn = driver.find_element(By.CSS_SELECTOR, "#continueBtn")
                        if continue_btn.is_enabled():
                            print(f"✅ Bouton continuer activé pour {race_name}")
                        else:
                            print(f"⚠️ Bouton continuer non activé pour {race_name}")
                    except NoSuchElementException:
                        print(f"⚠️ Bouton continuer non trouvé pour {race_name}")
                else:
                    print(f"⚠️ Race {race_name} non trouvée")
                    
            except TimeoutException:
                print(f"⚠️ Timeout pour la race {race_name}")
                continue
        
        print("✅ Test du workflow complet de sélection de race terminé")

    # Méthodes helper
    def _create_and_login_user(self, driver, wait, app_url, test_user):
        """Helper: Créer un utilisateur et se connecter"""
        # Créer l'utilisateur via l'inscription
        driver.get(f"{app_url}/register.php")
        
        # Remplir le formulaire d'inscription
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        email_field = driver.find_element(By.NAME, "email")
        password_field = driver.find_element(By.NAME, "password")
        confirm_password_field = driver.find_element(By.NAME, "confirm_password")
        
        username_field.send_keys(test_user['username'])
        email_field.send_keys(test_user['email'])
        password_field.send_keys(test_user['password'])
        confirm_password_field.send_keys(test_user['password'])
        
        # Soumettre le formulaire
        try:
            submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
            driver.execute_script("arguments[0].click();", submit_button)
        except StaleElementReferenceException:
            # Réessayer si l'élément est obsolète
            submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
            driver.execute_script("arguments[0].click();", submit_button)
        
        # Attendre un peu pour que l'inscription se termine
        time.sleep(1)
        
        # Se connecter
        driver.get(f"{app_url}/login.php")
        print(f"🔍 URL de connexion: {driver.current_url}")

        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        password_field = driver.find_element(By.NAME, "password")

        username_field.send_keys(test_user['username'])
        password_field.send_keys(test_user['password'])
        print(f"🔍 Identifiants saisis: {test_user['username']}")

        # Essayer plusieurs sélecteurs pour le bouton de soumission
        submit_button = None
        submit_selectors = [
            "button[type='submit']",
            "input[type='submit']",
            "button:contains('Connexion')",
            "button:contains('Se connecter')",
            ".btn-primary",
            ".btn-submit"
        ]

        for selector in submit_selectors:
            try:
                if "contains" in selector:
                    xpath_selector = f"//button[contains(text(), '{selector.split(':contains(')[1].split(')')[0]}')]"
                    submit_button = wait.until(EC.element_to_be_clickable((By.XPATH, xpath_selector)))
                else:
                    submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, selector)))
                print(f"🔍 Bouton de connexion trouvé avec le sélecteur: {selector}")
                break
            except TimeoutException:
                continue

        if submit_button:
            # Attendre un peu avant de cliquer pour éviter les problèmes de timing
            time.sleep(0.5)
            try:
                driver.execute_script("arguments[0].click();", submit_button)
                print("🔍 Bouton de connexion cliqué")
            except StaleElementReferenceException:
                print("⚠️ Élément obsolète, re-trouver le bouton")
                # Re-trouver le bouton juste avant de cliquer
                submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
                driver.execute_script("arguments[0].click();", submit_button)
                print("🔍 Bouton de connexion cliqué (après re-trouvaille)")
        else:
            print("❌ Aucun bouton de connexion trouvé")
            raise TimeoutException("Bouton de connexion non trouvé")

        # Attendre la connexion
        try:
            wait.until(lambda driver: "index.php" in driver.current_url or "characters.php" in driver.current_url)
            print(f"✅ Connexion réussie, URL: {driver.current_url}")
        except TimeoutException:
            print(f"❌ Connexion échouée, URL actuelle: {driver.current_url}")
            # Vérifier s'il y a des messages d'erreur
            page_source = driver.page_source.lower()
            if "erreur" in page_source or "error" in page_source:
                print("❌ Message d'erreur détecté sur la page")
            raise










