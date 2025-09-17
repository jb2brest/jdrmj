#!/usr/bin/env python3
"""
Test simple pour vérifier que Selenium fonctionne
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

def test_simple():
    """Test simple de Selenium"""
    print("🧪 Test simple de Selenium...")
    
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
        # Tester une page simple
        driver.get("https://www.google.com")
        
        # Vérifier que la page se charge
        wait = WebDriverWait(driver, 10)
        wait.until(EC.presence_of_element_located((By.NAME, "q")))
        
        print("✅ Selenium fonctionne correctement!")
        print(f"   Page chargée: {driver.title}")
        
        return True
        
    except Exception as e:
        print(f"❌ Erreur: {e}")
        return False
        
    finally:
        driver.quit()

if __name__ == "__main__":
    success = test_simple()
    sys.exit(0 if success else 1)
