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
        self._created_reports = set()  # Pour éviter les doublons
        
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
        """Après l'exécution d'un test - ne plus utilisé pour créer des rapports"""
        pass
    
    def pytest_runtest_logreport(self, report):
        """Log des résultats de test"""
        if report.when == 'call' and self.reporter and report.nodeid not in self._created_reports:
            # Marquer immédiatement pour éviter les doublons
            self._created_reports.add(report.nodeid)
            
            # Déterminer le statut du test
            if report.skipped:
                status = "SKIPPED"
                error_message = str(report.longrepr) if hasattr(report, 'longrepr') and report.longrepr else ""
            elif report.failed:
                status = "FAILED"
                error_message = str(report.longrepr) if hasattr(report, 'longrepr') and report.longrepr else ""
            else:
                status = "PASSED"
                error_message = ""
            
            # Créer le rapport JSON
            test_name = report.nodeid.split("::")[-1] if "::" in report.nodeid else report.nodeid
            test_file = str(report.fspath) if hasattr(report, 'fspath') else ""
            start_time = getattr(report, 'start', time.time() - 1)
            end_time = getattr(report, 'stop', time.time())
            
            report_path = self.reporter.create_test_report(
                test_name=test_name,
                test_file=test_file,
                status=status,
                start_time=start_time,
                end_time=end_time,
                error_message=error_message,
                error_line=""
            )
            
            # Le message est déjà affiché par json_test_reporter.py
    

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
