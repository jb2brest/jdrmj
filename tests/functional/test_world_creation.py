#!/usr/bin/env python3
"""
Tests pour la création et gestion des mondes
"""

import pytest
import time
import os
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


class TestWorldCreation:
    """Tests pour la création et gestion des mondes"""
    
    def test_create_world_success(self, driver, wait, app_url, test_user):
        """Test de création d'un monde avec succès"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de gestion des mondes
        driver.get(f"{app_url}/manage_worlds.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le formulaire de création de monde
        world_name = f"Monde de Test {int(time.time())}"
        world_description = "Description du monde de test pour les tests automatisés"
        
        # Trouver et remplir le champ nom
        name_input = wait.until(EC.presence_of_element_located((By.NAME, "name")))
        name_input.clear()
        name_input.send_keys(world_name)
        
        # Trouver et remplir le champ description
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys(world_description)
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "input[type='submit'][value*='Créer']")
        submit_button.click()
        
        # Vérifier que le monde a été créé
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{world_name}')]")))
        
        # Vérifier le message de succès
        success_message = driver.find_element(By.CSS_SELECTOR, ".alert-success, .success, [class*='success']")
        assert "créé avec succès" in success_message.text.lower()
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': world_description
        })
    
    def test_create_world_empty_name(self, driver, wait, app_url, test_user):
        """Test de création d'un monde avec un nom vide"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de gestion des mondes
        driver.get(f"{app_url}/manage_worlds.php")
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
    
    def test_create_world_duplicate_name(self, driver, wait, app_url, test_user):
        """Test de création d'un monde avec un nom déjà existant"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer un premier monde
        world_name = f"Monde Dupliqué {int(time.time())}"
        self._create_world(driver, wait, app_url, world_name, "Premier monde")
        
        # Essayer de créer un second monde avec le même nom
        driver.get(f"{app_url}/manage_worlds.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        name_input = driver.find_element(By.NAME, "name")
        name_input.clear()
        name_input.send_keys(world_name)
        
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys("Deuxième monde avec le même nom")
        
        submit_button = driver.find_element(By.CSS_SELECTOR, "input[type='submit'][value*='Créer']")
        submit_button.click()
        
        # Vérifier qu'une erreur est affichée
        error_message = driver.find_element(By.CSS_SELECTOR, ".alert-danger, .error, [class*='error']")
        assert "existe déjà" in error_message.text.lower() or "déjà" in error_message.text.lower()
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Premier monde"
        })
    
    def test_view_world_details(self, driver, wait, app_url, test_user):
        """Test d'affichage des détails d'un monde"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer un monde
        world_name = f"Monde à Visualiser {int(time.time())}"
        world_description = "Description détaillée du monde à visualiser"
        self._create_world(driver, wait, app_url, world_name, world_description)
        
        # Aller à la page de visualisation du monde
        driver.get(f"{app_url}/view_world.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que le monde est affiché
        world_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{world_name}')]")))
        assert world_element is not None
        
        # Vérifier que la description est affichée
        description_element = driver.find_element(By.XPATH, f"//*[contains(text(), '{world_description}')]")
        assert description_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': world_description
        })
    
    def test_world_list_display(self, driver, wait, app_url, test_user):
        """Test d'affichage de la liste des mondes"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer plusieurs mondes
        worlds = []
        for i in range(3):
            world_name = f"Monde Liste {i+1} {int(time.time())}"
            world_description = f"Description du monde {i+1}"
            self._create_world(driver, wait, app_url, world_name, world_description)
            worlds.append({'name': world_name, 'description': world_description})
        
        # Aller à la page de gestion des mondes
        driver.get(f"{app_url}/manage_worlds.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que tous les mondes sont affichés
        for world in worlds:
            world_element = driver.find_element(By.XPATH, f"//*[contains(text(), '{world['name']}')]")
            assert world_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].extend(worlds)
    
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
        submit_button = driver.find_element(By.CSS_SELECTOR, "input[type='submit']")
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
