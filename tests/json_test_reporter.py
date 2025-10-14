#!/usr/bin/env python3
"""
Système de rapports JSON individuels pour les tests JDR 4 MJ
Chaque test génère son propre rapport JSON
"""

import json
import os
import sys
import time
import traceback
from datetime import datetime
from pathlib import Path
from typing import Dict, Any, Optional, List

# Importer le détecteur de version
try:
    from version_detector import VersionDetector
    VERSION_DETECTOR_AVAILABLE = True
except ImportError:
    VERSION_DETECTOR_AVAILABLE = False

class JSONTestReporter:
    """Générateur de rapports JSON individuels pour chaque test"""
    
    def __init__(self, reports_dir: str = "reports", base_url: str = "http://localhost/jdrmj"):
        """Initialise le reporter JSON"""
        self.reports_dir = Path(reports_dir)
        self.reports_dir.mkdir(exist_ok=True)
        self.base_url = base_url
        
        # Répertoires pour organiser les rapports
        self.individual_reports_dir = self.reports_dir / "individual"
        self.individual_reports_dir.mkdir(exist_ok=True)
        
        self.aggregated_reports_dir = self.reports_dir / "aggregated"
        self.aggregated_reports_dir.mkdir(exist_ok=True)
        
        # Initialiser le détecteur de version
        self.version_detector = None
        if VERSION_DETECTOR_AVAILABLE:
            try:
                self.version_detector = VersionDetector()
            except Exception as e:
                print(f"⚠️ Erreur lors de l'initialisation du détecteur de version: {e}")
    
    def create_test_report(self, 
                          test_name: str,
                          test_file: str,
                          status: str,
                          start_time: float,
                          end_time: float,
                          error_message: str = "",
                          error_line: str = "",
                          category: str = "",
                          priority: str = "",
                          additional_data: Dict[str, Any] = None) -> str:
        """Crée un rapport JSON pour un test individuel"""
        
        duration = end_time - start_time
        current_time = datetime.now()
        
        # Détecter les informations de version
        version_info = {}
        if self.version_detector:
            try:
                version_info = self.version_detector.get_complete_version_info(self.base_url)
            except Exception as e:
                print(f"⚠️ Erreur lors de la détection de version: {e}")
                version_info = {"error": str(e)}
        
        # Données du rapport
        report_data = {
            "test_info": {
                "name": test_name,
                "file": test_file,
                "category": category or self._determine_category(test_file),
                "priority": priority or self._determine_priority(status, error_message),
                "timestamp": current_time.isoformat(),
                "date": current_time.strftime("%Y-%m-%d"),
                "time": current_time.strftime("%H:%M:%S"),
                "timezone": current_time.strftime("%Z"),
                "duration_seconds": round(duration, 3)
            },
            "result": {
                "status": status,
                "success": status == "PASSED",
                "error_message": error_message,
                "error_line": error_line,
                "stack_trace": traceback.format_exc() if error_message else ""
            },
            "execution": {
                "start_time": datetime.fromtimestamp(start_time).isoformat(),
                "end_time": datetime.fromtimestamp(end_time).isoformat(),
                "start_date": datetime.fromtimestamp(start_time).strftime("%Y-%m-%d"),
                "start_time_formatted": datetime.fromtimestamp(start_time).strftime("%H:%M:%S"),
                "end_date": datetime.fromtimestamp(end_time).strftime("%Y-%m-%d"),
                "end_time_formatted": datetime.fromtimestamp(end_time).strftime("%H:%M:%S"),
                "duration_formatted": self._format_duration(duration)
            },
            "environment": {
                "python_version": sys.version,
                "platform": sys.platform,
                "working_directory": str(Path.cwd())
            },
            "version_info": version_info,
            "additional_data": additional_data or {}
        }
        
        # Nom du fichier de rapport
        safe_test_name = self._sanitize_filename(test_name)
        report_filename = f"{safe_test_name}.json"
        report_path = self.individual_reports_dir / report_filename
        
        # Sauvegarder le rapport
        try:
            with open(report_path, 'w', encoding='utf-8') as f:
                json.dump(report_data, f, indent=2, ensure_ascii=False)
            
            print(f"📄 Rapport JSON créé: {report_path}")
            return str(report_path)
            
        except Exception as e:
            print(f"❌ Erreur lors de la création du rapport JSON: {e}")
            return ""
    
    def create_test_session_report(self, 
                                 session_name: str,
                                 test_reports: List[str],
                                 start_time: float,
                                 end_time: float) -> str:
        """Crée un rapport de session agrégé à partir des rapports individuels"""
        
        session_duration = end_time - start_time
        
        # Lire tous les rapports individuels
        individual_reports = []
        total_tests = 0
        passed_tests = 0
        failed_tests = 0
        error_tests = 0
        
        for report_path in test_reports:
            try:
                with open(report_path, 'r', encoding='utf-8') as f:
                    report_data = json.load(f)
                    individual_reports.append(report_data)
                    
                    total_tests += 1
                    if report_data['result']['status'] == 'PASSED':
                        passed_tests += 1
                    elif report_data['result']['status'] == 'FAILED':
                        failed_tests += 1
                    else:
                        error_tests += 1
                        
            except Exception as e:
                print(f"⚠️ Erreur lors de la lecture du rapport {report_path}: {e}")
        
        # Statistiques par catégorie
        categories = {}
        for report in individual_reports:
            category = report['test_info']['category']
            if category not in categories:
                categories[category] = {'total': 0, 'passed': 0, 'failed': 0, 'error': 0}
            
            categories[category]['total'] += 1
            status = report['result']['status']
            if status == 'PASSED':
                categories[category]['passed'] += 1
            elif status == 'FAILED':
                categories[category]['failed'] += 1
            else:
                categories[category]['error'] += 1
        
        # Détecter les informations de version pour la session
        session_version_info = {}
        if self.version_detector:
            try:
                session_version_info = self.version_detector.get_complete_version_info(self.base_url)
            except Exception as e:
                session_version_info = {"error": str(e)}
        
        current_time = datetime.now()
        
        # Données du rapport de session
        session_data = {
            "session_info": {
                "name": session_name,
                "timestamp": current_time.isoformat(),
                "date": current_time.strftime("%Y-%m-%d"),
                "time": current_time.strftime("%H:%M:%S"),
                "timezone": current_time.strftime("%Z"),
                "start_time": datetime.fromtimestamp(start_time).isoformat(),
                "end_time": datetime.fromtimestamp(end_time).isoformat(),
                "start_date": datetime.fromtimestamp(start_time).strftime("%Y-%m-%d"),
                "start_time_formatted": datetime.fromtimestamp(start_time).strftime("%H:%M:%S"),
                "end_date": datetime.fromtimestamp(end_time).strftime("%Y-%m-%d"),
                "end_time_formatted": datetime.fromtimestamp(end_time).strftime("%H:%M:%S"),
                "duration_seconds": round(session_duration, 3),
                "duration_formatted": self._format_duration(session_duration)
            },
            "summary": {
                "total_tests": total_tests,
                "passed_tests": passed_tests,
                "failed_tests": failed_tests,
                "error_tests": error_tests,
                "success_rate": round((passed_tests / total_tests * 100), 2) if total_tests > 0 else 0
            },
            "categories": categories,
            "individual_reports": [str(Path(r).name) for r in test_reports],
            "environment": {
                "python_version": sys.version,
                "platform": sys.platform,
                "working_directory": str(Path.cwd())
            },
            "version_info": session_version_info
        }
        
        # Nom du fichier de rapport de session
        safe_session_name = self._sanitize_filename(session_name)
        session_filename = f"session_{safe_session_name}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
        session_path = self.aggregated_reports_dir / session_filename
        
        # Sauvegarder le rapport de session
        try:
            with open(session_path, 'w', encoding='utf-8') as f:
                json.dump(session_data, f, indent=2, ensure_ascii=False)
            
            print(f"📊 Rapport de session créé: {session_path}")
            return str(session_path)
            
        except Exception as e:
            print(f"❌ Erreur lors de la création du rapport de session: {e}")
            return ""
    
    def _determine_category(self, file_path: str) -> str:
        """Détermine la catégorie du test basée sur le chemin du fichier"""
        if 'authentication' in file_path:
            return 'Authentification'
        elif 'character' in file_path:
            return 'Gestion_Personnages'
        elif 'campaign' in file_path:
            return 'Gestion_Campagnes'
        elif 'bestiary' in file_path:
            return 'Bestiaire'
        elif 'dm_user' in file_path:
            return 'Utilisateurs_MJ'
        elif 'integration' in file_path:
            return 'Integration'
        elif 'smoke' in file_path:
            return 'Tests_Fumee'
        else:
            return 'Autres'
    
    def _determine_priority(self, status: str, error_message: str) -> str:
        """Détermine la priorité basée sur le statut et le message d'erreur"""
        if status == 'FAILED':
            if 'timeout' in error_message.lower():
                return 'Haute'
            elif 'selenium' in error_message.lower():
                return 'Moyenne'
            elif 'database' in error_message.lower():
                return 'Haute'
            else:
                return 'Moyenne'
        elif status == 'PASSED':
            return 'Basse'
        else:
            return 'Inconnue'
    
    def _sanitize_filename(self, filename: str) -> str:
        """Nettoie un nom de fichier pour qu'il soit valide"""
        # Remplacer les caractères non valides
        invalid_chars = '<>:"/\\|?*'
        for char in invalid_chars:
            filename = filename.replace(char, '_')
        
        # Limiter la longueur
        if len(filename) > 100:
            filename = filename[:100]
        
        return filename
    
    def _format_duration(self, duration: float) -> str:
        """Formate une durée en secondes en format lisible"""
        if duration < 1:
            return f"{duration*1000:.0f}ms"
        elif duration < 60:
            return f"{duration:.2f}s"
        else:
            minutes = int(duration // 60)
            seconds = duration % 60
            return f"{minutes}m {seconds:.2f}s"
    
    def list_reports(self, report_type: str = "individual") -> List[str]:
        """Liste tous les rapports disponibles"""
        if report_type == "individual":
            reports_dir = self.individual_reports_dir
        elif report_type == "aggregated":
            reports_dir = self.aggregated_reports_dir
        else:
            return []
        
        try:
            return [str(f) for f in reports_dir.glob("*.json")]
        except Exception as e:
            print(f"❌ Erreur lors de la liste des rapports: {e}")
            return []
    
    def read_report(self, report_path: str) -> Optional[Dict[str, Any]]:
        """Lit un rapport JSON"""
        try:
            with open(report_path, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            print(f"❌ Erreur lors de la lecture du rapport {report_path}: {e}")
            return None
    
    def generate_summary_report(self, session_name: str = None) -> str:
        """Génère un rapport de résumé de tous les tests récents"""
        individual_reports = self.list_reports("individual")
        
        if not individual_reports:
            print("❌ Aucun rapport individuel trouvé")
            return ""
        
        # Trier par date de modification (plus récents en premier)
        individual_reports.sort(key=lambda x: os.path.getmtime(x), reverse=True)
        
        # Lire les rapports récents (dernières 24h)
        recent_reports = []
        current_time = time.time()
        one_day_ago = current_time - (24 * 60 * 60)
        
        for report_path in individual_reports:
            if os.path.getmtime(report_path) > one_day_ago:
                report_data = self.read_report(report_path)
                if report_data:
                    recent_reports.append(report_data)
        
        if not recent_reports:
            print("❌ Aucun rapport récent trouvé")
            return ""
        
        # Calculer les statistiques
        total_tests = len(recent_reports)
        passed_tests = len([r for r in recent_reports if r['result']['status'] == 'PASSED'])
        failed_tests = len([r for r in recent_reports if r['result']['status'] == 'FAILED'])
        error_tests = len([r for r in recent_reports if r['result']['status'] not in ['PASSED', 'FAILED']])
        
        # Statistiques par catégorie
        categories = {}
        for report in recent_reports:
            category = report['test_info']['category']
            if category not in categories:
                categories[category] = {'total': 0, 'passed': 0, 'failed': 0, 'error': 0}
            
            categories[category]['total'] += 1
            status = report['result']['status']
            if status == 'PASSED':
                categories[category]['passed'] += 1
            elif status == 'FAILED':
                categories[category]['failed'] += 1
            else:
                categories[category]['error'] += 1
        
        # Détecter les informations de version pour le résumé
        summary_version_info = {}
        if self.version_detector:
            try:
                summary_version_info = self.version_detector.get_complete_version_info(self.base_url)
            except Exception as e:
                summary_version_info = {"error": str(e)}
        
        current_time = datetime.now()
        
        # Données du rapport de résumé
        summary_data = {
            "summary_info": {
                "name": session_name or "Résumé des tests récents",
                "timestamp": current_time.isoformat(),
                "date": current_time.strftime("%Y-%m-%d"),
                "time": current_time.strftime("%H:%M:%S"),
                "timezone": current_time.strftime("%Z"),
                "period": "24 dernières heures",
                "total_reports_analyzed": total_tests
            },
            "statistics": {
                "total_tests": total_tests,
                "passed_tests": passed_tests,
                "failed_tests": failed_tests,
                "error_tests": error_tests,
                "success_rate": round((passed_tests / total_tests * 100), 2) if total_tests > 0 else 0
            },
            "categories": categories,
            "recent_tests": [
                {
                    "name": r['test_info']['name'],
                    "file": r['test_info']['file'],
                    "status": r['result']['status'],
                    "duration": r['test_info']['duration_seconds'],
                    "timestamp": r['test_info']['timestamp'],
                    "date": r['test_info'].get('date', ''),
                    "time": r['test_info'].get('time', '')
                }
                for r in recent_reports[:10]  # 10 tests les plus récents
            ],
            "version_info": summary_version_info
        }
        
        # Nom du fichier de rapport de résumé
        summary_filename = f"summary_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
        summary_path = self.aggregated_reports_dir / summary_filename
        
        # Sauvegarder le rapport de résumé
        try:
            with open(summary_path, 'w', encoding='utf-8') as f:
                json.dump(summary_data, f, indent=2, ensure_ascii=False)
            
            print(f"📋 Rapport de résumé créé: {summary_path}")
            return str(summary_path)
            
        except Exception as e:
            print(f"❌ Erreur lors de la création du rapport de résumé: {e}")
            return ""

def main():
    """Fonction principale pour tester le reporter JSON"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Système de rapports JSON pour les tests')
    parser.add_argument('--action', choices=['create', 'list', 'summary'], default='create',
                       help='Action à effectuer')
    parser.add_argument('--test-name', default='test_example',
                       help='Nom du test')
    parser.add_argument('--test-file', default='test_example.py',
                       help='Fichier de test')
    parser.add_argument('--status', choices=['PASSED', 'FAILED', 'ERROR'], default='PASSED',
                       help='Statut du test')
    parser.add_argument('--reports-dir', default='reports',
                       help='Répertoire des rapports')
    
    args = parser.parse_args()
    
    print("🎲 Système de Rapports JSON - JDR 4 MJ")
    print("=" * 50)
    
    # Créer le reporter
    reporter = JSONTestReporter(args.reports_dir)
    
    if args.action == 'create':
        # Créer un rapport de test exemple
        start_time = time.time()
        time.sleep(0.1)  # Simuler une durée de test
        end_time = time.time()
        
        report_path = reporter.create_test_report(
            test_name=args.test_name,
            test_file=args.test_file,
            status=args.status,
            start_time=start_time,
            end_time=end_time,
            error_message="Erreur de test simulée" if args.status != 'PASSED' else "",
            error_line="42" if args.status != 'PASSED' else ""
        )
        
        if report_path:
            print(f"✅ Rapport créé: {report_path}")
        
    elif args.action == 'list':
        # Lister les rapports
        individual_reports = reporter.list_reports("individual")
        aggregated_reports = reporter.list_reports("aggregated")
        
        print(f"📄 Rapports individuels ({len(individual_reports)}):")
        for report in individual_reports[:5]:  # Afficher les 5 premiers
            print(f"  - {Path(report).name}")
        
        print(f"\n📊 Rapports agrégés ({len(aggregated_reports)}):")
        for report in aggregated_reports[:5]:  # Afficher les 5 premiers
            print(f"  - {Path(report).name}")
    
    elif args.action == 'summary':
        # Générer un rapport de résumé
        summary_path = reporter.generate_summary_report("Test de résumé")
        if summary_path:
            print(f"✅ Rapport de résumé créé: {summary_path}")
    
    return 0

if __name__ == '__main__':
    sys.exit(main())
