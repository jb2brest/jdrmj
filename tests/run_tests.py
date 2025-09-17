#!/usr/bin/env python3
"""
Script de lancement des tests Selenium
"""
import os
import sys
import subprocess
import argparse
from pathlib import Path

def install_dependencies():
    """Installe les dépendances Python nécessaires"""
    print("🔧 Installation des dépendances...")
    try:
        subprocess.run([str(Path(__file__).parent.parent / "testenv" / "bin" / "python"), "-m", "pip", "install", "-r", "requirements.txt"], 
                      check=True, cwd=Path(__file__).parent)
        print("✅ Dépendances installées avec succès")
    except subprocess.CalledProcessError as e:
        print(f"❌ Erreur lors de l'installation des dépendances: {e}")
        return False
    return True

def run_tests(test_type="all", headless=False, parallel=False, verbose=False):
    """Lance les tests selon les paramètres spécifiés"""
    print(f"🚀 Lancement des tests ({test_type})...")
    
    # Configuration de base
    cmd = [str(Path(__file__).parent.parent / "testenv" / "bin" / "python"), "-m", "pytest"]
    
    # Type de tests
    if test_type == "smoke":
        cmd.extend(["-m", "smoke"])
    elif test_type == "authentication":
        cmd.extend(["tests/functional/test_authentication.py"])
    elif test_type == "character":
        cmd.extend(["tests/functional/test_character_management.py"])
    elif test_type == "campaign":
        cmd.extend(["tests/functional/test_campaign_management.py"])
    elif test_type == "bestiary":
        cmd.extend(["tests/functional/test_bestiary.py"])
    elif test_type == "functional":
        cmd.extend(["tests/functional/"])
    elif test_type == "all":
        cmd.extend(["tests/"])
    else:
        print(f"❌ Type de test inconnu: {test_type}")
        return False
    
    # Options
    if headless:
        os.environ["HEADLESS"] = "true"
    
    if parallel:
        cmd.extend(["-n", "auto"])
    
    if verbose:
        cmd.append("-v")
    
    # Variables d'environnement
    os.environ["TEST_BASE_URL"] = os.getenv("TEST_BASE_URL", "http://localhost/jdrmj")
    
    try:
        result = subprocess.run(cmd, cwd=Path(__file__).parent.parent)
        return result.returncode == 0
    except KeyboardInterrupt:
        print("\n⏹️ Tests interrompus par l'utilisateur")
        return False
    except Exception as e:
        print(f"❌ Erreur lors de l'exécution des tests: {e}")
        return False

def main():
    """Fonction principale"""
    parser = argparse.ArgumentParser(description="Lanceur de tests Selenium pour JDR 4 MJ")
    parser.add_argument("--type", "-t", 
                       choices=["all", "smoke", "authentication", "character", "campaign", "bestiary", "functional"],
                       default="all",
                       help="Type de tests à exécuter")
    parser.add_argument("--headless", "-H", 
                       action="store_true",
                       help="Exécuter les tests en mode headless")
    parser.add_argument("--parallel", "-p", 
                       action="store_true",
                       help="Exécuter les tests en parallèle")
    parser.add_argument("--verbose", "-v", 
                       action="store_true",
                       help="Mode verbeux")
    parser.add_argument("--install", "-i", 
                       action="store_true",
                       help="Installer les dépendances avant d'exécuter les tests")
    parser.add_argument("--url", "-u", 
                       default="http://localhost/jdrmj",
                       help="URL de base de l'application à tester")
    
    args = parser.parse_args()
    
    # Configuration de l'URL
    os.environ["TEST_BASE_URL"] = args.url
    
    print("🎲 Tests Selenium pour JDR 4 MJ")
    print("=" * 40)
    print(f"URL de test: {args.url}")
    print(f"Type de tests: {args.type}")
    print(f"Mode headless: {args.headless}")
    print(f"Parallélisme: {args.parallel}")
    print("=" * 40)
    
    # Installation des dépendances si demandée
    if args.install:
        if not install_dependencies():
            return 1
    
    # Lancement des tests
    success = run_tests(
        test_type=args.type,
        headless=args.headless,
        parallel=args.parallel,
        verbose=args.verbose
    )
    
    if success:
        print("\n✅ Tous les tests sont passés avec succès!")
        print("📊 Rapport disponible dans: tests/reports/report.html")
    else:
        print("\n❌ Certains tests ont échoué")
        print("📊 Consultez le rapport pour plus de détails: tests/reports/report.html")
    
    return 0 if success else 1

if __name__ == "__main__":
    sys.exit(main())
