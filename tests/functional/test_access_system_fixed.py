#!/usr/bin/env python3
"""
Tests fonctionnels pour le système d'accès entre lieux
"""

import pytest
import time
import os
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from test_steps_capturer import add_step


class TestAccessSystem:
    """Tests pour le système d'accès entre lieux"""
    
    def test_access_management_interface(self, driver, wait, app_url, test_user):
        """Test de l'interface de gestion des accès"""
        add_step("Début du test", "Test de l'interface de gestion des accès", "info")
        
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        add_step("Connexion utilisateur", f"Connexion avec l'utilisateur {test_user['username']}", "action")
        
        # Aller à la page de gestion des mondes
        driver.get(f"{app_url}/manage_worlds.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        add_step("Navigation", "Accès à la page de gestion des mondes", "action")
        
        # Créer un monde de test
        world_name = f"Monde Accès Test {int(time.time())}"
        self._create_world(driver, wait, world_name, "Monde pour tester les accès")
        add_step("Création monde", f"Création du monde '{world_name}'", "action")
        
        # Aller à la page de visualisation du monde
        driver.get(f"{app_url}/view_world.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le monde créé
        world_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{world_name}')]")))
        world_link.click()
        add_step("Sélection monde", f"Sélection du monde '{world_name}'", "action")
        
        # Créer un pays
        country_name = f"Pays Accès Test {int(time.time())}"
        self._create_country(driver, wait, country_name, "Pays pour tester les accès")
        add_step("Création pays", f"Création du pays '{country_name}'", "action")
        
        # Cliquer sur le pays créé
        country_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{country_name}')]")))
        country_link.click()
        add_step("Sélection pays", f"Sélection du pays '{country_name}'", "action")
        
        # Créer une région
        region_name = f"Région Accès Test {int(time.time())}"
        self._create_region(driver, wait, region_name, "Région pour tester les accès")
        add_step("Création région", f"Création de la région '{region_name}'", "action")
        
        # Cliquer sur la région créée
        region_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{region_name}')]")))
        region_link.click()
        add_step("Sélection région", f"Sélection de la région '{region_name}'", "action")
        
        # Créer le premier lieu
        place1_name = f"Lieu 1 Accès Test {int(time.time())}"
        self._create_place(driver, wait, place1_name, "Premier lieu pour tester les accès")
        add_step("Création lieu 1", f"Création du lieu '{place1_name}'", "action")
        
        # Créer le deuxième lieu
        place2_name = f"Lieu 2 Accès Test {int(time.time())}"
        self._create_place(driver, wait, place2_name, "Deuxième lieu pour tester les accès")
        add_step("Création lieu 2", f"Création du lieu '{place2_name}'", "action")
        
        # Aller au premier lieu
        place1_link = wait.until(EC.element_to_be_clickable((By.XPATH, f"//a[contains(text(), '{place1_name}')]")))
        place1_link.click()
        add_step("Accès lieu 1", f"Accès au lieu '{place1_name}'", "action")
        
        # Vérifier que le bouton "Gérer les accès" est présent
        manage_accesses_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'Gérer les accès')]")))
        assert manage_accesses_btn.is_displayed(), "Le bouton 'Gérer les accès' devrait être visible"
        add_step("Bouton accès présent", "Le bouton 'Gérer les accès' est visible", "assertion")
        
        # Cliquer sur "Gérer les accès"
        manage_accesses_btn.click()
        add_step("Ouverture gestion accès", "Clic sur le bouton 'Gérer les accès'", "action")
        
        # Vérifier que la page de gestion des accès s'affiche
        wait.until(EC.presence_of_element_located((By.XPATH, "//h1[contains(text(), 'Gestion des Accès')]")))
        add_step("Page gestion accès", "La page de gestion des accès s'affiche correctement", "assertion")
        
        # Vérifier qu'il n'y a pas d'accès configurés
        no_accesses_msg = driver.find_element(By.XPATH, "//p[contains(text(), 'Aucun accès configuré pour ce lieu')]")
        assert no_accesses_msg.is_displayed(), "Le message 'Aucun accès configuré' devrait être affiché"
        add_step("Aucun accès", "Aucun accès n'est configuré pour ce lieu", "assertion")
        
        # Cliquer sur "Ajouter un accès"
        add_access_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Ajouter un accès')]")))
        add_access_btn.click()
        add_step("Ouverture modal accès", "Clic sur 'Ajouter un accès'", "action")
        
        # Vérifier que la modal s'ouvre
        modal = wait.until(EC.presence_of_element_located((By.ID, "createAccessModal")))
        assert modal.is_displayed(), "La modal de création d'accès devrait être visible"
        add_step("Modal ouverte", "La modal de création d'accès est ouverte", "assertion")
        
        # Remplir le formulaire de création d'accès
        access_name = f"Porte Test {int(time.time())}"
        access_description = "Une porte de test pour vérifier le système d'accès"
        
        # Nom de l'accès
        name_input = driver.find_element(By.ID, "createAccessName")
        name_input.send_keys(access_name)
        add_step("Saisie nom accès", f"Saisie du nom d'accès: '{access_name}'", "action")
        
        # Description
        description_input = driver.find_element(By.ID, "createAccessDescription")
        description_input.send_keys(access_description)
        add_step("Saisie description", f"Saisie de la description: '{access_description}'", "action")
        
        # Sélectionner le lieu de destination
        to_place_select = driver.find_element(By.ID, "createAccessToPlace")
        to_place_select.click()
        
        # Sélectionner le deuxième lieu
        place2_option = wait.until(EC.element_to_be_clickable((By.XPATH, f"//option[contains(text(), '{place2_name}')]")))
        place2_option.click()
        add_step("Sélection destination", f"Sélection du lieu de destination: '{place2_name}'", "action")
        
        # Vérifier que les cases à cocher sont présentes
        visible_checkbox = driver.find_element(By.ID, "createAccessVisible")
        open_checkbox = driver.find_element(By.ID, "createAccessOpen")
        trapped_checkbox = driver.find_element(By.ID, "createAccessTrapped")
        
        assert visible_checkbox.is_displayed(), "La case 'Visible des joueurs' devrait être présente"
        assert open_checkbox.is_displayed(), "La case 'Ouvert' devrait être présente"
        assert trapped_checkbox.is_displayed(), "La case 'Piégé' devrait être présente"
        add_step("Cases à cocher", "Toutes les cases à cocher sont présentes", "assertion")
        
        # Cocher la case "Piégé" pour tester l'affichage des détails du piège
        trapped_checkbox.click()
        add_step("Activation piège", "Cochage de la case 'Piégé'", "action")
        
        # Vérifier que les détails du piège s'affichent
        trap_details = wait.until(EC.presence_of_element_located((By.ID, "trapDetails")))
        assert trap_details.is_displayed(), "Les détails du piège devraient s'afficher"
        add_step("Détails piège", "Les détails du piège s'affichent", "assertion")
        
        # Remplir les détails du piège
        trap_description = "Piège à flèches mortelles"
        trap_difficulty = "15"
        trap_damage = "2d6+3"
        
        trap_desc_input = driver.find_element(By.ID, "createAccessTrapDescription")
        trap_desc_input.send_keys(trap_description)
        
        trap_diff_input = driver.find_element(By.ID, "createAccessTrapDifficulty")
        trap_diff_input.send_keys(trap_difficulty)
        
        trap_damage_input = driver.find_element(By.ID, "createAccessTrapDamage")
        trap_damage_input.send_keys(trap_damage)
        add_step("Saisie détails piège", f"Saisie des détails du piège: {trap_description}, DD {trap_difficulty}, {trap_damage}", "action")
        
        # Soumettre le formulaire
        submit_btn = driver.find_element(By.XPATH, "//button[@type='submit' and contains(text(), 'Créer l\\'accès')]")
        submit_btn.click()
        add_step("Soumission formulaire", "Soumission du formulaire de création d'accès", "action")
        
        # Vérifier que l'accès a été créé
        wait.until(EC.presence_of_element_located((By.XPATH, "//div[contains(@class, 'alert-success')]")))
        success_message = driver.find_element(By.XPATH, "//div[contains(@class, 'alert-success')]")
        assert "Accès créé avec succès" in success_message.text, "Le message de succès devrait être affiché"
        add_step("Accès créé", "L'accès a été créé avec succès", "assertion")
        
        # Vérifier que l'accès apparaît dans la liste
        access_card = wait.until(EC.presence_of_element_located((By.XPATH, f"//h6[contains(text(), '{access_name}')]")))
        assert access_card.is_displayed(), "L'accès créé devrait apparaître dans la liste"
        add_step("Accès affiché", "L'accès créé apparaît dans la liste", "assertion")
        
        # Vérifier les badges de statut
        trapped_badge = driver.find_element(By.XPATH, "//span[contains(@class, 'badge bg-danger') and contains(text(), 'Piégé')]")
        assert trapped_badge.is_displayed(), "Le badge 'Piégé' devrait être affiché"
        add_step("Badge piégé", "Le badge 'Piégé' est affiché", "assertion")
        
        # Vérifier les détails du piège
        trap_details_display = driver.find_element(By.XPATH, f"//small[contains(text(), '{trap_description}')]")
        assert trap_details_display.is_displayed(), "Les détails du piège devraient être affichés"
        add_step("Détails piège affichés", "Les détails du piège sont affichés", "assertion")
        
        add_step("Test terminé", "Le test de l'interface de gestion des accès s'est terminé avec succès", "info")
    
    def _login_user(self, driver, wait, app_url, test_user):
        """Se connecter avec un utilisateur de test"""
        driver.get(f"{app_url}/login.php")
        wait.until(EC.presence_of_element_located((By.ID, "username")))
        
        username_input = driver.find_element(By.ID, "username")
        password_input = driver.find_element(By.ID, "password")
        
        username_input.send_keys(test_user['username'])
        password_input.send_keys(test_user['password'])
        
        login_btn = driver.find_element(By.XPATH, "//button[@type='submit']")
        login_btn.click()
        
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    
    def _create_world(self, driver, wait, world_name, world_description):
        """Créer un monde"""
        # Cliquer sur le bouton de création de monde
        create_world_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Créer un monde')]")))
        create_world_btn.click()
        
        # Remplir le formulaire
        name_input = wait.until(EC.presence_of_element_located((By.ID, "createWorldName")))
        name_input.send_keys(world_name)
        
        description_input = driver.find_element(By.ID, "createWorldDescription")
        description_input.send_keys(world_description)
        
        # Soumettre
        submit_btn = driver.find_element(By.XPATH, "//button[@type='submit' and contains(text(), 'Créer le monde')]")
        submit_btn.click()
        
        # Attendre le message de succès
        wait.until(EC.presence_of_element_located((By.XPATH, "//div[contains(@class, 'alert-success')]")))
    
    def _create_country(self, driver, wait, country_name, country_description):
        """Créer un pays"""
        # Cliquer sur le bouton de création de pays
        create_country_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Créer un pays')]")))
        create_country_btn.click()
        
        # Remplir le formulaire
        name_input = wait.until(EC.presence_of_element_located((By.ID, "createCountryName")))
        name_input.send_keys(country_name)
        
        description_input = driver.find_element(By.ID, "createCountryDescription")
        description_input.send_keys(country_description)
        
        # Soumettre
        submit_btn = driver.find_element(By.XPATH, "//button[@type='submit' and contains(text(), 'Créer le pays')]")
        submit_btn.click()
        
        # Attendre le message de succès
        wait.until(EC.presence_of_element_located((By.XPATH, "//div[contains(@class, 'alert-success')]")))
    
    def _create_region(self, driver, wait, region_name, region_description):
        """Créer une région"""
        # Cliquer sur le bouton de création de région
        create_region_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Créer une région')]")))
        create_region_btn.click()
        
        # Remplir le formulaire
        name_input = wait.until(EC.presence_of_element_located((By.ID, "createRegionName")))
        name_input.send_keys(region_name)
        
        description_input = driver.find_element(By.ID, "createRegionDescription")
        description_input.send_keys(region_description)
        
        # Soumettre
        submit_btn = driver.find_element(By.XPATH, "//button[@type='submit' and contains(text(), 'Créer la région')]")
        submit_btn.click()
        
        # Attendre le message de succès
        wait.until(EC.presence_of_element_located((By.XPATH, "//div[contains(@class, 'alert-success')]")))
    
    def _create_place(self, driver, wait, place_name, place_description):
        """Créer un lieu"""
        # Cliquer sur le bouton de création de lieu
        create_place_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Créer un lieu')]")))
        create_place_btn.click()
        
        # Remplir le formulaire
        name_input = wait.until(EC.presence_of_element_located((By.ID, "createPlaceTitle")))
        name_input.send_keys(place_name)
        
        description_input = driver.find_element(By.ID, "createPlaceNotes")
        description_input.send_keys(place_description)
        
        # Soumettre
        submit_btn = driver.find_element(By.XPATH, "//button[@type='submit' and contains(text(), 'Créer le lieu')]")
        submit_btn.click()
        
        # Attendre le message de succès
        wait.until(EC.presence_of_element_located((By.XPATH, "//div[contains(@class, 'alert-success')]")))
