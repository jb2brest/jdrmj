#!/usr/bin/env python3
"""
Script de configuration de l'environnement de test
"""
import os
import sys
import subprocess
import time
from pathlib import Path

def check_python_version():
    """Vérifie la version de Python"""
    if sys.version_info < (3, 8):
        print("❌ Python 3.8+ requis")
        return False
    print(f"✅ Python {sys.version.split()[0]} détecté")
    return True

def check_chrome_installation():
    """Vérifie l'installation de Chrome/Chromium"""
    chrome_paths = [
        '/usr/bin/google-chrome',
        '/usr/bin/chromium-browser',
        '/usr/bin/chromium',
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
    ]
    
    for path in chrome_paths:
        if os.path.exists(path):
            print(f"✅ Chrome/Chromium trouvé: {path}")
            return True
    
    print("❌ Chrome/Chromium non trouvé")
    print("   Installez Chrome ou Chromium pour exécuter les tests")
    return False

def install_dependencies():
    """Installe les dépendances Python"""
    print("🔧 Installation des dépendances...")
    try:
        subprocess.run([
            str(Path(__file__).parent.parent / "testenv" / "bin" / "python"), "-m", "pip", "install", "-r", "requirements.txt"
        ], check=True, cwd=Path(__file__).parent)
        print("✅ Dépendances installées avec succès")
        return True
    except subprocess.CalledProcessError as e:
        print(f"❌ Erreur lors de l'installation: {e}")
        return False

def create_directories():
    """Crée les répertoires nécessaires"""
    directories = [
        "reports",
        "screenshots",
        "logs"
    ]
    
    for directory in directories:
        dir_path = Path(__file__).parent / directory
        dir_path.mkdir(exist_ok=True)
        print(f"✅ Répertoire créé: {directory}")

def check_application_accessibility(base_url):
    """Vérifie l'accessibilité de l'application"""
    try:
        import requests
        response = requests.get(base_url, timeout=10)
        if response.status_code == 200:
            print(f"✅ Application accessible: {base_url}")
            return True
        else:
            print(f"❌ Application non accessible: {base_url} (Status: {response.status_code})")
            return False
    except ImportError:
        print("⚠️  Module requests non disponible, test d'accessibilité ignoré")
        return True
    except Exception as e:
        print(f"❌ Erreur lors de la vérification de l'application: {e}")
        return False

def run_smoke_tests():
    """Lance les tests de fumée"""
    print("🧪 Lancement des tests de fumée...")
    try:
        result = subprocess.run([
            str(Path(__file__).parent.parent / "testenv" / "bin" / "python"), "run_tests.py", "--type", "smoke", "--headless"
        ], cwd=Path(__file__).parent, capture_output=True, text=True)
        
        if result.returncode == 0:
            print("✅ Tests de fumée réussis")
            return True
        else:
            print("❌ Tests de fumée échoués")
            print(result.stdout)
            print(result.stderr)
            return False
    except Exception as e:
        print(f"❌ Erreur lors des tests de fumée: {e}")
        return False

def main():
    """Fonction principale"""
    print("🎲 Configuration de l'environnement de test JDR 4 MJ")
    print("=" * 50)
    
    # Vérifications préliminaires
    checks = [
        ("Version Python", check_python_version()),
        ("Installation Chrome", check_chrome_installation()),
    ]
    
    if not all(check[1] for check in checks):
        print("\n❌ Configuration échouée")
        return 1
    
    # Configuration
    create_directories()
    
    if not install_dependencies():
        print("\n❌ Installation des dépendances échouée")
        return 1
    
    # Vérification de l'application
    base_url = os.getenv('TEST_BASE_URL', 'http://localhost/jdrmj')
    if not check_application_accessibility(base_url):
        print(f"\n⚠️  Application non accessible à {base_url}")
        print("   Assurez-vous que l'application JDR 4 MJ est en cours d'exécution")
        print("   Vous pouvez définir TEST_BASE_URL pour spécifier une autre URL")
    
    # Tests de fumée
    if input("\n🧪 Lancer les tests de fumée ? (y/N): ").lower() == 'y':
        if not run_smoke_tests():
            print("\n❌ Tests de fumée échoués")
            return 1
    
    print("\n✅ Configuration terminée avec succès!")
    print("\n📋 Prochaines étapes:")
    print("   1. Assurez-vous que l'application JDR 4 MJ est accessible")
    print("   2. Lancez les tests: python run_tests.py")
    print("   3. Consultez les rapports dans tests/reports/")
    
    return 0

if __name__ == "__main__":
    sys.exit(main())
