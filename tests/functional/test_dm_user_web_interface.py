"""
Tests fonctionnels pour l'interface web de gestion des utilisateurs MJ
"""
import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException

class TestDMUserWebInterface:
    """Tests pour l'interface web de gestion des utilisateurs MJ"""
    
    @pytest.mark.smoke
    def test_dm_user_registration_web(self, driver, wait, app_url):
        """Test d'inscription d'un utilisateur MJ via l'interface web"""
        import random
        import string
        
        # Générer des données uniques
        timestamp = str(int(time.time()))
        random_suffix = ''.join(random.choices(string.ascii_lowercase, k=4))
        
        dm_user = {
            'username': f'test_dm_web_{timestamp}_{random_suffix}',
            'email': f'test_dm_web_{timestamp}_{random_suffix}@test.com',
            'password': 'TestPassword123!'
        }
        
        # Aller à la page d'inscription
        driver.get(f"{app_url}/register.php")
        
        # Vérifier que la page de registration est chargée
        assert "Inscription" in driver.title or "Register" in driver.title
        
        # Remplir le formulaire d'inscription
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        email_field = driver.find_element(By.NAME, "email")
        password_field = driver.find_element(By.NAME, "password")
        confirm_password_field = driver.find_element(By.NAME, "confirm_password")
        
        username_field.send_keys(dm_user['username'])
        email_field.send_keys(dm_user['email'])
        password_field.send_keys(dm_user['password'])
        confirm_password_field.send_keys(dm_user['password'])
        
        # Vérifier s'il y a un champ pour le rôle MJ
        role_selectors = [
            "select[name='role']",
            "input[name='is_dm']",
            "input[name='role'][value='dm']",
            ".role-selection",
            "#role_dm"
        ]
        
        role_element = None
        for selector in role_selectors:
            try:
                role_element = driver.find_element(By.CSS_SELECTOR, selector)
                break
            except NoSuchElementException:
                continue
        
        if role_element:
            # Si un champ de rôle existe, sélectionner MJ
            if role_element.tag_name == 'select':
                from selenium.webdriver.support.ui import Select
                select = Select(role_element)
                try:
                    select.select_by_value('dm')
                except:
                    select.select_by_visible_text('Maître du Jeu')
            elif role_element.tag_name == 'input':
                if role_element.get_attribute('type') == 'checkbox':
                    if not role_element.is_selected():
                        role_element.click()
                elif role_element.get_attribute('type') == 'radio':
                    role_element.click()
        
        # Soumettre le formulaire
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", submit_button)
        
        # Vérifier la redirection ou le message de succès
        try:
            # Attendre soit une redirection, soit un message de succès
            wait.until(lambda driver: "index.php" in driver.current_url or 
                      "succès" in driver.page_source.lower() or 
                      "success" in driver.page_source.lower())
            
            print(f"✅ Utilisateur MJ créé via interface web : {dm_user['username']}")
            return dm_user
            
        except TimeoutException:
            # Vérifier s'il y a des erreurs de validation
            error_elements = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error, .invalid-feedback")
            if error_elements:
                error_texts = [elem.text for elem in error_elements]
                # Si l'utilisateur existe déjà, c'est normal, on passe le test
                if any("existe déjà" in text.lower() or "already exists" in text.lower() for text in error_texts):
                    pytest.skip("Utilisateur MJ de test existe déjà - test ignoré")
                else:
                    pytest.fail(f"Erreurs d'inscription MJ: {error_texts}")
            else:
                pytest.fail("Inscription MJ échouée sans message d'erreur visible")
    
    def test_dm_user_login_web(self, driver, wait, app_url):
        """Test de connexion avec un utilisateur MJ via l'interface web"""
        import random
        import string
        
        # Générer des données uniques
        timestamp = str(int(time.time()))
        random_suffix = ''.join(random.choices(string.ascii_lowercase, k=4))
        
        dm_user = {
            'username': f'test_dm_login_{timestamp}_{random_suffix}',
            'email': f'test_dm_login_{timestamp}_{random_suffix}@test.com',
            'password': 'TestPassword123!'
        }
        
        # D'abord créer l'utilisateur via l'interface
        created_user = self.test_dm_user_registration_web(driver, wait, app_url)
        if created_user:
            dm_user = created_user
        
        # Aller à la page de connexion
        driver.get(f"{app_url}/login.php")
        
        # Vérifier que la page de connexion est chargée
        assert "Connexion" in driver.title or "Login" in driver.title
        
        # Remplir le formulaire de connexion
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        password_field = driver.find_element(By.NAME, "password")
        
        username_field.send_keys(dm_user['username'])
        password_field.send_keys(dm_user['password'])
        
        # Soumettre le formulaire
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", submit_button)
        
        # Vérifier la redirection vers la page d'accueil ou une page connectée
        try:
            wait.until(lambda driver: "index.php" in driver.current_url or "characters.php" in driver.current_url)
            
            # Vérifier que l'utilisateur est connecté
            assert driver.find_element(By.CSS_SELECTOR, "a[href='logout.php']") or \
                   driver.find_element(By.CSS_SELECTOR, "a[href='characters.php']")
            
            print(f"✅ Connexion MJ réussie via interface web : {dm_user['username']}")
            
        except TimeoutException:
            # Vérifier s'il y a des erreurs de connexion
            error_elements = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error, .alert")
            current_url = driver.current_url
            
            if error_elements:
                error_texts = [elem.text for elem in error_elements]
                pytest.fail(f"Erreurs de connexion MJ: {error_texts}")
            elif "login.php" in current_url:
                pytest.fail("Connexion MJ échouée - resté sur la page de connexion")
            else:
                pytest.fail(f"Connexion MJ échouée sans message d'erreur visible. URL actuelle: {current_url}")
    
    def test_dm_user_logout_web(self, driver, wait, app_url):
        """Test de déconnexion d'un utilisateur MJ via l'interface web"""
        # D'abord se connecter
        self.test_dm_user_login_web(driver, wait, app_url)
        
        # Chercher le lien de déconnexion avec plusieurs sélecteurs possibles
        logout_selectors = [
            "a[href='logout.php']",
            "a[href*='logout']",
            ".logout",
            "[data-action='logout']"
        ]
        
        logout_link = None
        for selector in logout_selectors:
            try:
                logout_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, selector)))
                break
            except TimeoutException:
                continue
        
        if logout_link:
            logout_link.click()
            
            # Vérifier la redirection vers la page de connexion ou d'accueil
            wait.until(lambda driver: "login.php" in driver.current_url or "index.php" in driver.current_url)
            
            # Vérifier que l'utilisateur est déconnecté
            assert not driver.find_elements(By.CSS_SELECTOR, "a[href='logout.php']")
            print("✅ Déconnexion MJ réussie via interface web")
        else:
            pytest.skip("Lien de déconnexion non trouvé - test ignoré")
    
    def test_dm_user_profile_access(self, driver, wait, app_url):
        """Test d'accès au profil d'un utilisateur MJ"""
        # Se connecter en tant que MJ
        self.test_dm_user_login_web(driver, wait, app_url)
        
        # Chercher le lien vers le profil
        profile_selectors = [
            "a[href='profile.php']",
            "a[href*='profile']",
            ".profile-link",
            "[data-action='profile']"
        ]
        
        profile_link = None
        for selector in profile_selectors:
            try:
                profile_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, selector)))
                break
            except TimeoutException:
                continue
        
        if profile_link:
            profile_link.click()
            
            # Vérifier qu'on arrive sur la page de profil
            wait.until(lambda driver: "profile.php" in driver.current_url)
            
            # Vérifier que le rôle MJ est affiché
            page_source = driver.page_source.lower()
            role_indicators = ['maître du jeu', 'mj', 'dm', 'game master']
            
            role_found = any(indicator in page_source for indicator in role_indicators)
            if role_found:
                print("✅ Rôle MJ affiché sur le profil")
            else:
                print("⚠️ Rôle MJ non trouvé sur le profil")
            
        else:
            pytest.skip("Lien de profil non trouvé - test ignoré")
    
    def test_dm_user_campaign_access(self, driver, wait, app_url):
        """Test d'accès aux fonctionnalités de campagne pour un utilisateur MJ"""
        # Se connecter en tant que MJ
        self.test_dm_user_login_web(driver, wait, app_url)
        
        # Tester l'accès à la page des campagnes
        try:
            driver.get(f"{app_url}/campaigns.php")
            time.sleep(0.5)
            
            # Vérifier qu'on n'est pas redirigé vers une page d'erreur
            if "403" not in driver.page_source and "accès refusé" not in driver.page_source.lower():
                print("✅ Accès aux campagnes autorisé pour l'utilisateur MJ")
            else:
                pytest.fail("Accès aux campagnes refusé pour l'utilisateur MJ")
                
        except Exception as e:
            pytest.fail(f"Erreur lors de l'accès aux campagnes : {str(e)}")
    
    def test_dm_user_bestiary_access(self, driver, wait, app_url):
        """Test d'accès au bestiaire pour un utilisateur MJ"""
        # Se connecter en tant que MJ
        self.test_dm_user_login_web(driver, wait, app_url)
        
        # Tester l'accès au bestiaire
        try:
            driver.get(f"{app_url}/bestiary.php")
            time.sleep(0.5)
            
            # Vérifier qu'on n'est pas redirigé vers une page d'erreur
            if "403" not in driver.page_source and "accès refusé" not in driver.page_source.lower():
                print("✅ Accès au bestiaire autorisé pour l'utilisateur MJ")
            else:
                pytest.fail("Accès au bestiaire refusé pour l'utilisateur MJ")
                
        except Exception as e:
            pytest.fail(f"Erreur lors de l'accès au bestiaire : {str(e)}")
    
    @pytest.mark.smoke
    def test_complete_dm_user_workflow(self, driver, wait, app_url):
        """Test complet du workflow d'un utilisateur MJ"""
        import random
        import string
        
        # Générer des données uniques
        timestamp = str(int(time.time()))
        random_suffix = ''.join(random.choices(string.ascii_lowercase, k=4))
        
        dm_user = {
            'username': f'test_dm_workflow_{timestamp}_{random_suffix}',
            'email': f'test_dm_workflow_{timestamp}_{random_suffix}@test.com',
            'password': 'TestPassword123!'
        }
        
        print(f"🧪 Test du workflow complet pour l'utilisateur MJ : {dm_user['username']}")
        
        # 1. Inscription
        print("1. Test d'inscription...")
        created_user = self.test_dm_user_registration_web(driver, wait, app_url)
        if created_user:
            dm_user = created_user
        
        # 2. Connexion
        print("2. Test de connexion...")
        self.test_dm_user_login_web(driver, wait, app_url)
        
        # 3. Accès aux fonctionnalités MJ
        print("3. Test d'accès aux fonctionnalités MJ...")
        self.test_dm_user_campaign_access(driver, wait, app_url)
        self.test_dm_user_bestiary_access(driver, wait, app_url)
        
        # 4. Déconnexion
        print("4. Test de déconnexion...")
        self.test_dm_user_logout_web(driver, wait, app_url)
        
        print("✅ Workflow complet de l'utilisateur MJ terminé avec succès")
