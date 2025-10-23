#!/usr/bin/env python3
"""
Démonstration du système de capture d'étapes de tests
Ce script montre comment utiliser le capteur d'étapes et génère un rapport JSON
"""

import time
import json
from test_steps_capturer import (
    start_test, end_test, add_action, add_assertion, add_info, 
    add_error, add_warning, step_context, export_test_steps
)
from json_test_reporter import JSONTestReporter

def demo_test_with_steps():
    """Démonstration d'un test avec capture d'étapes"""
    
    print("🎲 Démonstration du système de capture d'étapes de tests")
    print("=" * 60)
    
    # Démarrer le test
    start_test("demo_test_with_steps", "Test de démonstration du système de capture d'étapes")
    
    # Étape 1: Initialisation
    with step_context("Initialisation", "Préparation de l'environnement de test"):
        add_info("Environnement", "Environnement de test initialisé")
        add_action("Configuration", "Configuration des paramètres de test")
        time.sleep(0.1)  # Simuler une action
    
    # Étape 2: Connexion
    with step_context("Connexion", "Processus de connexion à l'application"):
        add_action("Navigation", "Navigation vers la page de connexion")
        add_action("Saisie identifiants", "Saisie du nom d'utilisateur et mot de passe")
        add_assertion("Connexion réussie", "Vérification de la connexion", 
                     expected="connecté", actual="connecté", passed=True)
        time.sleep(0.2)
    
    # Étape 3: Navigation
    with step_context("Navigation", "Navigation dans l'application"):
        add_action("Accès tableau de bord", "Accès au tableau de bord principal")
        add_action("Sélection menu", "Sélection d'un élément du menu")
        add_info("Page chargée", "Page de destination chargée avec succès")
        time.sleep(0.15)
    
    # Étape 4: Test de fonctionnalité
    with step_context("Test fonctionnalité", "Test d'une fonctionnalité spécifique"):
        add_action("Création élément", "Création d'un nouvel élément")
        add_assertion("Élément créé", "Vérification de la création", 
                     expected="créé", actual="créé", passed=True)
        add_action("Modification", "Modification de l'élément créé")
        add_assertion("Modification réussie", "Vérification de la modification", 
                     expected="modifié", actual="modifié", passed=True)
        time.sleep(0.3)
    
    # Étape 5: Test d'erreur (simulé)
    with step_context("Test d'erreur", "Test de gestion d'erreur"):
        add_action("Action invalide", "Tentative d'action invalide")
        add_error("Erreur simulée", "Erreur de test simulée pour démonstration", 
                 {"error_code": "TEST_ERROR", "details": "Ceci est une erreur simulée"})
        time.sleep(0.1)
    
    # Étape 6: Nettoyage
    with step_context("Nettoyage", "Nettoyage des données de test"):
        add_action("Suppression", "Suppression des éléments de test créés")
        add_assertion("Nettoyage réussi", "Vérification du nettoyage", 
                     expected="nettoyé", actual="nettoyé", passed=True)
        add_info("Test terminé", "Test de démonstration terminé avec succès")
        time.sleep(0.1)
    
    # Terminer le test
    end_test("completed")
    
    # Exporter les étapes
    test_steps_data = export_test_steps()
    
    print(f"✅ Test terminé avec {len(test_steps_data['steps'])} étapes capturées")
    print(f"📊 Résumé: {test_steps_data['summary']}")
    
    return test_steps_data

def create_json_report(test_steps_data):
    """Crée un rapport JSON avec les étapes capturées"""
    
    print("\n📄 Création du rapport JSON...")
    
    # Créer le reporter
    reporter = JSONTestReporter("tests/reports")
    
    # Calculer les temps
    start_time = time.time() - 2  # Simulation
    end_time = time.time()
    
    # Créer le rapport
    report_path = reporter.create_test_report(
        test_name="demo_test_with_steps",
        test_file="tests/test_steps_demo.py",
        status="PASSED",
        start_time=start_time,
        end_time=end_time,
        error_message="",
        category="Démonstration",
        priority="Basse",
        test_steps=test_steps_data['steps']
    )
    
    if report_path:
        print(f"✅ Rapport JSON créé: {report_path}")
        
        # Afficher un aperçu du rapport
        with open(report_path, 'r', encoding='utf-8') as f:
            report_data = json.load(f)
        
        print(f"\n📋 Aperçu du rapport:")
        print(f"  - Nom: {report_data['test_info']['name']}")
        print(f"  - Statut: {report_data['result']['status']}")
        print(f"  - Durée: {report_data['test_info']['duration_seconds']:.2f}s")
        print(f"  - Étapes: {len(report_data['test_steps'])}")
        
        return report_path
    else:
        print("❌ Erreur lors de la création du rapport")
        return None

def display_steps_summary(test_steps_data):
    """Affiche un résumé des étapes capturées"""
    
    print(f"\n📝 Résumé des étapes capturées:")
    print("-" * 40)
    
    for i, step in enumerate(test_steps_data['steps'], 1):
        step_type = step['type']
        duration = step['duration_seconds']
        name = step['name']
        description = step['description']
        
        # Icône selon le type
        icons = {
            'action': '▶️',
            'assertion': '✅',
            'info': 'ℹ️',
            'error': '❌',
            'warning': '⚠️',
            'screenshot': '📷'
        }
        
        icon = icons.get(step_type, '🔹')
        
        print(f"{i:2d}. {icon} {name}")
        print(f"    {description}")
        print(f"    Type: {step_type} | Durée: {duration:.3f}s")
        
        if step.get('details'):
            print(f"    Détails: {step['details']}")
        
        print()

def main():
    """Fonction principale de démonstration"""
    
    try:
        # Exécuter le test de démonstration
        test_steps_data = demo_test_with_steps()
        
        # Afficher le résumé
        display_steps_summary(test_steps_data)
        
        # Créer le rapport JSON
        report_path = create_json_report(test_steps_data)
        
        if report_path:
            print(f"\n🎉 Démonstration terminée avec succès!")
            print(f"📁 Rapport disponible dans: {report_path}")
            print(f"🌐 Consultez admin_versions.php pour voir les détails dans l'interface web")
        else:
            print(f"\n⚠️ Démonstration terminée avec des erreurs")
            
    except Exception as e:
        print(f"❌ Erreur lors de la démonstration: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    main()

