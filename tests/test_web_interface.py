#!/usr/bin/env python3
"""
Test de l'interface web pour vérifier l'affichage des détails des tests
"""

import requests
import json
import os

def test_admin_versions_page():
    """Test de la page admin_versions.php"""
    
    print("🌐 Test de l'interface web admin_versions.php")
    print("=" * 50)
    
    # URL de la page
    url = "http://localhost/jdrmj_staging/admin_versions.php"
    
    try:
        # Faire une requête GET
        response = requests.get(url, timeout=10)
        
        if response.status_code == 200:
            print("✅ Page admin_versions.php accessible")
            
            # Vérifier la présence des éléments clés
            content = response.text
            
            checks = [
                ("Onglet Tests", "id=\"tests-tab\""),
                ("Modal détails", "id=\"testDetailsModal\""),
                ("Fonction showTestDetails", "function showTestDetails"),
                ("Timeline CSS", ".timeline"),
                ("Test name link", "test-name-link")
            ]
            
            for check_name, check_pattern in checks:
                if check_pattern in content:
                    print(f"✅ {check_name} présent")
                else:
                    print(f"❌ {check_name} manquant")
            
            return True
            
        else:
            print(f"❌ Erreur HTTP {response.status_code}")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"❌ Erreur de connexion: {e}")
        return False

def test_json_report_access():
    """Test d'accès au rapport JSON de démonstration"""
    
    print("\n📄 Test d'accès au rapport JSON")
    print("-" * 30)
    
    # Chemin du rapport
    report_path = "/home/jean/Documents/jdrmj/tests/reports/individual/demo_test_with_steps.json"
    
    if os.path.exists(report_path):
        print("✅ Fichier de rapport JSON trouvé")
        
        try:
            with open(report_path, 'r', encoding='utf-8') as f:
                report_data = json.load(f)
            
            # Vérifier la structure du rapport
            required_fields = ['test_info', 'result', 'test_steps']
            for field in required_fields:
                if field in report_data:
                    print(f"✅ Champ '{field}' présent")
                else:
                    print(f"❌ Champ '{field}' manquant")
            
            # Vérifier les étapes
            if 'test_steps' in report_data and len(report_data['test_steps']) > 0:
                print(f"✅ {len(report_data['test_steps'])} étapes capturées")
                
                # Vérifier la structure des étapes
                first_step = report_data['test_steps'][0]
                step_fields = ['step_number', 'name', 'description', 'type', 'timestamp']
                for field in step_fields:
                    if field in first_step:
                        print(f"✅ Champ d'étape '{field}' présent")
                    else:
                        print(f"❌ Champ d'étape '{field}' manquant")
            else:
                print("❌ Aucune étape trouvée")
            
            return True
            
        except json.JSONDecodeError as e:
            print(f"❌ Erreur de parsing JSON: {e}")
            return False
        except Exception as e:
            print(f"❌ Erreur lors de la lecture: {e}")
            return False
    else:
        print("❌ Fichier de rapport JSON non trouvé")
        return False

def test_web_access_to_json():
    """Test d'accès web au rapport JSON"""
    
    print("\n🌐 Test d'accès web au rapport JSON")
    print("-" * 35)
    
    # URL du rapport JSON
    json_url = "http://localhost/jdrmj_staging/tests/reports/individual/demo_test_with_steps.json"
    
    try:
        response = requests.get(json_url, timeout=10)
        
        if response.status_code == 200:
            print("✅ Rapport JSON accessible via HTTP")
            
            try:
                json_data = response.json()
                if 'test_steps' in json_data:
                    print(f"✅ JSON valide avec {len(json_data['test_steps'])} étapes")
                    return True
                else:
                    print("❌ JSON invalide ou sans étapes")
                    return False
            except json.JSONDecodeError:
                print("❌ Réponse HTTP n'est pas du JSON valide")
                return False
                
        else:
            print(f"❌ Erreur HTTP {response.status_code} pour l'accès au JSON")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"❌ Erreur de connexion pour le JSON: {e}")
        return False

def main():
    """Fonction principale de test"""
    
    print("🧪 Test de l'interface web du système de capture d'étapes")
    print("=" * 60)
    
    results = []
    
    # Test 1: Page admin_versions.php
    results.append(test_admin_versions_page())
    
    # Test 2: Accès au rapport JSON
    results.append(test_json_report_access())
    
    # Test 3: Accès web au JSON
    results.append(test_web_access_to_json())
    
    # Résumé
    print("\n📊 Résumé des tests")
    print("=" * 20)
    
    passed = sum(results)
    total = len(results)
    
    print(f"Tests réussis: {passed}/{total}")
    
    if passed == total:
        print("🎉 Tous les tests sont passés !")
        print("\n🌐 Vous pouvez maintenant:")
        print("1. Aller sur http://localhost/jdrmj_staging/admin_versions.php")
        print("2. Cliquer sur l'onglet 'Tests'")
        print("3. Cliquer sur le nom du test 'demo_test_with_steps'")
        print("4. Voir les détails des étapes dans la modal")
    else:
        print("⚠️ Certains tests ont échoué. Vérifiez la configuration.")
    
    return passed == total

if __name__ == "__main__":
    success = main()
    exit(0 if success else 1)

