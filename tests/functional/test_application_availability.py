"""
Tests pour vérifier la disponibilité de l'application
"""
import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException

class TestApplicationAvailability:
    """Tests pour vérifier que l'application est accessible"""
    
    def test_application_homepage_accessible(self, driver, wait, app_url):
        """Test que la page d'accueil est accessible"""
        driver.get(app_url)
        
        # Vérifier que la page se charge (peut être index.php ou login.php)
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que le titre contient "JDR" ou "D&D"
        title = driver.title
        assert "JDR" in title or "D&D" in title or "Donjon" in title, f"Titre inattendu: {title}"
    
    def test_login_page_accessible(self, driver, wait, app_url):
        """Test que la page de connexion est accessible"""
        driver.get(f"{app_url}/login.php")
        
        # Vérifier que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que c'est bien une page de connexion
        title = driver.title
        assert "Connexion" in title or "Login" in title, f"Titre inattendu: {title}"
        
        # Vérifier la présence des champs de connexion
        assert driver.find_elements(By.NAME, "username"), "Champ username non trouvé"
        assert driver.find_elements(By.NAME, "password"), "Champ password non trouvé"
    
    def test_register_page_accessible(self, driver, wait, app_url):
        """Test que la page d'inscription est accessible"""
        driver.get(f"{app_url}/register.php")
        
        # Vérifier que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que c'est bien une page d'inscription
        title = driver.title
        assert "Inscription" in title or "Register" in title, f"Titre inattendu: {title}"
        
        # Vérifier la présence des champs d'inscription
        assert driver.find_elements(By.NAME, "username"), "Champ username non trouvé"
        assert driver.find_elements(By.NAME, "email"), "Champ email non trouvé"
        assert driver.find_elements(By.NAME, "password"), "Champ password non trouvé"
    
    def test_application_responsive(self, driver, wait, app_url):
        """Test que l'application est responsive"""
        driver.get(app_url)
        
        # Tester différentes tailles d'écran
        sizes = [(1920, 1080), (1366, 768), (768, 1024), (375, 667)]
        
        for width, height in sizes:
            driver.set_window_size(width, height)
            time.sleep(0.5)  # Attendre le redimensionnement
            
            # Vérifier que la page est toujours visible
            body = driver.find_element(By.TAG_NAME, "body")
            assert body.is_displayed(), f"Page non visible à {width}x{height}"
    
    def test_application_no_javascript_errors(self, driver, wait, app_url):
        """Test qu'il n'y a pas d'erreurs JavaScript majeures"""
        driver.get(app_url)
        
        # Attendre que la page se charge complètement
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        time.sleep(0.5)  # Attendre les scripts
        
        # Vérifier les logs de la console (si disponibles)
        logs = driver.get_log('browser')
        error_logs = [log for log in logs if log['level'] == 'SEVERE']
        
        # Ignorer certaines erreurs communes qui ne cassent pas l'application
        critical_errors = []
        for error in error_logs:
            message = error['message'].lower()
            if not any(ignore in message for ignore in ['favicon', '404', 'net::err_', 'chrome-extension']):
                critical_errors.append(error)
        
        assert len(critical_errors) == 0, f"Erreurs JavaScript critiques: {critical_errors}"
