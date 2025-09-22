#!/usr/bin/env python3
"""
Script de diagnostic simple pour tester la page des campagnes
"""
import sys
import os

# Ajouter le répertoire tests au path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'tests'))

def run_campaign_debug():
    """Exécuter le test de diagnostic des campagnes"""
    try:
        import pytest
        
        # Exécuter le test de diagnostic
        result = pytest.main([
            'tests/functional/test_campaign_debug.py::TestCampaignDebug::test_campaign_page_diagnostic',
            '-v',
            '-s',  # Ne pas capturer la sortie
            '--tb=short'
        ])
        
        if result == 0:
            print("\n✅ Test de diagnostic réussi")
        else:
            print(f"\n❌ Test de diagnostic échoué avec le code: {result}")
            
    except ImportError as e:
        print(f"❌ Erreur d'import: {e}")
        print("Assurez-vous que pytest et selenium sont installés:")
        print("pip install pytest selenium")
    except Exception as e:
        print(f"❌ Erreur inattendue: {e}")

if __name__ == "__main__":
    print("🔍 Diagnostic de la page des campagnes...")
    run_campaign_debug()
