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
        
        # Aller à la page de visualisation du monde
        driver.get(f"{app_url}/view_world.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{world_name}')]")))
        world_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un pays
        country_name = f"Pays pour Région {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        # Cliquer sur le pays créé
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{country_name}')]")))
        country_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le formulaire de création de région
        region_name = f"Région de Test {int(time.time())}"
        region_description = "Description de la région de test pour les tests automatisés"
        
        # Trouver et remplir le champ nom
        name_input = wait.until(EC.presence_of_element_located((By.NAME, "name")))
        name_input.clear()
        name_input.send_keys(region_name)
        
        # Trouver et remplir le champ description
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys(region_description)
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "input[type='submit'][value*='Créer']")
        submit_button.click()
        
        # Vérifier que la région a été créée
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{region_name}')]")))
        
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
        
        # Cliquer sur le monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{world_name}')]")))
        world_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un pays
        country_name = f"Pays pour Test Vide {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        # Cliquer sur le pays créé
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{country_name}')]")))
        country_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le formulaire avec un nom vide
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys("Description sans nom")
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "input[type='submit'][value*='Créer']")
        submit_button.click()
        
        # Vérifier qu'une erreur est affichée
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
        
        # Cliquer sur le monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{world_name}')]")))
        world_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un pays
        country_name = f"Pays pour Dupliqué {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        # Cliquer sur le pays créé
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{country_name}')]")))
        country_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer une première région
        region_name = f"Région Dupliquée {int(time.time())}"
        self._create_region(driver, wait, region_name, "Première région")
        
        # Essayer de créer une seconde région avec le même nom
        name_input = driver.find_element(By.NAME, "name")
        name_input.clear()
        name_input.send_keys(region_name)
        
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys("Deuxième région avec le même nom")
        
        submit_button = driver.find_element(By.CSS_SELECTOR, "input[type='submit'][value*='Créer']")
        submit_button.click()
        
        # Vérifier qu'une erreur est affichée
        error_message = driver.find_element(By.CSS_SELECTOR, ".alert-danger, .error, [class*='error']")
        assert "existe déjà" in error_message.text.lower() or "déjà" in error_message.text.lower()
        
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
        
        # Cliquer sur le monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{world_name}')]")))
        world_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un pays
        country_name = f"Pays pour Visualisation {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        # Cliquer sur le pays créé
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{country_name}')]")))
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
        
        # Cliquer sur la région pour voir ses détails
        region_link = driver.find_element(By.XPATH, f"//a[contains(text(), '{region_name}')]")
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
        
        # Cliquer sur le monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{world_name}')]")))
        world_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un pays
        country_name = f"Pays pour Liste {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        # Cliquer sur le pays créé
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{country_name}')]")))
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
        
        # Remplir le formulaire
        name_input = driver.find_element(By.NAME, "name")
        name_input.clear()
        name_input.send_keys(name)
        
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys(description)
        
        # Soumettre
        submit_button = driver.find_element(By.CSS_SELECTOR, "input[type='submit'][value*='Créer']")
        submit_button.click()
        
        # Attendre la confirmation
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{name}')]")))
    
    def _create_country(self, driver, wait, name, description):
        """Méthode utilitaire pour créer un pays"""
        # Remplir le formulaire
        name_input = driver.find_element(By.NAME, "name")
        name_input.clear()
        name_input.send_keys(name)
        
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys(description)
        
        # Soumettre
        submit_button = driver.find_element(By.CSS_SELECTOR, "input[type='submit'][value*='Créer']")
        submit_button.click()
        
        # Attendre la confirmation
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{name}')]")))
    
    def _create_region(self, driver, wait, name, description):
        """Méthode utilitaire pour créer une région"""
        # Remplir le formulaire
        name_input = driver.find_element(By.NAME, "name")
        name_input.clear()
        name_input.send_keys(name)
        
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys(description)
        
        # Soumettre
        submit_button = driver.find_element(By.CSS_SELECTOR, "input[type='submit'][value*='Créer']")
        submit_button.click()
        
        # Attendre la confirmation
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{name}')]")))
