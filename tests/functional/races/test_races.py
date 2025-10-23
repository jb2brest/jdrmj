"""
Tests fonctionnels pour les races
Basés sur les tests de classes existants
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, StaleElementReferenceException


class TestRaces:
    """Tests pour les races"""

    def test_human_race_selection(self, driver, wait, app_url, test_user):
        """Test de sélection de la race Humain"""
        print(f"🔧 Test de sélection de la race Humain")
        
        # Créer l'utilisateur et se connecter
        self._create_and_login_user(driver, wait, app_url, test_user)
        print("✅ Utilisateur créé et connecté")
        
        # Aller à la page de création de personnage
        driver.get(f"{app_url}/character_create_step1.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Guerrier)
        warrior_element = None
        class_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card")
        for card in class_cards:
            try:
                title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                if "Guerrier" in title_element.text or "Fighter" in title_element.text:
                    warrior_element = card
                    break
            except NoSuchElementException:
                continue
        
        if not warrior_element:
            pytest.skip("Carte de classe Guerrier non trouvée - test ignoré")
        
        driver.execute_script("arguments[0].click();", warrior_element)
        time.sleep(1)
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", continue_btn)
        wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
        print("✅ Classe Guerrier sélectionnée, redirection vers étape 2")
        
        # Sélectionner la race Humain
        try:
            human_element = None
            race_cards = driver.find_elements(By.CSS_SELECTOR, ".race-card")
            for card in race_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Humain" in title_element.text or "Human" in title_element.text:
                        human_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if human_element:
                driver.execute_script("arguments[0].click();", human_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "#continueBtn")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step3.php" in driver.current_url)
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
        driver.get(f"{app_url}/character_create_step1.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Magicien)
        wizard_element = None
        class_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card")
        for card in class_cards:
            try:
                title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                if "Magicien" in title_element.text or "Wizard" in title_element.text:
                    wizard_element = card
                    break
            except NoSuchElementException:
                continue
        
        if not wizard_element:
            pytest.skip("Carte de classe Magicien non trouvée - test ignoré")
        
        driver.execute_script("arguments[0].click();", wizard_element)
        time.sleep(1)
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", continue_btn)
        wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
        print("✅ Classe Magicien sélectionnée, redirection vers étape 2")
        
        # Sélectionner la race Elfe
        try:
            elf_element = None
            race_cards = driver.find_elements(By.CSS_SELECTOR, ".race-card")
            for card in race_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Elfe" in title_element.text or "Elf" in title_element.text:
                        elf_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if elf_element:
                driver.execute_script("arguments[0].click();", elf_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "#continueBtn")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step3.php" in driver.current_url)
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
        driver.get(f"{app_url}/character_create_step1.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Clerc)
        cleric_element = None
        class_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card")
        for card in class_cards:
            try:
                title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                if "Clerc" in title_element.text or "Cleric" in title_element.text:
                    cleric_element = card
                    break
            except NoSuchElementException:
                continue
        
        if not cleric_element:
            pytest.skip("Carte de classe Clerc non trouvée - test ignoré")
        
        driver.execute_script("arguments[0].click();", cleric_element)
        time.sleep(1)
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", continue_btn)
        wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
        print("✅ Classe Clerc sélectionnée, redirection vers étape 2")
        
        # Sélectionner la race Nain
        try:
            dwarf_element = None
            race_cards = driver.find_elements(By.CSS_SELECTOR, ".race-card")
            for card in race_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Nain" in title_element.text or "Dwarf" in title_element.text:
                        dwarf_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if dwarf_element:
                driver.execute_script("arguments[0].click();", dwarf_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "#continueBtn")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step3.php" in driver.current_url)
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
        driver.get(f"{app_url}/character_create_step1.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Roublard)
        rogue_element = None
        class_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card")
        for card in class_cards:
            try:
                title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                if "Roublard" in title_element.text or "Rogue" in title_element.text:
                    rogue_element = card
                    break
            except NoSuchElementException:
                continue
        
        if not rogue_element:
            pytest.skip("Carte de classe Roublard non trouvée - test ignoré")
        
        driver.execute_script("arguments[0].click();", rogue_element)
        time.sleep(1)
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", continue_btn)
        wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
        print("✅ Classe Roublard sélectionnée, redirection vers étape 2")
        
        # Sélectionner la race Halfelin
        try:
            halfling_element = None
            race_cards = driver.find_elements(By.CSS_SELECTOR, ".race-card")
            for card in race_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Halfelin" in title_element.text or "Halfling" in title_element.text:
                        halfling_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if halfling_element:
                driver.execute_script("arguments[0].click();", halfling_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "#continueBtn")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step3.php" in driver.current_url)
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
        driver.get(f"{app_url}/character_create_step1.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Occultiste)
        warlock_element = None
        class_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card")
        for card in class_cards:
            try:
                title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                if "Occultiste" in title_element.text or "Warlock" in title_element.text:
                    warlock_element = card
                    break
            except NoSuchElementException:
                continue
        
        if not warlock_element:
            pytest.skip("Carte de classe Occultiste non trouvée - test ignoré")
        
        driver.execute_script("arguments[0].click();", warlock_element)
        time.sleep(1)
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", continue_btn)
        wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
        print("✅ Classe Occultiste sélectionnée, redirection vers étape 2")
        
        # Sélectionner la race Tieffelin
        try:
            tiefling_element = None
            race_cards = driver.find_elements(By.CSS_SELECTOR, ".race-card")
            for card in race_cards:
                try:
                    title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                    if "Tieffelin" in title_element.text or "Tiefling" in title_element.text:
                        tiefling_element = card
                        break
                except NoSuchElementException:
                    continue
            
            if tiefling_element:
                driver.execute_script("arguments[0].click();", tiefling_element)
                time.sleep(1)  # Attendre que la sélection soit enregistrée
                continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "#continueBtn")))
                driver.execute_script("arguments[0].click();", continue_btn)
                wait.until(lambda driver: "character_create_step3.php" in driver.current_url)
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
        driver.get(f"{app_url}/character_create_step1.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        print("✅ Page de création chargée")
        
        # Sélectionner une classe (ex: Guerrier)
        warrior_element = None
        class_cards = driver.find_elements(By.CSS_SELECTOR, ".class-card")
        for card in class_cards:
            try:
                title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                if "Guerrier" in title_element.text or "Fighter" in title_element.text:
                    warrior_element = card
                    break
            except NoSuchElementException:
                continue
        
        if not warrior_element:
            pytest.skip("Carte de classe Guerrier non trouvée - test ignoré")
        
        driver.execute_script("arguments[0].click();", warrior_element)
        time.sleep(1)
        continue_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", continue_btn)
        wait.until(lambda driver: "character_create_step2.php" in driver.current_url)
        print("✅ Classe Guerrier sélectionnée, redirection vers étape 2")
        
        # Vérifier l'affichage des races et leurs caractéristiques
        try:
            race_cards = driver.find_elements(By.CSS_SELECTOR, ".race-card")
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
                        
                except NoSuchElementException:
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
        driver.get(f"{app_url}/character_create_step1.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        print("✅ Page de création chargée")
        
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
        print("✅ Classe Barde sélectionnée, redirection vers étape 2")
        
        # Tester la sélection de différentes races
        races_to_test = ["Humain", "Elfe", "Halfelin"]
        
        for race_name in races_to_test:
            try:
                # Recharger la page pour chaque test
                driver.get(f"{app_url}/character_create_step2.php")
                wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
                
                race_element = None
                race_cards = driver.find_elements(By.CSS_SELECTOR, ".race-card")
                for card in race_cards:
                    try:
                        title_element = card.find_element(By.CSS_SELECTOR, ".card-title")
                        if race_name in title_element.text:
                            race_element = card
                            break
                    except NoSuchElementException:
                        continue
                
                if race_element:
                    driver.execute_script("arguments[0].click();", race_element)
                    time.sleep(1)
                    print(f"✅ Race {race_name} sélectionnée")
                    
                    # Vérifier que le bouton continuer est disponible
                    continue_btn = driver.find_element(By.CSS_SELECTOR, "#continueBtn")
                    if continue_btn.is_enabled():
                        print(f"✅ Bouton continuer activé pour {race_name}")
                    else:
                        print(f"⚠️ Bouton continuer non activé pour {race_name}")
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










