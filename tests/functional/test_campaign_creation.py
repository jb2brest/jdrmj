#!/usr/bin/env python3
"""
Tests pour la création et gestion des campagnes
"""

import pytest
import time
import os
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


class TestCampaignCreation:
    """Tests pour la création et gestion des campagnes"""
    
    def test_create_campaign_success(self, driver, wait, app_url, test_user):
        """Test de création d'une campagne avec succès"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de gestion des campagnes
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le formulaire de création de campagne
        campaign_title = f"Campagne de Test {int(time.time())}"
        campaign_description = "Description de la campagne de test pour les tests automatisés"
        game_system = "D&D 5e"
        
        # Trouver et remplir le champ titre
        title_input = wait.until(EC.presence_of_element_located((By.NAME, "title")))
        title_input.clear()
        title_input.send_keys(campaign_title)
        
        # Trouver et remplir le champ description
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys(campaign_description)
        
        # Sélectionner le système de jeu
        game_system_select = driver.find_element(By.NAME, "game_system")
        game_system_select.send_keys(game_system)
        
        # Cocher la case publique
        public_checkbox = driver.find_element(By.NAME, "is_public")
        if not public_checkbox.is_selected():
            public_checkbox.click()
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_button.click()
        
        # Vérifier que la campagne a été créée
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{campaign_title}')]")))
        
        # Vérifier le message de succès
        success_message = driver.find_element(By.CSS_SELECTOR, ".alert-success, .success, [class*='success']")
        assert "créée avec succès" in success_message.text.lower()
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': campaign_description,
            'game_system': game_system,
            'is_public': True
        })
    
    def test_create_campaign_empty_title(self, driver, wait, app_url, test_user):
        """Test de création d'une campagne avec un titre vide"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de gestion des campagnes
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le formulaire avec un titre vide
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys("Description sans titre")
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_button.click()
        
        # Vérifier qu'une erreur est affichée
        error_message = driver.find_element(By.CSS_SELECTOR, ".alert-danger, .error, [class*='error']")
        assert "titre" in error_message.text.lower() and "caractères" in error_message.text.lower()
    
    def test_create_campaign_short_title(self, driver, wait, app_url, test_user):
        """Test de création d'une campagne avec un titre trop court"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de gestion des campagnes
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le formulaire avec un titre trop court
        title_input = driver.find_element(By.NAME, "title")
        title_input.clear()
        title_input.send_keys("AB")  # Moins de 3 caractères
        
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys("Description avec titre court")
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_button.click()
        
        # Vérifier qu'une erreur est affichée
        error_message = driver.find_element(By.CSS_SELECTOR, ".alert-danger, .error, [class*='error']")
        assert "titre" in error_message.text.lower() and "caractères" in error_message.text.lower()
    
    def test_create_campaign_private(self, driver, wait, app_url, test_user):
        """Test de création d'une campagne privée"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de gestion des campagnes
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le formulaire de création de campagne privée
        campaign_title = f"Campagne Privée {int(time.time())}"
        campaign_description = "Description de la campagne privée de test"
        
        # Trouver et remplir le champ titre
        title_input = wait.until(EC.presence_of_element_located((By.NAME, "title")))
        title_input.clear()
        title_input.send_keys(campaign_title)
        
        # Trouver et remplir le champ description
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys(campaign_description)
        
        # Décocher la case publique
        public_checkbox = driver.find_element(By.NAME, "is_public")
        if public_checkbox.is_selected():
            public_checkbox.click()
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_button.click()
        
        # Vérifier que la campagne a été créée
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{campaign_title}')]")))
        
        # Vérifier que la campagne est marquée comme privée
        private_badge = driver.find_element(By.XPATH, f"//*[contains(text(), '{campaign_title}')]/following-sibling::*//span[contains(text(), 'Privée')]")
        assert private_badge is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': campaign_description,
            'game_system': 'D&D 5e',
            'is_public': False
        })
    
    def test_view_campaign_details(self, driver, wait, app_url, test_user):
        """Test d'affichage des détails d'une campagne"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer une campagne
        campaign_title = f"Campagne à Visualiser {int(time.time())}"
        campaign_description = "Description détaillée de la campagne à visualiser"
        self._create_campaign(driver, wait, app_url, campaign_title, campaign_description)
        
        # Aller à la page de gestion des campagnes
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton de visualisation de la campagne
        view_button = wait.until(EC.element_to_be_clickable((By.XPATH, f"//*[contains(text(), '{campaign_title}')]/following-sibling::*//a[contains(@href, 'view_campaign.php')]")))
        view_button.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que le titre de la campagne est affiché
        title_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{campaign_title}')]")))
        assert title_element is not None
        
        # Vérifier que la description est affichée
        description_element = driver.find_element(By.XPATH, f"//*[contains(text(), '{campaign_description}')]")
        assert description_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': campaign_description,
            'game_system': 'D&D 5e',
            'is_public': True
        })
    
    def test_campaign_list_display(self, driver, wait, app_url, test_user):
        """Test d'affichage de la liste des campagnes"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer plusieurs campagnes
        campaigns = []
        for i in range(3):
            campaign_title = f"Campagne Liste {i+1} {int(time.time())}"
            campaign_description = f"Description de la campagne {i+1}"
            self._create_campaign(driver, wait, app_url, campaign_title, campaign_description)
            campaigns.append({
                'title': campaign_title,
                'description': campaign_description,
                'game_system': 'D&D 5e',
                'is_public': True
            })
        
        # Aller à la page de gestion des campagnes
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que toutes les campagnes sont affichées
        for campaign in campaigns:
            campaign_element = driver.find_element(By.XPATH, f"//*[contains(text(), '{campaign['title']}')]")
            assert campaign_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].extend(campaigns)
    
    def test_campaign_invite_code_display(self, driver, wait, app_url, test_user):
        """Test d'affichage du code d'invitation de la campagne"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer une campagne
        campaign_title = f"Campagne Code Invitation {int(time.time())}"
        campaign_description = "Campagne pour tester l'affichage du code d'invitation"
        self._create_campaign(driver, wait, app_url, campaign_title, campaign_description)
        
        # Aller à la page de gestion des campagnes
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que le code d'invitation est affiché
        invite_code_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{campaign_title}')]/following-sibling::*//code")))
        assert invite_code_element is not None
        assert len(invite_code_element.text) > 0
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': campaign_description,
            'game_system': 'D&D 5e',
            'is_public': True
        })
    
    def test_campaign_different_game_systems(self, driver, wait, app_url, test_user):
        """Test de création de campagnes avec différents systèmes de jeu"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        game_systems = ["D&D 5e", "Pathfinder", "Autre"]
        campaigns = []
        
        for game_system in game_systems:
            campaign_title = f"Campagne {game_system} {int(time.time())}"
            campaign_description = f"Campagne de test pour {game_system}"
            
            # Aller à la page de gestion des campagnes
            driver.get(f"{app_url}/campaigns.php")
            wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
            
            # Remplir le formulaire
            title_input = driver.find_element(By.NAME, "title")
            title_input.clear()
            title_input.send_keys(campaign_title)
            
            description_input = driver.find_element(By.NAME, "description")
            description_input.clear()
            description_input.send_keys(campaign_description)
            
            # Sélectionner le système de jeu
            game_system_select = driver.find_element(By.NAME, "game_system")
            game_system_select.send_keys(game_system)
            
            # Soumettre le formulaire
            submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_button.click()
            
            # Vérifier que la campagne a été créée
            wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{campaign_title}')]")))
            
            campaigns.append({
                'title': campaign_title,
                'description': campaign_description,
                'game_system': game_system,
                'is_public': True
            })
        
        # Vérifier que toutes les campagnes sont affichées avec leurs systèmes de jeu
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        for campaign in campaigns:
            campaign_element = driver.find_element(By.XPATH, f"//*[contains(text(), '{campaign['title']}')]")
            assert campaign_element is not None
            
            # Vérifier que le système de jeu est affiché
            system_element = driver.find_element(By.XPATH, f"//*[contains(text(), '{campaign['title']}')]/following-sibling::*//*[contains(text(), '{campaign['game_system']}')]")
            assert system_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].extend(campaigns)
    
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
    
    def _create_campaign(self, driver, wait, app_url, title, description, game_system="D&D 5e", is_public=True):
        """Méthode utilitaire pour créer une campagne"""
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le formulaire
        title_input = driver.find_element(By.NAME, "title")
        title_input.clear()
        title_input.send_keys(title)
        
        description_input = driver.find_element(By.NAME, "description")
        description_input.clear()
        description_input.send_keys(description)
        
        # Sélectionner le système de jeu
        game_system_select = driver.find_element(By.NAME, "game_system")
        game_system_select.send_keys(game_system)
        
        # Gérer la visibilité
        public_checkbox = driver.find_element(By.NAME, "is_public")
        if is_public and not public_checkbox.is_selected():
            public_checkbox.click()
        elif not is_public and public_checkbox.is_selected():
            public_checkbox.click()
        
        # Soumettre
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_button.click()
        
        # Attendre la confirmation
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(text(), '{title}')]")))
