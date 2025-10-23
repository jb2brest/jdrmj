#!/usr/bin/env python3
"""
Script de nettoyage des données de test pour les campagnes
"""

import os
import sys
import pymysql
import json
import subprocess
from datetime import datetime

def get_database_config():
    """Récupère la configuration de la base de données de test"""
    try:
        # Essayer d'importer la configuration PHP
        result = subprocess.run([
            'php', '-r', 
            'include "config/database.test.php"; $config = include "config/database.test.php"; echo json_encode($config);'
        ], capture_output=True, text=True, cwd=os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
        
        if result.returncode == 0:
            config = json.loads(result.stdout)
            return config
    except Exception as e:
        print(f"Erreur lors de la lecture de la config PHP: {e}")
    
    # Configuration par défaut si la lecture PHP échoue
    return {
        'host': 'localhost',
        'dbname': 'u839591438_jdrmj',
        'username': 'u839591438_jdrmj',
        'password': 'M8jbsYJUj6FE$;C',
        'charset': 'utf8mb4'
    }

def cleanup_campaign_test_data():
    """Nettoie toutes les données de test des campagnes"""
    print("🧹 Nettoyage des données de test des campagnes")
    print("=" * 50)
    
    try:
        config = get_database_config()
        connection = pymysql.connect(
            host=config['host'],
            user=config['username'],
            password=config['password'],
            database=config['dbname'],
            charset=config.get('charset', 'utf8mb4'),
            autocommit=False
        )
        
        cursor = connection.cursor()
        
        # Compter les données avant suppression
        cursor.execute("SELECT COUNT(*) FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%'")
        campaigns_count = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM campaign_sessions WHERE title LIKE 'Session%Test%' OR title LIKE 'Session%Liste%' OR title LIKE 'Session%Visualiser%' OR title LIKE 'Session%Notes%'")
        sessions_count = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM campaign_members WHERE user_id IN (SELECT id FROM users WHERE username LIKE 'test_user_%')")
        members_count = cursor.fetchone()[0]
        
        print(f"📊 Données trouvées:")
        print(f"   - Campagnes: {campaigns_count}")
        print(f"   - Sessions: {sessions_count}")
        print(f"   - Membres: {members_count}")
        print()
        
        if campaigns_count == 0 and sessions_count == 0 and members_count == 0:
            print("✅ Aucune donnée de test à nettoyer")
            return
        
        # Supprimer dans l'ordre hiérarchique
        print("🗑️ Suppression en cours...")
        
        # 1. Supprimer les notifications liées aux campagnes de test
        try:
            cursor.execute("DELETE FROM notifications WHERE campaign_id IN (SELECT id FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%')")
            print(f"   ✅ Notifications supprimées")
        except Exception:
            pass  # La table n'existe peut-être pas
        
        # 2. Supprimer les applications de campagne de test
        try:
            cursor.execute("DELETE FROM campaign_applications WHERE campaign_id IN (SELECT id FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%')")
            print(f"   ✅ Applications de campagne supprimées")
        except Exception:
            pass  # La table n'existe peut-être pas
        
        # 3. Supprimer les événements de campagne de test
        try:
            cursor.execute("DELETE FROM campaign_events WHERE campaign_id IN (SELECT id FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%')")
            print(f"   ✅ Événements de campagne supprimés")
        except Exception:
            pass  # La table n'existe peut-être pas
        
        # 4. Supprimer les associations de lieux avec les campagnes de test
        try:
            cursor.execute("DELETE FROM place_campaigns WHERE campaign_id IN (SELECT id FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%')")
            print(f"   ✅ Associations de lieux supprimées")
        except Exception:
            pass  # La table n'existe peut-être pas
        
        # 5. Supprimer les joueurs dans les lieux des campagnes de test
        try:
            cursor.execute("DELETE pp FROM place_players pp INNER JOIN place_campaigns pc ON pp.place_id = pc.place_id WHERE pc.campaign_id IN (SELECT id FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%')")
            print(f"   ✅ Joueurs dans les lieux supprimés")
        except Exception:
            pass  # La table n'existe peut-être pas
        
        # 6. Supprimer les PNJ dans les lieux des campagnes de test
        try:
            cursor.execute("DELETE pn FROM place_npcs pn INNER JOIN place_campaigns pc ON pn.place_id = pc.place_id WHERE pc.campaign_id IN (SELECT id FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%')")
            print(f"   ✅ PNJ dans les lieux supprimés")
        except Exception:
            pass  # La table n'existe peut-être pas
        
        # 7. Supprimer les monstres dans les lieux des campagnes de test
        try:
            cursor.execute("DELETE pm FROM place_monsters pm INNER JOIN place_campaigns pc ON pm.place_id = pc.place_id WHERE pc.campaign_id IN (SELECT id FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%')")
            print(f"   ✅ Monstres dans les lieux supprimés")
        except Exception:
            pass  # La table n'existe peut-être pas
        
        # 8. Supprimer les membres des campagnes de test
        if members_count > 0:
            cursor.execute("DELETE FROM campaign_members WHERE campaign_id IN (SELECT id FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%')")
            print(f"   ✅ {members_count} membre(s) supprimé(s)")
        
        # 9. Supprimer les sessions de campagne de test
        if sessions_count > 0:
            cursor.execute("DELETE FROM campaign_sessions WHERE campaign_id IN (SELECT id FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%')")
            print(f"   ✅ {sessions_count} session(s) supprimée(s)")
        
        # 10. Supprimer les campagnes de test
        if campaigns_count > 0:
            cursor.execute("DELETE FROM campaigns WHERE title LIKE 'Campagne%Test%' OR title LIKE 'Campagne%Liste%' OR title LIKE 'Campagne%Visualiser%' OR title LIKE 'Campagne%Dupliqué%' OR title LIKE 'Campagne%Privée%' OR title LIKE 'Campagne%Invitation%' OR title LIKE 'Campagne%Membres%' OR title LIKE 'Campagne%Suppression%' OR title LIKE 'Campagne%Permissions%' OR title LIKE 'Campagne%Notes%' OR title LIKE 'Campagne%Session%'")
            print(f"   ✅ {campaigns_count} campagne(s) supprimée(s)")
        
        connection.commit()
        print()
        print("✅ Nettoyage terminé avec succès!")
        
        connection.close()
        
    except Exception as e:
        print(f"❌ Erreur lors du nettoyage: {e}")
        sys.exit(1)

def cleanup_user_campaign_test_data(username_pattern="test_user_%"):
    """Nettoie les données de test de campagne d'un utilisateur spécifique"""
    print(f"🧹 Nettoyage des données de test de campagne pour l'utilisateur: {username_pattern}")
    print("=" * 70)
    
    try:
        config = get_database_config()
        connection = pymysql.connect(
            host=config['host'],
            user=config['username'],
            password=config['password'],
            database=config['dbname'],
            charset=config.get('charset', 'utf8mb4'),
            autocommit=False
        )
        
        cursor = connection.cursor()
        
        # Trouver l'utilisateur
        cursor.execute("SELECT id, username FROM users WHERE username LIKE %s", (username_pattern,))
        users = cursor.fetchall()
        
        if not users:
            print(f"ℹ️ Aucun utilisateur trouvé avec le pattern: {username_pattern}")
            return
        
        for user_id, username in users:
            print(f"👤 Nettoyage des données de campagne pour l'utilisateur: {username} (ID: {user_id})")
            
            # Supprimer les données liées dans l'ordre hiérarchique
            # 1. Notifications liées aux campagnes
            try:
                cursor.execute("DELETE FROM notifications WHERE campaign_id IN (SELECT id FROM campaigns WHERE dm_id = %s)", (user_id,))
            except Exception:
                pass
            
            # 2. Applications de campagne
            try:
                cursor.execute("DELETE FROM campaign_applications WHERE campaign_id IN (SELECT id FROM campaigns WHERE dm_id = %s)", (user_id,))
            except Exception:
                pass
            
            # 3. Événements de campagne
            try:
                cursor.execute("DELETE FROM campaign_events WHERE campaign_id IN (SELECT id FROM campaigns WHERE dm_id = %s)", (user_id,))
            except Exception:
                pass
            
            # 4. Associations de lieux avec les campagnes
            try:
                cursor.execute("DELETE FROM place_campaigns WHERE campaign_id IN (SELECT id FROM campaigns WHERE dm_id = %s)", (user_id,))
            except Exception:
                pass
            
            # 5. Joueurs dans les lieux des campagnes
            try:
                cursor.execute("DELETE pp FROM place_players pp INNER JOIN place_campaigns pc ON pp.place_id = pc.place_id WHERE pc.campaign_id IN (SELECT id FROM campaigns WHERE dm_id = %s)", (user_id,))
            except Exception:
                pass
            
            # 6. PNJ dans les lieux des campagnes
            try:
                cursor.execute("DELETE pn FROM place_npcs pn INNER JOIN place_campaigns pc ON pn.place_id = pc.place_id WHERE pc.campaign_id IN (SELECT id FROM campaigns WHERE dm_id = %s)", (user_id,))
            except Exception:
                pass
            
            # 7. Monstres dans les lieux des campagnes
            try:
                cursor.execute("DELETE pm FROM place_monsters pm INNER JOIN place_campaigns pc ON pm.place_id = pc.place_id WHERE pc.campaign_id IN (SELECT id FROM campaigns WHERE dm_id = %s)", (user_id,))
            except Exception:
                pass
            
            # 8. Membres des campagnes
            cursor.execute("DELETE FROM campaign_members WHERE campaign_id IN (SELECT id FROM campaigns WHERE dm_id = %s)", (user_id,))
            
            # 9. Sessions de campagne
            cursor.execute("DELETE FROM campaign_sessions WHERE campaign_id IN (SELECT id FROM campaigns WHERE dm_id = %s)", (user_id,))
            
            # 10. Campagnes
            cursor.execute("DELETE FROM campaigns WHERE dm_id = %s", (user_id,))
            
            print(f"   ✅ Données de campagne de {username} supprimées")
        
        connection.commit()
        print()
        print("✅ Nettoyage des utilisateurs terminé avec succès!")
        
        connection.close()
        
    except Exception as e:
        print(f"❌ Erreur lors du nettoyage: {e}")
        sys.exit(1)

def main():
    """Fonction principale"""
    if len(sys.argv) > 1:
        if sys.argv[1] == "--help" or sys.argv[1] == "-h":
            print("🧹 Script de nettoyage des données de test de campagne")
            print("=" * 50)
            print()
            print("Usage:")
            print("  python3 cleanup_campaign_test_data.py                    # Nettoie toutes les données de test de campagne")
            print("  python3 cleanup_campaign_test_data.py --user PATTERN     # Nettoie les données d'un utilisateur")
            print("  python3 cleanup_campaign_test_data.py --help             # Affiche cette aide")
            print()
            print("Exemples:")
            print("  python3 cleanup_campaign_test_data.py")
            print("  python3 cleanup_campaign_test_data.py --user test_user_%")
            print("  python3 cleanup_campaign_test_data.py --user test_user_1234567890")
            return
        elif sys.argv[1] == "--user" and len(sys.argv) > 2:
            cleanup_user_campaign_test_data(sys.argv[2])
            return
    
    # Nettoyage par défaut
    cleanup_campaign_test_data()

if __name__ == "__main__":
    main()
