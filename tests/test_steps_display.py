#!/usr/bin/env python3
"""
Test de l'affichage des étapes dans l'interface web
"""

import requests
import json
import os

def test_json_reports_with_steps():
    """Test des rapports JSON avec étapes"""
    
    print("📄 Test des rapports JSON avec étapes")
    print("=" * 40)
    
    # Liste des rapports à tester
    test_reports = [
        "test_barbarian_character_creation.json",
        "test_user_login.json",
        "test_bard_character_creation.json",
        "demo_test_with_steps.json"
    ]
    
    base_url = "http://localhost/jdrmj_staging/tests/reports/individual/"
    
    results = []
    
    for report_name in test_reports:
        print(f"\n🔍 Test du rapport: {report_name}")
        
        try:
            url = base_url + report_name
            response = requests.get(url, timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                
                # Vérifier la structure
                required_fields = ['test_info', 'result', 'test_steps']
                structure_ok = all(field in data for field in required_fields)
                
                if structure_ok:
                    print(f"  ✅ Structure correcte")
                    
                    # Vérifier les étapes
                    steps = data.get('test_steps', [])
                    if steps:
                        print(f"  ✅ {len(steps)} étapes trouvées")
                        
                        # Vérifier la structure des étapes
                        first_step = steps[0]
                        step_fields = ['step_number', 'name', 'description', 'type', 'timestamp']
                        step_structure_ok = all(field in first_step for field in step_fields)
                        
                        if step_structure_ok:
                            print(f"  ✅ Structure des étapes correcte")
                            print(f"  📝 Première étape: {first_step['name']} ({first_step['type']})")
                            results.append(True)
                        else:
                            print(f"  ❌ Structure des étapes incorrecte")
                            results.append(False)
                    else:
                        print(f"  ❌ Aucune étape trouvée")
                        results.append(False)
                else:
                    print(f"  ❌ Structure du rapport incorrecte")
                    results.append(False)
                    
            else:
                print(f"  ❌ Erreur HTTP {response.status_code}")
                results.append(False)
                
        except Exception as e:
            print(f"  ❌ Erreur: {e}")
            results.append(False)
    
    return results

def test_admin_versions_interface():
    """Test de l'interface admin_versions.php"""
    
    print("\n🌐 Test de l'interface admin_versions.php")
    print("=" * 45)
    
    try:
        # Note: Cette page nécessite une authentification admin
        # On teste juste que la page contient les éléments nécessaires
        url = "http://localhost/jdrmj_staging/admin_versions.php"
        response = requests.get(url, timeout=10)
        
        if response.status_code == 200:
            content = response.text
            
            # Vérifier les éléments de l'interface
            checks = [
                ("Modal détails", "testDetailsModal"),
                ("Fonction showTestDetails", "showTestDetails"),
                ("Timeline CSS", ".timeline"),
                ("Test name link", "test-name-link")
            ]
            
            results = []
            for check_name, pattern in checks:
                if pattern in content:
                    print(f"  ✅ {check_name} présent")
                    results.append(True)
                else:
                    print(f"  ❌ {check_name} manquant")
                    results.append(False)
            
            return results
            
        else:
            print(f"  ❌ Erreur HTTP {response.status_code}")
            print(f"  ℹ️  La page nécessite une authentification admin")
            return [False]
            
    except Exception as e:
        print(f"  ❌ Erreur: {e}")
        return [False]

def test_local_reports():
    """Test des rapports locaux"""
    
    print("\n📁 Test des rapports locaux")
    print("=" * 30)
    
    reports_dir = "/home/jean/Documents/jdrmj/tests/reports/individual"
    
    if not os.path.exists(reports_dir):
        print("  ❌ Répertoire des rapports non trouvé")
        return [False]
    
    # Compter les rapports avec étapes
    json_files = [f for f in os.listdir(reports_dir) if f.endswith('.json')]
    reports_with_steps = 0
    total_reports = len(json_files)
    
    for json_file in json_files[:5]:  # Tester les 5 premiers
        try:
            file_path = os.path.join(reports_dir, json_file)
            with open(file_path, 'r', encoding='utf-8') as f:
                data = json.load(f)
            
            if 'test_steps' in data and data['test_steps']:
                reports_with_steps += 1
                print(f"  ✅ {json_file}: {len(data['test_steps'])} étapes")
            else:
                print(f"  ❌ {json_file}: Aucune étape")
                
        except Exception as e:
            print(f"  ❌ {json_file}: Erreur - {e}")
    
    print(f"\n  📊 {reports_with_steps}/{min(5, total_reports)} rapports testés ont des étapes")
    
    return [reports_with_steps > 0]

def main():
    """Fonction principale"""
    
    print("🧪 Test de l'affichage des étapes dans l'interface web")
    print("=" * 60)
    
    # Tests
    json_results = test_json_reports_with_steps()
    interface_results = test_admin_versions_interface()
    local_results = test_local_reports()
    
    # Résumé
    print(f"\n📊 Résumé des tests")
    print("=" * 25)
    
    all_results = json_results + interface_results + local_results
    passed = sum(all_results)
    total = len(all_results)
    
    print(f"Tests réussis: {passed}/{total}")
    
    if passed == total:
        print("🎉 Tous les tests sont passés !")
        print("\n🌐 L'interface web est prête:")
        print("1. Connectez-vous en tant qu'admin sur http://localhost/jdrmj_staging/")
        print("2. Allez sur admin_versions.php")
        print("3. Cliquez sur l'onglet 'Tests'")
        print("4. Cliquez sur n'importe quel nom de test")
        print("5. Vérifiez que les étapes s'affichent dans la modal")
    else:
        print("⚠️ Certains tests ont échoué")
    
    return passed == total

if __name__ == "__main__":
    success = main()
    exit(0 if success else 1)

