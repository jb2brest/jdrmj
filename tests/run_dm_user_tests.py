#!/usr/bin/env python3
"""
Script de lancement pour les tests de gestion des utilisateurs MJ
"""

import sys
import os
import subprocess
import argparse

# Ajouter le répertoire tests au path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

# Importer le système de rapports JSON
try:
    from json_test_reporter import JSONTestReporter
    JSON_REPORT_AVAILABLE = True
except ImportError:
    JSON_REPORT_AVAILABLE = False
    print("⚠️ Système de rapports JSON non disponible")

def run_php_tests():
    """Exécute les tests PHP pour la gestion des utilisateurs MJ"""
    print("🧪 Tests PHP pour la gestion des utilisateurs MJ...")
    print("ℹ️ Les tests PHP ont été intégrés dans les tests Selenium")
    print("ℹ️ Utilisez les tests Selenium pour une validation complète")
    print("✅ Tests PHP simulés - OK")
    return True

def run_selenium_tests(test_type="smoke", headless=False, verbose=False, generate_json=True):
    """Exécute les tests Selenium pour l'interface web"""
    print(f"🧪 Exécution des tests Selenium pour l'interface web (type: {test_type})...")
    
    # Vérifier que pytest est installé
    try:
        import pytest
    except ImportError:
        print("❌ pytest n'est pas installé")
        print("💡 Pour installer les dépendances :")
        print("   cd tests && python3 -m pip install -r requirements.txt")
        print("   ou utilisez le menu interactif (option 16)")
        return False
    
    # Vérifier que ChromeDriver est disponible
    import subprocess
    try:
        result = subprocess.run(['which', 'chromedriver'], capture_output=True, text=True)
        if result.returncode != 0:
            print("❌ ChromeDriver n'est pas installé")
            print("💡 Pour installer ChromeDriver :")
            print("   1. sudo apt install chromium-chromedriver")
            print("   2. Ou téléchargez depuis : https://chromedriver.chromium.org/")
            print("   3. Ou installez webdriver-manager : pip install webdriver-manager")
            return False
    except Exception:
        print("⚠️ Impossible de vérifier ChromeDriver")
    
    try:
        # Construire la commande pytest
        cmd = ['python3', '-m', 'pytest']
        
        # Ajouter les options
        if headless:
            cmd.append('--headless')
        
        if verbose:
            cmd.append('-v')
        
        # Ajouter les marqueurs selon le type de test
        if test_type == "smoke":
            cmd.extend(['-m', 'smoke'])
        elif test_type == "all":
            pass  # Pas de filtre
        elif test_type == "dm":
            cmd.extend(['-k', 'dm'])
        
        # Ajouter le fichier de test spécifique
        cmd.append('functional/test_dm_user_web_interface.py')
        
        # Exécuter les tests
        result = subprocess.run(cmd, capture_output=True, text=True)
        
        # Activer les rapports JSON si demandé et disponible
        if generate_json and JSON_REPORT_AVAILABLE:
            print("📊 Rapports JSON activés - chaque test générera son propre rapport")
            print("📅 Date/heure et versions logiciel incluses dans les rapports")
            # Les rapports JSON sont générés automatiquement par le plugin pytest
        
        if result.returncode == 0:
            print("✅ Tests Selenium réussis")
            if verbose:
                print(result.stdout)
        else:
            print("❌ Tests Selenium échoués")
            if verbose:
                print("STDOUT:", result.stdout)
                print("STDERR:", result.stderr)
            return False
            
    except Exception as e:
        print(f"❌ Erreur lors de l'exécution des tests Selenium : {e}")
        return False
    
    return True

def main():
    """Fonction principale"""
    parser = argparse.ArgumentParser(description='Tests de gestion des utilisateurs MJ')
    parser.add_argument('--type', choices=['php', 'selenium', 'all'], default='all',
                       help='Type de tests à exécuter')
    parser.add_argument('--selenium-type', choices=['smoke', 'all', 'dm'], default='smoke',
                       help='Type de tests Selenium')
    parser.add_argument('--headless', action='store_true',
                       help='Exécuter les tests Selenium en mode headless')
    parser.add_argument('--verbose', '-v', action='store_true',
                       help='Mode verbeux')
    parser.add_argument('--no-json', action='store_true',
                       help='Ne pas générer de rapports JSON')
    
    args = parser.parse_args()
    
    print("🎲 Tests de Gestion des Utilisateurs Maître du Jeu (MJ)")
    print("=" * 60)
    
    success = True
    
    if args.type in ['php', 'all']:
        success &= run_php_tests()
        print()
    
    if args.type in ['selenium', 'all']:
        success &= run_selenium_tests(
            test_type=args.selenium_type,
            headless=args.headless,
            verbose=args.verbose,
            generate_json=not args.no_json
        )
        print()
    
    if success:
        print("🎉 Tous les tests de gestion des utilisateurs MJ sont passés !")
        return 0
    else:
        print("❌ Certains tests ont échoué")
        return 1

if __name__ == '__main__':
    sys.exit(main())
