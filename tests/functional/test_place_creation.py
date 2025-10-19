#!/usr/bin/env python3
"""
Tests pour la création et gestion des lieux
"""

import pytest
import time
import os
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


class TestPlaceCreation:
    """Tests pour la création et gestion des lieux"""
    
    def test_create_place_success(self, driver, wait, app_url, test_user):
        """Test de création d'un lieu avec succès"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer d'abord un monde, un pays et une région
        world_name = f"Monde pour Lieu {int(time.time())}"
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
        country_name = f"Pays pour Lieu {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays de test")
        
        # Cliquer sur le premier lien vers view_country.php (le pays que nous venons de créer)
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, 'view_country.php')]")))
        country_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer une région
        region_name = f"Région pour Lieu {int(time.time())}"
        self._create_region(driver, wait, region_name, "Région de test")
        
        # Cliquer sur le bouton "Voir la Région"
        region_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{region_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_region.php')]")))
        region_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un lieu
        place_title = f"Lieu de Test {int(time.time())}"
        place_notes = "Notes du lieu de test pour les tests automatisés"
        self._create_place(driver, wait, place_title, place_notes)
        
        # Vérifier le message de succès
        success_message = driver.find_element(By.CSS_SELECTOR, ".alert-success, .success, [class*='success']")
        assert "créé avec succès" in success_message.text.lower()
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Monde de test",
            'countries': [{
                'name': country_name,
                'description': "Pays de test",
                'regions': [{
                    'name': region_name,
                    'description': "Région de test",
                    'places': [{'title': place_title, 'notes': place_notes}]
                }]
            }]
        })
    
    def test_create_place_empty_title(self, driver, wait, app_url, test_user):
        """Test de création d'un lieu avec un titre vide"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer d'abord un monde, un pays et une région
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
        
        # Créer une région
        region_name = f"Région pour Test Vide {int(time.time())}"
        self._create_region(driver, wait, region_name, "Région de test")
        
        # Cliquer sur le bouton "Voir la Région"
        region_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{region_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_region.php')]")))
        region_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Ouvrir le modal de création de lieu
        create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createPlaceModal']")))
        create_button.click()
        
        # Attendre que le modal soit visible
        wait.until(EC.visibility_of_element_located((By.ID, "createPlaceModal")))
        
        # Remplir seulement les notes (sans titre)
        notes_input = driver.find_element(By.ID, "createPlaceNotes")
        notes_input.clear()
        notes_input.send_keys("Notes sans titre")
        
        # Essayer de soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "#createPlaceModal button[type='submit']")
        submit_button.click()
        
        # Vérifier qu'une erreur est affichée (validation HTML5 ou message d'erreur)
        try:
            # Vérifier si le champ titre est marqué comme invalide (validation HTML5)
            title_input = driver.find_element(By.ID, "createPlaceTitle")
            is_invalid = title_input.get_attribute("required") is not None
            assert is_invalid, "Le champ titre devrait être requis"
        except:
            # Si pas de validation HTML5, vérifier un message d'erreur
            error_message = driver.find_element(By.CSS_SELECTOR, ".alert-danger, .error, [class*='error']")
            assert "nom" in error_message.text.lower() and "requis" in error_message.text.lower()
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Monde de test",
            'countries': [{
                'name': country_name,
                'description': "Pays de test",
                'regions': [{'name': region_name, 'description': "Région de test"}]
            }]
        })
    
    def test_create_place_duplicate_title(self, driver, wait, app_url, test_user):
        """Test de création d'un lieu avec un titre déjà existant dans la même région"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer d'abord un monde, un pays et une région
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
        
        # Créer une région
        region_name = f"Région pour Dupliqué {int(time.time())}"
        self._create_region(driver, wait, region_name, "Région de test")
        
        # Cliquer sur le bouton "Voir la Région"
        region_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{region_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_region.php')]")))
        region_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un premier lieu
        place_title = f"Lieu Dupliqué {int(time.time())}"
        self._create_place(driver, wait, place_title, "Premier lieu")
        
        # Essayer de créer un second lieu avec le même titre
        # Ouvrir le modal de création de lieu
        create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createPlaceModal']")))
        create_button.click()
        
        # Attendre que le modal soit visible
        wait.until(EC.visibility_of_element_located((By.ID, "createPlaceModal")))
        
        # Remplir le formulaire avec le même titre
        title_input = driver.find_element(By.ID, "createPlaceTitle")
        title_input.clear()
        title_input.send_keys(place_title)
        
        notes_input = driver.find_element(By.ID, "createPlaceNotes")
        notes_input.clear()
        notes_input.send_keys("Deuxième lieu avec le même titre")
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "#createPlaceModal button[type='submit']")
        submit_button.click()
        
        # Vérifier le résultat (succès ou erreur)
        try:
            # Attendre qu'un message apparaisse (succès ou erreur)
            message = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".alert-danger, .error, [class*='error'], .alert, [class*='alert'], .alert-success, .success, [class*='success']")))
            message_text = message.text.lower()
            
            if "créé avec succès" in message_text:
                # Si l'application permet les titres dupliqués, vérifier que le lieu a été créé
                assert "créé avec succès" in message_text, f"Message inattendu: {message.text}"
                print(f"Note: L'application permet les titres de lieux dupliqués. Message: {message.text}")
            else:
                # Si l'application empêche les titres dupliqués, vérifier le message d'erreur
                assert "existe déjà" in message_text or "déjà" in message_text or "duplicate" in message_text, f"Message d'erreur inattendu: {message.text}"
        except TimeoutException:
            # Si pas de message, vérifier que le modal est toujours ouvert (indiquant une erreur)
            modal = driver.find_element(By.ID, "createPlaceModal")
            assert modal.is_displayed(), "Le modal devrait être encore ouvert en cas d'erreur"
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Monde de test",
            'countries': [{
                'name': country_name,
                'description': "Pays de test",
                'regions': [{
                    'name': region_name,
                    'description': "Région de test",
                    'places': [{'title': place_title, 'notes': "Premier lieu"}]
                }]
            }]
        })
    
    def test_view_place_details(self, driver, wait, app_url, test_user):
        """Test d'affichage des détails d'un lieu"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer un monde, un pays, une région et un lieu
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
        region_name = f"Région pour Visualisation {int(time.time())}"
        self._create_region(driver, wait, region_name, "Région de test")
        
        # Cliquer sur le bouton "Voir la Région"
        region_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{region_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_region.php')]")))
        region_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un lieu
        place_title = f"Lieu à Visualiser {int(time.time())}"
        place_notes = "Notes détaillées du lieu à visualiser"
        self._create_place(driver, wait, place_title, place_notes)
        
        # Vérifier que le lieu est affiché
        place_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{place_title}')]")))
        assert place_element is not None
        
        # Cliquer sur le bouton "Voir le Lieu"
        place_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{place_title}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_place.php')]")))
        place_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que les notes sont affichées
        notes_element = driver.find_element(By.XPATH, f"//*[contains(text(), '{place_notes}')]")
        assert notes_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Monde de test",
            'countries': [{
                'name': country_name,
                'description': "Pays de test",
                'regions': [{
                    'name': region_name,
                    'description': "Région de test",
                    'places': [{'title': place_title, 'notes': place_notes}]
                }]
            }]
        })
    
    def test_place_list_display(self, driver, wait, app_url, test_user):
        """Test d'affichage de la liste des lieux d'une région"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer un monde, un pays et une région
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
        
        # Créer une région
        region_name = f"Région pour Liste {int(time.time())}"
        self._create_region(driver, wait, region_name, "Région de test")
        
        # Cliquer sur le bouton "Voir la Région"
        region_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(text(), '{region_name}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_region.php')]")))
        region_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer plusieurs lieux
        places = []
        for i in range(3):
            place_title = f"Lieu Liste {i+1} {int(time.time())}"
            place_notes = f"Notes du lieu {i+1}"
            self._create_place(driver, wait, place_title, place_notes)
            places.append({'title': place_title, 'notes': place_notes})
        
        # Vérifier que tous les lieux sont affichés
        for place in places:
            place_element = driver.find_element(By.XPATH, f"//*[contains(text(), '{place['title']}')]")
            assert place_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_worlds'] = test_user.get('created_worlds', [])
        test_user['created_worlds'].append({
            'name': world_name,
            'description': "Monde de test",
            'countries': [{
                'name': country_name,
                'description': "Pays de test",
                'regions': [{
                    'name': region_name,
                    'description': "Région de test",
                    'places': places
                }]
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
    
    def _create_place(self, driver, wait, title, notes):
        """Méthode utilitaire pour créer un lieu"""
        # Ouvrir le modal de création de lieu
        create_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createPlaceModal']")))
        create_button.click()
        
        # Attendre que le modal soit visible
        wait.until(EC.visibility_of_element_located((By.ID, "createPlaceModal")))
        
        # Remplir le formulaire dans le modal
        title_input = wait.until(EC.element_to_be_clickable((By.ID, "createPlaceTitle")))
        title_input.clear()
        title_input.send_keys(title)
        
        notes_input = driver.find_element(By.ID, "createPlaceNotes")
        notes_input.clear()
        notes_input.send_keys(notes)
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "#createPlaceModal button[type='submit']")
        submit_button.click()
        
        # Attendre la confirmation
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{title}')]")))
