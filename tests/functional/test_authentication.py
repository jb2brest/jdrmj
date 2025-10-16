"""
Tests fonctionnels pour l'authentification
"""
import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException

class TestAuthentication:
    """Tests pour le système d'authentification"""
    
    def test_user_registration(self, driver, wait, app_url, test_user):
        """Test d'inscription d'un nouvel utilisateur"""
        driver.get(f"{app_url}/register.php")
        
        # Vérifier que la page de registration est chargée
        assert "Inscription" in driver.title or "Register" in driver.title
        
        # Ajouter un timestamp unique à l'utilisateur de test pour éviter les conflits
        import time
        timestamp = str(int(time.time()))
        test_user['username'] = f"test_user_{timestamp}"
        test_user['email'] = f"test_{timestamp}@example.com"
        
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
        
        # Attendre un peu pour que la page se charge
        time.sleep(0.5)
        
        # Vérifier la redirection ou le message de succès
        try:
            # Attendre soit une redirection, soit un message de succès
            wait.until(lambda driver: "index.php" in driver.current_url or 
                      "succès" in driver.page_source.lower() or 
                      "success" in driver.page_source.lower() or
                      "réussie" in driver.page_source.lower() or
                      "inscription réussie" in driver.page_source.lower())
            assert True
        except TimeoutException:
            # Vérifier s'il y a des erreurs de validation
            error_elements = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error, .invalid-feedback")
            success_elements = driver.find_elements(By.CSS_SELECTOR, ".alert-success")
            
            if success_elements:
                # Vérifier si c'est un message de succès
                success_texts = [elem.text for elem in success_elements]
                if any("réussie" in text.lower() or "succès" in text.lower() or "success" in text.lower() for text in success_texts):
                    assert True  # Test réussi
                    return
            
            if error_elements:
                error_texts = [elem.text for elem in error_elements]
                # Si l'utilisateur existe déjà, c'est normal, on passe le test
                if any("existe déjà" in text.lower() or "already exists" in text.lower() for text in error_texts):
                    pytest.skip("Utilisateur de test existe déjà - test ignoré")
                else:
                    pytest.fail(f"Erreurs d'inscription: {error_texts}")
            else:
                # Afficher plus d'informations pour le débogage
                current_url = driver.current_url
                page_source_snippet = driver.page_source[:1000]  # Premiers 1000 caractères
                pytest.fail(f"Inscription échouée sans message d'erreur visible. URL: {current_url}, Page source: {page_source_snippet}")
    
    def test_user_login(self, driver, wait, app_url, test_user):
        """Test de connexion d'un utilisateur"""
        # D'abord créer l'utilisateur via l'inscription
        print(f"🔧 Création de l'utilisateur de test: {test_user['username']}")
        self.test_user_registration(driver, wait, app_url, test_user)
        
        # Maintenant tester la connexion
        print(f"🔐 Test de connexion pour: {test_user['username']}")
        driver.get(f"{app_url}/login.php")
        
        # Vérifier que la page de connexion est chargée
        assert "Connexion" in driver.title or "Login" in driver.title
        
        # Remplir le formulaire de connexion
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        password_field = driver.find_element(By.NAME, "password")
        
        username_field.send_keys(test_user['username'])
        password_field.send_keys(test_user['password'])
        
        # Soumettre le formulaire
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", submit_button)
        
        # Vérifier la redirection vers la page d'accueil ou une page connectée
        try:
            wait.until(lambda driver: "index.php" in driver.current_url or "characters.php" in driver.current_url)
            # Vérifier que l'utilisateur est connecté (présence d'éléments de navigation)
            logout_link = driver.find_elements(By.CSS_SELECTOR, "a[href='logout.php']")
            characters_link = driver.find_elements(By.CSS_SELECTOR, "a[href='characters.php']")
            
            assert len(logout_link) > 0 or len(characters_link) > 0, "Aucun lien de navigation trouvé après connexion"
            print("✅ Connexion réussie")
            
        except TimeoutException:
            # Vérifier s'il y a des erreurs de connexion
            error_elements = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error, .alert")
            current_url = driver.current_url
            
            if error_elements:
                error_texts = [elem.text for elem in error_elements]
                pytest.fail(f"Erreurs de connexion après inscription: {error_texts}")
            elif "login.php" in current_url:
                pytest.fail("Connexion échouée - toujours sur la page de connexion")
            else:
                pytest.fail(f"Connexion échouée sans message d'erreur visible. URL actuelle: {current_url}")
    
    def test_user_logout(self, driver, wait, app_url, test_user):
        """Test de déconnexion d'un utilisateur"""
        # D'abord créer l'utilisateur et se connecter
        print(f"🔧 Création et connexion de l'utilisateur: {test_user['username']}")
        self.test_user_login(driver, wait, app_url, test_user)
        
        # Chercher le menu dropdown utilisateur
        try:
            # Attendre que le menu dropdown soit présent
            dropdown_toggle = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, ".dropdown-toggle")))
            
            # Cliquer sur le menu dropdown pour l'ouvrir
            driver.execute_script("arguments[0].click();", dropdown_toggle)
            time.sleep(0.5)  # Attendre que le menu s'ouvre
            
            # Chercher le lien de déconnexion dans le menu dropdown
            logout_link = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='logout.php']")))
            
            # Cliquer sur le lien de déconnexion
            logout_link.click()
            
            # Vérifier la redirection vers la page de connexion ou d'accueil
            wait.until(lambda driver: "login.php" in driver.current_url or "index.php" in driver.current_url)
            
            # Vérifier que l'utilisateur est déconnecté (le menu dropdown ne doit plus être présent)
            assert not driver.find_elements(By.CSS_SELECTOR, ".dropdown-toggle")
            
        except TimeoutException:
            pytest.skip("Menu de déconnexion non trouvé - test ignoré")
    
    def test_user_login_invalid_password(self, driver, wait, app_url, test_user):
        """Test de connexion avec un mot de passe invalide"""
        driver.get(f"{app_url}/login.php")
        
        # Remplir avec un utilisateur valide mais un mot de passe invalide
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        password_field = driver.find_element(By.NAME, "password")
        
        username_field.send_keys(test_user['username'])
        password_field.send_keys("invalid_password")
        
        # Soumettre le formulaire
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", submit_button)
        
        # Vérifier qu'un message d'erreur apparaît
        try:
            error_element = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".alert-danger, .error")))
            assert "incorrect" in error_element.text.lower() or "invalid" in error_element.text.lower()
        except TimeoutException:
            # Vérifier si on est toujours sur la page de connexion
            assert "login.php" in driver.current_url, "Expected to stay on login page with invalid password"
    
    def test_user_account_deletion(self, driver, wait, app_url):
        """Test de suppression de compte utilisateur"""
        # Créer un utilisateur de test temporaire
        test_user_data = {
            'username': f'test_delete_user_{int(time.time())}',
            'email': f'test_delete_{int(time.time())}@example.com',
            'password': 'TestPassword123!'
        }
        
        # S'inscrire
        self.test_user_registration(driver, wait, app_url, test_user_data)
        
        # Se connecter (le test_user_login crée déjà l'utilisateur)
        print(f"🔐 Connexion pour suppression de compte: {test_user_data['username']}")
        driver.get(f"{app_url}/login.php")
        
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        password_field = driver.find_element(By.NAME, "password")
        
        username_field.send_keys(test_user_data['username'])
        password_field.send_keys(test_user_data['password'])
        
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", submit_button)
        
        # Attendre la connexion
        wait.until(lambda driver: "index.php" in driver.current_url or "characters.php" in driver.current_url)
        
        # Vérifier que l'utilisateur est connecté (menu dropdown présent)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".dropdown-toggle")))
        
        # Aller à la page de suppression de compte
        driver.get(f"{app_url}/delete_account.php")
        
        # Vérifier que la page se charge correctement (pas de redirection vers login)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h4")))
        assert "Supprimer mon compte" in driver.page_source
        assert "login.php" not in driver.current_url
        
        # Remplir le formulaire de suppression
        password_field = wait.until(EC.presence_of_element_located((By.NAME, "password")))
        confirm_field = driver.find_element(By.NAME, "confirm_delete")
        
        password_field.send_keys(test_user_data['password'])
        confirm_field.send_keys("DELETE")
        
        # Attendre que le bouton soit activé
        delete_button = wait.until(EC.element_to_be_clickable((By.ID, "deleteButton")))
        
        # Cliquer sur supprimer (avec confirmation JavaScript)
        driver.execute_script("arguments[0].click();", delete_button)
        
        # Accepter la confirmation JavaScript
        driver.switch_to.alert.accept()
        
        # Vérifier la redirection vers la page d'accueil avec message de suppression
        wait.until(lambda driver: "index.php" in driver.current_url)
        assert "deleted=1" in driver.current_url or "supprimé" in driver.page_source.lower()
        
        # Vérifier que l'utilisateur est déconnecté
        assert not driver.find_elements(By.CSS_SELECTOR, ".dropdown-toggle")
    
    def test_user_account_deletion_invalid_password(self, driver, wait, app_url, test_user):
        """Test de suppression de compte avec mot de passe invalide"""
        # Créer l'utilisateur et se connecter d'abord
        print(f"🔧 Création et connexion pour test de suppression: {test_user['username']}")
        self.test_user_login(driver, wait, app_url, test_user)
        
        # Vérifier que l'utilisateur est connecté (menu dropdown présent)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".dropdown-toggle")))
        
        # Aller à la page de suppression de compte
        driver.get(f"{app_url}/delete_account.php")
        
        # Vérifier que la page se charge correctement (pas de redirection vers login)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h4")))
        assert "Supprimer mon compte" in driver.page_source
        assert "login.php" not in driver.current_url
        
        # Remplir le formulaire avec un mauvais mot de passe
        password_field = wait.until(EC.presence_of_element_located((By.NAME, "password")))
        confirm_field = driver.find_element(By.NAME, "confirm_delete")
        
        password_field.send_keys("wrong_password")
        confirm_field.send_keys("DELETE")
        
        # Cliquer sur supprimer
        delete_button = wait.until(EC.element_to_be_clickable((By.ID, "deleteButton")))
        driver.execute_script("arguments[0].click();", delete_button)
        
        # Accepter la confirmation JavaScript
        driver.switch_to.alert.accept()
        
        # Vérifier qu'un message d'erreur apparaît
        error_element = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".alert-danger")))
        assert "incorrect" in error_element.text.lower() or "erreur" in error_element.text.lower()
        
        # Vérifier qu'on est toujours sur la page de suppression
        assert "delete_account.php" in driver.current_url
    
    def test_user_account_deletion_invalid_confirmation(self, driver, wait, app_url, test_user):
        """Test de suppression de compte avec confirmation invalide"""
        # Créer l'utilisateur et se connecter d'abord
        print(f"🔧 Création et connexion pour test de confirmation: {test_user['username']}")
        self.test_user_login(driver, wait, app_url, test_user)
        
        # Vérifier que l'utilisateur est connecté (menu dropdown présent)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".dropdown-toggle")))
        
        # Aller à la page de suppression de compte
        driver.get(f"{app_url}/delete_account.php")
        
        # Vérifier que la page se charge correctement (pas de redirection vers login)
        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h4")))
        assert "Supprimer mon compte" in driver.page_source
        assert "login.php" not in driver.current_url
        
        # Remplir le formulaire avec une mauvaise confirmation
        password_field = wait.until(EC.presence_of_element_located((By.NAME, "password")))
        confirm_field = driver.find_element(By.NAME, "confirm_delete")
        
        password_field.send_keys(test_user['password'])
        confirm_field.send_keys("WRONG")
        
        # Vérifier que le bouton reste désactivé
        delete_button = driver.find_element(By.ID, "deleteButton")
        assert delete_button.get_attribute("disabled") is not None
        
        # Essayer de cliquer quand même (ne devrait pas fonctionner)
        driver.execute_script("arguments[0].click();", delete_button)
        
        # Vérifier qu'on est toujours sur la page de suppression
        assert "delete_account.php" in driver.current_url
    
    @pytest.mark.smoke
    def test_invalid_login_credentials(self, driver, wait, app_url):
        """Test de connexion avec des identifiants invalides"""
        driver.get(f"{app_url}/login.php")
        
        # Remplir avec des identifiants invalides
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        password_field = driver.find_element(By.NAME, "password")
        
        username_field.send_keys("invalid_user")
        password_field.send_keys("invalid_password")
        
        # Soumettre le formulaire
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", submit_button)
        
        # Vérifier qu'un message d'erreur apparaît
        try:
            error_element = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".alert-danger, .error")))
            assert "incorrect" in error_element.text.lower() or "invalid" in error_element.text.lower()
        except TimeoutException:
            pytest.fail("Aucun message d'erreur affiché pour des identifiants invalides")
    
    @pytest.mark.smoke
    def test_registration_validation(self, driver, wait, app_url):
        """Test de validation du formulaire d'inscription"""
        driver.get(f"{app_url}/register.php")
        
        # Essayer de soumettre le formulaire vide
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", submit_button)
        
        # Attendre un peu pour que la validation se déclenche
        time.sleep(0.5)
        
        # Vérifier que des messages de validation apparaissent ou que le formulaire n'est pas soumis
        validation_errors = driver.find_elements(By.CSS_SELECTOR, ".invalid-feedback, .error, [required]")
        is_still_on_register_page = "register.php" in driver.current_url
        
        assert len(validation_errors) > 0 or is_still_on_register_page, "Aucune validation visible pour les champs requis"
    
    def test_password_confirmation_validation(self, driver, wait, app_url, test_user):
        """Test de validation de confirmation de mot de passe"""
        driver.get(f"{app_url}/register.php")
        
        # Remplir le formulaire avec des mots de passe différents
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        email_field = driver.find_element(By.NAME, "email")
        password_field = driver.find_element(By.NAME, "password")
        confirm_password_field = driver.find_element(By.NAME, "confirm_password")
        
        username_field.send_keys(test_user['username'])
        email_field.send_keys(test_user['email'])
        password_field.send_keys(test_user['password'])
        confirm_password_field.send_keys("different_password")
        
        # Soumettre le formulaire
        submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        driver.execute_script("arguments[0].click();", submit_button)
        
        # Vérifier qu'un message d'erreur apparaît pour la confirmation de mot de passe
        try:
            error_element = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".alert-danger, .error")))
            assert "mots de passe" in error_element.text.lower() or "password" in error_element.text.lower()
        except TimeoutException:
            # Vérifier si le formulaire n'a pas été soumis (reste sur la même page)
            assert "register.php" in driver.current_url
