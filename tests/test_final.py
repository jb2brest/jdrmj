#!/usr/bin/env python3
"""
Test final pour vérifier que le système de tests fonctionne
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

def test_imports():
    """Test que tous les modules peuvent être importés"""
    try:
        import selenium
        import pytest
        from selenium import webdriver
        from selenium.webdriver.chrome.service import Service
        from selenium.webdriver.chrome.options import Options
        print("✅ Tous les modules Selenium importés avec succès")
        return True
    except ImportError as e:
        print(f"❌ Erreur d'import: {e}")
        return False

def test_chromedriver():
    """Test que ChromeDriver est accessible"""
    import subprocess
    try:
        result = subprocess.run(['chromedriver', '--version'], 
                              capture_output=True, text=True, timeout=5)
        if result.returncode == 0:
            print(f"✅ ChromeDriver accessible: {result.stdout.strip()}")
            return True
        else:
            print(f"❌ ChromeDriver erreur: {result.stderr}")
            return False
    except Exception as e:
        print(f"❌ ChromeDriver non accessible: {e}")
        return False

def test_selenium_basic():
    """Test basique de Selenium"""
    try:
        from selenium import webdriver
        from selenium.webdriver.chrome.service import Service
        from selenium.webdriver.chrome.options import Options
        
        # Configuration Chrome
        chrome_options = Options()
        chrome_options.add_argument('--headless')
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        chrome_options.add_argument('--disable-gpu')
        
        # Créer le driver
        service = Service("/usr/local/bin/chromedriver")
        driver = webdriver.Chrome(service=service, options=chrome_options)
        
        # Test simple
        driver.get("https://www.google.com")
        title = driver.title
        driver.quit()
        
        if "Google" in title:
            print("✅ Test Selenium basique réussi")
            return True
        else:
            print(f"❌ Test Selenium échoué: titre = {title}")
            return False
            
    except Exception as e:
        print(f"❌ Erreur Selenium: {e}")
        return False

def test_pytest_fixtures():
    """Test que les fixtures pytest fonctionnent"""
    try:
        import subprocess
        result = subprocess.run([
            '../testenv/bin/python', '-m', 'pytest', 
            'functional/test_fixtures.py', '-v', '--tb=short'
        ], capture_output=True, text=True, timeout=30)
        
        if result.returncode == 0:
            print("✅ Tests de fixtures pytest réussis")
            return True
        else:
            print(f"❌ Tests de fixtures échoués: {result.stdout}")
            return False
            
    except Exception as e:
        print(f"❌ Erreur pytest: {e}")
        return False

def main():
    """Fonction principale"""
    print("🎲 Test final du système de tests Selenium")
    print("=" * 50)
    
    tests = [
        ("Import des modules", test_imports),
        ("ChromeDriver", test_chromedriver),
        ("Selenium basique", test_selenium_basic),
        ("Fixtures pytest", test_pytest_fixtures)
    ]
    
    passed = 0
    total = len(tests)
    
    for name, test_func in tests:
        print(f"\n🧪 {name}...")
        if test_func():
            passed += 1
        else:
            print(f"❌ {name} échoué")
    
    print(f"\n📊 Résultats: {passed}/{total} tests réussis")
    
    if passed == total:
        print("🎉 Tous les tests sont passés ! Le système est prêt.")
        return True
    else:
        print("❌ Certains tests ont échoué. Vérifiez la configuration.")
        return False

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)
