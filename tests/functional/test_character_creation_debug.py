"""
Test de diagnostic pour la création de personnages par étapes
"""
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time

class TestCharacterCreationDebug:
    """Tests de diagnostic pour la création de personnages"""
    
    def test_character_creation_step1_diagnostic(self, driver, wait, app_url, test_user):
        """Test de diagnostic de l'étape 1 de création de personnage"""
        # Se connecter d'abord
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de création de personnage étape 1
        driver.get(f"{app_url}/character_create_step1.php")
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Diagnostic complet
        print(f"\n=== DIAGNOSTIC ÉTAPE 1 CRÉATION PERSONNAGE ===")
        print(f"URL actuelle: {driver.current_url}")
        print(f"Titre de la page: '{driver.title}'")
        print(f"Code de statut HTTP: {driver.execute_script('return document.readyState')}")
        
        # Vérifier le contenu de la page
        page_source = driver.page_source
        print(f"Taille du contenu: {len(page_source)} caractères")
        
        # Chercher des mots-clés dans le contenu
        keywords = ['étape', 'classe', 'guerrier', 'magicien', 'clerc', 'choisissez']
        found_keywords = []
        for keyword in keywords:
            if keyword.lower() in page_source.lower():
                found_keywords.append(keyword)
        print(f"Mots-clés trouvés: {found_keywords}")
        
        # Vérifier les éléments de classe
        try:
            class_elements = driver.find_elements(By.CSS_SELECTOR, ".class-card, [data-class-id], .card")
            print(f"Éléments de classe trouvés: {len(class_elements)}")
            for i, elem in enumerate(class_elements[:5]):
                text = elem.text.strip()
                classes = elem.get_attribute('class')
                data_id = elem.get_attribute('data-class-id')
                print(f"  Élément {i+1}: '{text}' (class: {classes}, data-class-id: {data_id})")
        except:
            print("Aucun élément de classe trouvé")
        
        # Vérifier les boutons
        try:
            buttons = driver.find_elements(By.CSS_SELECTOR, "button, .btn, input[type='submit']")
            print(f"Nombre de boutons trouvés: {len(buttons)}")
            for i, button in enumerate(buttons[:5]):
                text = button.text.strip()
                value = button.get_attribute('value')
                button_type = button.get_attribute('type')
                print(f"  Bouton {i+1}: '{text}' (value: {value}, type: {button_type})")
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
        
        # Vérifier le contenu principal
        try:
            main_content = driver.find_elements(By.CSS_SELECTOR, "main, .main-content, .content, .container")
            if main_content:
                print(f"Contenu principal trouvé: {len(main_content)} éléments")
                for i, content in enumerate(main_content[:2]):
                    text = content.text.strip()[:500]  # Premiers 500 caractères
                    print(f"  Contenu {i+1}: '{text}...'")
            else:
                print("Aucun contenu principal trouvé")
        except:
            print("Erreur lors de la recherche du contenu principal")
        
        print("=== FIN DIAGNOSTIC ===\n")
        
        # Le test passe toujours pour permettre le diagnostic
        assert True, "Diagnostic terminé - vérifiez les logs ci-dessus"
    
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
