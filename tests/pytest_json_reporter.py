#!/usr/bin/env python3
"""
Plugin pytest pour la génération automatique de rapports JSON
"""

import pytest
import time
import sys
import os
from pathlib import Path

# Ajouter le répertoire tests au path pour importer json_test_reporter
sys.path.insert(0, str(Path(__file__).parent))

try:
    from json_test_reporter import JSONTestReporter
    JSON_REPORTER_AVAILABLE = True
except ImportError:
    JSON_REPORTER_AVAILABLE = False

class JSONReporterPlugin:
    """Plugin pytest pour générer des rapports JSON automatiquement"""
    
    def __init__(self):
        self.reporter = None
        self._skipped_tests = set()  # Pour tracker les tests ignorés déjà traités
        
        if JSON_REPORTER_AVAILABLE:
            # Récupérer l'URL de base depuis les variables d'environnement
            base_url = os.getenv("TEST_BASE_URL", "http://localhost/jdrmj")
            self.reporter = JSONTestReporter(base_url=base_url)
    
    def pytest_sessionstart(self, session):
        """Début de session de tests"""
        print("🎲 Début de session de tests avec rapports JSON individuels")
    
    def pytest_sessionfinish(self, session, exitstatus):
        """Fin de session de tests"""
        print("📊 Session de tests terminée - rapports individuels générés")
    
    def pytest_runtest_setup(self, item):
        """Avant l'exécution d'un test"""
        if self.reporter:
            item.start_time = time.time()
    
    def pytest_runtest_teardown(self, item, nextitem):
        """Après l'exécution d'un test"""
        print(f"DEBUG: teardown appelé pour {item.name}")
        if not self.reporter or not hasattr(item, 'start_time'):
            return
        
        # Vérifier si le test a été ignoré - si oui, ne pas créer de rapport ici
        # car il a déjà été créé dans pytest_runtest_logreport
        test_nodeid = f"{item.fspath}::{item.cls.__name__}::{item.name}" if hasattr(item, 'cls') and item.cls else f"{item.fspath}::{item.name}"
        if test_nodeid in self._skipped_tests:
            return
        
        end_time = time.time()
        
        # Récupérer les informations du test
        test_name = item.name
        test_file = str(item.fspath)
        
        # Déterminer le statut du test
        status = "PASSED"  # Par défaut
        error_message = ""
        error_line = ""
        
        # Vérifier le statut du test
        if hasattr(item, 'rep_call'):
            if item.rep_call.failed:
                status = "FAILED"
                if hasattr(item.rep_call, 'longrepr'):
                    error_message = str(item.rep_call.longrepr)
                    # Extraire le numéro de ligne si possible
                    if "line" in error_message:
                        import re
                        line_match = re.search(r'line (\d+)', error_message)
                        if line_match:
                            error_line = line_match.group(1)
        
        # Créer le rapport JSON pour ce test
        report_path = self.reporter.create_test_report(
            test_name=test_name,
            test_file=test_file,
            status=status,
            start_time=item.start_time,
            end_time=end_time,
            error_message=error_message,
            error_line=error_line
        )
        
        # Rapport individuel créé - pas besoin de le stocker pour une session
    
    def pytest_runtest_logreport(self, report):
        """Log des résultats de test"""
        # Debug: afficher tous les rapports (désactivé pour les tests)
        # print(f"DEBUG: logreport - when={report.when}, outcome={report.outcome}, skipped={report.skipped}")
        
        if report.when == 'call':
            # Stocker le rapport pour l'utiliser dans teardown
            if hasattr(report, 'item'):
                report.item.rep_call = report
                
        # Traiter les tests ignorés (peuvent être ignorés à n'importe quel moment)
        if report.skipped and self.reporter:
            self._create_test_report_from_logreport(report)
            # Marquer ce test comme traité pour éviter qu'il soit traité dans teardown
            self._skipped_tests.add(report.nodeid)
        
        # Éviter que la phase teardown crée un rapport pour les tests ignorés
        if report.when == 'teardown' and report.nodeid in self._skipped_tests:
            return
    
    def _create_test_report_from_logreport(self, report):
        """Créer un rapport JSON à partir d'un logreport pytest"""
        # Extraire le nom du test depuis nodeid
        test_name = report.nodeid.split("::")[-1] if "::" in report.nodeid else report.nodeid
        test_file = str(report.fspath) if hasattr(report, 'fspath') else ""
        
        # Déterminer le statut
        status = "SKIPPED"
        error_message = ""
        if hasattr(report, 'longrepr') and report.longrepr:
            error_message = str(report.longrepr)
        
        # Utiliser les timestamps du rapport si disponibles
        start_time = getattr(report, 'start', time.time() - 1)
        end_time = getattr(report, 'stop', time.time())
        
        # Créer le rapport JSON
        report_path = self.reporter.create_test_report(
            test_name=test_name,
            test_file=test_file,
            status=status,
            start_time=start_time,
            end_time=end_time,
            error_message=error_message,
            error_line=""
        )
        
        # Rapport individuel créé - pas besoin de le stocker pour une session

# Instance globale du plugin
json_reporter_plugin = JSONReporterPlugin()

def pytest_configure(config):
    """Configuration pytest"""
    if JSON_REPORTER_AVAILABLE:
        config.pluginmanager.register(json_reporter_plugin, "json_reporter")
        print("✅ Plugin de rapports JSON activé")
    else:
        print("⚠️ Plugin de rapports JSON non disponible (json_test_reporter.py manquant)")

def pytest_unconfigure(config):
    """Nettoyage pytest"""
    if JSON_REPORTER_AVAILABLE:
        config.pluginmanager.unregister(json_reporter_plugin)

# Décorateur pour les tests qui veulent des rapports JSON personnalisés
def json_report(test_name=None, category=None, priority=None):
    """Décorateur pour marquer un test avec des métadonnées JSON"""
    def decorator(func):
        func._json_report_name = test_name or func.__name__
        func._json_report_category = category
        func._json_report_priority = priority
        return func
    return decorator
