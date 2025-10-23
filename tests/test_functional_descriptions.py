#!/usr/bin/env python3
"""
Test des descriptions fonctionnelles dans les rapports de tests
"""

import requests
import json
import os

def test_functional_descriptions():
    """Test des descriptions fonctionnelles dans les rapports"""
    
    print("📝 Test des descriptions fonctionnelles")
    print("=" * 45)
    
    # Liste des rapports à tester avec leurs types attendus
    test_reports = [
        ("test_barbarian_character_creation.json", "character_creation"),
        ("test_user_login.json", "login"),
        ("test_bard_character_view.json", "character_view"),
        ("test_barbarian_starting_equipment.json", "starting_equipment"),
        ("test_bard_level_progression.json", "level_progression"),
        ("test_user_account_deletion.json", "deletion")
    ]
    
    base_url = "http://localhost/jdrmj_staging/tests/reports/individual/"
    
    results = []
    
    for report_name, expected_type in test_reports:
        print(f"\n🔍 Test du rapport: {report_name}")
        
        try:
            url = base_url + report_name
            response = requests.get(url, timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                
                # Vérifier la structure
                if 'test_steps' not in data or not data['test_steps']:
                    print(f"  ❌ Aucune étape trouvée")
                    results.append(False)
                    continue
                
                steps = data['test_steps']
                print(f"  ✅ {len(steps)} étapes trouvées")
                
                # Vérifier les descriptions fonctionnelles
                functional_descriptions = []
                for step in steps:
                    if 'description' in step and step['description']:
                        functional_descriptions.append(step['description'])
                
                if len(functional_descriptions) >= 4:
                    print(f"  ✅ Descriptions fonctionnelles présentes")
                    
                    # Afficher les descriptions
                    for i, desc in enumerate(functional_descriptions, 1):
                        print(f"    {i}. {desc}")
                    
                    # Vérifier que les descriptions ne sont pas techniques
                    technical_terms = ["test", "exécution", "technique", "code", "script"]
                    is_functional = True
                    
                    for desc in functional_descriptions:
                        desc_lower = desc.lower()
                        if any(term in desc_lower for term in technical_terms):
                            # Vérifier si c'est vraiment technique ou fonctionnel
                            if "test de" in desc_lower or "exécution du test" in desc_lower:
                                is_functional = False
                                break
                    
                    if is_functional:
                        print(f"  ✅ Descriptions fonctionnelles (non techniques)")
                        results.append(True)
                    else:
                        print(f"  ⚠️  Descriptions encore techniques")
                        results.append(False)
                else:
                    print(f"  ❌ Pas assez de descriptions fonctionnelles")
                    results.append(False)
                    
            else:
                print(f"  ❌ Erreur HTTP {response.status_code}")
                results.append(False)
                
        except Exception as e:
            print(f"  ❌ Erreur: {e}")
            results.append(False)
    
    return results

def test_specific_functional_descriptions():
    """Test des descriptions spécifiques par type de test"""
    
    print("\n🎯 Test des descriptions spécifiques par type")
    print("=" * 50)
    
    # Tests spécifiques avec descriptions attendues
    specific_tests = [
        {
            "file": "test_user_login.json",
            "expected_descriptions": [
                "Préparation de l'environnement de connexion",
                "Connexion utilisateur",
                "Tentative de connexion avec les identifiants fournis"
            ]
        },
        {
            "file": "test_barbarian_character_creation.json",
            "expected_descriptions": [
                "Préparation de la création de personnage",
                "Création de personnage",
                "Création d'un nouveau personnage avec les caractéristiques choisies"
            ]
        },
        {
            "file": "test_bard_starting_equipment.json",
            "expected_descriptions": [
                "Préparation du test d'équipement de départ",
                "Équipement de départ",
                "Vérification de l'équipement initial du personnage"
            ]
        }
    ]
    
    base_url = "http://localhost/jdrmj_staging/tests/reports/individual/"
    results = []
    
    for test_case in specific_tests:
        print(f"\n🔍 Test spécifique: {test_case['file']}")
        
        try:
            url = base_url + test_case['file']
            response = requests.get(url, timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                
                if 'test_steps' not in data or not data['test_steps']:
                    print(f"  ❌ Aucune étape trouvée")
                    results.append(False)
                    continue
                
                steps = data['test_steps']
                descriptions = [step.get('description', '') for step in steps]
                
                # Vérifier que les descriptions attendues sont présentes
                found_descriptions = 0
                for expected_desc in test_case['expected_descriptions']:
                    if any(expected_desc in desc for desc in descriptions):
                        found_descriptions += 1
                        print(f"  ✅ Trouvé: {expected_desc}")
                    else:
                        print(f"  ❌ Manquant: {expected_desc}")
                
                if found_descriptions >= len(test_case['expected_descriptions']) * 0.8:  # 80% des descriptions
                    print(f"  ✅ Descriptions spécifiques correctes ({found_descriptions}/{len(test_case['expected_descriptions'])})")
                    results.append(True)
                else:
                    print(f"  ❌ Descriptions spécifiques incorrectes ({found_descriptions}/{len(test_case['expected_descriptions'])})")
                    results.append(False)
                    
            else:
                print(f"  ❌ Erreur HTTP {response.status_code}")
                results.append(False)
                
        except Exception as e:
            print(f"  ❌ Erreur: {e}")
            results.append(False)
    
    return results

def main():
    """Fonction principale"""
    
    print("🧪 Test des descriptions fonctionnelles dans les rapports de tests")
    print("=" * 70)
    
    # Tests
    general_results = test_functional_descriptions()
    specific_results = test_specific_functional_descriptions()
    
    # Résumé
    print(f"\n📊 Résumé des tests")
    print("=" * 25)
    
    all_results = general_results + specific_results
    passed = sum(all_results)
    total = len(all_results)
    
    print(f"Tests réussis: {passed}/{total}")
    
    if passed == total:
        print("🎉 Tous les tests sont passés !")
        print("\n✅ Les descriptions sont maintenant fonctionnelles et claires")
        print("🌐 L'interface web affiche des descriptions compréhensibles par les utilisateurs")
        print("\n📝 Exemples de descriptions fonctionnelles:")
        print("  • 'Préparation de la création de personnage'")
        print("  • 'Création d'un nouveau personnage avec les caractéristiques choisies'")
        print("  • 'L'utilisateur est connecté avec succès'")
        print("  • 'Vérification de l'équipement initial du personnage'")
    else:
        print("⚠️ Certains tests ont échoué")
        print("🔧 Les descriptions peuvent encore être améliorées")
    
    return passed == total

if __name__ == "__main__":
    success = main()
    exit(0 if success else 1)

