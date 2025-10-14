#!/usr/bin/env python3
"""
Menu interactif pour l'exécution des tests JDR MJ
"""

import os
import sys
import subprocess
import time
from pathlib import Path

# Importer le système de rapports JSON
try:
    from json_test_reporter import JSONTestReporter
    JSON_REPORT_AVAILABLE = True
except ImportError:
    JSON_REPORT_AVAILABLE = False

class TestMenu:
    def __init__(self):
        self.base_dir = Path(__file__).parent
        self.parent_dir = self.base_dir.parent
        
    def clear_screen(self):
        """Efface l'écran"""
        os.system('clear' if os.name == 'posix' else 'cls')
    
    def print_header(self):
        """Affiche l'en-tête du menu"""
        print("🎲" + "=" * 60)
        print("🎲  MENU INTERACTIF DES TESTS - JDR 4 MJ")
        print("🎲" + "=" * 60)
        print()
    
    def print_menu(self):
        """Affiche le menu principal"""
        print("📋 CATÉGORIES DE TESTS DISPONIBLES :")
        print()
        print("🔐 AUTHENTIFICATION ET UTILISATEURS")
        print("   1. Tests d'authentification (connexion/déconnexion)")
        print("   2. Tests de gestion des utilisateurs MJ")
        print("   3. Tests d'inscription d'utilisateurs")
        print()
        print("👤 GESTION DES PERSONNAGES")
        print("   4. Tests de création de personnages")
        print("   5. Tests de gestion des personnages")
        print("   6. Tests d'équipement des personnages")
        print()
        print("🏰 GESTION DES CAMPAGNES")
        print("   7. Tests de gestion des campagnes")
        print("   8. Tests de création de campagnes")
        print("   9. Tests de sessions de campagne")
        print()
        print("🐉 BESTIAIRE ET MONSTRES")
        print("   10. Tests du bestiaire")
        print("   11. Tests de création de monstres")
        print("   12. Tests de gestion des monstres")
        print()
        print("🧪 TESTS SPÉCIALISÉS")
        print("   13. Tests de fumée (rapides)")
        print("   14. Tests d'intégration complets")
        print("   15. Tests de diagnostic")
        print()
        print("⚙️  OPTIONS ET CONFIGURATION")
        print("   16. Installation des dépendances")
        print("   17. Configuration de l'environnement")
        print("   18. Générer des rapports JSON des tests")
        print("   19. Aide et documentation")
        print()
        print("   0. Quitter")
        print()
    
    def get_user_choice(self):
        """Récupère le choix de l'utilisateur"""
        while True:
            try:
                choice = input("🎯 Votre choix (0-19): ").strip()
                if choice.isdigit() and 0 <= int(choice) <= 19:
                    return int(choice)
                else:
                    print("❌ Veuillez entrer un nombre entre 0 et 19")
            except KeyboardInterrupt:
                print("\n👋 Au revoir !")
                sys.exit(0)
            except:
                print("❌ Choix invalide")
    
    def run_command(self, cmd, description):
        """Exécute une commande et affiche le résultat"""
        print(f"\n🚀 {description}...")
        print("-" * 50)
        
        try:
            result = subprocess.run(cmd, shell=True, cwd=self.parent_dir)
            if result.returncode == 0:
                print(f"\n✅ {description} terminé avec succès !")
            else:
                print(f"\n❌ {description} a échoué (code: {result.returncode})")
        except KeyboardInterrupt:
            print(f"\n⏹️ {description} interrompu par l'utilisateur")
        except Exception as e:
            print(f"\n❌ Erreur lors de {description}: {e}")
        
        input("\n⏸️ Appuyez sur Entrée pour continuer...")
    
    def run_python_script(self, script_path, args="", description=""):
        """Exécute un script Python"""
        cmd = f"cd tests && python3 {script_path} {args}"
        self.run_command(cmd, description or f"Exécution de {script_path}")
    
    def run_selenium_tests(self, test_type, description, headless=True, generate_json=True):
        """Exécute des tests Selenium"""
        headless_flag = "--headless" if headless else ""
        json_flag = "" if generate_json else "--no-json"
        cmd = f"cd tests && python3 run_tests.py --type {test_type} {headless_flag} --verbose {json_flag}"
        self.run_command(cmd, description)
    
    def run_dm_user_tests(self, test_type="all", description="", generate_json=True):
        """Exécute les tests d'utilisateurs MJ"""
        json_flag = "" if generate_json else "--no-json"
        cmd = f"cd tests && python3 run_dm_user_tests.py --type {test_type} {json_flag}"
        self.run_command(cmd, description or "Tests de gestion des utilisateurs MJ")
    
    def install_dependencies(self):
        """Installe les dépendances"""
        print("\n🔧 Installation des dépendances...")
        print("-" * 50)
        
        try:
            print("📦 Installation des dépendances Python...")
            subprocess.run(["python3", "-m", "pip", "install", "-r", "requirements.txt"], 
                          cwd=self.base_dir, check=True)
            print("✅ Dépendances installées avec succès !")
        except subprocess.CalledProcessError as e:
            print(f"❌ Erreur lors de l'installation : {e}")
            print("💡 Solutions possibles :")
            print("   1. Installez pip : sudo apt install python3-pip")
            print("   2. Ou utilisez : sudo apt install python3-selenium python3-pytest")
            print("   3. Ou essayez : python3 -m ensurepip --upgrade")
        except FileNotFoundError:
            print("❌ python3 ou pip non trouvé")
            print("💡 Installez Python 3 et pip sur votre système :")
            print("   sudo apt update && sudo apt install python3 python3-pip")
        
        input("\n⏸️ Appuyez sur Entrée pour continuer...")
    
    def generate_json_report(self):
        """Génère des rapports JSON des tests"""
        if not JSON_REPORT_AVAILABLE:
            print("\n❌ Système de rapports JSON non disponible")
            print("💡 Le module json_test_reporter.py est requis")
            input("\n⏸️ Appuyez sur Entrée pour continuer...")
            return
        
        print("\n📊 GÉNÉRATION DE RAPPORTS JSON")
        print("=" * 50)
        print()
        print("Options disponibles :")
        print("1. Exécuter des tests avec rapports JSON automatiques")
        print("2. Lister les rapports JSON existants")
        print("3. Générer un rapport de résumé")
        print("4. Afficher les statistiques des rapports")
        print("0. Annuler")
        print()
        
        while True:
            try:
                choice = input("🎯 Choisissez une option (0-4): ").strip()
                if choice.isdigit() and 0 <= int(choice) <= 4:
                    choice = int(choice)
                    break
                else:
                    print("❌ Veuillez entrer un nombre entre 0 et 4")
            except KeyboardInterrupt:
                print("\n👋 Annulé")
                return
        
        if choice == 0:
            return
        
        try:
            reporter = JSONTestReporter()
            
            if choice == 1:
                # Exécuter des tests avec rapports JSON
                print("\n🚀 Exécution de tests avec rapports JSON automatiques...")
                print("Les rapports seront générés automatiquement pour chaque test")
                print("Utilisez les options du menu principal pour exécuter des tests spécifiques")
                
            elif choice == 2:
                # Lister les rapports existants
                print("\n📄 RAPPORTS JSON EXISTANTS")
                print("=" * 40)
                
                individual_reports = reporter.list_reports("individual")
                aggregated_reports = reporter.list_reports("aggregated")
                
                print(f"📄 Rapports individuels ({len(individual_reports)}):")
                for report in individual_reports[:10]:  # Afficher les 10 premiers
                    report_data = reporter.read_report(report)
                    if report_data:
                        status_icon = "✅" if report_data['result']['success'] else "❌"
                        print(f"  {status_icon} {Path(report).name} - {report_data['test_info']['name']}")
                
                print(f"\n📊 Rapports agrégés ({len(aggregated_reports)}):")
                for report in aggregated_reports[:5]:  # Afficher les 5 premiers
                    print(f"  📋 {Path(report).name}")
                
            elif choice == 3:
                # Générer un rapport de résumé
                print("\n📋 Génération du rapport de résumé...")
                summary_path = reporter.generate_summary_report("Résumé des tests récents")
                if summary_path:
                    print(f"✅ Rapport de résumé créé: {summary_path}")
                else:
                    print("❌ Aucun rapport récent trouvé")
                    
            elif choice == 4:
                # Afficher les statistiques
                print("\n📊 STATISTIQUES DES RAPPORTS")
                print("=" * 40)
                
                individual_reports = reporter.list_reports("individual")
                if individual_reports:
                    # Analyser les rapports
                    total_tests = len(individual_reports)
                    passed_tests = 0
                    failed_tests = 0
                    categories = {}
                    
                    for report_path in individual_reports:
                        report_data = reporter.read_report(report_path)
                        if report_data:
                            if report_data['result']['success']:
                                passed_tests += 1
                            else:
                                failed_tests += 1
                            
                            category = report_data['test_info']['category']
                            if category not in categories:
                                categories[category] = {'total': 0, 'passed': 0, 'failed': 0}
                            
                            categories[category]['total'] += 1
                            if report_data['result']['success']:
                                categories[category]['passed'] += 1
                            else:
                                categories[category]['failed'] += 1
                    
                    success_rate = (passed_tests / total_tests * 100) if total_tests > 0 else 0
                    
                    print(f"Total des tests: {total_tests}")
                    print(f"Tests réussis: {passed_tests}")
                    print(f"Tests échoués: {failed_tests}")
                    print(f"Taux de réussite: {success_rate:.1f}%")
                    
                    print(f"\n📊 DÉTAILS PAR CATÉGORIE")
                    print("=" * 35)
                    for category, stats in categories.items():
                        cat_success_rate = (stats['passed'] / stats['total'] * 100) if stats['total'] > 0 else 0
                        print(f"{category}: {stats['passed']}/{stats['total']} réussis ({cat_success_rate:.1f}%)")
                else:
                    print("❌ Aucun rapport trouvé")
                
        except Exception as e:
            print(f"❌ Erreur lors de la génération des rapports: {e}")
        
        input("\n⏸️ Appuyez sur Entrée pour continuer...")
    
    def show_help(self):
        """Affiche l'aide et la documentation"""
        self.clear_screen()
        print("📚 AIDE ET DOCUMENTATION")
        print("=" * 50)
        print()
        print("🔗 FICHIERS DE DOCUMENTATION :")
        print("   • tests/README.md - Documentation générale des tests")
        print("   • tests/README_DM_USER_TESTS.md - Tests utilisateurs MJ")
        print("   • tests/QUICK_START.md - Guide de démarrage rapide")
        print()
        print("📁 STRUCTURE DES TESTS :")
        print("   • tests/functional/ - Tests fonctionnels Selenium")
        print("   • tests/fixtures/ - Données de test")
        print("   • tests/reports/ - Rapports de tests")
        print()
        print("🚀 COMMANDES UTILES :")
        print("   • python3 run_tests.py --help - Aide complète")
        print("   • python3 run_dm_user_tests.py --help - Aide tests MJ")
        print("   • make help - Aide Makefile")
        print()
        print("🌐 URL DE TEST PAR DÉFAUT : http://localhost/jdrmj")
        print("   Modifiable via la variable d'environnement TEST_BASE_URL")
        print()
        input("⏸️ Appuyez sur Entrée pour continuer...")
    
    def configure_environment(self):
        """Configure l'environnement de test"""
        self.clear_screen()
        print("⚙️ CONFIGURATION DE L'ENVIRONNEMENT")
        print("=" * 50)
        print()
        
        current_url = os.getenv("TEST_BASE_URL", "http://localhost/jdrmj")
        print(f"🌐 URL actuelle : {current_url}")
        print()
        
        new_url = input("Nouvelle URL (ou Entrée pour garder l'actuelle): ").strip()
        if new_url:
            os.environ["TEST_BASE_URL"] = new_url
            print(f"✅ URL mise à jour : {new_url}")
        
        print()
        print("🔧 Autres options de configuration :")
        print("   • Mode headless : HEADLESS=true")
        print("   • Parallélisme : PARALLEL=true")
        print("   • Mode verbeux : VERBOSE=true")
        print()
        
        input("⏸️ Appuyez sur Entrée pour continuer...")
    
    def handle_choice(self, choice):
        """Traite le choix de l'utilisateur"""
        if choice == 0:
            print("👋 Au revoir !")
            sys.exit(0)
        
        elif choice == 1:
            self.run_selenium_tests("authentication", "Tests d'authentification")
        
        elif choice == 2:
            self.run_dm_user_tests("all", "Tests de gestion des utilisateurs MJ")
        
        elif choice == 3:
            self.run_selenium_tests("authentication", "Tests d'inscription d'utilisateurs")
        
        elif choice == 4:
            self.run_selenium_tests("character", "Tests de création de personnages")
        
        elif choice == 5:
            self.run_selenium_tests("character", "Tests de gestion des personnages")
        
        elif choice == 6:
            self.run_selenium_tests("character", "Tests d'équipement des personnages")
        
        elif choice == 7:
            self.run_selenium_tests("campaign", "Tests de gestion des campagnes")
        
        elif choice == 8:
            self.run_selenium_tests("campaign", "Tests de création de campagnes")
        
        elif choice == 9:
            self.run_selenium_tests("campaign", "Tests de sessions de campagne")
        
        elif choice == 10:
            self.run_selenium_tests("bestiary", "Tests du bestiaire")
        
        elif choice == 11:
            self.run_selenium_tests("bestiary", "Tests de création de monstres")
        
        elif choice == 12:
            self.run_selenium_tests("bestiary", "Tests de gestion des monstres")
        
        elif choice == 13:
            self.run_selenium_tests("smoke", "Tests de fumée (rapides)")
        
        elif choice == 14:
            self.run_selenium_tests("all", "Tests d'intégration complets")
        
        elif choice == 15:
            self.run_python_script("diagnostic_test.py", "", "Tests de diagnostic")
        
        elif choice == 16:
            self.install_dependencies()
        
        elif choice == 17:
            self.configure_environment()
        
        elif choice == 18:
            self.generate_json_report()
        
        elif choice == 19:
            self.show_help()
    
    def run(self):
        """Lance le menu interactif"""
        while True:
            self.clear_screen()
            self.print_header()
            self.print_menu()
            
            choice = self.get_user_choice()
            self.handle_choice(choice)

def main():
    """Fonction principale"""
    try:
        menu = TestMenu()
        menu.run()
    except KeyboardInterrupt:
        print("\n👋 Au revoir !")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Erreur inattendue : {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
