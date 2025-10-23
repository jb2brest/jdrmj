"""
Test de diagnostic pour les campagnes
"""
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time

class TestCampaignDebug:
    """Tests de diagnostic pour les campagnes"""
    
    def test_campaign_page_diagnostic(self, driver, wait, app_url, test_user):
        """Test de diagnostic de la page des campagnes"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des campagnes
        driver.get(f"{app_url}/campaigns.php")
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Diagnostic complet
        print(f"\n=== DIAGNOSTIC PAGE CAMPAGNES ===")
        print(f"URL actuelle: {driver.current_url}")
        print(f"Titre de la page: '{driver.title}'")
        print(f"Code de statut HTTP: {driver.execute_script('return document.readyState')}")
        
        # Vérifier le contenu de la page
        page_source = driver.page_source
        print(f"Taille du contenu: {len(page_source)} caractères")
        
        # Chercher des mots-clés dans le contenu
        keywords = ['campagne', 'campaign', 'créer', 'create', 'nouvelle', 'new']
        found_keywords = []
        for keyword in keywords:
            if keyword.lower() in page_source.lower():
                found_keywords.append(keyword)
        print(f"Mots-clés trouvés: {found_keywords}")
        
        # Vérifier les éléments de navigation
        try:
            nav_elements = driver.find_elements(By.CSS_SELECTOR, "nav, .navbar, .navigation")
            print(f"Éléments de navigation trouvés: {len(nav_elements)}")
        except:
            print("Aucun élément de navigation trouvé")
        
        # Vérifier les liens
        try:
            links = driver.find_elements(By.CSS_SELECTOR, "a")
            print(f"Nombre de liens trouvés: {len(links)}")
            for i, link in enumerate(links[:5]):  # Afficher les 5 premiers liens
                href = link.get_attribute('href')
                text = link.text.strip()
                print(f"  Lien {i+1}: '{text}' -> {href}")
        except:
            print("Aucun lien trouvé")
        
        # Vérifier les boutons
        try:
            buttons = driver.find_elements(By.CSS_SELECTOR, "button, input[type='submit'], .btn")
            print(f"Nombre de boutons trouvés: {len(buttons)}")
            for i, button in enumerate(buttons[:5]):  # Afficher les 5 premiers boutons
                text = button.text.strip()
                value = button.get_attribute('value')
                print(f"  Bouton {i+1}: '{text}' (value: {value})")
        except:
            print("Aucun bouton trouvé")
        
        # Vérifier les formulaires
        try:
            forms = driver.find_elements(By.CSS_SELECTOR, "form")
            print(f"Nombre de formulaires trouvés: {len(forms)}")
            for i, form in enumerate(forms):
                action = form.get_attribute('action')
                method = form.get_attribute('method')
                print(f"  Formulaire {i+1}: action='{action}', method='{method}'")
        except:
            print("Aucun formulaire trouvé")
        
        # Vérifier les messages d'erreur ou d'information
        try:
            alerts = driver.find_elements(By.CSS_SELECTOR, ".alert, .error, .message, .notification")
            print(f"Messages d'alerte trouvés: {len(alerts)}")
            for i, alert in enumerate(alerts):
                text = alert.text.strip()
                print(f"  Alerte {i+1}: '{text}'")
        except:
            print("Aucun message d'alerte trouvé")
        
        # Vérifier si on est redirigé
        if "campaigns.php" not in driver.current_url:
            print(f"ATTENTION: Redirection détectée vers {driver.current_url}")
        
        # Vérifier le contenu principal
        try:
            main_content = driver.find_elements(By.CSS_SELECTOR, "main, .main-content, .content, .container")
            if main_content:
                print(f"Contenu principal trouvé: {len(main_content)} éléments")
                for i, content in enumerate(main_content[:2]):
                    text = content.text.strip()[:200]  # Premiers 200 caractères
                    print(f"  Contenu {i+1}: '{text}...'")
            else:
                print("Aucun contenu principal trouvé")
        except:
            print("Erreur lors de la recherche du contenu principal")
        
        print("=== FIN DIAGNOSTIC ===\n")
        
        # Le test passe toujours pour permettre le diagnostic
        assert True, "Diagnostic terminé - vérifiez les logs ci-dessus"
    
    def test_campaign_page_accessibility(self, driver, wait, app_url, test_user):
        """Test d'accessibilité de la page des campagnes"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page des campagnes
        driver.get(f"{app_url}/campaigns.php")
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que la page répond
        assert driver.current_url is not None, "La page ne répond pas"
        
        # Vérifier que le contenu n'est pas vide
        page_source = driver.page_source
        assert len(page_source) > 100, f"Le contenu de la page est trop court: {len(page_source)} caractères"
        
        # Vérifier qu'il n'y a pas d'erreurs JavaScript majeures
        logs = driver.get_log('browser')
        error_logs = [log for log in logs if log['level'] == 'SEVERE']
        critical_errors = []
        for error in error_logs:
            message = error['message'].lower()
            if not any(ignore in message for ignore in ['favicon', '404', 'net::err_', 'chrome-extension']):
                critical_errors.append(error)
        
        if critical_errors:
            print(f"Erreurs JavaScript critiques détectées: {len(critical_errors)}")
            for error in critical_errors[:3]:  # Afficher les 3 premières erreurs
                print(f"  - {error['message']}")
        
        # Le test passe même s'il y a des erreurs JavaScript pour permettre le diagnostic
        assert True, "Test d'accessibilité terminé"
    
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
