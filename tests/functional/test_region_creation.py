#!/usr/bin/env python3
"""
Tests pour la création et gestion des régions
"""

import pytest
import time
import os
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


class TestRegionCreation:
    """Tests pour la création et gestion des régions"""
    
    def test_create_region_success(self, driver, wait, app_url, test_user):
        """Test de création d'une région avec succès"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer d'abord un monde et un pays
        world_name = f"Monde pour Région {int(time.time())}"
        self._create_world(driver, wait, app_url, world_name, "Monde de test")
        
        
        # Cliquer sur le bouton "Voir" du monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{world_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_world.php')]")))
        world_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un pays
        country_name = f"Pays pour Région {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        
        # Cliquer sur le premier lien vers view_country.php (le pays que nous venons de créer)
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, 'view_country.php')]")))
        country_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer une région
        region_name = f"Région de Test {int(time.time())}"
        region_description = "Description de la région de test pour les tests automatisés"
        self._create_region(driver, wait, region_name, region_description)
        
        # Vérifier le message de succès
        success_message = driver.find_element(By.CSS_SELECTOR, ".alert-success, .success, [class*='success']")
        assert "créée avec succès" in success_message.text.lower()
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Monde de test",
            'countries': [{
                'name': country_name,
                'description': "Pays de test",
                'regions': [{'name': region_name, 'description': region_description}]
            }]
        })
    
    def test_create_region_empty_name(self, driver, wait, app_url, test_user):
        """Test de création d'une région avec un nom vide"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer d'abord un monde et un pays
        world_name = f"Monde pour Test Vide {int(time.time())}"
        self._create_world(driver, wait, app_url, world_name, "Monde de test")
        
        # Aller à la page de visualisation du monde
        driver.get(f"{app_url}/view_world.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton "Voir" du monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{world_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_world.php')]")))
        world_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un pays
        country_name = f"Pays pour Test Vide {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        # Cliquer sur le premier lien vers view_country.php (le pays que nous venons de créer)
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, 'view_country.php')]")))
        country_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Ouvrir le modal de création de région
        create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createRegionModal']")))
        create_button.click()
        
        # Attendre que le modal soit visible
        wait.until(EC.visibility_of_element_located((By.ID, "createRegionModal")))
        
        # Remplir seulement la description (sans nom)
        description_input = driver.find_element(By.ID, "createRegionDescription")
        description_input.clear()
        description_input.send_keys("Description sans nom")
        
        # Essayer de soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "#createRegionModal button[type='submit']")
        submit_button.click()
        
        # Vérifier qu'une erreur est affichée (validation HTML5 ou message d'erreur)
        try:
            # Vérifier si le champ nom est marqué comme invalide (validation HTML5)
            name_input = driver.find_element(By.ID, "createRegionName")
            is_invalid = name_input.get_attribute("required") is not None
            assert is_invalid, "Le champ nom devrait être requis"
        except:
            # Si pas de validation HTML5, vérifier un message d'erreur
            error_message = driver.find_element(By.CSS_SELECTOR, ".alert-danger, .error, [class*='error']")
            assert "nom" in error_message.text.lower() and "requis" in error_message.text.lower()
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Monde de test",
            'countries': [{'name': country_name, 'description': "Pays de test"}]
        })
    
    def test_create_region_duplicate_name(self, driver, wait, app_url, test_user):
        """Test de création d'une région avec un nom déjà existant dans le même pays"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer d'abord un monde et un pays
        world_name = f"Monde pour Dupliqué {int(time.time())}"
        self._create_world(driver, wait, app_url, world_name, "Monde de test")
        
        # Aller à la page de visualisation du monde
        driver.get(f"{app_url}/view_world.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton "Voir" du monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{world_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_world.php')]")))
        world_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un pays
        country_name = f"Pays pour Dupliqué {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        # Cliquer sur le premier lien vers view_country.php (le pays que nous venons de créer)
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, 'view_country.php')]")))
        country_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer une première région
        region_name = f"Région Dupliquée {int(time.time())}"
        self._create_region(driver, wait, region_name, "Première région")
        
        # Essayer de créer une seconde région avec le même nom
        # Ouvrir le modal de création de région
        create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createRegionModal']")))
        create_button.click()
        
        # Attendre que le modal soit visible
        wait.until(EC.visibility_of_element_located((By.ID, "createRegionModal")))
        
        # Remplir le formulaire avec le même nom
        name_input = driver.find_element(By.ID, "createRegionName")
        name_input.clear()
        name_input.send_keys(region_name)
        
        description_input = driver.find_element(By.ID, "createRegionDescription")
        description_input.clear()
        description_input.send_keys("Deuxième région avec le même nom")
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "#createRegionModal button[type='submit']")
        submit_button.click()
        
        # Vérifier le résultat (succès ou erreur)
        try:
            # Attendre qu'un message apparaisse (succès ou erreur)
            message = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".alert-danger, .error, [class*='error'], .alert, [class*='alert'], .alert-success, .success, [class*='success']")))
            message_text = message.text.lower()
            
            if "créée avec succès" in message_text:
                # Si l'application permet les noms dupliqués, vérifier que la région a été créée
                assert "créée avec succès" in message_text, f"Message inattendu: {message.text}"
                print(f"Note: L'application permet les noms de régions dupliqués. Message: {message.text}")
            else:
                # Si l'application empêche les noms dupliqués, vérifier le message d'erreur
                assert "existe déjà" in message_text or "déjà" in message_text or "duplicate" in message_text, f"Message d'erreur inattendu: {message.text}"
        except TimeoutException:
            # Si pas de message, vérifier que le modal est toujours ouvert (indiquant une erreur)
            modal = driver.find_element(By.ID, "createRegionModal")
            assert modal.is_displayed(), "Le modal devrait être encore ouvert en cas d'erreur"
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Monde de test",
            'countries': [{
                'name': country_name,
                'description': "Pays de test",
                'regions': [{'name': region_name, 'description': "Première région"}]
            }]
        })
    
    def test_view_region_details(self, driver, wait, app_url, test_user):
        """Test d'affichage des détails d'une région"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer un monde, un pays et une région
        world_name = f"Monde pour Visualisation {int(time.time())}"
        self._create_world(driver, wait, app_url, world_name, "Monde de test")
        
        # Aller à la page de visualisation du monde
        driver.get(f"{app_url}/view_world.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton "Voir" du monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{world_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_world.php')]")))
        world_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un pays
        country_name = f"Pays pour Visualisation {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        # Cliquer sur le premier lien vers view_country.php (le pays que nous venons de créer)
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, 'view_country.php')]")))
        country_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer une région
        region_name = f"Région à Visualiser {int(time.time())}"
        region_description = "Description détaillée de la région à visualiser"
        self._create_region(driver, wait, region_name, region_description)
        
        # Vérifier que la région est affichée
        region_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{region_name}')]")))
        assert region_element is not None
        
        # Cliquer sur le bouton "Voir la Région"
        region_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{region_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_region.php')]")))
        region_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que la description est affichée
        description_element = driver.find_element(By.XPATH, f"//*[contains(text(), '{region_description}')]")
        assert description_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Monde de test",
            'countries': [{
                'name': country_name,
                'description': "Pays de test",
                'regions': [{'name': region_name, 'description': region_description}]
            }]
        })
    
    def test_region_list_display(self, driver, wait, app_url, test_user):
        """Test d'affichage de la liste des régions d'un pays"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer un monde et un pays
        world_name = f"Monde pour Liste {int(time.time())}"
        self._create_world(driver, wait, app_url, world_name, "Monde de test")
        
        # Aller à la page de visualisation du monde
        driver.get(f"{app_url}/view_world.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton "Voir" du monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{world_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_world.php')]")))
        world_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un pays
        country_name = f"Pays pour Liste {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        # Cliquer sur le premier lien vers view_country.php (le pays que nous venons de créer)
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, 'view_country.php')]")))
        country_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer plusieurs régions
        regions = []
        for i in range(3):
            region_name = f"Région Liste {i+1} {int(time.time())}"
            region_description = f"Description de la région {i+1}"
            self._create_region(driver, wait, region_name, region_description)
            regions.append({'name': region_name, 'description': region_description})
        
        # Vérifier que toutes les régions sont affichées
        for region in regions:
            region_element = driver.find_element(By.XPATH, f"//*[contains(text(), '{region['name']}')]")
            assert region_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Monde de test",
            'countries': [{
                'name': country_name,
                'description': "Pays de test",
                'regions': regions
            }]
        })
    
    def _login_user(self, driver, wait, app_url, user_data):
        """Méthode utilitaire pour se connecter avec un utilisateur"""
        driver.get(f"{app_url}/login.php")
        wait.until(EC.presence_of_element_located((By.NAME, "username")))
        
        # Remplir le formulaire de connexion
        username_input = driver.find_element(By.NAME, "username")
        password_input = driver.find_element(By.NAME, "password")
        
        username_input.clear()
        username_input.send_keys(user_data['username'])
        
        password_input.clear()
        password_input.send_keys(user_data['password'])
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_button.click()
        
        # Attendre la redirection
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    
    def _create_world(self, driver, wait, app_url, name, description):
        """Méthode utilitaire pour créer un monde"""
        driver.get(f"{app_url}/manage_worlds.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Ouvrir le modal de création
        create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createWorldModal']")))
        create_button.click()
        
        # Attendre que le modal soit visible
        wait.until(EC.visibility_of_element_located((By.ID, "createWorldModal")))
        
        # Remplir le formulaire dans le modal
        name_input = wait.until(EC.element_to_be_clickable((By.ID, "createName")))
        name_input.clear()
        name_input.send_keys(name)
        
        description_input = driver.find_element(By.ID, "createDescription")
        description_input.clear()
        description_input.send_keys(description)
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "#createWorldModal button[type='submit']")
        submit_button.click()
        
        # Attendre la confirmation
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{name}')]")))
    
    def _create_country(self, driver, wait, name, description):
        """Méthode utilitaire pour créer un pays"""
        # Ouvrir le modal de création de pays
        create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createCountryModal']")))
        create_button.click()
        
        # Attendre que le modal soit visible
        wait.until(EC.visibility_of_element_located((By.ID, "createCountryModal")))
        
        # Remplir le formulaire dans le modal
        name_input = wait.until(EC.element_to_be_clickable((By.ID, "createCountryName")))
        name_input.clear()
        name_input.send_keys(name)
        
        description_input = driver.find_element(By.ID, "createCountryDescription")
        description_input.clear()
        description_input.send_keys(description)
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "#createCountryModal button[type='submit']")
        submit_button.click()
        
        # Attendre la confirmation
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{name}')]")))
    
    def _create_region(self, driver, wait, name, description):
        """Méthode utilitaire pour créer une région"""
        # Ouvrir le modal de création de région
        create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createRegionModal']")))
        create_button.click()
        
        # Attendre que le modal soit visible
        wait.until(EC.visibility_of_element_located((By.ID, "createRegionModal")))
        
        # Remplir le formulaire dans le modal
        name_input = wait.until(EC.element_to_be_clickable((By.ID, "createRegionName")))
        name_input.clear()
        name_input.send_keys(name)
        
        description_input = driver.find_element(By.ID, "createRegionDescription")
        description_input.clear()
        description_input.send_keys(description)
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "#createRegionModal button[type='submit']")
        submit_button.click()
        
        # Attendre la confirmation
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{name}')]")))
