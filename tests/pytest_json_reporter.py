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
        self.test_reports = []
        self.session_start_time = None
        
        if JSON_REPORTER_AVAILABLE:
            # Récupérer l'URL de base depuis les variables d'environnement
            base_url = os.getenv("TEST_BASE_URL", "http://localhost/jdrmj")
            self.reporter = JSONTestReporter(base_url=base_url)
    
    def pytest_sessionstart(self, session):
        """Début de session de tests"""
        self.session_start_time = time.time()
        print("🎲 Début de session de tests avec rapports JSON")
    
    def pytest_sessionfinish(self, session, exitstatus):
        """Fin de session de tests"""
        if not self.reporter or not self.session_start_time:
            return
        
        session_end_time = time.time()
        
        # Créer un rapport de session
        session_name = f"pytest_session_{int(session_start_time)}"
        session_report = self.reporter.create_test_session_report(
            session_name=session_name,
            test_reports=self.test_reports,
            start_time=self.session_start_time,
            end_time=session_end_time
        )
        
        if session_report:
            print(f"📊 Rapport de session créé: {session_report}")
    
    def pytest_runtest_setup(self, item):
        """Avant l'exécution d'un test"""
        if self.reporter:
            item.start_time = time.time()
    
    def pytest_runtest_teardown(self, item, nextitem):
        """Après l'exécution d'un test"""
        if not self.reporter or not hasattr(item, 'start_time'):
            return
        
        end_time = time.time()
        
        # Récupérer les informations du test
        test_name = item.name
        test_file = str(item.fspath)
        
        # Déterminer le statut du test
        status = "PASSED"  # Par défaut
        error_message = ""
        error_line = ""
        
        # Vérifier si le test a échoué
        if hasattr(item, 'rep_call') and item.rep_call.failed:
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
        
        if report_path:
            self.test_reports.append(report_path)
    
    def pytest_runtest_logreport(self, report):
        """Log des résultats de test"""
        if report.when == 'call':
            # Stocker le rapport pour l'utiliser dans teardown
            if hasattr(report, 'item'):
                report.item.rep_call = report

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
