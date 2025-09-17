#!/usr/bin/env python3
"""
Test de diagnostic pour identifier les problèmes
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

def test_application_diagnostic():
    """Test de diagnostic de l'application"""
    print("🔍 Diagnostic de l'application JDR 4 MJ")
    print("=" * 50)
    
    # Configuration Chrome
    chrome_options = Options()
    chrome_options.add_argument('--headless')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    
    # Créer le driver
    service = Service("/usr/local/bin/chromedriver")
    driver = webdriver.Chrome(service=service, options=chrome_options)
    
    try:
        app_url = "http://localhost/jdrmj"
        
        # Test 1: Page d'accueil
        print("1. Test de la page d'accueil...")
        driver.get(app_url)
        time.sleep(2)
        
        title = driver.title
        print(f"   Titre de la page: {title}")
        
        if "JDR" in title or "D&D" in title:
            print("   ✅ Page d'accueil accessible")
        else:
            print(f"   ❌ Page d'accueil inattendue: {title}")
        
        # Test 2: Page de connexion
        print("2. Test de la page de connexion...")
        driver.get(f"{app_url}/login.php")
        time.sleep(2)
        
        title = driver.title
        print(f"   Titre de la page: {title}")
        
        if "Connexion" in title or "Login" in title:
            print("   ✅ Page de connexion accessible")
            
            # Vérifier les champs
            try:
                username_field = driver.find_element(By.NAME, "username")
                password_field = driver.find_element(By.NAME, "password")
                print("   ✅ Champs de connexion présents")
            except:
                print("   ❌ Champs de connexion manquants")
        else:
            print(f"   ❌ Page de connexion inattendue: {title}")
        
        # Test 3: Page d'inscription
        print("3. Test de la page d'inscription...")
        driver.get(f"{app_url}/register.php")
        time.sleep(2)
        
        title = driver.title
        print(f"   Titre de la page: {title}")
        
        if "Inscription" in title or "Register" in title:
            print("   ✅ Page d'inscription accessible")
        else:
            print(f"   ❌ Page d'inscription inattendue: {title}")
        
        # Test 4: Test de connexion avec utilisateur inexistant
        print("4. Test de connexion avec utilisateur inexistant...")
        driver.get(f"{app_url}/login.php")
        
        try:
            username_field = driver.find_element(By.NAME, "username")
            password_field = driver.find_element(By.NAME, "password")
            
            username_field.send_keys("utilisateur_inexistant")
            password_field.send_keys("motdepasse_inexistant")
            
            submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            driver.execute_script("arguments[0].click();", submit_button)
            
            time.sleep(3)
            
            # Vérifier s'il y a un message d'erreur
            error_elements = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error")
            if error_elements:
                error_text = error_elements[0].text
                print(f"   ✅ Message d'erreur affiché: {error_text}")
            else:
                print("   ⚠️  Aucun message d'erreur visible")
                
        except Exception as e:
            print(f"   ❌ Erreur lors du test de connexion: {e}")
        
        # Test 5: Vérifier les logs de la console
        print("5. Vérification des erreurs JavaScript...")
        logs = driver.get_log('browser')
        error_logs = [log for log in logs if log['level'] == 'SEVERE']
        
        if error_logs:
            print(f"   ⚠️  {len(error_logs)} erreurs JavaScript trouvées:")
            for log in error_logs[:3]:  # Afficher seulement les 3 premières
                print(f"      - {log['message']}")
        else:
            print("   ✅ Aucune erreur JavaScript critique")
        
        print("\n🎯 Résumé du diagnostic:")
        print("   - Si tous les tests sont ✅, l'application est accessible")
        print("   - Si des tests sont ❌, vérifiez que l'application est en cours d'exécution")
        print("   - L'URL testée était:", app_url)
        
        return True
        
    except Exception as e:
        print(f"❌ Erreur lors du diagnostic: {e}")
        return False
        
    finally:
        driver.quit()

if __name__ == "__main__":
    success = test_application_diagnostic()
    sys.exit(0 if success else 1)
