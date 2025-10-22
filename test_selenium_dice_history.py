#!/usr/bin/env python3
"""
Test Selenium pour l'historique des jets de dés
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.firefox.options import Options
import time
import json

def test_dice_history():
    print("🧪 Test Selenium pour l'historique des jets de dés")
    print("=" * 50)
    
    # Configuration Firefox
    firefox_options = Options()
    firefox_options.add_argument("--headless")  # Mode sans interface
    
    driver = None
    try:
        # Initialiser le driver
        driver = webdriver.Firefox(options=firefox_options)
        driver.set_window_size(1920, 1080)
        
        # Aller sur la page
        url = "http://localhost/jdrmj/view_place.php?id=154"
        print(f"🌐 Navigation vers: {url}")
        driver.get(url)
        
        # Attendre que la page se charge
        time.sleep(3)
        
        # Vérifier les éléments de la zone "Jets de dés"
        print("\n📋 Vérification des éléments de la zone 'Jets de dés':")
        
        # Vérifier la présence de la zone "Jets de dés"
        try:
            dice_section = driver.find_element(By.XPATH, "//h5[contains(text(), 'Jets de dés')]")
            print("  ✅ Zone 'Jets de dés' trouvée")
        except:
            print("  ❌ Zone 'Jets de dés' non trouvée")
            return
        
        # Vérifier les boutons de dés
        dice_buttons = driver.find_elements(By.CSS_SELECTOR, ".dice-btn")
        print(f"  ✅ Boutons de dés trouvés: {len(dice_buttons)}")
        
        # Vérifier la zone d'historique
        try:
            history_section = driver.find_element(By.XPATH, "//h6[contains(text(), 'Historique des jets')]")
            print("  ✅ Section 'Historique des jets' trouvée")
        except:
            print("  ❌ Section 'Historique des jets' non trouvée")
            return
        
        # Vérifier la div dice-history
        try:
            dice_history_div = driver.find_element(By.ID, "dice-history")
            print("  ✅ Div 'dice-history' trouvée")
            
            # Vérifier le contenu initial
            content = dice_history_div.get_attribute("innerHTML")
            print(f"  📄 Contenu initial: {content[:100]}...")
            
        except:
            print("  ❌ Div 'dice-history' non trouvée")
            return
        
        # Vérifier les variables JavaScript
        print("\n🔍 Vérification des variables JavaScript:")
        
        # Vérifier window.campaignId
        campaign_id = driver.execute_script("return window.campaignId;")
        print(f"  📊 window.campaignId: {campaign_id}")
        
        # Vérifier window.placeId
        place_id = driver.execute_script("return window.placeId;")
        print(f"  📊 window.placeId: {place_id}")
        
        # Vérifier window.isOwnerDM
        is_owner_dm = driver.execute_script("return window.isOwnerDM;")
        print(f"  📊 window.isOwnerDM: {is_owner_dm}")
        
        # Tester l'API directement
        print("\n🌐 Test de l'API get_dice_rolls_history.php:")
        
        if campaign_id:
            api_url = f"http://localhost/jdrmj/api/get_dice_rolls_history.php?campaign_id={campaign_id}&show_hidden=false"
            print(f"  🔗 URL API: {api_url}")
            
            # Faire une requête AJAX via JavaScript
            api_response = driver.execute_script(f"""
                return fetch('{api_url}')
                    .then(response => response.json())
                    .then(data => data)
                    .catch(error => {{ error: error.toString() }});
            """)
            
            print(f"  📊 Réponse API: {api_response}")
            
        else:
            print("  ❌ Pas de campaign_id disponible")
        
        # Attendre un peu pour voir si l'historique se charge
        print("\n⏳ Attente du chargement de l'historique...")
        time.sleep(5)
        
        # Vérifier le contenu final de l'historique
        try:
            dice_history_div = driver.find_element(By.ID, "dice-history")
            final_content = dice_history_div.get_attribute("innerHTML")
            print(f"  📄 Contenu final: {final_content[:200]}...")
            
            # Vérifier s'il y a des erreurs dans la console
            logs = driver.get_log('browser')
            if logs:
                print("\n🚨 Erreurs dans la console:")
                for log in logs:
                    if log['level'] == 'SEVERE':
                        print(f"  ❌ {log['message']}")
            
        except Exception as e:
            print(f"  ❌ Erreur lors de la vérification finale: {e}")
        
        print("\n✅ Test terminé")
        
    except Exception as e:
        print(f"❌ Erreur lors du test: {e}")
        
    finally:
        if driver:
            driver.quit()

if __name__ == "__main__":
    test_dice_history()
