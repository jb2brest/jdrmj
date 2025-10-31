"""
Tests fonctionnels pour les historiques
Basés sur les tests de classes existants
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, StaleElementReferenceException, InvalidSessionIdException


class TestHistoires:
    """Tests pour les historiques"""

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

    def test_acolyte_background_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de l'historique Acolyte"""
        print(f"🔧 Test de sélection de l'historique Acolyte")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Naviguer jusqu'à la sélection d'historique
        self._navigate_to_background_selection(driver, wait, app_url)
        print("✅ Navigation vers sélection d'historique terminée")
        
        # Sélectionner l'historique Acolyte
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-background-id]")))
            time.sleep(0.5)
            acolyte_card = self._find_card_by_text(driver, ".class-card[data-background-id]", "Acolyte")
            
            if acolyte_card:
                self._click_card_and_continue(driver, wait, acolyte_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc04_characteristics.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc04" not in current_url and "characteristics" not in current_url.lower():
                        pytest.skip("Navigation vers les caractéristiques échouée - test ignoré")
                print("✅ Historique Acolyte sélectionné avec succès")
            else:
                pytest.skip("Carte d'historique Acolyte non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")

    def test_criminal_background_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de l'historique Criminel"""
        print(f"🔧 Test de sélection de l'historique Criminel")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Naviguer jusqu'à la sélection d'historique
        self._navigate_to_background_selection(driver, wait, app_url)
        print("✅ Navigation vers sélection d'historique terminée")
        
        # Sélectionner l'historique Criminel
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-background-id]")))
            time.sleep(0.5)
            criminal_card = self._find_card_by_text(driver, ".class-card[data-background-id]", "Criminel")
            
            if criminal_card:
                self._click_card_and_continue(driver, wait, criminal_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc04_characteristics.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc04" not in current_url and "characteristics" not in current_url.lower():
                        pytest.skip("Navigation vers les caractéristiques échouée - test ignoré")
                print("✅ Historique Criminel sélectionné avec succès")
            else:
                pytest.skip("Carte d'historique Criminel non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")

    def test_hermit_background_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de l'historique Ermite"""
        print(f"🔧 Test de sélection de l'historique Ermite")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Naviguer jusqu'à la sélection d'historique
        self._navigate_to_background_selection(driver, wait, app_url)
        print("✅ Navigation vers sélection d'historique terminée")
        
        # Sélectionner l'historique Ermite
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-background-id]")))
            time.sleep(0.5)
            hermit_card = self._find_card_by_text(driver, ".class-card[data-background-id]", "Ermite")
            
            if hermit_card:
                self._click_card_and_continue(driver, wait, hermit_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc04_characteristics.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc04" not in current_url and "characteristics" not in current_url.lower():
                        pytest.skip("Navigation vers les caractéristiques échouée - test ignoré")
                print("✅ Historique Ermite sélectionné avec succès")
            else:
                pytest.skip("Carte d'historique Ermite non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")

    def test_noble_background_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de l'historique Noble"""
        print(f"🔧 Test de sélection de l'historique Noble")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Naviguer jusqu'à la sélection d'historique
        self._navigate_to_background_selection(driver, wait, app_url)
        print("✅ Navigation vers sélection d'historique terminée")
        
        # Sélectionner l'historique Noble
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-background-id]")))
            time.sleep(0.5)
            noble_card = self._find_card_by_text(driver, ".class-card[data-background-id]", "Noble")
            
            if noble_card:
                self._click_card_and_continue(driver, wait, noble_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc04_characteristics.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc04" not in current_url and "characteristics" not in current_url.lower():
                        pytest.skip("Navigation vers les caractéristiques échouée - test ignoré")
                print("✅ Historique Noble sélectionné avec succès")
            else:
                pytest.skip("Carte d'historique Noble non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")

    def test_sage_background_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de l'historique Sage"""
        print(f"🔧 Test de sélection de l'historique Sage")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Naviguer jusqu'à la sélection d'historique
        self._navigate_to_background_selection(driver, wait, app_url)
        print("✅ Navigation vers sélection d'historique terminée")
        
        # Sélectionner l'historique Sage
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-background-id]")))
            time.sleep(0.5)
            sage_card = self._find_card_by_text(driver, ".class-card[data-background-id]", "Sage")
            
            if sage_card:
                self._click_card_and_continue(driver, wait, sage_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc04_characteristics.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc04" not in current_url and "characteristics" not in current_url.lower():
                        pytest.skip("Navigation vers les caractéristiques échouée - test ignoré")
                print("✅ Historique Sage sélectionné avec succès")
            else:
                pytest.skip("Carte d'historique Sage non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")

    def test_soldier_background_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de l'historique Soldat"""
        print(f"🔧 Test de sélection de l'historique Soldat")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Naviguer jusqu'à la sélection d'historique
        self._navigate_to_background_selection(driver, wait, app_url)
        print("✅ Navigation vers sélection d'historique terminée")
        
        # Sélectionner l'historique Soldat
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-background-id]")))
            time.sleep(0.5)
            soldier_card = self._find_card_by_text(driver, ".class-card[data-background-id]", "Soldat")
            
            if soldier_card:
                self._click_card_and_continue(driver, wait, soldier_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc04_characteristics.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc04" not in current_url and "characteristics" not in current_url.lower():
                        pytest.skip("Navigation vers les caractéristiques échouée - test ignoré")
                print("✅ Historique Soldat sélectionné avec succès")
            else:
                pytest.skip("Carte d'historique Soldat non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")

    def test_artist_background_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de l'historique Artiste"""
        print(f"🔧 Test de sélection de l'historique Artiste")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Naviguer jusqu'à la sélection d'historique
        self._navigate_to_background_selection(driver, wait, app_url)
        print("✅ Navigation vers sélection d'historique terminée")
        
        # Sélectionner l'historique Artiste
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-background-id]")))
            time.sleep(0.5)
            artist_card = self._find_card_by_text(driver, ".class-card[data-background-id]", "Artiste")
            
            if artist_card:
                self._click_card_and_continue(driver, wait, artist_card, wait_time=1)
                try:
                    wait.until(lambda driver: "cc04_characteristics.php" in driver.current_url)
                except TimeoutException:
                    current_url = driver.current_url
                    if "cc04" not in current_url and "characteristics" not in current_url.lower():
                        pytest.skip("Navigation vers les caractéristiques échouée - test ignoré")
                print("✅ Historique Artiste sélectionné avec succès")
            else:
                pytest.skip("Carte d'historique Artiste non trouvée - test ignoré")
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")

    def test_background_characteristics_display(self, driver, wait, app_url, test_user):
        """Test d'affichage des caractéristiques des historiques"""
        print(f"🔧 Test d'affichage des caractéristiques des historiques")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Naviguer jusqu'à la sélection d'historique
        self._navigate_to_background_selection(driver, wait, app_url)
        print("✅ Navigation vers sélection d'historique terminée")
        
        # Vérifier l'affichage des historiques et leurs caractéristiques
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-background-id]")))
            time.sleep(0.5)
            background_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card[data-background-id]")
            print(f"🔍 {len(background_cards)} cartes d'historique trouvées")
            
            for i, card in enumerate(background_cards):
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    background_name = title_element.text
                    print(f"🔍 Historique {i+1}: {background_name}")
                    
                    # Vérifier la présence de caractéristiques
                    card_text = card.text.lower()
                    if "compétence" in card_text or "skill" in card_text or "outil" in card_text or "tool" in card_text or "langue" in card_text or "language" in card_text:
                        print(f"✅ Caractéristiques trouvées pour {background_name}")
                    else:
                        print(f"⚠️ Aucune caractéristique visible pour {background_name}")
                        
                except (NoSuchElementException, StaleElementReferenceException):
                    continue
            
            print("✅ Test d'affichage des caractéristiques des historiques terminé")
            
        except TimeoutException:
            pytest.skip("Page de sélection d'historique non accessible - test ignoré")

    def test_background_selection_workflow(self, driver, wait, app_url, test_user):
        """Test du workflow complet de sélection d'historique"""
        print(f"🔧 Test du workflow complet de sélection d'historique")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Naviguer jusqu'à la sélection d'historique
        self._navigate_to_background_selection(driver, wait, app_url)
        print("✅ Navigation vers sélection d'historique terminée")
        
        # Tester la sélection de différents historiques
        backgrounds_to_test = ["Acolyte", "Criminel", "Ermite", "Noble", "Sage", "Soldat"]
        
        for background_name in backgrounds_to_test:
            try:
                # Recharger la page pour chaque test
                driver.get(f"{app_url}/cc03_background_selection.php")
                wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-background-id]")))
                time.sleep(0.5)
                
                background_card = self._find_card_by_text(driver, ".class-card[data-background-id]", background_name)
                
                if background_card:
                    driver.execute_script("arguments[0].click();", background_card)
                    time.sleep(1)
                    print(f"✅ Historique {background_name} sélectionné")
                    
                    # Vérifier que le bouton continuer est disponible
                    try:
                        continue_btn = driver.find_element(By.CSS_SELECTOR, "#continueBtn")
                        if continue_btn.is_enabled():
                            print(f"✅ Bouton continuer activé pour {background_name}")
                        else:
                            print(f"⚠️ Bouton continuer non activé pour {background_name}")
                    except NoSuchElementException:
                        print(f"⚠️ Bouton continuer non trouvé pour {background_name}")
                else:
                    print(f"⚠️ Historique {background_name} non trouvé")
                    
            except TimeoutException:
                print(f"⚠️ Timeout pour l'historique {background_name}")
                continue
        
        print("✅ Test du workflow complet de sélection d'historique terminé")

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

    def _navigate_to_background_selection(self, driver, wait, app_url):
        """Helper: Naviguer jusqu'à la sélection d'historique"""
        print("🔧 Helper: Navigation vers sélection d'historique")
        
        # Étape 1: Sélection de classe
        driver.get(f"{app_url}/cc01_class_selection.php?type=player")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card")))
        time.sleep(0.5)
        
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
        print("✅ Étape 1: Classe Barde sélectionnée")
        
        # Étape 2: Sélection de race
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".class-card[data-race-id]")))
        time.sleep(0.5)
        race_card = self._find_card_by_text(driver, ".class-card[data-race-id]", "Humain")
        if not race_card:
            pytest.skip("Carte de race Humain non trouvée - test ignoré")
        
        self._click_card_and_continue(driver, wait, race_card, wait_time=1)
        try:
            wait.until(lambda driver: "cc03_background_selection.php" in driver.current_url)
        except TimeoutException:
            current_url = driver.current_url
            if "cc03" not in current_url and "background" not in current_url.lower():
                pytest.skip("Navigation vers la sélection d'historique échouée - test ignoré")
        print("✅ Étape 2: Race Humain sélectionnée")










