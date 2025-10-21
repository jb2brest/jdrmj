#!/usr/bin/env python3
"""
Test simple de connexion pour déboguer
"""

import pytest
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


def test_simple_login(driver, wait, app_url):
    """Test simple de connexion"""
    print("🔍 Test de connexion simple")
    
    # Aller à la page de connexion
    driver.get(f"{app_url}/login.php")
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    
    print("✅ Page de connexion chargée")
    
    # Remplir le formulaire de connexion
    username_input = driver.find_element(By.NAME, "username")
    password_input = driver.find_element(By.NAME, "password")
    
    username_input.clear()
    username_input.send_keys("Jean")
    
    password_input.clear()
    password_input.send_keys("admin123")
    
    print("✅ Formulaire rempli")
    
    # Soumettre le formulaire
    submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
    submit_button.click()
    
    print("✅ Formulaire soumis")
    
    # Attendre la redirection
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    
    print("✅ Redirection effectuée")
    
    # Vérifier qu'on est connecté
    current_url = driver.current_url
    print(f"📍 URL actuelle: {current_url}")
    
    # Aller à la page de gestion des mondes
    driver.get(f"{app_url}/manage_worlds.php")
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    
    print("✅ Page manage_worlds.php chargée")
    
    # Vérifier si le bouton de création existe
    try:
        create_button = driver.find_element(By.CSS_SELECTOR, "[data-bs-target='#createWorldModal']")
        print("✅ Bouton de création trouvé")
        print(f"📍 Texte du bouton: {create_button.text}")
        print(f"📍 Bouton visible: {create_button.is_displayed()}")
    except NoSuchElementException:
        print("❌ Bouton de création non trouvé")
        # Afficher le HTML de la page pour déboguer
        print("📄 HTML de la page:")
        print(driver.page_source[:1000])
    
    # Vérifier le titre de la page
    page_title = driver.title
    print(f"📄 Titre de la page: {page_title}")
    
    # Vérifier s'il y a des messages d'erreur
    try:
        error_elements = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error, [class*='error']")
        if error_elements:
            print("❌ Messages d'erreur trouvés:")
            for error in error_elements:
                print(f"  - {error.text}")
        else:
            print("✅ Aucun message d'erreur")
    except:
        print("⚠️ Impossible de vérifier les messages d'erreur")



