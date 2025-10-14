#!/usr/bin/env python3
"""
Démonstration du système de rapports JSON pour les tests JDR 4 MJ
"""

import sys
import time
from pathlib import Path

# Ajouter le répertoire tests au path
sys.path.insert(0, str(Path(__file__).parent))

from json_test_reporter import JSONTestReporter

def demo_individual_reports():
    """Démonstration de la création de rapports individuels"""
    print("🎲 DÉMONSTRATION - RAPPORTS JSON INDIVIDUELS")
    print("=" * 60)
    
    reporter = JSONTestReporter()
    
    # Simuler plusieurs tests
    test_scenarios = [
        {
            "name": "test_user_login_success",
            "file": "functional/test_authentication.py",
            "status": "PASSED",
            "error_message": "",
            "error_line": "",
            "category": "Authentification",
            "priority": "Basse"
        },
        {
            "name": "test_user_login_invalid_password",
            "file": "functional/test_authentication.py", 
            "status": "FAILED",
            "error_message": "AssertionError: Expected login to fail with invalid password",
            "error_line": "45",
            "category": "Authentification",
            "priority": "Haute"
        },
        {
            "name": "test_character_creation",
            "file": "functional/test_character_management.py",
            "status": "PASSED",
            "error_message": "",
            "error_line": "",
            "category": "Gestion_Personnages",
            "priority": "Basse"
        },
        {
            "name": "test_campaign_creation_timeout",
            "file": "functional/test_campaign_management.py",
            "status": "FAILED",
            "error_message": "TimeoutException: Campaign creation took too long",
            "error_line": "123",
            "category": "Gestion_Campagnes",
            "priority": "Haute"
        },
        {
            "name": "test_bestiary_search",
            "file": "functional/test_bestiary.py",
            "status": "PASSED",
            "error_message": "",
            "error_line": "",
            "category": "Bestiaire",
            "priority": "Basse"
        }
    ]
    
    print("📝 Création de rapports pour 5 tests simulés...")
    print()
    
    created_reports = []
    
    for i, scenario in enumerate(test_scenarios, 1):
        print(f"🧪 Test {i}/5: {scenario['name']}")
        
        start_time = time.time()
        time.sleep(0.05)  # Simuler une durée de test
        end_time = time.time()
        
        report_path = reporter.create_test_report(
            test_name=scenario['name'],
            test_file=scenario['file'],
            status=scenario['status'],
            start_time=start_time,
            end_time=end_time,
            error_message=scenario['error_message'],
            error_line=scenario['error_line'],
            category=scenario['category'],
            priority=scenario['priority']
        )
        
        if report_path:
            created_reports.append(report_path)
            status_icon = "✅" if scenario['status'] == "PASSED" else "❌"
            print(f"   {status_icon} Rapport créé: {Path(report_path).name}")
        else:
            print(f"   ❌ Erreur lors de la création du rapport")
        
        print()
    
    return created_reports

def demo_session_report(reports):
    """Démonstration de la création d'un rapport de session"""
    print("📊 DÉMONSTRATION - RAPPORT DE SESSION")
    print("=" * 50)
    
    reporter = JSONTestReporter()
    
    session_start_time = time.time() - 30  # Session de 30 secondes
    session_end_time = time.time()
    
    print("📋 Création du rapport de session...")
    
    session_report = reporter.create_test_session_report(
        session_name="demo_test_session",
        test_reports=reports,
        start_time=session_start_time,
        end_time=session_end_time
    )
    
    if session_report:
        print(f"✅ Rapport de session créé: {Path(session_report).name}")
        print()
        
        # Afficher le contenu du rapport de session
        session_data = reporter.read_report(session_report)
        if session_data:
            print("📊 RÉSUMÉ DE LA SESSION:")
            print(f"  - Nom: {session_data['session_info']['name']}")
            print(f"  - Durée: {session_data['session_info']['duration_formatted']}")
            print(f"  - Total des tests: {session_data['summary']['total_tests']}")
            print(f"  - Tests réussis: {session_data['summary']['passed_tests']}")
            print(f"  - Tests échoués: {session_data['summary']['failed_tests']}")
            print(f"  - Taux de réussite: {session_data['summary']['success_rate']}%")
            
            print("\n📊 DÉTAILS PAR CATÉGORIE:")
            for category, stats in session_data['categories'].items():
                success_rate = (stats['passed'] / stats['total'] * 100) if stats['total'] > 0 else 0
                print(f"  - {category}: {stats['passed']}/{stats['total']} réussis ({success_rate:.1f}%)")
    else:
        print("❌ Erreur lors de la création du rapport de session")

def demo_summary_report():
    """Démonstration de la génération d'un rapport de résumé"""
    print("\n📋 DÉMONSTRATION - RAPPORT DE RÉSUMÉ")
    print("=" * 50)
    
    reporter = JSONTestReporter()
    
    print("📊 Génération du rapport de résumé...")
    
    summary_report = reporter.generate_summary_report("Démonstration des rapports JSON")
    
    if summary_report:
        print(f"✅ Rapport de résumé créé: {Path(summary_report).name}")
        print()
        
        # Afficher le contenu du rapport de résumé
        summary_data = reporter.read_report(summary_report)
        if summary_data:
            print("📊 STATISTIQUES GLOBALES:")
            print(f"  - Période: {summary_data['summary_info']['period']}")
            print(f"  - Rapports analysés: {summary_data['summary_info']['total_reports_analyzed']}")
            print(f"  - Total des tests: {summary_data['statistics']['total_tests']}")
            print(f"  - Tests réussis: {summary_data['statistics']['passed_tests']}")
            print(f"  - Tests échoués: {summary_data['statistics']['failed_tests']}")
            print(f"  - Taux de réussite: {summary_data['statistics']['success_rate']}%")
            
            print("\n📊 DÉTAILS PAR CATÉGORIE:")
            for category, stats in summary_data['categories'].items():
                success_rate = (stats['passed'] / stats['total'] * 100) if stats['total'] > 0 else 0
                print(f"  - {category}: {stats['passed']}/{stats['total']} réussis ({success_rate:.1f}%)")
            
            print("\n📄 TESTS RÉCENTS:")
            for test in summary_data['recent_tests'][:3]:  # Afficher les 3 premiers
                status_icon = "✅" if test['status'] == "PASSED" else "❌"
                print(f"  {status_icon} {test['name']} ({test['duration']}s)")
    else:
        print("❌ Erreur lors de la génération du rapport de résumé")

def demo_list_reports():
    """Démonstration de la liste des rapports"""
    print("\n📄 DÉMONSTRATION - LISTE DES RAPPORTS")
    print("=" * 50)
    
    reporter = JSONTestReporter()
    
    print("📄 Rapports individuels:")
    individual_reports = reporter.list_reports("individual")
    for report in individual_reports:
        report_data = reporter.read_report(report)
        if report_data:
            status_icon = "✅" if report_data['result']['success'] else "❌"
            print(f"  {status_icon} {Path(report).name} - {report_data['test_info']['name']}")
    
    print("\n📊 Rapports agrégés:")
    aggregated_reports = reporter.list_reports("aggregated")
    for report in aggregated_reports:
        print(f"  📋 {Path(report).name}")

def main():
    """Fonction principale de démonstration"""
    print("🎲 DÉMONSTRATION DU SYSTÈME DE RAPPORTS JSON")
    print("=" * 70)
    print("Cette démonstration montre comment le nouveau système de rapports JSON")
    print("génère automatiquement un rapport pour chaque test exécuté.")
    print()
    
    # 1. Créer des rapports individuels
    reports = demo_individual_reports()
    
    # 2. Créer un rapport de session
    demo_session_report(reports)
    
    # 3. Générer un rapport de résumé
    demo_summary_report()
    
    # 4. Lister tous les rapports
    demo_list_reports()
    
    print("\n🎉 DÉMONSTRATION TERMINÉE")
    print("=" * 30)
    print("📁 Tous les rapports sont disponibles dans:")
    print("  - Rapports individuels: tests/reports/individual/")
    print("  - Rapports agrégés: tests/reports/aggregated/")
    print()
    print("💡 UTILISATION:")
    print("  - Chaque test génère automatiquement son rapport JSON")
    print("  - Les rapports contiennent toutes les informations de test")
    print("  - Les rapports peuvent être analysés et agrégés")
    print("  - Le système est intégré dans pytest via le plugin")

if __name__ == '__main__':
    main()
