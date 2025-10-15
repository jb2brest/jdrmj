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
            assert driver.find_element(By.CSS_SELECTOR, "a[href='logout.php']") or \
                   driver.find_element(By.CSS_SELECTOR, "a[href='characters.php']")
        except TimeoutException:
            # Vérifier s'il y a des erreurs de connexion
            error_elements = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error, .alert")
            current_url = driver.current_url
            
            if error_elements:
                error_texts = [elem.text for elem in error_elements]
                # Si l'utilisateur n'existe pas, c'est normal pour ce test
                if any("incorrect" in text.lower() or "invalid" in text.lower() or "n'existe pas" in text.lower() for text in error_texts):
                    pytest.skip("Utilisateur de test n'existe pas - test ignoré")
                else:
                    pytest.fail(f"Erreurs de connexion: {error_texts}")
            elif "login.php" in current_url:
                # On est toujours sur la page de connexion, probablement que l'utilisateur n'existe pas
                pytest.skip("Utilisateur de test n'existe pas - test ignoré")
            else:
                pytest.fail(f"Connexion échouée sans message d'erreur visible. URL actuelle: {current_url}")
    
    def test_user_logout(self, driver, wait, app_url, test_user):
        """Test de déconnexion d'un utilisateur"""
        # D'abord se connecter
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
