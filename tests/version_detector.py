#!/usr/bin/env python3
"""
Détecteur de version pour l'application JDR 4 MJ
"""

import os
import sys
import re
import subprocess
import requests
from pathlib import Path
from datetime import datetime
from typing import Dict, Any, Optional

class VersionDetector:
    """Détecteur de version pour l'application JDR 4 MJ"""
    
    def __init__(self, project_root: str = None):
        """Initialise le détecteur de version"""
        if project_root is None:
            # Remonter depuis le répertoire tests vers la racine du projet
            self.project_root = Path(__file__).parent.parent
        else:
            self.project_root = Path(project_root)
        
        self.version_info = {}
    
    def detect_php_version(self) -> Optional[str]:
        """Détecte la version de PHP"""
        try:
            result = subprocess.run(['php', '--version'], capture_output=True, text=True)
            if result.returncode == 0:
                # Extraire la version de la sortie
                version_match = re.search(r'PHP (\d+\.\d+\.\d+)', result.stdout)
                if version_match:
                    return version_match.group(1)
        except (subprocess.CalledProcessError, FileNotFoundError):
            pass
        return None
    
    def detect_mysql_version(self) -> Optional[str]:
        """Détecte la version de MySQL/MariaDB"""
        try:
            result = subprocess.run(['mysql', '--version'], capture_output=True, text=True)
            if result.returncode == 0:
                # Extraire la version de la sortie
                version_match = re.search(r'(\d+\.\d+\.\d+)', result.stdout)
                if version_match:
                    return version_match.group(1)
        except (subprocess.CalledProcessError, FileNotFoundError):
            pass
        return None
    
    def detect_apache_version(self) -> Optional[str]:
        """Détecte la version d'Apache"""
        try:
            result = subprocess.run(['apache2', '-v'], capture_output=True, text=True)
            if result.returncode == 0:
                # Extraire la version de la sortie
                version_match = re.search(r'Apache/(\d+\.\d+\.\d+)', result.stdout)
                if version_match:
                    return version_match.group(1)
        except (subprocess.CalledProcessError, FileNotFoundError):
            pass
        return None
    
    def detect_application_version(self) -> Optional[str]:
        """Détecte la version de l'application JDR 4 MJ"""
        # Chercher un fichier version.txt ou VERSION
        version_files = ['version.txt', 'VERSION', 'VERSION.txt', 'version']
        
        for version_file in version_files:
            version_path = self.project_root / version_file
            if version_path.exists():
                try:
                    with open(version_path, 'r', encoding='utf-8') as f:
                        content = f.read().strip()
                        if content:
                            # Chercher une ligne VERSION= dans le contenu
                            version_match = re.search(r'VERSION=([^\s\n]+)', content)
                            if version_match:
                                return version_match.group(1)
                            # Sinon retourner la première ligne non commentée
                            lines = content.split('\n')
                            for line in lines:
                                line = line.strip()
                                if line and not line.startswith('#'):
                                    return line
                except Exception:
                    continue
        
        # Chercher dans composer.json
        composer_path = self.project_root / 'composer.json'
        if composer_path.exists():
            try:
                import json
                with open(composer_path, 'r', encoding='utf-8') as f:
                    composer_data = json.load(f)
                    if 'version' in composer_data:
                        return composer_data['version']
            except Exception:
                pass
        
        # Chercher dans package.json
        package_path = self.project_root / 'package.json'
        if package_path.exists():
            try:
                import json
                with open(package_path, 'r', encoding='utf-8') as f:
                    package_data = json.load(f)
                    if 'version' in package_data:
                        return package_data['version']
            except Exception:
                pass
        
        # Chercher dans les fichiers PHP pour une constante VERSION
        php_files = list(self.project_root.glob('**/*.php'))
        for php_file in php_files[:10]:  # Limiter la recherche
            try:
                with open(php_file, 'r', encoding='utf-8') as f:
                    content = f.read()
                    # Chercher des patterns comme define('VERSION', '1.0.0')
                    version_patterns = [
                        r"define\s*\(\s*['\"]VERSION['\"]\s*,\s*['\"]([^'\"]+)['\"]",
                        r"const\s+VERSION\s*=\s*['\"]([^'\"]+)['\"]",
                        r"\$version\s*=\s*['\"]([^'\"]+)['\"]"
                    ]
                    
                    for pattern in version_patterns:
                        match = re.search(pattern, content)
                        if match:
                            return match.group(1)
            except Exception:
                continue
        
        return None
    
    def detect_git_info(self) -> Dict[str, Any]:
        """Détecte les informations Git"""
        git_info = {}
        
        try:
            # Version de Git
            result = subprocess.run(['git', '--version'], capture_output=True, text=True)
            if result.returncode == 0:
                version_match = re.search(r'git version (\d+\.\d+\.\d+)', result.stdout)
                if version_match:
                    git_info['git_version'] = version_match.group(1)
            
            # Commit actuel
            result = subprocess.run(['git', 'rev-parse', 'HEAD'], 
                                  capture_output=True, text=True, cwd=self.project_root)
            if result.returncode == 0:
                git_info['commit_hash'] = result.stdout.strip()[:8]  # 8 premiers caractères
            
            # Branche actuelle
            result = subprocess.run(['git', 'branch', '--show-current'], 
                                  capture_output=True, text=True, cwd=self.project_root)
            if result.returncode == 0:
                git_info['branch'] = result.stdout.strip()
            
            # Dernière modification
            result = subprocess.run(['git', 'log', '-1', '--format=%ci'], 
                                  capture_output=True, text=True, cwd=self.project_root)
            if result.returncode == 0:
                git_info['last_commit_date'] = result.stdout.strip()
                
        except (subprocess.CalledProcessError, FileNotFoundError):
            pass
        
        return git_info
    
    def detect_system_info(self) -> Dict[str, Any]:
        """Détecte les informations système"""
        system_info = {}
        
        try:
            # Version du système d'exploitation
            with open('/etc/os-release', 'r') as f:
                content = f.read()
                name_match = re.search(r'NAME="([^"]+)"', content)
                version_match = re.search(r'VERSION="([^"]+)"', content)
                
                if name_match:
                    system_info['os_name'] = name_match.group(1)
                if version_match:
                    system_info['os_version'] = version_match.group(1)
        except Exception:
            system_info['os_name'] = sys.platform
            system_info['os_version'] = 'Unknown'
        
        # Architecture
        system_info['architecture'] = os.uname().machine if hasattr(os, 'uname') else 'Unknown'
        
        # Version de Python
        system_info['python_version'] = sys.version
        
        return system_info
    
    def detect_web_server_info(self, base_url: str = "http://localhost/jdrmj") -> Dict[str, Any]:
        """Détecte les informations du serveur web"""
        web_info = {}
        
        try:
            # Tester la connectivité
            response = requests.get(base_url, timeout=5)
            web_info['server_status'] = 'online'
            web_info['http_status'] = response.status_code
            
            # Headers du serveur
            if 'Server' in response.headers:
                web_info['server_header'] = response.headers['Server']
            
            if 'X-Powered-By' in response.headers:
                web_info['powered_by'] = response.headers['X-Powered-By']
                
        except requests.RequestException as e:
            web_info['server_status'] = 'offline'
            web_info['error'] = str(e)
        
        return web_info
    
    def get_complete_version_info(self, base_url: str = "http://localhost/jdrmj") -> Dict[str, Any]:
        """Récupère toutes les informations de version"""
        version_info = {
            'detection_timestamp': datetime.now().isoformat(),
            'application': {},
            'system': {},
            'web_server': {},
            'git': {},
            'database': {},
            'web_server_software': {}
        }
        
        # Version de l'application
        app_version = self.detect_application_version()
        if app_version:
            version_info['application']['version'] = app_version
        else:
            version_info['application']['version'] = 'Unknown'
        
        # Informations système
        version_info['system'] = self.detect_system_info()
        
        # Informations Git
        version_info['git'] = self.detect_git_info()
        
        # Versions des logiciels
        php_version = self.detect_php_version()
        if php_version:
            version_info['web_server_software']['php'] = php_version
        
        mysql_version = self.detect_mysql_version()
        if mysql_version:
            version_info['database']['mysql'] = mysql_version
        
        apache_version = self.detect_apache_version()
        if apache_version:
            version_info['web_server_software']['apache'] = apache_version
        
        # Informations du serveur web
        version_info['web_server'] = self.detect_web_server_info(base_url)
        
        return version_info
    
    def get_version_summary(self) -> str:
        """Retourne un résumé des versions détectées"""
        info = self.get_complete_version_info()
        
        summary_parts = []
        
        # Version de l'application
        if info['application']['version'] != 'Unknown':
            summary_parts.append(f"App: {info['application']['version']}")
        
        # Version PHP
        if 'php' in info['web_server_software']:
            summary_parts.append(f"PHP: {info['web_server_software']['php']}")
        
        # Version MySQL
        if 'mysql' in info['database']:
            summary_parts.append(f"MySQL: {info['database']['mysql']}")
        
        # Version Apache
        if 'apache' in info['web_server_software']:
            summary_parts.append(f"Apache: {info['web_server_software']['apache']}")
        
        # Commit Git
        if 'commit_hash' in info['git']:
            summary_parts.append(f"Git: {info['git']['commit_hash']}")
        
        return " | ".join(summary_parts) if summary_parts else "Versions non détectées"

def main():
    """Fonction principale pour tester le détecteur de version"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Détecteur de version pour JDR 4 MJ')
    parser.add_argument('--url', default='http://localhost/jdrmj',
                       help='URL de base de l\'application')
    parser.add_argument('--summary', action='store_true',
                       help='Afficher seulement un résumé')
    parser.add_argument('--json', action='store_true',
                       help='Afficher en format JSON')
    
    args = parser.parse_args()
    
    print("🔍 Détecteur de Version - JDR 4 MJ")
    print("=" * 50)
    
    detector = VersionDetector()
    
    if args.summary:
        summary = detector.get_version_summary()
        print(f"📋 Résumé: {summary}")
    elif args.json:
        import json
        info = detector.get_complete_version_info(args.url)
        print(json.dumps(info, indent=2, ensure_ascii=False))
    else:
        info = detector.get_complete_version_info(args.url)
        
        print("📱 APPLICATION")
        print(f"  Version: {info['application']['version']}")
        
        print("\n🖥️ SYSTÈME")
        print(f"  OS: {info['system'].get('os_name', 'Unknown')}")
        print(f"  Version: {info['system'].get('os_version', 'Unknown')}")
        print(f"  Architecture: {info['system'].get('architecture', 'Unknown')}")
        print(f"  Python: {info['system'].get('python_version', 'Unknown')}")
        
        print("\n🌐 SERVEUR WEB")
        print(f"  Statut: {info['web_server'].get('server_status', 'Unknown')}")
        if 'http_status' in info['web_server']:
            print(f"  HTTP Status: {info['web_server']['http_status']}")
        if 'server_header' in info['web_server']:
            print(f"  Server: {info['web_server']['server_header']}")
        
        print("\n💻 LOGICIELS")
        if 'php' in info['web_server_software']:
            print(f"  PHP: {info['web_server_software']['php']}")
        if 'apache' in info['web_server_software']:
            print(f"  Apache: {info['web_server_software']['apache']}")
        if 'mysql' in info['database']:
            print(f"  MySQL: {info['database']['mysql']}")
        
        print("\n📝 GIT")
        if 'git_version' in info['git']:
            print(f"  Version: {info['git']['git_version']}")
        if 'branch' in info['git']:
            print(f"  Branche: {info['git']['branch']}")
        if 'commit_hash' in info['git']:
            print(f"  Commit: {info['git']['commit_hash']}")
        if 'last_commit_date' in info['git']:
            print(f"  Dernière modification: {info['git']['last_commit_date']}")
        
        print(f"\n⏰ Détection: {info['detection_timestamp']}")

if __name__ == '__main__':
    main()
