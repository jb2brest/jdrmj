#!/usr/bin/env python3
"""
Menu interactif avancé pour l'exécution des tests JDR MJ
Permet de choisir par catégorie ou par test individuel
"""

import os
import sys
import subprocess
import time
import glob
from pathlib import Path

# Importer le système de rapports JSON
try:
    from json_test_reporter import JSONTestReporter
    JSON_REPORT_AVAILABLE = True
except ImportError:
    JSON_REPORT_AVAILABLE = False

class AdvancedTestMenu:
    def __init__(self):
        self.base_dir = Path(__file__).parent
        self.parent_dir = self.base_dir.parent
        
        # Récupérer l'environnement depuis les variables d'environnement
        self.test_environment = os.environ.get('TEST_ENVIRONMENT', 'local')
        self.headless_mode = os.environ.get('HEADLESS', 'false').lower() == 'true'
        
        # Déterminer le répertoire des tests selon l'environnement
        if self.test_environment == 'staging':
            self.tests_dir = Path("/var/www/html/jdrmj_staging/tests")
        elif self.test_environment == 'production':
            self.tests_dir = Path("/var/www/html/jdrmj/tests")
        else:  # local
            self.tests_dir = self.base_dir
        
        self.functional_dir = self.tests_dir / "functional"
        
        # Définir les catégories de tests
        self.test_categories = {
            "authentification": {
                "name": "🔐 Authentification et Utilisateurs",
                "files": ["test_authentication.py"],
                "description": "Tests de connexion, déconnexion, inscription"
            },
            "personnages": {
                "name": "👤 Gestion des Personnages", 
                "files": [
                    "test_character_creation_steps.py",
                    "test_character_creation_step1_simple.py",
                    "test_character_creation_debug.py",
                    "test_character_management.py"
                ],
                "description": "Tests de création et gestion des personnages"
            },
            "classes": {
                "name": "⚔️ Tests des Classes de Personnage",
                "files": [],
                "description": "Tests spécifiques à chaque classe de personnage",
                "subcategories": {
                    "barbare": {
                        "name": "🪓 Classe Barbare",
                        "files": ["classes/barbare/test_barbarian_class.py"],
                        "description": "Tests de la classe Barbare et ses capacités"
                    },
                    "barde": {
                        "name": "🎭 Classe Barde", 
                        "files": ["classes/barde/test_bard_class.py"],
                        "description": "Tests de la classe Barde et ses capacités"
                    },
                    "clerc": {
                        "name": "⛪ Classe Clerc",
                        "files": ["classes/clerc/test_cleric_class.py"],
                        "description": "Tests de la classe Clerc et ses capacités"
                    },
                    "druide": {
                        "name": "🌿 Classe Druide",
                        "files": ["classes/druide/test_druid_class.py"],
                        "description": "Tests de la classe Druide et ses capacités"
                    },
                    "ensorceleur": {
                        "name": "⚡ Classe Ensorceleur",
                        "files": ["classes/ensorceleur/test_sorcerer_class.py"],
                        "description": "Tests de la classe Ensorceleur et ses capacités"
                    },
                    "guerrier": {
                        "name": "⚔️ Classe Guerrier",
                        "files": ["classes/guerrier/test_fighter_class.py"],
                        "description": "Tests de la classe Guerrier et ses capacités"
                    },
                    "magicien": {
                        "name": "🔮 Classe Magicien",
                        "files": ["classes/magicien/test_wizard_class.py"],
                        "description": "Tests de la classe Magicien et ses capacités"
                    },
                    "moine": {
                        "name": "🥋 Classe Moine",
                        "files": ["classes/moine/test_monk_class.py"],
                        "description": "Tests de la classe Moine et ses capacités"
                    },
                    "occultiste": {
                        "name": "🔮 Classe Occultiste",
                        "files": ["classes/occultiste/test_warlock_class.py"],
                        "description": "Tests de la classe Occultiste et ses capacités"
                    },
                    "rodeur": {
                        "name": "🏹 Classe Rôdeur",
                        "files": ["classes/rodeur/test_ranger_class.py"],
                        "description": "Tests de la classe Rôdeur et ses capacités"
                    },
                    "paladin": {
                        "name": "⚔️ Classe Paladin",
                        "files": ["classes/paladin/test_paladin_class.py"],
                        "description": "Tests de la classe Paladin et ses capacités"
                    },
                    "roublard": {
                        "name": "🗡️ Classe Roublard",
                        "files": ["classes/roublard/test_rogue_class.py"],
                        "description": "Tests de la classe Roublard et ses capacités"
                    }
                }
            },
            "campagnes": {
                "name": "🏰 Gestion des Campagnes",
                "files": [
                    "test_campaign_management.py",
                    "test_campaign_simple.py",
                    "test_campaign_admin_deletion.py",
                    "test_campaign_debug.py"
                ],
                "description": "Tests de gestion des campagnes (anciens)",
                "subcategories": {
                    "creation": {
                        "name": "🏗️ Création de Campagnes",
                        "files": ["test_campaign_creation.py"],
                        "description": "Tests de création et gestion des campagnes"
                    },
                    "sessions": {
                        "name": "📅 Sessions de Campagne",
                        "files": ["test_campaign_sessions.py"],
                        "description": "Tests de gestion des sessions de campagne"
                    },
                    "membres": {
                        "name": "👥 Membres de Campagne",
                        "files": ["test_campaign_members.py"],
                        "description": "Tests de gestion des membres de campagne"
                    }
                }
            },
            "bestiaire": {
                "name": "🐉 Bestiaire et Monstres",
                "files": ["test_bestiary.py"],
                "description": "Tests du bestiaire et des monstres"
            },
            "utilisateurs_mj": {
                "name": "🎭 Utilisateurs MJ",
                "files": [
                    "test_dm_user_management.py",
                    "test_dm_user_web_interface.py",
                    "test_dm_setup.py"
                ],
                "description": "Tests de gestion des utilisateurs MJ"
            },
            "disponibilite": {
                "name": "🌐 Disponibilité de l'Application",
                "files": ["test_application_availability.py"],
                "description": "Tests de disponibilité et d'accessibilité"
            },
            "integration": {
                "name": "🔗 Tests d'Intégration",
                "files": ["test_integration.py"],
                "description": "Tests d'intégration complets"
            },
            "fixtures": {
                "name": "🧪 Tests de Fixtures",
                "files": ["test_fixtures.py"],
                "description": "Tests des données de test"
            },
            "races": {
                "name": "🧬 Tests des Races",
                "files": ["races/test_races.py"],
                "description": "Tests de sélection et caractéristiques des races"
            },
            "histoires": {
                "name": "📚 Tests des Historiques",
                "files": ["histoires/test_histoires.py"],
                "description": "Tests de sélection et caractéristiques des historiques"
            },
            "univers": {
                "name": "🌍 Univers et Géographie",
                "files": [
                    "test_world_creation.py",
                    "test_country_creation.py", 
                    "test_region_creation.py",
                    "test_place_creation.py",
                    "test_access_system.py",
                    "test_access_system_fixed.py"
                ],
                "description": "Tests de création et gestion de l'univers (mondes, pays, régions, lieux et accès)"
            }
        }
        
    def clear_screen(self):
        """Efface l'écran"""
        os.system('clear' if os.name == 'posix' else 'cls')
    
    def print_header(self):
        """Affiche l'en-tête du menu"""
        print("🎲" + "=" * 70)
        print("🎲  MENU INTERACTIF AVANCÉ DES TESTS - JDR 4 MJ")
        print("🎲" + "=" * 70)
        print()
    
    def print_main_menu(self):
        """Affiche le menu principal"""
        print("📋 MODES DE SÉLECTION :")
        print()
        print("1. 🗂️  Lancer par catégorie de tests")
        print("2. 🎯 Lancer un test spécifique")
        print("3. 🚀 Lancer tous les tests")
        print("4. 📊 Gérer les rapports JSON")
        print("5. ⚙️  Configuration")
        print("6. 📚 Aide")
        print()
        print("0. 🚪 Quitter")
        print()
        
        # Afficher les informations d'environnement
        if self.test_environment != 'local' or self.headless_mode:
            print("🔧 CONFIGURATION ACTUELLE :")
            if self.test_environment != 'local':
                print(f"   🌍 Environnement: {self.test_environment}")
            if self.headless_mode:
                print("   🔧 Mode headless: activé")
            print()
    
    def print_category_menu(self):
        """Affiche le menu des catégories"""
        print("🗂️  CATÉGORIES DE TESTS DISPONIBLES :")
        print()
        
        for i, (category_key, category_info) in enumerate(self.test_categories.items(), 1):
            # Vérifier si c'est une catégorie avec sous-catégories
            if "subcategories" in category_info:
                # Compter les sous-catégories disponibles
                available_subcategories = []
                for sub_key, sub_info in category_info["subcategories"].items():
                    for file in sub_info["files"]:
                        if (self.functional_dir / file).exists():
                            available_subcategories.append(sub_key)
                            break
                
                status = f"({len(available_subcategories)}/{len(category_info['subcategories'])} sous-catégories)"
                print(f"   {i}. {category_info['name']} {status}")
                print(f"      {category_info['description']}")
                print()
            else:
                # Compter les fichiers disponibles
                available_files = []
                for file in category_info["files"]:
                    if (self.functional_dir / file).exists():
                        available_files.append(file)
                
                status = f"({len(available_files)}/{len(category_info['files'])} fichiers)" if available_files else "(aucun fichier)"
                print(f"   {i}. {category_info['name']} {status}")
                print(f"      {category_info['description']}")
                print()
        
        print(f"   0. 🔄 Retour au menu principal")
        print()
    
    def print_individual_test_menu(self):
        """Affiche le menu des tests individuels"""
        print("🎯 TESTS INDIVIDUELS DISPONIBLES :")
        print()
        
        # Récupérer tous les fichiers de test
        test_files = list(self.functional_dir.glob("test_*.py"))
        test_files.sort()
        
        if not test_files:
            print("❌ Aucun fichier de test trouvé")
            return []
        
        # Afficher les fichiers par catégorie
        for category_key, category_info in self.test_categories.items():
            # Vérifier si c'est une catégorie avec sous-catégories
            if "subcategories" in category_info:
                print(f"📁 {category_info['name']}:")
                for sub_key, sub_info in category_info["subcategories"].items():
                    subcategory_files = []
                    for file in sub_info["files"]:
                        file_path = self.functional_dir / file
                        if file_path.exists():
                            subcategory_files.append(file_path)
                    
                    if subcategory_files:
                        print(f"   📂 {sub_info['name']}:")
                        for file_path in subcategory_files:
                            # Extraire les noms de tests du fichier
                            test_names = self.extract_test_names(file_path)
                            if test_names:
                                print(f"      📄 {file_path.name}:")
                                for test_name in test_names:
                                    print(f"         • {test_name}")
                            else:
                                print(f"      📄 {file_path.name}")
                print()
            else:
                category_files = []
                for file in category_info["files"]:
                    file_path = self.functional_dir / file
                    if file_path.exists():
                        category_files.append(file_path)
                
                if category_files:
                    print(f"📁 {category_info['name']}:")
                    for file_path in category_files:
                        # Extraire les noms de tests du fichier
                        test_names = self.extract_test_names(file_path)
                        if test_names:
                            print(f"   📄 {file_path.name}:")
                            for test_name in test_names:
                                print(f"      • {test_name}")
                        else:
                            print(f"   📄 {file_path.name}")
                    print()
        
        return test_files
    
    def extract_test_names(self, file_path):
        """Extrait les noms des tests d'un fichier Python"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            test_names = []
            lines = content.split('\n')
            
            for line in lines:
                line = line.strip()
                # Chercher les définitions de tests
                if line.startswith('def test_') and '(' in line:
                    test_name = line.split('(')[0].replace('def ', '')
                    test_names.append(test_name)
            
            return test_names
        except Exception:
            return []
    
    def get_user_choice(self, max_choice):
        """Récupère le choix de l'utilisateur"""
        while True:
            try:
                choice = input(f"🎯 Votre choix (0-{max_choice}): ").strip()
                if choice.isdigit():
                    choice_int = int(choice)
                    if 0 <= choice_int <= max_choice:
                        return choice_int
                    else:
                        print(f"❌ Veuillez entrer un nombre entre 0 et {max_choice}")
                else:
                    print(f"❌ Veuillez entrer un nombre valide entre 0 et {max_choice}")
            except KeyboardInterrupt:
                print("\n👋 Au revoir !")
                sys.exit(0)
            except EOFError:
                print("\n👋 Au revoir !")
                sys.exit(0)
            except Exception as e:
                print(f"❌ Erreur: {e}")
                print(f"❌ Veuillez entrer un nombre entre 0 et {max_choice}")
    
    def run_command(self, cmd, description):
        """Exécute une commande et affiche le résultat"""
        print(f"\n🚀 {description}...")
        print("-" * 60)
        
        try:
            # Utiliser le bon répertoire de travail selon l'environnement
            working_dir = self.tests_dir if self.test_environment != 'local' else self.parent_dir
            result = subprocess.run(cmd, shell=True, cwd=working_dir)
            if result.returncode == 0:
                print(f"\n✅ {description} terminé avec succès !")
            else:
                print(f"\n❌ {description} a échoué (code: {result.returncode})")
        except KeyboardInterrupt:
            print(f"\n⏹️ {description} interrompu par l'utilisateur")
        except Exception as e:
            print(f"\n❌ Erreur lors de {description}: {e}")
        
        input("\n⏸️ Appuyez sur Entrée pour continuer...")
    
    def run_tests_by_category(self, category_key):
        """Lance les tests d'une catégorie"""
        if category_key not in self.test_categories:
            print("❌ Catégorie non trouvée")
            return
        
        category_info = self.test_categories[category_key]
        
        # Vérifier si c'est une catégorie avec sous-catégories
        if "subcategories" in category_info:
            self.show_subcategory_menu(category_key, category_info)
            return
        
        available_files = []
        
        # Vérifier quels fichiers sont disponibles
        for file in category_info["files"]:
            file_path = self.functional_dir / file
            if file_path.exists():
                available_files.append(file)
        
        if not available_files:
            print(f"❌ Aucun fichier de test trouvé pour la catégorie {category_info['name']}")
            input("\n⏸️ Appuyez sur Entrée pour continuer...")
            return
        
        # Construire la commande pytest
        test_files_str = " ".join([f"functional/{file}" for file in available_files])
        
        # Ajouter les variables d'environnement
        env_vars = []
        if self.headless_mode:
            env_vars.append("HEADLESS=true")
        if self.test_environment != 'local':
            env_vars.append(f"TEST_ENVIRONMENT={self.test_environment}")
        
        # Déterminer le PYTHONPATH selon l'environnement
        if self.test_environment == 'staging':
            pythonpath = "/var/www/html/jdrmj_staging/tests"
        elif self.test_environment == 'production':
            pythonpath = "/var/www/html/jdrmj/tests"
        else:  # local
            pythonpath = "/home/jean/Documents/jdrmj/tests"
        
        env_vars.append(f"PYTHONPATH={pythonpath}")
        env_prefix = " ".join(env_vars) + " " if env_vars else ""
        cmd = f"{env_prefix}python3 -m pytest {test_files_str} -v -p pytest_json_reporter"
        
        self.run_command(cmd, f"Tests de la catégorie: {category_info['name']}")
    
    def show_subcategory_menu(self, category_key, category_info):
        """Affiche le menu des sous-catégories"""
        while True:
            self.clear_screen()
            self.print_header()
            
            print(f"⚔️  SOUS-CATÉGORIES - {category_info['name'].upper()}")
            print("=" * 60)
            print()
            print(f"📋 {category_info['description']}")
            print()
            
            subcategories = category_info["subcategories"]
            for i, (sub_key, sub_info) in enumerate(subcategories.items(), 1):
                # Vérifier quels fichiers sont disponibles
                available_files = []
                for file in sub_info["files"]:
                    if (self.functional_dir / file).exists():
                        available_files.append(file)
                
                status = f"({len(available_files)}/{len(sub_info['files'])} fichiers)" if available_files else "(aucun fichier)"
                print(f"   {i}. {sub_info['name']} {status}")
                print(f"      {sub_info['description']}")
                print()
            
            print(f"   0. 🔄 Retour au menu des catégories")
            print()
            
            choice = self.get_user_choice(len(subcategories))
            
            if choice == 0:
                break
            
            sub_key = list(subcategories.keys())[choice - 1]
            sub_info = subcategories[sub_key]
            
            # Vérifier quels fichiers sont disponibles
            available_files = []
            for file in sub_info["files"]:
                file_path = self.functional_dir / file
                if file_path.exists():
                    available_files.append(file)
            
            if not available_files:
                print(f"❌ Aucun fichier de test trouvé pour {sub_info['name']}")
                input("\n⏸️ Appuyez sur Entrée pour continuer...")
                continue
            
            # Construire la commande pytest
            test_files_str = " ".join([f"functional/{file}" for file in available_files])
            
            # Déterminer le PYTHONPATH selon l'environnement
            if self.test_environment == 'staging':
                pythonpath = "/var/www/html/jdrmj_staging/tests"
            elif self.test_environment == 'production':
                pythonpath = "/var/www/html/jdrmj/tests"
            else:  # local
                pythonpath = "/home/jean/Documents/jdrmj/tests"
            
            cmd = f"PYTHONPATH={pythonpath} python3 -m pytest {test_files_str} -v -p pytest_json_reporter"
            
            self.run_command(cmd, f"Tests de {sub_info['name']}")
    
    def run_specific_test(self):
        """Lance un test spécifique"""
        print("🎯 SÉLECTION D'UN TEST SPÉCIFIQUE")
        print("=" * 50)
        print()
        
        # Afficher les tests disponibles
        test_files = self.print_individual_test_menu()
        
        if not test_files:
            input("\n⏸️ Appuyez sur Entrée pour continuer...")
            return
        
        print("Options de sélection :")
        print("1. 📄 Lancer un fichier de test complet")
        print("2. 🎯 Lancer un test spécifique dans un fichier")
        print("0. 🔄 Retour au menu principal")
        print()
        
        choice = self.get_user_choice(2)
        
        if choice == 0:
            return
        elif choice == 1:
            self.run_specific_file()
        elif choice == 2:
            self.run_specific_test_in_file()
    
    def run_specific_file(self):
        """Lance un fichier de test spécifique"""
        print("\n📄 SÉLECTION D'UN FICHIER DE TEST")
        print("=" * 40)
        print()
        
        # Lister les fichiers disponibles
        test_files = list(self.functional_dir.glob("test_*.py"))
        test_files.sort()
        
        for i, file_path in enumerate(test_files, 1):
            print(f"{i}. {file_path.name}")
        
        print(f"0. 🔄 Retour au menu précédent")
        print()
        
        choice = self.get_user_choice(len(test_files))
        
        if choice == 0:
            return
        
        selected_file = test_files[choice - 1]
        
        # Déterminer le PYTHONPATH selon l'environnement
        if self.test_environment == 'staging':
            pythonpath = "/var/www/html/jdrmj_staging/tests"
        elif self.test_environment == 'production':
            pythonpath = "/var/www/html/jdrmj/tests"
        else:  # local
            pythonpath = "/home/jean/Documents/jdrmj/tests"
        
        cmd = f"PYTHONPATH={pythonpath} python3 -m pytest functional/{selected_file.name} -v -p pytest_json_reporter"
        
        self.run_command(cmd, f"Test du fichier: {selected_file.name}")
    
    def run_specific_test_in_file(self):
        """Lance un test spécifique dans un fichier"""
        print("\n🎯 SÉLECTION D'UN TEST SPÉCIFIQUE")
        print("=" * 40)
        print()
        
        # Sélectionner le fichier
        test_files = list(self.functional_dir.glob("test_*.py"))
        test_files.sort()
        
        print("Sélectionnez d'abord le fichier :")
        for i, file_path in enumerate(test_files, 1):
            print(f"{i}. {file_path.name}")
        
        print(f"0. 🔄 Retour au menu précédent")
        print()
        
        file_choice = self.get_user_choice(len(test_files))
        
        if file_choice == 0:
            return
        
        selected_file = test_files[file_choice - 1]
        
        # Extraire les tests du fichier
        test_names = self.extract_test_names(selected_file)
        
        if not test_names:
            print(f"❌ Aucun test trouvé dans {selected_file.name}")
            input("\n⏸️ Appuyez sur Entrée pour continuer...")
            return
        
        print(f"\nTests disponibles dans {selected_file.name}:")
        for i, test_name in enumerate(test_names, 1):
            print(f"{i}. {test_name}")
        
        print(f"0. 🔄 Retour au menu précédent")
        print()
        
        test_choice = self.get_user_choice(len(test_names))
        
        if test_choice == 0:
            return
        
        selected_test = test_names[test_choice - 1]
        
        # Ajouter les variables d'environnement
        env_vars = []
        if self.headless_mode:
            env_vars.append("HEADLESS=true")
        if self.test_environment != 'local':
            env_vars.append(f"TEST_ENVIRONMENT={self.test_environment}")
        
        # Déterminer le PYTHONPATH selon l'environnement
        if self.test_environment == 'staging':
            pythonpath = "/var/www/html/jdrmj_staging/tests"
        elif self.test_environment == 'production':
            pythonpath = "/var/www/html/jdrmj/tests"
        else:  # local
            pythonpath = "/home/jean/Documents/jdrmj/tests"
        
        env_vars.append(f"PYTHONPATH={pythonpath}")
        env_prefix = " ".join(env_vars) + " " if env_vars else ""
        cmd = f"{env_prefix}python3 -m pytest functional/{selected_file.name}::{selected_test} -v -p pytest_json_reporter"
        
        self.run_command(cmd, f"Test: {selected_test}")
    
    def run_all_tests(self):
        """Lance tous les tests"""
        print("🚀 LANCEMENT DE TOUS LES TESTS")
        print("=" * 40)
        print()
        print("⚠️  Attention: Cela peut prendre plusieurs minutes...")
        print()
        
        confirm = input("Voulez-vous continuer? (o/N): ").strip().lower()
        if confirm not in ['o', 'oui', 'y', 'yes']:
            print("❌ Annulé")
            input("\n⏸️ Appuyez sur Entrée pour continuer...")
            return
        
        # Ajouter les variables d'environnement
        env_vars = []
        if self.headless_mode:
            env_vars.append("HEADLESS=true")
        if self.test_environment != 'local':
            env_vars.append(f"TEST_ENVIRONMENT={self.test_environment}")
        
        # Déterminer le PYTHONPATH selon l'environnement
        if self.test_environment == 'staging':
            pythonpath = "/var/www/html/jdrmj_staging/tests"
        elif self.test_environment == 'production':
            pythonpath = "/var/www/html/jdrmj/tests"
        else:  # local
            pythonpath = "/home/jean/Documents/jdrmj/tests"
        
        env_vars.append(f"PYTHONPATH={pythonpath}")
        env_prefix = " ".join(env_vars) + " " if env_vars else ""
        cmd = f"{env_prefix}python3 -m pytest functional/ -v -p pytest_json_reporter"
        self.run_command(cmd, "Tous les tests")
    
    def manage_json_reports(self):
        """Gère les rapports JSON"""
        if not JSON_REPORT_AVAILABLE:
            print("\n❌ Système de rapports JSON non disponible")
            print("💡 Le module json_test_reporter.py est requis")
            input("\n⏸️ Appuyez sur Entrée pour continuer...")
            return
        
        print("\n📊 GESTION DES RAPPORTS JSON")
        print("=" * 40)
        print()
        print("1. 📄 Lister les rapports existants")
        print("2. 📊 Afficher les statistiques")
        print("3. 🗑️  Nettoyer les anciens rapports")
        print("0. 🔄 Retour au menu principal")
        print()
        
        choice = self.get_user_choice(3)
        
        if choice == 0:
            return
        elif choice == 1:
            self.list_reports()
        elif choice == 2:
            self.show_statistics()
        elif choice == 3:
            self.clean_reports()
    
    def list_reports(self):
        """Liste les rapports existants"""
        try:
            reporter = JSONTestReporter()
            individual_reports = reporter.list_reports("individual")
            
            print(f"\n📄 RAPPORTS INDIVIDUELS ({len(individual_reports)}):")
            print("-" * 50)
            
            for report in individual_reports[:20]:  # Afficher les 20 premiers
                report_data = reporter.read_report(report)
                if report_data:
                    status_icon = "✅" if report_data['result']['success'] else "❌"
                    test_name = report_data['test_info']['name']
                    category = report_data['test_info']['category']
                    print(f"  {status_icon} {test_name} ({category})")
            
            if len(individual_reports) > 20:
                print(f"  ... et {len(individual_reports) - 20} autres rapports")
                
        except Exception as e:
            print(f"❌ Erreur lors de la lecture des rapports: {e}")
        
        input("\n⏸️ Appuyez sur Entrée pour continuer...")
    
    def show_statistics(self):
        """Affiche les statistiques des rapports"""
        try:
            reporter = JSONTestReporter()
            individual_reports = reporter.list_reports("individual")
            
            if not individual_reports:
                print("❌ Aucun rapport trouvé")
                input("\n⏸️ Appuyez sur Entrée pour continuer...")
                return
            
            # Analyser les rapports
            total_tests = len(individual_reports)
            passed_tests = 0
            failed_tests = 0
            skipped_tests = 0
            categories = {}
            
            for report_path in individual_reports:
                report_data = reporter.read_report(report_path)
                if report_data:
                    status = report_data['result']['status']
                    if status == 'PASSED':
                        passed_tests += 1
                    elif status == 'FAILED':
                        failed_tests += 1
                    elif status == 'SKIPPED':
                        skipped_tests += 1
                    
                    category = report_data['test_info']['category']
                    if category not in categories:
                        categories[category] = {'total': 0, 'passed': 0, 'failed': 0, 'skipped': 0}
                    
                    categories[category]['total'] += 1
                    if status == 'PASSED':
                        categories[category]['passed'] += 1
                    elif status == 'FAILED':
                        categories[category]['failed'] += 1
                    elif status == 'SKIPPED':
                        categories[category]['skipped'] += 1
            
            success_rate = (passed_tests / total_tests * 100) if total_tests > 0 else 0
            
            print(f"\n📊 STATISTIQUES GLOBALES")
            print("=" * 30)
            print(f"Total des tests: {total_tests}")
            print(f"Tests réussis: {passed_tests}")
            print(f"Tests échoués: {failed_tests}")
            print(f"Tests ignorés: {skipped_tests}")
            print(f"Taux de réussite: {success_rate:.1f}%")
            
            print(f"\n📊 DÉTAILS PAR CATÉGORIE")
            print("=" * 35)
            for category, stats in categories.items():
                cat_success_rate = (stats['passed'] / stats['total'] * 100) if stats['total'] > 0 else 0
                print(f"{category}: {stats['passed']}/{stats['total']} réussis ({cat_success_rate:.1f}%)")
                print(f"  - Échoués: {stats['failed']}, Ignorés: {stats['skipped']}")
                
        except Exception as e:
            print(f"❌ Erreur lors de l'analyse des rapports: {e}")
        
        input("\n⏸️ Appuyez sur Entrée pour continuer...")
    
    def clean_reports(self):
        """Nettoie les anciens rapports"""
        print("\n🗑️  NETTOYAGE DES RAPPORTS")
        print("=" * 30)
        print()
        print("⚠️  Cette action supprimera tous les rapports JSON existants")
        print()
        
        confirm = input("Voulez-vous continuer? (o/N): ").strip().lower()
        if confirm not in ['o', 'oui', 'y', 'yes']:
            print("❌ Annulé")
            input("\n⏸️ Appuyez sur Entrée pour continuer...")
            return
        
        try:
            reports_dir = self.base_dir / "reports"
            if reports_dir.exists():
                import shutil
                shutil.rmtree(reports_dir)
                print("✅ Rapports supprimés avec succès")
            else:
                print("ℹ️  Aucun rapport à supprimer")
        except Exception as e:
            print(f"❌ Erreur lors de la suppression: {e}")
        
        input("\n⏸️ Appuyez sur Entrée pour continuer...")
    
    def configure_environment(self):
        """Configure l'environnement de test"""
        print("\n⚙️  CONFIGURATION DE L'ENVIRONNEMENT")
        print("=" * 40)
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
    
    def show_help(self):
        """Affiche l'aide"""
        print("\n📚 AIDE ET DOCUMENTATION")
        print("=" * 40)
        print()
        print("🎯 MODES DE SÉLECTION :")
        print("   • Par catégorie : Lance tous les tests d'une catégorie")
        print("   • Test spécifique : Lance un test ou un fichier précis")
        print("   • Tous les tests : Lance l'ensemble de la suite")
        print()
        print("📊 RAPPORTS JSON :")
        print("   • Chaque test génère un rapport JSON individuel")
        print("   • Les rapports sont stockés dans tests/reports/individual/")
        print("   • Accessibles via l'interface admin")
        print()
        print("🌐 URL DE TEST : http://localhost/jdrmj")
        print("   Modifiable via la variable d'environnement TEST_BASE_URL")
        print()
        
        input("⏸️ Appuyez sur Entrée pour continuer...")
    
    def handle_main_choice(self, choice):
        """Traite le choix du menu principal"""
        if choice == 0:
            print("👋 Au revoir !")
            sys.exit(0)
        elif choice == 1:
            self.show_category_menu()
        elif choice == 2:
            self.run_specific_test()
        elif choice == 3:
            self.run_all_tests()
        elif choice == 4:
            self.manage_json_reports()
        elif choice == 5:
            self.configure_environment()
        elif choice == 6:
            self.show_help()
    
    def show_category_menu(self):
        """Affiche le menu des catégories"""
        while True:
            self.clear_screen()
            self.print_header()
            self.print_category_menu()
            
            choice = self.get_user_choice(len(self.test_categories))
            
            if choice == 0:
                break
            
            category_key = list(self.test_categories.keys())[choice - 1]
            self.run_tests_by_category(category_key)
    
    def run(self):
        """Lance le menu interactif"""
        while True:
            self.clear_screen()
            self.print_header()
            self.print_main_menu()
            
            choice = self.get_user_choice(6)
            self.handle_main_choice(choice)

def main():
    """Fonction principale"""
    try:
        menu = AdvancedTestMenu()
        menu.run()
    except KeyboardInterrupt:
        print("\n👋 Au revoir !")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Erreur inattendue : {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
