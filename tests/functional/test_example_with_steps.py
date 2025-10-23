#!/usr/bin/env python3
"""
Exemple de test avec capture d'étapes détaillées
Ce test démontre l'utilisation du capteur d'étapes pour générer des rapports détaillés
"""

import pytest
import time
from test_steps_capturer import (
    add_action, add_assertion, add_info, add_error, add_warning,
    step_context, get_test_steps
)

class TestExampleWithSteps:
    """Tests d'exemple avec capture d'étapes détaillées"""
    
    def test_login_process_with_steps(self, driver):
        """Test du processus de connexion avec capture d'étapes détaillées"""
        
        # Étape 1: Navigation vers la page de connexion
        with step_context("Navigation", "Accès à la page de connexion"):
            driver.get("http://localhost/jdrmj/login.php")
            add_info("Page chargée", "Page de connexion affichée")
        
        # Étape 2: Vérification de la présence des éléments
        with step_context("Vérification des éléments", "Contrôle de la présence des champs de connexion"):
            username_field = driver.find_element("name", "username")
            password_field = driver.find_element("name", "password")
            login_button = driver.find_element("type", "submit")
            
            add_assertion("Champ nom d'utilisateur", "Le champ nom d'utilisateur est présent", 
                         expected="présent", actual="présent", passed=True)
            add_assertion("Champ mot de passe", "Le champ mot de passe est présent", 
                         expected="présent", actual="présent", passed=True)
            add_assertion("Bouton de connexion", "Le bouton de connexion est présent", 
                         expected="présent", actual="présent", passed=True)
        
        # Étape 3: Saisie des identifiants
        with step_context("Saisie des identifiants", "Remplissage des champs de connexion"):
            username_field.send_keys("test_user")
            password_field.send_keys("test_password")
            
            add_action("Saisie nom d'utilisateur", "test_user saisi dans le champ username")
            add_action("Saisie mot de passe", "test_password saisi dans le champ password")
        
        # Étape 4: Soumission du formulaire
        with step_context("Soumission", "Clic sur le bouton de connexion"):
            login_button.click()
            add_action("Clic bouton connexion", "Formulaire de connexion soumis")
        
        # Étape 5: Vérification du résultat
        with step_context("Vérification du résultat", "Contrôle de la redirection ou du message d'erreur"):
            time.sleep(2)  # Attendre le traitement
            
            current_url = driver.current_url
            page_title = driver.title
            
            add_info("URL actuelle", f"Page actuelle: {current_url}")
            add_info("Titre de la page", f"Titre: {page_title}")
            
            # Vérifier si on est redirigé ou si il y a une erreur
            if "dashboard" in current_url or "index" in current_url:
                add_assertion("Connexion réussie", "Redirection vers le tableau de bord", 
                             expected="redirection", actual="redirection", passed=True)
            else:
                # Chercher un message d'erreur
                try:
                    error_element = driver.find_element("class", "alert-danger")
                    error_message = error_element.text
                    add_warning("Connexion échouée", f"Message d'erreur: {error_message}")
                except:
                    add_info("Pas de message d'erreur visible", "Aucun message d'erreur trouvé")
    
    def test_form_validation_with_steps(self, driver):
        """Test de validation de formulaire avec capture d'étapes détaillées"""
        
        # Étape 1: Navigation vers un formulaire
        with step_context("Navigation", "Accès à un formulaire de test"):
            driver.get("http://localhost/jdrmj/register.php")
            add_info("Page chargée", "Page d'inscription affichée")
        
        # Étape 2: Test de validation avec champs vides
        with step_context("Test validation champs vides", "Soumission du formulaire sans données"):
            submit_button = driver.find_element("type", "submit")
            submit_button.click()
            
            add_action("Soumission formulaire vide", "Formulaire soumis sans données")
            
            # Vérifier les messages d'erreur
            time.sleep(1)
            error_elements = driver.find_elements("class", "alert-danger")
            
            if error_elements:
                add_assertion("Messages d'erreur affichés", "Des messages d'erreur sont présents", 
                             expected="présents", actual="présents", passed=True)
                for i, error in enumerate(error_elements):
                    add_info(f"Erreur {i+1}", error.text)
            else:
                add_warning("Pas de messages d'erreur", "Aucun message d'erreur trouvé")
        
        # Étape 3: Test avec données invalides
        with step_context("Test données invalides", "Saisie de données invalides"):
            username_field = driver.find_element("name", "username")
            email_field = driver.find_element("name", "email")
            password_field = driver.find_element("name", "password")
            
            username_field.send_keys("a")  # Nom trop court
            email_field.send_keys("email-invalide")  # Email invalide
            password_field.send_keys("123")  # Mot de passe trop court
            
            add_action("Saisie nom court", "Nom d'utilisateur trop court saisi")
            add_action("Saisie email invalide", "Email invalide saisi")
            add_action("Saisie mot de passe court", "Mot de passe trop court saisi")
            
            submit_button.click()
            add_action("Soumission données invalides", "Formulaire soumis avec données invalides")
            
            # Vérifier les messages d'erreur spécifiques
            time.sleep(1)
            error_elements = driver.find_elements("class", "alert-danger")
            
            if error_elements:
                add_assertion("Validation des données", "Messages d'erreur de validation affichés", 
                             expected="messages d'erreur", actual="messages d'erreur", passed=True)
            else:
                add_error("Validation manquante", "Aucun message d'erreur de validation trouvé")
    
    def test_navigation_with_steps(self, driver):
        """Test de navigation avec capture d'étapes détaillées"""
        
        # Étape 1: Page d'accueil
        with step_context("Page d'accueil", "Accès à la page d'accueil"):
            driver.get("http://localhost/jdrmj/index.php")
            add_info("Page d'accueil", "Page d'accueil chargée")
        
        # Étape 2: Navigation vers différentes sections
        navigation_links = [
            ("Grimoire", "grimoire.php", "Accès au grimoire"),
            ("Bestiaire", "bestiary.php", "Accès au bestiaire"),
            ("Campagnes", "campaigns.php", "Accès aux campagnes")
        ]
        
        for link_text, expected_url, description in navigation_links:
            with step_context(f"Navigation vers {link_text}", description):
                try:
                    # Chercher le lien
                    link = driver.find_element("link text", link_text)
                    add_action(f"Lien {link_text} trouvé", f"Lien vers {link_text} localisé")
                    
                    # Cliquer sur le lien
                    link.click()
                    add_action(f"Clic sur {link_text}", f"Navigation vers {link_text}")
                    
                    # Vérifier la redirection
                    time.sleep(2)
                    current_url = driver.current_url
                    
                    if expected_url in current_url:
                        add_assertion(f"Redirection vers {link_text}", f"URL contient {expected_url}", 
                                     expected=expected_url, actual=current_url, passed=True)
                    else:
                        add_warning(f"Redirection inattendue vers {link_text}", 
                                  f"URL attendue: {expected_url}, URL actuelle: {current_url}")
                    
                    # Retour à la page d'accueil pour le test suivant
                    driver.get("http://localhost/jdrmj/index.php")
                    add_action("Retour accueil", "Retour à la page d'accueil")
                    
                except Exception as e:
                    add_error(f"Erreur navigation vers {link_text}", str(e))
    
    def test_error_handling_with_steps(self, driver):
        """Test de gestion d'erreurs avec capture d'étapes détaillées"""
        
        # Étape 1: Test d'une page inexistante
        with step_context("Page inexistante", "Tentative d'accès à une page inexistante"):
            try:
                driver.get("http://localhost/jdrmj/page_inexistante.php")
                add_action("Accès page inexistante", "Tentative d'accès à une page qui n'existe pas")
                
                time.sleep(2)
                page_title = driver.title
                current_url = driver.current_url
                
                add_info("Titre de la page", f"Titre: {page_title}")
                add_info("URL actuelle", f"URL: {current_url}")
                
                # Vérifier si on a une page 404 ou une redirection
                if "404" in page_title or "Not Found" in page_title:
                    add_assertion("Page 404", "Page d'erreur 404 affichée", 
                                 expected="404", actual="404", passed=True)
                else:
                    add_warning("Pas de page 404", "Aucune page d'erreur 404 détectée")
                    
            except Exception as e:
                add_error("Erreur lors du test page inexistante", str(e))
        
        # Étape 2: Test avec des paramètres invalides
        with step_context("Paramètres invalides", "Test avec des paramètres URL invalides"):
            try:
                driver.get("http://localhost/jdrmj/view_character.php?id=999999")
                add_action("Accès avec ID invalide", "Tentative d'accès avec un ID de personnage inexistant")
                
                time.sleep(2)
                page_title = driver.title
                
                add_info("Titre après paramètre invalide", f"Titre: {page_title}")
                
                # Vérifier la gestion de l'erreur
                if "erreur" in page_title.lower() or "error" in page_title.lower():
                    add_assertion("Gestion erreur paramètre", "Erreur gérée correctement", 
                                 expected="erreur gérée", actual="erreur gérée", passed=True)
                else:
                    add_info("Pas d'erreur visible", "Aucune erreur visible dans le titre")
                    
            except Exception as e:
                add_error("Erreur lors du test paramètres invalides", str(e))

if __name__ == "__main__":
    # Exécution directe du test pour démonstration
    pytest.main([__file__, "-v", "--tb=short"])

