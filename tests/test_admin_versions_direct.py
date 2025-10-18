#!/usr/bin/env python3
"""
Test direct du fichier admin_versions.php pour vérifier les modifications
"""

import os
import re

def test_file_content():
    """Test du contenu du fichier admin_versions.php"""
    
    print("🔍 Test du contenu du fichier admin_versions.php")
    print("=" * 50)
    
    # Chemin du fichier
    file_path = "/var/www/html/jdrmj_staging/admin_versions.php"
    
    if not os.path.exists(file_path):
        print(f"❌ Fichier non trouvé: {file_path}")
        return False
    
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        print(f"✅ Fichier trouvé ({len(content)} caractères)")
        
        # Tests des modifications
        tests = [
            ("Modal détails", "testDetailsModal"),
            ("Fonction showTestDetails", "showTestDetails"),
            ("Timeline CSS", ".timeline"),
            ("Test name link", "test-name-link"),
            ("Onglet Tests", 'id="tests-tab"'),
            ("Styles timeline", "timeline-marker"),
            ("Fonction exportTestDetails", "exportTestDetails"),
            ("Fonction loadTestDetails", "loadTestDetails"),
            ("Fonction displayTestDetails", "displayTestDetails")
        ]
        
        results = []
        for test_name, pattern in tests:
            if pattern in content:
                print(f"✅ {test_name} présent")
                results.append(True)
            else:
                print(f"❌ {test_name} manquant")
                results.append(False)
        
        # Test spécifique pour les noms de tests cliquables
        if 'onclick="showTestDetails(' in content:
            print("✅ Noms de tests cliquables présents")
            results.append(True)
        else:
            print("❌ Noms de tests cliquables manquants")
            results.append(False)
        
        # Test de la structure de la modal
        if 'modal-xl' in content and 'testDetailsContent' in content:
            print("✅ Structure de la modal correcte")
            results.append(True)
        else:
            print("❌ Structure de la modal incorrecte")
            results.append(False)
        
        # Résumé
        passed = sum(results)
        total = len(results)
        
        print(f"\n📊 Résumé: {passed}/{total} tests passés")
        
        if passed == total:
            print("🎉 Toutes les modifications sont présentes !")
            return True
        else:
            print("⚠️ Certaines modifications sont manquantes")
            return False
            
    except Exception as e:
        print(f"❌ Erreur lors de la lecture du fichier: {e}")
        return False

def test_json_report():
    """Test du rapport JSON de démonstration"""
    
    print("\n📄 Test du rapport JSON de démonstration")
    print("-" * 40)
    
    json_path = "/var/www/html/jdrmj_staging/tests/reports/individual/demo_test_with_steps.json"
    
    if not os.path.exists(json_path):
        print(f"❌ Rapport JSON non trouvé: {json_path}")
        return False
    
    try:
        import json
        with open(json_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        print("✅ Rapport JSON valide")
        
        # Vérifier la structure
        required_fields = ['test_info', 'result', 'test_steps']
        for field in required_fields:
            if field in data:
                print(f"✅ Champ '{field}' présent")
            else:
                print(f"❌ Champ '{field}' manquant")
                return False
        
        # Vérifier les étapes
        if 'test_steps' in data and len(data['test_steps']) > 0:
            print(f"✅ {len(data['test_steps'])} étapes capturées")
            
            # Vérifier la structure des étapes
            first_step = data['test_steps'][0]
            step_fields = ['step_number', 'name', 'description', 'type', 'timestamp']
            for field in step_fields:
                if field in first_step:
                    print(f"✅ Champ d'étape '{field}' présent")
                else:
                    print(f"❌ Champ d'étape '{field}' manquant")
                    return False
            
            return True
        else:
            print("❌ Aucune étape trouvée")
            return False
            
    except json.JSONDecodeError as e:
        print(f"❌ Erreur de parsing JSON: {e}")
        return False
    except Exception as e:
        print(f"❌ Erreur lors de la lecture: {e}")
        return False

def main():
    """Fonction principale"""
    
    print("🧪 Test direct des modifications admin_versions.php")
    print("=" * 60)
    
    # Test 1: Contenu du fichier
    file_ok = test_file_content()
    
    # Test 2: Rapport JSON
    json_ok = test_json_report()
    
    # Résumé final
    print(f"\n📊 Résumé final")
    print("=" * 20)
    
    if file_ok and json_ok:
        print("🎉 Tous les tests sont passés !")
        print("\n🌐 Pour tester l'interface web:")
        print("1. Connectez-vous en tant qu'administrateur sur http://localhost/jdrmj_staging/")
        print("2. Allez sur http://localhost/jdrmj_staging/admin_versions.php")
        print("3. Cliquez sur l'onglet 'Tests'")
        print("4. Cliquez sur le nom du test 'demo_test_with_steps'")
        print("5. Vérifiez que la modal s'ouvre avec les détails des étapes")
        return True
    else:
        print("⚠️ Certains tests ont échoué")
        return False

if __name__ == "__main__":
    success = main()
    exit(0 if success else 1)
