#!/usr/bin/env python3
"""
Script de débogage pour Selenium
"""
import os
import sys
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
import time

def test_selenium_connection():
    """Test simple de connexion Selenium"""
    print("🔍 Test de connexion Selenium...")
    
    # Configuration Chrome
    chrome_options = Options()
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--window-size=1920,1080')
    chrome_options.add_argument('--disable-extensions')
    chrome_options.add_argument('--disable-logging')
    chrome_options.add_argument('--disable-web-security')
    chrome_options.add_argument('--allow-running-insecure-content')
    chrome_options.add_argument('--ignore-certificate-errors')
    chrome_options.add_argument('--ignore-ssl-errors')
    chrome_options.add_argument('--ignore-certificate-errors-spki-list')
    chrome_options.add_argument('--disable-features=VizDisplayCompositor')
    
    # Ne pas utiliser headless pour voir ce qui se passe
    # chrome_options.add_argument('--headless')
    
    try:
        service = Service("/usr/bin/chromedriver")
        driver = webdriver.Chrome(service=service, options=chrome_options)
        
        print("✅ ChromeDriver initialisé avec succès")
        
        # Test 1: Page simple
        print("🌐 Test 1: Accès à Google...")
        driver.get("https://www.google.com")
        time.sleep(2)
        print(f"   Titre: {driver.title}")
        print(f"   URL: {driver.current_url}")
        
        # Test 2: Application locale
        print("🏠 Test 2: Accès à l'application locale...")
        driver.get("http://localhost/jdrmj")
        time.sleep(2)
        print(f"   Titre: {driver.title}")
        print(f"   URL: {driver.current_url}")
        
        # Test 3: Page d'inscription
        print("📝 Test 3: Accès à la page d'inscription...")
        driver.get("http://localhost/jdrmj/register.php")
        time.sleep(2)
        print(f"   Titre: {driver.title}")
        print(f"   URL: {driver.current_url}")
        
        # Afficher le contenu de la page
        page_source = driver.page_source
        if "Inscription" in page_source:
            print("✅ Page d'inscription chargée correctement")
        else:
            print("❌ Page d'inscription non chargée")
            print(f"   Contenu de la page (premiers 500 caractères): {page_source[:500]}")
        
        driver.quit()
        print("✅ Test terminé avec succès")
        
    except Exception as e:
        print(f"❌ Erreur: {e}")
        return False
    
    return True

if __name__ == "__main__":
    success = test_selenium_connection()
    sys.exit(0 if success else 1)
