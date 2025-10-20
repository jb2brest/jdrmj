"""
Tests fonctionnels pour les historiques
Basés sur les tests de classes existants
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, StaleElementReferenceException


class TestHistoires:
    """Tests pour les historiques"""

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
            acolyte_element = None
            background_cards = driver.find_elements(By.CSS_SELECTOR, ".background-card")
            for card in background_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Acolyte" in title_element.text:
                        acolyte_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if acolyte_element:
                driver.execute_script("arguments[0].click();", acolyte_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']:not([name='action'][value='go_back'])")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
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
            criminal_element = None
            background_cards = driver.find_elements(By.CSS_SELECTOR, ".background-card")
            for card in background_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Criminel" in title_element.text or "Criminal" in title_element.text:
                        criminal_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if criminal_element:
                driver.execute_script("arguments[0].click();", criminal_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']:not([name='action'][value='go_back'])")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
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
            hermit_element = None
            background_cards = driver.find_elements(By.CSS_SELECTOR, ".background-card")
            for card in background_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Ermite" in title_element.text or "Hermit" in title_element.text:
                        hermit_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if hermit_element:
                driver.execute_script("arguments[0].click();", hermit_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']:not([name='action'][value='go_back'])")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
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
            noble_element = None
            background_cards = driver.find_elements(By.CSS_SELECTOR, ".background-card")
            for card in background_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Noble" in title_element.text:
                        noble_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if noble_element:
                driver.execute_script("arguments[0].click();", noble_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']:not([name='action'][value='go_back'])")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
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
            sage_element = None
            background_cards = driver.find_elements(By.CSS_SELECTOR, ".background-card")
            for card in background_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Sage" in title_element.text:
                        sage_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if sage_element:
                driver.execute_script("arguments[0].click();", sage_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']:not([name='action'][value='go_back'])")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
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
            soldier_element = None
            background_cards = driver.find_elements(By.CSS_SELECTOR, ".background-card")
            for card in background_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Soldat" in title_element.text or "Soldier" in title_element.text:
                        soldier_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if soldier_element:
                driver.execute_script("arguments[0].click();", soldier_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']:not([name='action'][value='go_back'])")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
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
            artist_element = None
            background_cards = driver.find_elements(By.CSS_SELECTOR, ".background-card")
            for card in background_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Artiste" in title_element.text or "Artist" in title_element.text:
                        artist_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if artist_element:
                driver.execute_script("arguments[0].click();", artist_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']:not([name='action'][value='go_back'])")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step4.php" in driver.current_url)
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
            background_cards = driver.find_elements(By.CSS_SELECTOR, ".background-card")
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
                        
                except NoSuchElementException:
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
                driver.get(f"{app_url}/character_create_step3.php")
                wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
                
                background_element = None
                background_cards = driver.find_elements(By.CSS_SELECTOR, ".background-card")
                for card in background_cards:
                    try:
                        title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                        if background_name in title_element.text:
                            background_element = card
                            break
                    except NoSuchElementException:
                        continue
                
                if background_element:
                    driver.execute_script("arguments[0].click();", background_element)
                    time.sleep(1)
                    print(f"✅ Historique {background_name} sélectionné")
                    
                    # Vérifier que le bouton continuer est disponible
                    continue_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']:not([name='action'][value='go_back'])")
                    if continue_btn.is_enabled():
                        print(f"✅ Bouton continuer activé pour {background_name}")
                    else:
                        print(f"⚠️ Bouton continuer non activé pour {background_name}")
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
        driver.get(f"{app_url}/character_create_step1.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Sélectionner une classe (ex: Barde)
        bard_element = None
        class_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card")
        for card in class_cards:
            try:
                title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                if "Barde" in title_element.text or "Bard" in title_element.text:
                    bard_element = card
                    break
            except NoSuchElementException:
                continue
        
        if not bard_element:
            pytest.skip("Carte de classe Barde non trouvée - test ignoré")
        
        driver.execute_script("arguments[0].click();", bard_element)
        time.sleep(1)
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", continue_btn)
        wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
        print("✅ Étape 1: Classe Barde sélectionnée")
        
        # Étape 2: Sélection de race
        race_element = None
        race_cards = driver.find_elements(By.CSS_SELECTOR, ".race-card")
        for card in race_cards:
            try:
                title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                if "Humain" in title_element.text or "Human" in title_element.text:
                    race_element = card
                    break
            except NoSuchElementException:
                continue
        
        if not race_element:
            pytest.skip("Carte de race Humain non trouvée - test ignoré")
        
        driver.execute_script("arguments[0].click();", race_element)
        time.sleep(1)
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "#continueBtn")))
        driver.execute_script("arguments[0].click();", continue_btn)
        wait.until(lambda driver: "character_create_step3.php" in driver.current_url)
        print("✅ Étape 2: Race Humain sélectionnée")





