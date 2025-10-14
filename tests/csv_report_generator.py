#!/usr/bin/env python3
"""
Générateur de rapports CSV pour les tests JDR 4 MJ
"""

import csv
import json
import os
import sys
import subprocess
import re
from datetime import datetime
from pathlib import Path
from typing import List, Dict, Any, Optional

class CSVReportGenerator:
    """Générateur de rapports CSV pour les tests"""
    
    def __init__(self, output_dir: str = "reports"):
        """Initialise le générateur de rapports"""
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(exist_ok=True)
        self.timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        
    def parse_pytest_output(self, output: str) -> List[Dict[str, Any]]:
        """Parse la sortie de pytest pour extraire les informations des tests"""
        tests = []
        lines = output.split('\n')
        
        current_test = None
        in_failure_section = False
        
        for line in lines:
            line = line.strip()
            
            # Détection du début d'un test
            if line.startswith('tests/') and ('::' in line or '.py::' in line):
                # Format: tests/functional/test_file.py::test_function PASSED
                # ou: tests/functional/test_file.py::TestClass::test_method FAILED
                parts = line.split()
                if len(parts) >= 2:
                    test_path = parts[0]
                    status = parts[1]
                    
                    # Extraire le nom du test
                    if '::' in test_path:
                        file_path, test_name = test_path.split('::', 1)
                    else:
                        file_path = test_path
                        test_name = "unknown"
                    
                    current_test = {
                        'file_path': file_path,
                        'test_name': test_name,
                        'status': status,
                        'error_line': '',
                        'error_message': '',
                        'duration': '',
                        'timestamp': datetime.now().isoformat()
                    }
                    tests.append(current_test)
                    in_failure_section = False
            
            # Détection de la durée
            elif current_test and line.startswith('[') and ']' in line:
                # Format: [0.12s] ou [ 12.34s]
                duration_match = re.search(r'\[([\d.]+\s*s?)\]', line)
                if duration_match:
                    current_test['duration'] = duration_match.group(1)
            
            # Détection des erreurs et échecs
            elif current_test and (line.startswith('FAILED') or line.startswith('ERROR')):
                in_failure_section = True
                current_test['status'] = 'FAILED'
            
            # Capture des messages d'erreur
            elif current_test and in_failure_section:
                if line.startswith('E   ') or line.startswith('FAILED'):
                    # Ligne d'erreur
                    error_line = line.replace('E   ', '').replace('FAILED ', '')
                    if not current_test['error_message']:
                        current_test['error_message'] = error_line
                    else:
                        current_test['error_message'] += f" | {error_line}"
                
                # Recherche de numéros de ligne dans les erreurs
                line_match = re.search(r'line (\d+)', line)
                if line_match and not current_test['error_line']:
                    current_test['error_line'] = line_match.group(1)
        
        return tests
    
    def run_pytest_with_json(self, test_path: str = ".", extra_args: List[str] = None) -> List[Dict[str, Any]]:
        """Exécute pytest avec sortie JSON pour une meilleure capture"""
        if extra_args is None:
            extra_args = []
        
        # Commande pytest avec sortie JSON
        cmd = [
            "python3", "-m", "pytest",
            test_path,
            "--tb=short",  # Traceback court
            "-v",  # Mode verbeux
            "--durations=10",  # Afficher les 10 tests les plus lents
        ] + extra_args
        
        try:
            print(f"🔍 Exécution de: {' '.join(cmd)}")
            result = subprocess.run(cmd, capture_output=True, text=True, cwd=Path(__file__).parent)
            
            # Parser la sortie standard
            tests = self.parse_pytest_output(result.stdout)
            
            # Ajouter les informations d'erreur de stderr
            if result.stderr:
                stderr_lines = result.stderr.split('\n')
                for test in tests:
                    if test['status'] == 'FAILED' and not test['error_message']:
                        # Chercher des erreurs dans stderr
                        for line in stderr_lines:
                            if 'FAILED' in line or 'ERROR' in line:
                                test['error_message'] = line.strip()
                                break
            
            return tests
            
        except Exception as e:
            print(f"❌ Erreur lors de l'exécution de pytest: {e}")
            return []
    
    def generate_csv_report(self, tests: List[Dict[str, Any]], filename: str = None) -> str:
        """Génère un rapport CSV à partir des résultats de tests"""
        if filename is None:
            filename = f"test_report_{self.timestamp}.csv"
        
        csv_path = self.output_dir / filename
        
        # En-têtes CSV
        headers = [
            'Timestamp',
            'Fichier_Test',
            'Nom_Test',
            'Statut',
            'Duree',
            'Ligne_Erreur',
            'Message_Erreur',
            'Categorie',
            'Priorite'
        ]
        
        try:
            with open(csv_path, 'w', newline='', encoding='utf-8') as csvfile:
                writer = csv.DictWriter(csvfile, fieldnames=headers, delimiter=';')
                writer.writeheader()
                
                for test in tests:
                    # Déterminer la catégorie basée sur le chemin du fichier
                    category = self._determine_category(test['file_path'])
                    
                    # Déterminer la priorité basée sur le statut
                    priority = self._determine_priority(test['status'], test['error_message'])
                    
                    row = {
                        'Timestamp': test.get('timestamp', ''),
                        'Fichier_Test': test.get('file_path', ''),
                        'Nom_Test': test.get('test_name', ''),
                        'Statut': test.get('status', ''),
                        'Duree': test.get('duration', ''),
                        'Ligne_Erreur': test.get('error_line', ''),
                        'Message_Erreur': test.get('error_message', ''),
                        'Categorie': category,
                        'Priorite': priority
                    }
                    writer.writerow(row)
            
            print(f"📊 Rapport CSV généré: {csv_path}")
            return str(csv_path)
            
        except Exception as e:
            print(f"❌ Erreur lors de la génération du rapport CSV: {e}")
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
        elif 'smoke' in file_path or 'smoke' in file_path:
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
    
    def generate_summary_report(self, tests: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Génère un résumé des résultats de tests"""
        total_tests = len(tests)
        passed_tests = len([t for t in tests if t['status'] == 'PASSED'])
        failed_tests = len([t for t in tests if t['status'] == 'FAILED'])
        error_tests = len([t for t in tests if t['status'] == 'ERROR'])
        
        # Statistiques par catégorie
        categories = {}
        for test in tests:
            category = self._determine_category(test['file_path'])
            if category not in categories:
                categories[category] = {'total': 0, 'passed': 0, 'failed': 0, 'error': 0}
            
            categories[category]['total'] += 1
            if test['status'] == 'PASSED':
                categories[category]['passed'] += 1
            elif test['status'] == 'FAILED':
                categories[category]['failed'] += 1
            elif test['status'] == 'ERROR':
                categories[category]['error'] += 1
        
        return {
            'timestamp': datetime.now().isoformat(),
            'total_tests': total_tests,
            'passed_tests': passed_tests,
            'failed_tests': failed_tests,
            'error_tests': error_tests,
            'success_rate': (passed_tests / total_tests * 100) if total_tests > 0 else 0,
            'categories': categories
        }
    
    def save_summary_json(self, summary: Dict[str, Any], filename: str = None) -> str:
        """Sauvegarde le résumé en format JSON"""
        if filename is None:
            filename = f"test_summary_{self.timestamp}.json"
        
        json_path = self.output_dir / filename
        
        try:
            with open(json_path, 'w', encoding='utf-8') as jsonfile:
                json.dump(summary, jsonfile, indent=2, ensure_ascii=False)
            
            print(f"📋 Résumé JSON généré: {json_path}")
            return str(json_path)
            
        except Exception as e:
            print(f"❌ Erreur lors de la génération du résumé JSON: {e}")
            return ""

def main():
    """Fonction principale pour tester le générateur"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Générateur de rapports CSV pour les tests')
    parser.add_argument('--test-path', default='functional/', 
                       help='Chemin vers les tests à exécuter')
    parser.add_argument('--output-dir', default='reports',
                       help='Répertoire de sortie pour les rapports')
    parser.add_argument('--extra-args', nargs='*', default=[],
                       help='Arguments supplémentaires pour pytest')
    
    args = parser.parse_args()
    
    print("🎲 Générateur de Rapports CSV - JDR 4 MJ")
    print("=" * 50)
    
    # Créer le générateur
    generator = CSVReportGenerator(args.output_dir)
    
    # Exécuter les tests et capturer les résultats
    print(f"🧪 Exécution des tests dans: {args.test_path}")
    tests = generator.run_pytest_with_json(args.test_path, args.extra_args)
    
    if not tests:
        print("❌ Aucun test trouvé ou erreur lors de l'exécution")
        return 1
    
    print(f"📊 {len(tests)} tests trouvés")
    
    # Générer le rapport CSV
    csv_file = generator.generate_csv_report(tests)
    
    # Générer le résumé
    summary = generator.generate_summary_report(tests)
    json_file = generator.save_summary_json(summary)
    
    # Afficher le résumé
    print("\n📋 RÉSUMÉ DES TESTS")
    print("=" * 30)
    print(f"Total des tests: {summary['total_tests']}")
    print(f"Tests réussis: {summary['passed_tests']}")
    print(f"Tests échoués: {summary['failed_tests']}")
    print(f"Tests en erreur: {summary['error_tests']}")
    print(f"Taux de réussite: {summary['success_rate']:.1f}%")
    
    print(f"\n📁 Fichiers générés:")
    print(f"  - Rapport CSV: {csv_file}")
    print(f"  - Résumé JSON: {json_file}")
    
    return 0

if __name__ == '__main__':
    sys.exit(main())
