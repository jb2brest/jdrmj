#!/usr/bin/env python3
"""
Tests pour la gestion des sessions de campagne
"""

import pytest
import time
import os
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


class TestCampaignSessions:
    """Tests pour la gestion des sessions de campagne"""
    
    @pytest.mark.skip(reason="Fonctionnalité sessions de campagne non implémentée")
    def test_create_campaign_session_success(self, driver, wait, app_url, test_user):
        """Test de création d'une session de campagne avec succès"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer d'abord une campagne
        campaign_title = f"Campagne pour Session {int(time.time())}"
        campaign_description = "Campagne de test pour créer des sessions"
        self._create_campaign(driver, wait, app_url, campaign_title, campaign_description)
        
        # Aller à la page de visualisation de la campagne
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton de visualisation de la campagne
        view_button = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_campaign.php')]")))
        view_button.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le formulaire de création de session
        session_title = f"Session de Test {int(time.time())}"
        session_description = "Description de la session de test pour les tests automatisés"
        
        # Trouver et remplir le champ titre de la session
        session_title_input = wait.until(EC.presence_of_element_located((By.NAME, "session_title")))
        session_title_input.clear()
        session_title_input.send_keys(session_title)
        
        # Trouver et remplir le champ description de la session
        session_description_input = driver.find_element(By.NAME, "session_description")
        session_description_input.clear()
        session_description_input.send_keys(session_description)
        
        # Soumettre le formulaire de session
        session_submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'][value*='session'], input[type='submit'][value*='session']")
        session_submit_button.click()
        
        # Vérifier que la session a été créée
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(., '{session_title}')]")))
        
        # Vérifier le message de succès
        success_message = driver.find_element(By.CSS_SELECTOR, ".alert-success, .success, [class*='success']")
        assert "créée avec succès" in success_message.text.lower() or "session" in success_message.text.lower()
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': campaign_description,
            'game_system': 'D&D 5e',
            'is_public': True,
            'sessions': [{
                'title': session_title,
                'description': session_description
            }]
        })
    
    @pytest.mark.skip(reason="Fonctionnalité sessions de campagne non implémentée")
    def test_create_campaign_session_empty_title(self, driver, wait, app_url, test_user):
        """Test de création d'une session avec un titre vide"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer d'abord une campagne
        campaign_title = f"Campagne pour Test Vide {int(time.time())}"
        self._create_campaign(driver, wait, app_url, campaign_title, "Campagne de test")
        
        # Aller à la page de visualisation de la campagne
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton de visualisation de la campagne
        view_button = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_campaign.php')]")))
        view_button.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le formulaire avec un titre vide
        session_description_input = driver.find_element(By.NAME, "session_description")
        session_description_input.clear()
        session_description_input.send_keys("Description sans titre")
        
        # Soumettre le formulaire de session
        session_submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'][value*='session'], input[type='submit'][value*='session']")
        session_submit_button.click()
        
        # Vérifier qu'une erreur est affichée
        error_message = driver.find_element(By.CSS_SELECTOR, ".alert-danger, .error, [class*='error']")
        assert "titre" in error_message.text.lower() and "requis" in error_message.text.lower()
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': "Campagne de test",
            'game_system': 'D&D 5e',
            'is_public': True
        })
    
    @pytest.mark.skip(reason="Fonctionnalité sessions de campagne non implémentée")
    def test_view_campaign_session_details(self, driver, wait, app_url, test_user):
        """Test d'affichage des détails d'une session de campagne"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer une campagne et une session
        campaign_title = f"Campagne pour Visualisation {int(time.time())}"
        self._create_campaign(driver, wait, app_url, campaign_title, "Campagne de test")
        
        # Aller à la page de visualisation de la campagne
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton de visualisation de la campagne
        view_button = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_campaign.php')]")))
        view_button.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer une session
        session_title = f"Session à Visualiser {int(time.time())}"
        session_description = "Description détaillée de la session à visualiser"
        self._create_session(driver, wait, session_title, session_description)
        
        # Vérifier que la session est affichée
        session_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(., '{session_title}')]")))
        assert session_element is not None
        
        # Cliquer sur la session pour voir ses détails
        session_link = driver.find_element(By.XPATH, f"//a[contains(text(), '{session_title}')]")
        session_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que la description est affichée
        description_element = driver.find_element(By.XPATH, f"//*[contains(., '{session_description}')]")
        assert description_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': "Campagne de test",
            'game_system': 'D&D 5e',
            'is_public': True,
            'sessions': [{
                'title': session_title,
                'description': session_description
            }]
        })
    
    @pytest.mark.skip(reason="Fonctionnalité sessions de campagne non implémentée")
    def test_campaign_session_list_display(self, driver, wait, app_url, test_user):
        """Test d'affichage de la liste des sessions d'une campagne"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer une campagne
        campaign_title = f"Campagne pour Liste {int(time.time())}"
        self._create_campaign(driver, wait, app_url, campaign_title, "Campagne de test")
        
        # Aller à la page de visualisation de la campagne
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton de visualisation de la campagne
        view_button = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_campaign.php')]")))
        view_button.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer plusieurs sessions
        sessions = []
        for i in range(3):
            session_title = f"Session Liste {i+1} {int(time.time())}"
            session_description = f"Description de la session {i+1}"
            self._create_session(driver, wait, session_title, session_description)
            sessions.append({
                'title': session_title,
                'description': session_description
            })
        
        # Vérifier que toutes les sessions sont affichées
        for session in sessions:
            session_element = driver.find_element(By.XPATH, f"//*[contains(., '{session['title']}')]")
            assert session_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': "Campagne de test",
            'game_system': 'D&D 5e',
            'is_public': True,
            'sessions': sessions
        })
    
    @pytest.mark.skip(reason="Fonctionnalité sessions de campagne non implémentée")
    def test_campaign_session_notes(self, driver, wait, app_url, test_user):
        """Test d'ajout de notes à une session de campagne"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer une campagne et une session
        campaign_title = f"Campagne pour Notes {int(time.time())}"
        self._create_campaign(driver, wait, app_url, campaign_title, "Campagne de test")
        
        # Aller à la page de visualisation de la campagne
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton de visualisation de la campagne
        view_button = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_campaign.php')]")))
        view_button.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer une session
        session_title = f"Session avec Notes {int(time.time())}"
        session_description = "Session de test avec des notes détaillées"
        self._create_session(driver, wait, session_title, session_description)
        
        # Vérifier que la session est affichée
        session_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(., '{session_title}')]")))
        assert session_element is not None
        
        # Cliquer sur la session pour voir ses détails
        session_link = driver.find_element(By.XPATH, f"//a[contains(text(), '{session_title}')]")
        session_link.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Ajouter des notes à la session
        session_notes = f"Notes détaillées de la session {int(time.time())}"
        
        # Trouver le champ de notes et l'utiliser
        notes_input = driver.find_element(By.NAME, "session_notes")
        notes_input.clear()
        notes_input.send_keys(session_notes)
        
        # Soumettre les notes
        notes_submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'][value*='notes'], input[type='submit'][value*='notes']")
        notes_submit_button.click()
        
        # Vérifier que les notes ont été ajoutées
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(., '{session_notes}')]")))
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': "Campagne de test",
            'game_system': 'D&D 5e',
            'is_public': True,
            'sessions': [{
                'title': session_title,
                'description': session_description,
                'notes': session_notes
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
        wait.until(EC.presence_of_element_located((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{title}')]")))
    
    def _create_session(self, driver, wait, title, description):
        """Méthode utilitaire pour créer une session"""
        # Remplir le formulaire
        title_input = driver.find_element(By.NAME, "session_title")
        title_input.clear()
        title_input.send_keys(title)
        
        description_input = driver.find_element(By.NAME, "session_description")
        description_input.clear()
        description_input.send_keys(description)
        
        # Soumettre
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'][value*='session'], input[type='submit'][value*='session']")
        submit_button.click()
        
        # Attendre la confirmation
        wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(., '{title}')]")))
