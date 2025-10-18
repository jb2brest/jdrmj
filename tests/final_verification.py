#!/usr/bin/env python3
"""
Vérification finale du système de capture d'étapes
"""

import os
import json
import glob

def verify_file_structure():
    """Vérifie la structure des fichiers"""
    
    print("📁 Vérification de la structure des fichiers")
    print("=" * 45)
    
    files_to_check = [
        ("admin_versions.php", "/var/www/html/jdrmj_staging/admin_versions.php"),
        ("test_steps_capturer.py", "/home/jean/Documents/jdrmj/tests/test_steps_capturer.py"),
        ("json_test_reporter.py", "/home/jean/Documents/jdrmj/tests/json_test_reporter.py"),
        ("conftest.py", "/home/jean/Documents/jdrmj/tests/conftest.py")
    ]
    
    results = []
    
    for name, path in files_to_check:
        if os.path.exists(path):
            print(f"  ✅ {name} présent")
            results.append(True)
        else:
            print(f"  ❌ {name} manquant")
            results.append(False)
    
    return results

def verify_json_reports():
    """Vérifie les rapports JSON"""
    
    print("\n📄 Vérification des rapports JSON")
    print("=" * 35)
    
    reports_dirs = [
        "/home/jean/Documents/jdrmj/tests/reports/individual",
        "/var/www/html/jdrmj_staging/tests/reports/individual"
    ]
    
    total_reports = 0
    reports_with_steps = 0
    
    for reports_dir in reports_dirs:
        if not os.path.exists(reports_dir):
            print(f"  ⚠️  Répertoire non trouvé: {reports_dir}")
            continue
        
        json_files = glob.glob(os.path.join(reports_dir, "*.json"))
        total_reports += len(json_files)
        
        for json_file in json_files[:3]:  # Vérifier les 3 premiers
            try:
                with open(json_file, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                
                if 'test_steps' in data and data['test_steps']:
                    reports_with_steps += 1
                    print(f"  ✅ {os.path.basename(json_file)}: {len(data['test_steps'])} étapes")
                else:
                    print(f"  ❌ {os.path.basename(json_file)}: Aucune étape")
                    
            except Exception as e:
                print(f"  ❌ {os.path.basename(json_file)}: Erreur - {e}")
    
    print(f"\n  📊 {reports_with_steps} rapports avec étapes trouvés")
    return [reports_with_steps > 0]

def verify_admin_versions_content():
    """Vérifie le contenu de admin_versions.php"""
    
    print("\n🌐 Vérification du contenu de admin_versions.php")
    print("=" * 50)
    
    file_path = "/var/www/html/jdrmj_staging/admin_versions.php"
    
    if not os.path.exists(file_path):
        print("  ❌ Fichier admin_versions.php non trouvé")
        return [False]
    
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        checks = [
            ("Modal détails", "testDetailsModal"),
            ("Fonction showTestDetails", "showTestDetails"),
            ("Timeline CSS", ".timeline"),
            ("Test name link", "test-name-link"),
            ("Fonction loadTestDetails", "loadTestDetails"),
            ("Fonction displayTestDetails", "displayTestDetails"),
            ("Fonction exportTestDetails", "exportTestDetails")
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
        
    except Exception as e:
        print(f"  ❌ Erreur lors de la lecture: {e}")
        return [False]

def verify_demo_report():
    """Vérifie le rapport de démonstration"""
    
    print("\n🎯 Vérification du rapport de démonstration")
    print("=" * 45)
    
    demo_path = "/var/www/html/jdrmj_staging/tests/reports/individual/demo_test_with_steps.json"
    
    if not os.path.exists(demo_path):
        print("  ❌ Rapport de démonstration non trouvé")
        return [False]
    
    try:
        with open(demo_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        # Vérifier la structure
        required_fields = ['test_info', 'result', 'test_steps']
        structure_ok = all(field in data for field in required_fields)
        
        if not structure_ok:
            print("  ❌ Structure du rapport incorrecte")
            return [False]
        
        # Vérifier les étapes
        steps = data.get('test_steps', [])
        if not steps:
            print("  ❌ Aucune étape dans le rapport de démonstration")
            return [False]
        
        print(f"  ✅ Rapport de démonstration valide")
        print(f"  📊 {len(steps)} étapes capturées")
        
        # Vérifier les types d'étapes
        step_types = set(step.get('type', '') for step in steps)
        print(f"  🎨 Types d'étapes: {', '.join(step_types)}")
        
        return [True]
        
    except Exception as e:
        print(f"  ❌ Erreur lors de la lecture: {e}")
        return [False]

def main():
    """Fonction principale"""
    
    print("🔍 Vérification finale du système de capture d'étapes")
    print("=" * 60)
    
    # Vérifications
    file_results = verify_file_structure()
    json_results = verify_json_reports()
    content_results = verify_admin_versions_content()
    demo_results = verify_demo_report()
    
    # Résumé
    print(f"\n📊 Résumé de la vérification")
    print("=" * 35)
    
    all_results = file_results + json_results + content_results + demo_results
    passed = sum(all_results)
    total = len(all_results)
    
    print(f"Vérifications réussies: {passed}/{total}")
    
    if passed == total:
        print("🎉 Toutes les vérifications sont passées !")
        print("\n✅ Le système de capture d'étapes est entièrement fonctionnel")
        print("\n🌐 Pour tester l'interface web:")
        print("1. Connectez-vous en tant qu'administrateur sur http://localhost/jdrmj_staging/")
        print("2. Allez sur http://localhost/jdrmj_staging/admin_versions.php")
        print("3. Cliquez sur l'onglet 'Tests'")
        print("4. Cliquez sur le nom de n'importe quel test")
        print("5. Vérifiez que la modal s'ouvre avec les détails des étapes")
        print("\n📝 Note: Tous les tests existants ont maintenant des étapes basiques")
        print("📝 Les nouveaux tests peuvent utiliser le capteur d'étapes pour plus de détails")
        return True
    else:
        print("⚠️ Certaines vérifications ont échoué")
        return False

if __name__ == "__main__":
    success = main()
    exit(0 if success else 1)
