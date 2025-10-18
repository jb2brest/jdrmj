#!/usr/bin/env python3
"""
Capteur d'étapes de tests pour le système de rapports JSON
Permet de capturer les étapes détaillées d'un test pour l'affichage dans admin_versions.php
"""

import time
from datetime import datetime
from typing import List, Dict, Any, Optional
from contextlib import contextmanager

class TestStepsCapturer:
    """Classe pour capturer les étapes détaillées d'un test"""
    
    def __init__(self):
        self.steps: List[Dict[str, Any]] = []
        self.current_step: Optional[Dict[str, Any]] = None
        self.start_time: Optional[float] = None
        self.end_time: Optional[float] = None
    
    def start_test(self, test_name: str, test_description: str = ""):
        """Démarre la capture d'un test"""
        self.steps = []
        self.current_step = None
        self.start_time = time.time()
        
        self.add_step("Début du test", f"Test: {test_name}", "info")
        if test_description:
            self.add_step("Description", test_description, "info")
    
    def end_test(self, status: str = "completed"):
        """Termine la capture d'un test"""
        self.end_time = time.time()
        self.add_step("Fin du test", f"Statut: {status}", "info")
    
    def add_step(self, step_name: str, description: str, step_type: str = "action", 
                 details: Dict[str, Any] = None, screenshot_path: str = None):
        """Ajoute une étape au test"""
        current_time = time.time()
        step_duration = 0
        
        if self.current_step:
            step_duration = current_time - self.current_step.get('timestamp', current_time)
        
        step = {
            "step_number": len(self.steps) + 1,
            "name": step_name,
            "description": description,
            "type": step_type,  # action, assertion, info, error, warning
            "timestamp": current_time,
            "datetime": datetime.fromtimestamp(current_time).isoformat(),
            "duration_seconds": step_duration,
            "details": details or {},
            "screenshot_path": screenshot_path
        }
        
        self.steps.append(step)
        self.current_step = step
        
        return step
    
    def add_action(self, action_name: str, description: str, details: Dict[str, Any] = None):
        """Ajoute une action (clic, saisie, navigation, etc.)"""
        return self.add_step(action_name, description, "action", details)
    
    def add_assertion(self, assertion_name: str, description: str, 
                     expected: Any = None, actual: Any = None, passed: bool = True):
        """Ajoute une assertion (vérification)"""
        details = {
            "expected": expected,
            "actual": actual,
            "passed": passed
        }
        step_type = "assertion" if passed else "error"
        return self.add_step(assertion_name, description, step_type, details)
    
    def add_info(self, info_name: str, description: str, details: Dict[str, Any] = None):
        """Ajoute une information"""
        return self.add_step(info_name, description, "info", details)
    
    def add_error(self, error_name: str, description: str, error_details: Dict[str, Any] = None):
        """Ajoute une erreur"""
        return self.add_step(error_name, description, "error", error_details)
    
    def add_warning(self, warning_name: str, description: str, warning_details: Dict[str, Any] = None):
        """Ajoute un avertissement"""
        return self.add_step(warning_name, description, "warning", warning_details)
    
    def add_screenshot(self, screenshot_path: str, description: str = "Capture d'écran"):
        """Ajoute une capture d'écran"""
        return self.add_step("Capture d'écran", description, "screenshot", 
                           {"screenshot_path": screenshot_path}, screenshot_path)
    
    @contextmanager
    def step_context(self, step_name: str, description: str, step_type: str = "action"):
        """Contexte pour une étape avec gestion automatique de la durée"""
        start_time = time.time()
        step = self.add_step(step_name, description, step_type)
        
        try:
            yield step
        except Exception as e:
            self.add_error(f"Erreur dans {step_name}", str(e), {"exception": str(e)})
            raise
        finally:
            # Mettre à jour la durée de l'étape
            end_time = time.time()
            step["duration_seconds"] = end_time - start_time
    
    def get_steps(self) -> List[Dict[str, Any]]:
        """Retourne toutes les étapes capturées"""
        return self.steps.copy()
    
    def get_summary(self) -> Dict[str, Any]:
        """Retourne un résumé des étapes"""
        if not self.steps:
            return {}
        
        total_duration = (self.end_time or time.time()) - (self.start_time or 0)
        
        step_types = {}
        for step in self.steps:
            step_type = step["type"]
            if step_type not in step_types:
                step_types[step_type] = 0
            step_types[step_type] += 1
        
        return {
            "total_steps": len(self.steps),
            "total_duration_seconds": total_duration,
            "step_types": step_types,
            "has_errors": any(step["type"] == "error" for step in self.steps),
            "has_warnings": any(step["type"] == "warning" for step in self.steps),
            "has_screenshots": any(step.get("screenshot_path") for step in self.steps)
        }
    
    def format_steps_for_display(self) -> List[Dict[str, Any]]:
        """Formate les étapes pour l'affichage dans l'interface web"""
        formatted_steps = []
        
        for step in self.steps:
            formatted_step = {
                "step_number": step["step_number"],
                "name": step["name"],
                "description": step["description"],
                "type": step["type"],
                "datetime": step["datetime"],
                "duration_formatted": self._format_duration(step["duration_seconds"]),
                "details": step["details"],
                "screenshot_path": step.get("screenshot_path")
            }
            
            # Ajouter des classes CSS pour le type d'étape
            css_classes = {
                "action": "text-primary",
                "assertion": "text-success",
                "info": "text-info",
                "error": "text-danger",
                "warning": "text-warning",
                "screenshot": "text-secondary"
            }
            formatted_step["css_class"] = css_classes.get(step["type"], "text-muted")
            
            # Ajouter des icônes pour le type d'étape
            icons = {
                "action": "fas fa-play",
                "assertion": "fas fa-check",
                "info": "fas fa-info-circle",
                "error": "fas fa-times",
                "warning": "fas fa-exclamation-triangle",
                "screenshot": "fas fa-camera"
            }
            formatted_step["icon"] = icons.get(step["type"], "fas fa-circle")
            
            formatted_steps.append(formatted_step)
        
        return formatted_steps
    
    def _format_duration(self, duration: float) -> str:
        """Formate une durée en format lisible"""
        if duration < 0.001:
            return "< 1ms"
        elif duration < 1:
            return f"{duration*1000:.0f}ms"
        elif duration < 60:
            return f"{duration:.2f}s"
        else:
            minutes = int(duration // 60)
            seconds = duration % 60
            return f"{minutes}m {seconds:.2f}s"
    
    def export_to_dict(self) -> Dict[str, Any]:
        """Exporte les étapes au format dictionnaire pour le rapport JSON"""
        return {
            "steps": self.get_steps(),
            "summary": self.get_summary(),
            "formatted_steps": self.format_steps_for_display()
        }

# Instance globale pour les tests
_global_capturer = TestStepsCapturer()

def get_test_capturer() -> TestStepsCapturer:
    """Retourne l'instance globale du capteur de tests"""
    return _global_capturer

def reset_test_capturer():
    """Remet à zéro le capteur de tests"""
    global _global_capturer
    _global_capturer = TestStepsCapturer()

# Fonctions utilitaires pour faciliter l'utilisation
def start_test(test_name: str, test_description: str = ""):
    """Démarre la capture d'un test"""
    _global_capturer.start_test(test_name, test_description)

def end_test(status: str = "completed"):
    """Termine la capture d'un test"""
    _global_capturer.end_test(status)

def add_step(step_name: str, description: str, step_type: str = "action", 
             details: Dict[str, Any] = None, screenshot_path: str = None):
    """Ajoute une étape au test"""
    return _global_capturer.add_step(step_name, description, step_type, details, screenshot_path)

def add_action(action_name: str, description: str, details: Dict[str, Any] = None):
    """Ajoute une action"""
    return _global_capturer.add_action(action_name, description, details)

def add_assertion(assertion_name: str, description: str, 
                 expected: Any = None, actual: Any = None, passed: bool = True):
    """Ajoute une assertion"""
    return _global_capturer.add_assertion(assertion_name, description, expected, actual, passed)

def add_info(info_name: str, description: str, details: Dict[str, Any] = None):
    """Ajoute une information"""
    return _global_capturer.add_info(info_name, description, details)

def add_error(error_name: str, description: str, error_details: Dict[str, Any] = None):
    """Ajoute une erreur"""
    return _global_capturer.add_error(error_name, description, error_details)

def add_warning(warning_name: str, description: str, warning_details: Dict[str, Any] = None):
    """Ajoute un avertissement"""
    return _global_capturer.add_warning(warning_name, description, warning_details)

def add_screenshot(screenshot_path: str, description: str = "Capture d'écran"):
    """Ajoute une capture d'écran"""
    return _global_capturer.add_screenshot(screenshot_path, description)

@contextmanager
def step_context(step_name: str, description: str, step_type: str = "action"):
    """Contexte pour une étape"""
    with _global_capturer.step_context(step_name, description, step_type) as step:
        yield step

def get_test_steps() -> List[Dict[str, Any]]:
    """Retourne toutes les étapes capturées"""
    return _global_capturer.get_steps()

def export_test_steps() -> Dict[str, Any]:
    """Exporte les étapes au format dictionnaire"""
    return _global_capturer.export_to_dict()
