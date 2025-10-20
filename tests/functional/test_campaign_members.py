#!/usr/bin/env python3
"""
Tests pour la gestion des membres de campagne
"""

import pytest
import time
import os
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


class TestCampaignMembers:
    """Tests pour la gestion des membres de campagne"""
    
    @pytest.mark.skip(reason="Fonctionnalité join_campaign.php non implémentée")
    def test_join_campaign_with_invite_code(self, driver, wait, app_url, test_user):
        """Test de rejoindre une campagne avec un code d'invitation"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer une campagne
        campaign_title = f"Campagne pour Invitation {int(time.time())}"
        campaign_description = "Campagne de test pour tester les invitations"
        self._create_campaign(driver, wait, app_url, campaign_title, campaign_description)
        
        # Aller à la page de gestion des campagnes
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Récupérer le code d'invitation
        invite_code_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//code")))
        invite_code = invite_code_element.text
        
        # Se déconnecter
        driver.get(f"{app_url}/logout.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un second utilisateur de test
        second_user = {
            'username': f'test_user_2_{int(time.time())}',
            'email': f'test_2_{int(time.time())}@example.com',
            'password': 'TestPassword123!',
            'is_dm': False
        }
        
        # Inscrire le second utilisateur
        self._register_user(driver, wait, app_url, second_user)
        
        # Se connecter avec le second utilisateur
        self._login_user(driver, wait, app_url, second_user)
        
        # Aller à la page de rejoindre une campagne
        driver.get(f"{app_url}/join_campaign.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le code d'invitation
        invite_code_input = wait.until(EC.presence_of_element_located((By.NAME, "invite_code")))
        invite_code_input.clear()
        invite_code_input.send_keys(invite_code)
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'], button[type='submit']")
        submit_button.click()
        
        # Vérifier que l'utilisateur a rejoint la campagne
        wait.until(EC.presence_of_element_located((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]")))
        
        # Vérifier le message de succès
        success_message = driver.find_element(By.CSS_SELECTOR, ".alert-success, .success, [class*='success']")
        assert "rejoint" in success_message.text.lower() or "membre" in success_message.text.lower()
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': campaign_description,
            'game_system': 'D&D 5e',
            'is_public': True,
            'members': [second_user['username']]
        })
        
        # Ajouter le second utilisateur aux données de nettoyage
        test_user['created_users'] = test_user.get('created_users', [])
        test_user['created_users'].append(second_user)
    
    @pytest.mark.skip(reason="Fonctionnalité join_campaign.php non implémentée")
    def test_join_campaign_invalid_invite_code(self, driver, wait, app_url, test_user):
        """Test de rejoindre une campagne avec un code d'invitation invalide"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de rejoindre une campagne
        driver.get(f"{app_url}/join_campaign.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir un code d'invitation invalide
        invite_code_input = wait.until(EC.presence_of_element_located((By.NAME, "invite_code")))
        invite_code_input.clear()
        invite_code_input.send_keys("INVALID_CODE_123")
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'], button[type='submit']")
        submit_button.click()
        
        # Vérifier qu'une erreur est affichée
        error_message = driver.find_element(By.CSS_SELECTOR, ".alert-danger, .error, [class*='error']")
        assert "invalide" in error_message.text.lower() or "trouvé" in error_message.text.lower()
    
    def test_view_campaign_members(self, driver, wait, app_url, test_user):
        """Test d'affichage des membres d'une campagne"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer une campagne
        campaign_title = f"Campagne pour Membres {int(time.time())}"
        campaign_description = "Campagne de test pour afficher les membres"
        self._create_campaign(driver, wait, app_url, campaign_title, campaign_description)
        
        # Aller à la page de visualisation de la campagne
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton de visualisation de la campagne
        view_button = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_campaign.php')]")))
        view_button.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que le DM est affiché comme membre
        dm_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(., '{test_user['username']}')]")))
        assert dm_element is not None
        
        # Vérifier que le rôle MJ est affiché
        dm_role_element = driver.find_element(By.XPATH, f"//*[contains(., 'MJ') or contains(., 'mj')]")
        assert dm_role_element is not None
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': campaign_description,
            'game_system': 'D&D 5e',
            'is_public': True,
            'members': [test_user['username']]
        })
    
    @pytest.mark.skip(reason="Fonctionnalité join_campaign.php non implémentée")
    def test_remove_campaign_member(self, driver, wait, app_url, test_user):
        """Test de suppression d'un membre de campagne"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer une campagne
        campaign_title = f"Campagne pour Suppression {int(time.time())}"
        campaign_description = "Campagne de test pour supprimer des membres"
        self._create_campaign(driver, wait, app_url, campaign_title, campaign_description)
        
        # Aller à la page de gestion des campagnes
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Récupérer le code d'invitation
        invite_code_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//code")))
        invite_code = invite_code_element.text
        
        # Se déconnecter
        driver.get(f"{app_url}/logout.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un second utilisateur de test
        second_user = {
            'username': f'test_user_3_{int(time.time())}',
            'email': f'test_3_{int(time.time())}@example.com',
            'password': 'TestPassword123!',
            'is_dm': False
        }
        
        # Inscrire le second utilisateur
        self._register_user(driver, wait, app_url, second_user)
        
        # Se connecter avec le second utilisateur
        self._login_user(driver, wait, app_url, second_user)
        
        # Rejoindre la campagne
        self._join_campaign(driver, wait, app_url, invite_code)
        
        # Se déconnecter
        driver.get(f"{app_url}/logout.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Se reconnecter avec le DM
        self._login_user(driver, wait, app_url, test_user)
        
        # Aller à la page de visualisation de la campagne
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton de visualisation de la campagne
        view_button = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_campaign.php')]")))
        view_button.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que le membre est présent
        member_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//*[contains(., '{second_user['username']}')]")))
        assert member_element is not None
        
        # Supprimer le membre
        remove_button = driver.find_element(By.XPATH, f"//*[contains(., '{second_user['username']}')]/ancestor::tr//button[contains(@class, 'btn-danger') or contains(@class, 'remove')]")
        remove_button.click()
        
        # Confirmer la suppression
        confirm_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'][value*='confirm'], button[type='submit'][value*='confirm']")
        confirm_button.click()
        
        # Vérifier que le membre a été supprimé
        wait.until(EC.invisibility_of_element_located((By.XPATH, f"//*[contains(., '{second_user['username']}')]")))
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': campaign_description,
            'game_system': 'D&D 5e',
            'is_public': True,
            'members': [test_user['username']]
        })
        
        # Ajouter le second utilisateur aux données de nettoyage
        test_user['created_users'] = test_user.get('created_users', [])
        test_user['created_users'].append(second_user)
    
    @pytest.mark.skip(reason="Fonctionnalité join_campaign.php non implémentée")
    def test_campaign_member_permissions(self, driver, wait, app_url, test_user):
        """Test des permissions des membres de campagne"""
        # Se connecter avec l'utilisateur de test
        self._login_user(driver, wait, app_url, test_user)
        
        # Créer une campagne
        campaign_title = f"Campagne pour Permissions {int(time.time())}"
        campaign_description = "Campagne de test pour tester les permissions"
        self._create_campaign(driver, wait, app_url, campaign_title, campaign_description)
        
        # Aller à la page de gestion des campagnes
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Récupérer le code d'invitation
        invite_code_element = wait.until(EC.presence_of_element_located((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//code")))
        invite_code = invite_code_element.text
        
        # Se déconnecter
        driver.get(f"{app_url}/logout.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Créer un second utilisateur de test
        second_user = {
            'username': f'test_user_4_{int(time.time())}',
            'email': f'test_4_{int(time.time())}@example.com',
            'password': 'TestPassword123!',
            'is_dm': False
        }
        
        # Inscrire le second utilisateur
        self._register_user(driver, wait, app_url, second_user)
        
        # Se connecter avec le second utilisateur
        self._login_user(driver, wait, app_url, second_user)
        
        # Rejoindre la campagne
        self._join_campaign(driver, wait, app_url, invite_code)
        
        # Aller à la page de visualisation de la campagne
        driver.get(f"{app_url}/campaigns.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Cliquer sur le bouton de visualisation de la campagne
        view_button = wait.until(EC.element_to_be_clickable((By.XPATH, f"//h5[contains(@class, 'card-title') and contains(., '{campaign_title}')]/ancestor::div[contains(@class, 'card')]//a[contains(@href, 'view_campaign.php')]")))
        view_button.click()
        
        # Attendre que la page se charge
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Vérifier que l'utilisateur peut voir la campagne mais pas la modifier
        # (Les boutons de modification ne doivent pas être visibles pour un joueur)
        try:
            edit_button = driver.find_element(By.CSS_SELECTOR, "button[title*='Modifier'], button[title*='modifier']")
            assert False, "Le bouton de modification ne devrait pas être visible pour un joueur"
        except NoSuchElementException:
            # C'est normal, le bouton ne doit pas être visible
            pass
        
        # Stocker les données pour le nettoyage
        test_user['created_campaigns'] = test_user.get('created_campaigns', [])
        test_user['created_campaigns'].append({
            'title': campaign_title,
            'description': campaign_description,
            'game_system': 'D&D 5e',
            'is_public': True,
            'members': [test_user['username'], second_user['username']]
        })
        
        # Ajouter le second utilisateur aux données de nettoyage
        test_user['created_users'] = test_user.get('created_users', [])
        test_user['created_users'].append(second_user)
    
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
    
    def _register_user(self, driver, wait, app_url, user_data):
        """Méthode utilitaire pour inscrire un utilisateur"""
        driver.get(f"{app_url}/register.php")
        wait.until(EC.presence_of_element_located((By.NAME, "username")))
        
        # Remplir le formulaire d'inscription
        username_input = driver.find_element(By.NAME, "username")
        email_input = driver.find_element(By.NAME, "email")
        password_input = driver.find_element(By.NAME, "password")
        confirm_password_input = driver.find_element(By.NAME, "confirm_password")
        
        username_input.clear()
        username_input.send_keys(user_data['username'])
        
        email_input.clear()
        email_input.send_keys(user_data['email'])
        
        password_input.clear()
        password_input.send_keys(user_data['password'])
        
        confirm_password_input.clear()
        confirm_password_input.send_keys(user_data['password'])
        
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
    
    def _join_campaign(self, driver, wait, app_url, invite_code):
        """Méthode utilitaire pour rejoindre une campagne"""
        driver.get(f"{app_url}/join_campaign.php")
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        
        # Remplir le code d'invitation
        invite_code_input = driver.find_element(By.NAME, "invite_code")
        invite_code_input.clear()
        invite_code_input.send_keys(invite_code)
        
        # Soumettre le formulaire
        submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit'], button[type='submit']")
        submit_button.click()
        
        # Attendre la confirmation
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
