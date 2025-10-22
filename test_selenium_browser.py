#!/usr/bin/env python3
"""
Test Selenium avec navigateur pour l'historique des jets de dés
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.firefox.options import Options
import time
import json

def test_dice_history_with_browser():
    print("🧪 Test Selenium avec navigateur pour l'historique des jets de dés")
    print("=" * 60)
    
    # Configuration Firefox
    firefox_options = Options()
    # firefox_options.add_argument("--headless")  # Commenté pour voir le navigateur
    firefox_options.add_argument("--no-sandbox")
    firefox_options.add_argument("--disable-dev-shm-usage")
    
    driver = None
    try:
        # Initialiser le driver
        driver = webdriver.Firefox(options=firefox_options)
        driver.set_window_size(1920, 1080)
        
        # Aller sur la page de connexion
        print("🌐 Navigation vers la page de connexion...")
        driver.get("http://localhost/jdrmj/login.php")
        time.sleep(2)
        
        # Vérifier si on est sur la page de connexion
        if "Connexion" in driver.title:
            print("✅ Page de connexion chargée")
            
            # Se connecter (vous devrez adapter ces informations)
            username_field = driver.find_element(By.NAME, "username")
            password_field = driver.find_element(By.NAME, "password")
            
            # Remplir les champs (adaptez selon vos identifiants)
            username_field.send_keys("jean")  # Remplacez par votre nom d'utilisateur
            password_field.send_keys("password")  # Remplacez par votre mot de passe
            
            # Cliquer sur le bouton de connexion
            login_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            login_button.click()
            
            # Attendre la redirection
            time.sleep(3)
            
            print("🔐 Tentative de connexion effectuée")
            
            # Vérifier si la connexion a réussi
            if "Connexion" not in driver.title:
                print("✅ Connexion réussie")
                
                # Aller sur la page du lieu
                print("🌐 Navigation vers view_place.php?id=154...")
                driver.get("http://localhost/jdrmj/view_place.php?id=154")
                time.sleep(3)
                
                # Vérifier les éléments de la page
                print("📋 Vérification des éléments de la page:")
                
                # Vérifier la présence de la zone "Jets de dés"
                try:
                    dice_section = driver.find_element(By.XPATH, "//h5[contains(text(), 'Jets de dés')]")
                    print("  ✅ Zone 'Jets de dés' trouvée")
                except:
                    print("  ❌ Zone 'Jets de dés' non trouvée")
                
                # Vérifier la div dice-history
                try:
                    dice_history_div = driver.find_element(By.ID, "dice-history")
                    print("  ✅ Div 'dice-history' trouvée")
                    
                    # Vérifier le contenu initial
                    content = dice_history_div.get_attribute("innerHTML")
                    print(f"  📄 Contenu initial: {content[:100]}...")
                    
                except:
                    print("  ❌ Div 'dice-history' non trouvée")
                
                # Vérifier les variables JavaScript
                print("\n🔍 Vérification des variables JavaScript:")
                
                # Vérifier window.campaignId
                campaign_id = driver.execute_script("return window.campaignId;")
                print(f"  📊 window.campaignId: {campaign_id}")
                
                # Vérifier window.placeId
                place_id = driver.execute_script("return window.placeId;")
                print(f"  📊 window.placeId: {place_id}")
                
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
                
            else:
                print("❌ Connexion échouée - toujours sur la page de connexion")
        else:
            print("❌ Page de connexion non trouvée")
        
        print("\n✅ Test terminé")
        
    except Exception as e:
        print(f"❌ Erreur lors du test: {e}")
        
    finally:
        if driver:
            input("Appuyez sur Entrée pour fermer le navigateur...")
            driver.quit()

if __name__ == "__main__":
    test_dice_history_with_browser()
