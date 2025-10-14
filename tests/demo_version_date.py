#!/usr/bin/env python3
"""
Démonstration des nouvelles fonctionnalités : Date/Heure et Version Logiciel
"""

import sys
import time
from pathlib import Path

# Ajouter le répertoire tests au path
sys.path.insert(0, str(Path(__file__).parent))

from json_test_reporter import JSONTestReporter
from version_detector import VersionDetector

def demo_version_detection():
    """Démonstration de la détection de version"""
    print("🔍 DÉMONSTRATION - DÉTECTION DE VERSION")
    print("=" * 60)
    
    detector = VersionDetector()
    
    print("📋 Résumé des versions détectées:")
    summary = detector.get_version_summary()
    print(f"  {summary}")
    print()
    
    print("📊 Informations détaillées:")
    info = detector.get_complete_version_info()
    
    print(f"  📱 Application: {info['application']['version']}")
    print(f"  🖥️ Système: {info['system']['os_name']} {info['system']['os_version']}")
    print(f"  🐍 Python: {info['system']['python_version'].split()[0]}")
    
    if 'php' in info['web_server_software']:
        print(f"  🐘 PHP: {info['web_server_software']['php']}")
    if 'apache' in info['web_server_software']:
        print(f"  🌐 Apache: {info['web_server_software']['apache']}")
    if 'mysql' in info['database']:
        print(f"  🗄️ MySQL: {info['database']['mysql']}")
    
    if 'commit_hash' in info['git']:
        print(f"  📝 Git: {info['git']['commit_hash']} ({info['git']['branch']})")
    
    print(f"  🌐 Serveur: {info['web_server']['server_status']} (HTTP {info['web_server'].get('http_status', 'N/A')})")
    print()

def demo_date_time_tracking():
    """Démonstration du suivi de date/heure"""
    print("📅 DÉMONSTRATION - SUIVI DATE/HEURE")
    print("=" * 50)
    
    reporter = JSONTestReporter()
    
    # Simuler plusieurs tests à différents moments
    test_scenarios = [
        {
            "name": "test_morning_login",
            "file": "functional/test_authentication.py",
            "status": "PASSED",
            "category": "Authentification",
            "description": "Test de connexion matinal"
        },
        {
            "name": "test_afternoon_character_creation",
            "file": "functional/test_character_management.py",
            "status": "PASSED",
            "category": "Gestion_Personnages",
            "description": "Test de création de personnage l'après-midi"
        },
        {
            "name": "test_evening_campaign_error",
            "file": "functional/test_campaign_management.py",
            "status": "FAILED",
            "category": "Gestion_Campagnes",
            "description": "Test de campagne avec erreur en soirée",
            "error_message": "Timeout lors de la création de campagne"
        }
    ]
    
    print("🧪 Création de tests avec suivi temporel...")
    print()
    
    created_reports = []
    
    for i, scenario in enumerate(test_scenarios, 1):
        print(f"📝 Test {i}/3: {scenario['name']}")
        print(f"   Description: {scenario['description']}")
        
        start_time = time.time()
        time.sleep(0.1)  # Simuler une durée de test
        end_time = time.time()
        
        report_path = reporter.create_test_report(
            test_name=scenario['name'],
            test_file=scenario['file'],
            status=scenario['status'],
            start_time=start_time,
            end_time=end_time,
            error_message=scenario.get('error_message', ''),
            category=scenario['category']
        )
        
        if report_path:
            created_reports.append(report_path)
            
            # Lire et afficher les informations temporelles
            report_data = reporter.read_report(report_path)
            if report_data:
                test_info = report_data['test_info']
                execution = report_data['execution']
                
                print(f"   📅 Date: {test_info['date']}")
                print(f"   ⏰ Heure: {test_info['time']}")
                print(f"   🕐 Début: {execution['start_time_formatted']}")
                print(f"   🕐 Fin: {execution['end_time_formatted']}")
                print(f"   ⏱️ Durée: {execution['duration_formatted']}")
                
                status_icon = "✅" if scenario['status'] == "PASSED" else "❌"
                print(f"   {status_icon} Statut: {scenario['status']}")
        
        print()
    
    return created_reports

def demo_version_in_reports(reports):
    """Démonstration des informations de version dans les rapports"""
    print("📊 DÉMONSTRATION - VERSIONS DANS LES RAPPORTS")
    print("=" * 60)
    
    reporter = JSONTestReporter()
    
    print("🔍 Analyse des rapports générés...")
    print()
    
    for report_path in reports:
        report_data = reporter.read_report(report_path)
        if report_data and 'version_info' in report_data:
            test_name = report_data['test_info']['name']
            version_info = report_data['version_info']
            
            print(f"📄 Rapport: {test_name}")
            print(f"   📱 Version App: {version_info['application']['version']}")
            print(f"   🖥️ OS: {version_info['system']['os_name']}")
            print(f"   🐍 Python: {version_info['system']['python_version'].split()[0]}")
            
            if 'php' in version_info['web_server_software']:
                print(f"   🐘 PHP: {version_info['web_server_software']['php']}")
            if 'mysql' in version_info['database']:
                print(f"   🗄️ MySQL: {version_info['database']['mysql']}")
            if 'commit_hash' in version_info['git']:
                print(f"   📝 Git: {version_info['git']['commit_hash']}")
            
            print(f"   🌐 Serveur: {version_info['web_server']['server_status']}")
            print()

def demo_session_with_version_date():
    """Démonstration d'une session complète avec version et date"""
    print("🎯 DÉMONSTRATION - SESSION COMPLÈTE")
    print("=" * 50)
    
    reporter = JSONTestReporter()
    
    # Créer quelques rapports de test
    reports = []
    start_time = time.time()
    
    test_cases = [
        ("test_version_check", "PASSED", "Vérification de version"),
        ("test_date_validation", "PASSED", "Validation de date"),
        ("test_timeout_issue", "FAILED", "Problème de timeout")
    ]
    
    for test_name, status, description in test_cases:
        print(f"🧪 {description}...")
        
        test_start = time.time()
        time.sleep(0.05)
        test_end = time.time()
        
        report_path = reporter.create_test_report(
            test_name=test_name,
            test_file=f"functional/test_{test_name}.py",
            status=status,
            start_time=test_start,
            end_time=test_end,
            error_message="Timeout après 30 secondes" if status == "FAILED" else "",
            category="Tests_Integration"
        )
        
        if report_path:
            reports.append(report_path)
    
    end_time = time.time()
    
    # Créer le rapport de session
    print("\n📊 Création du rapport de session...")
    session_report = reporter.create_test_session_report(
        session_name="demo_version_date_session",
        test_reports=reports,
        start_time=start_time,
        end_time=end_time
    )
    
    if session_report:
        print(f"✅ Rapport de session créé: {Path(session_report).name}")
        
        # Afficher les informations de la session
        session_data = reporter.read_report(session_report)
        if session_data:
            session_info = session_data['session_info']
            version_info = session_data.get('version_info', {})
            
            print(f"\n📋 INFORMATIONS DE SESSION:")
            print(f"  📅 Date: {session_info['date']}")
            print(f"  ⏰ Heure: {session_info['time']}")
            print(f"  🕐 Début: {session_info['start_time_formatted']}")
            print(f"  🕐 Fin: {session_info['end_time_formatted']}")
            print(f"  ⏱️ Durée: {session_info['duration_formatted']}")
            
            if version_info and 'application' in version_info:
                print(f"\n📱 VERSIONS TESTÉES:")
                print(f"  Application: {version_info['application']['version']}")
                if 'php' in version_info.get('web_server_software', {}):
                    print(f"  PHP: {version_info['web_server_software']['php']}")
                if 'mysql' in version_info.get('database', {}):
                    print(f"  MySQL: {version_info['database']['mysql']}")
                if 'commit_hash' in version_info.get('git', {}):
                    print(f"  Git: {version_info['git']['commit_hash']}")

def main():
    """Fonction principale de démonstration"""
    print("🎲 DÉMONSTRATION - DATE/HEURE ET VERSION LOGICIEL")
    print("=" * 70)
    print("Cette démonstration montre les nouvelles fonctionnalités ajoutées")
    print("aux rapports JSON : suivi de date/heure et détection de version.")
    print()
    
    # 1. Détection de version
    demo_version_detection()
    
    # 2. Suivi de date/heure
    reports = demo_date_time_tracking()
    
    # 3. Versions dans les rapports
    demo_version_in_reports(reports)
    
    # 4. Session complète
    demo_session_with_version_date()
    
    print("\n🎉 DÉMONSTRATION TERMINÉE")
    print("=" * 30)
    print("✅ Nouvelles fonctionnalités ajoutées :")
    print("  📅 Date et heure détaillées dans chaque rapport")
    print("  📱 Version de l'application détectée automatiquement")
    print("  🖥️ Versions des logiciels (PHP, MySQL, Apache, etc.)")
    print("  📝 Informations Git (commit, branche)")
    print("  🌐 Statut du serveur web")
    print()
    print("📁 Tous les rapports sont disponibles dans:")
    print("  - Rapports individuels: tests/reports/individual/")
    print("  - Rapports agrégés: tests/reports/aggregated/")

if __name__ == '__main__':
    main()
