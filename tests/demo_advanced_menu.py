#!/usr/bin/env python3
"""
Script de démonstration du menu avancé des tests
"""

import os
import sys
from pathlib import Path

# Ajouter le répertoire parent au path pour importer les modules
sys.path.insert(0, str(Path(__file__).parent))

from advanced_test_menu import AdvancedTestMenu

def demo_menu():
    """Démonstration du menu avancé"""
    print("🎲 DÉMONSTRATION DU MENU AVANCÉ DES TESTS")
    print("=" * 50)
    print()
    
    menu = AdvancedTestMenu()
    
    print("📋 CATÉGORIES DISPONIBLES :")
    print()
    for i, (category_key, category_info) in enumerate(menu.test_categories.items(), 1):
        # Compter les fichiers disponibles
        available_files = []
        for file in category_info["files"]:
            if (menu.functional_dir / file).exists():
                available_files.append(file)
        
        status = f"({len(available_files)}/{len(category_info['files'])} fichiers)" if available_files else "(aucun fichier)"
        print(f"   {i}. {category_info['name']} {status}")
        print(f"      {category_info['description']}")
        print()
    
    print("🎯 TESTS INDIVIDUELS DISPONIBLES :")
    print()
    
    # Récupérer tous les fichiers de test
    test_files = list(menu.functional_dir.glob("test_*.py"))
    test_files.sort()
    
    for file_path in test_files:
        # Extraire les noms de tests du fichier
        test_names = menu.extract_test_names(file_path)
        if test_names:
            print(f"📄 {file_path.name}:")
            for test_name in test_names:
                print(f"   • {test_name}")
        else:
            print(f"📄 {file_path.name}")
        print()
    
    print("📊 FONCTIONNALITÉS DU MENU :")
    print()
    print("1. 🗂️  Lancer par catégorie :")
    print("   - Sélectionnez une catégorie (ex: Authentification)")
    print("   - Tous les tests de cette catégorie seront exécutés")
    print()
    print("2. 🎯 Lancer un test spécifique :")
    print("   - Choisissez un fichier de test")
    print("   - Ou choisissez un test précis dans un fichier")
    print()
    print("3. 🚀 Lancer tous les tests :")
    print("   - Exécute l'ensemble de la suite de tests")
    print()
    print("4. 📊 Gérer les rapports JSON :")
    print("   - Lister les rapports existants")
    print("   - Afficher les statistiques")
    print("   - Nettoyer les anciens rapports")
    print()
    print("5. ⚙️  Configuration :")
    print("   - Modifier l'URL de test")
    print("   - Configurer les options d'environnement")
    print()
    print("6. 📚 Aide :")
    print("   - Documentation et guide d'utilisation")
    print()

if __name__ == "__main__":
    demo_menu()
