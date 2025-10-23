#!/usr/bin/env python3
"""
Script de nettoyage des données de test pour les mondes, pays, régions et lieux
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

def cleanup_test_data():
    """Nettoie toutes les données de test des mondes, pays, régions et lieux"""
    print("🧹 Nettoyage des données de test des mondes, pays, régions et lieux")
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
        
        # Compter les données avant suppression
        cursor.execute("SELECT COUNT(*) FROM places WHERE title LIKE 'Lieu%Test%' OR title LIKE 'Lieu%Liste%' OR title LIKE 'Lieu%Visualiser%' OR title LIKE 'Lieu%Dupliqué%'")
        places_count = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM regions WHERE name LIKE 'Région%Test%' OR name LIKE 'Région%Liste%' OR name LIKE 'Région%Visualiser%' OR name LIKE 'Région%Dupliqué%'")
        regions_count = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM countries WHERE name LIKE 'Pays%Test%' OR name LIKE 'Pays%Liste%' OR name LIKE 'Pays%Visualiser%' OR name LIKE 'Pays%Dupliqué%'")
        countries_count = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM worlds WHERE name LIKE 'Monde%Test%' OR name LIKE 'Monde%Liste%' OR name LIKE 'Monde%Visualiser%' OR name LIKE 'Monde%Dupliqué%'")
        worlds_count = cursor.fetchone()[0]
        
        print(f"📊 Données trouvées:")
        print(f"   - Mondes: {worlds_count}")
        print(f"   - Pays: {countries_count}")
        print(f"   - Régions: {regions_count}")
        print(f"   - Lieux: {places_count}")
        print()
        
        if worlds_count == 0 and countries_count == 0 and regions_count == 0 and places_count == 0:
            print("✅ Aucune donnée de test à nettoyer")
            return
        
        # Supprimer dans l'ordre hiérarchique
        print("🗑️ Suppression en cours...")
        
        # 1. Supprimer les lieux de test
        if places_count > 0:
            cursor.execute("DELETE FROM places WHERE title LIKE 'Lieu%Test%' OR title LIKE 'Lieu%Liste%' OR title LIKE 'Lieu%Visualiser%' OR title LIKE 'Lieu%Dupliqué%'")
            print(f"   ✅ {places_count} lieu(x) supprimé(s)")
        
        # 2. Supprimer les régions de test
        if regions_count > 0:
            cursor.execute("DELETE FROM regions WHERE name LIKE 'Région%Test%' OR name LIKE 'Région%Liste%' OR name LIKE 'Région%Visualiser%' OR name LIKE 'Région%Dupliqué%'")
            print(f"   ✅ {regions_count} région(s) supprimée(s)")
        
        # 3. Supprimer les pays de test
        if countries_count > 0:
            cursor.execute("DELETE FROM countries WHERE name LIKE 'Pays%Test%' OR name LIKE 'Pays%Liste%' OR name LIKE 'Pays%Visualiser%' OR name LIKE 'Pays%Dupliqué%'")
            print(f"   ✅ {countries_count} pays supprimé(s)")
        
        # 4. Supprimer les mondes de test
        if worlds_count > 0:
            cursor.execute("DELETE FROM worlds WHERE name LIKE 'Monde%Test%' OR name LIKE 'Monde%Liste%' OR name LIKE 'Monde%Visualiser%' OR name LIKE 'Monde%Dupliqué%'")
            print(f"   ✅ {worlds_count} monde(s) supprimé(s)")
        
        connection.commit()
        print()
        print("✅ Nettoyage terminé avec succès!")
        
        connection.close()
        
    except Exception as e:
        print(f"❌ Erreur lors du nettoyage: {e}")
        sys.exit(1)

def cleanup_user_test_data(username_pattern="test_user_%"):
    """Nettoie les données de test d'un utilisateur spécifique"""
    print(f"🧹 Nettoyage des données de test pour l'utilisateur: {username_pattern}")
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
            print(f"👤 Nettoyage des données pour l'utilisateur: {username} (ID: {user_id})")
            
            # Supprimer les données liées dans l'ordre hiérarchique
            # 1. Lieux
            cursor.execute("DELETE FROM places WHERE country_id IN (SELECT id FROM countries WHERE world_id IN (SELECT id FROM worlds WHERE created_by = %s))", (user_id,))
            cursor.execute("DELETE FROM places WHERE region_id IN (SELECT id FROM regions WHERE country_id IN (SELECT id FROM countries WHERE world_id IN (SELECT id FROM worlds WHERE created_by = %s)))", (user_id,))
            
            # 2. Régions
            cursor.execute("DELETE FROM regions WHERE country_id IN (SELECT id FROM countries WHERE world_id IN (SELECT id FROM worlds WHERE created_by = %s))", (user_id,))
            
            # 3. Pays
            cursor.execute("DELETE FROM countries WHERE world_id IN (SELECT id FROM worlds WHERE created_by = %s)", (user_id,))
            
            # 4. Mondes
            cursor.execute("DELETE FROM worlds WHERE created_by = %s", (user_id,))
            
            # 5. Autres données liées
            cursor.execute("DELETE FROM characters WHERE user_id = %s", (user_id,))
            cursor.execute("DELETE FROM campaigns WHERE dm_id = %s", (user_id,))
            cursor.execute("DELETE FROM campaign_sessions WHERE dm_id = %s", (user_id,))
            cursor.execute("DELETE FROM dice_rolls WHERE user_id = %s", (user_id,))
            cursor.execute("DELETE FROM scene_tokens WHERE user_id = %s", (user_id,))
            cursor.execute("DELETE FROM place_objects WHERE user_id = %s", (user_id,))
            cursor.execute("DELETE FROM monsters WHERE created_by = %s", (user_id,))
            cursor.execute("DELETE FROM magical_items WHERE created_by = %s", (user_id,))
            cursor.execute("DELETE FROM poisons WHERE created_by = %s", (user_id,))
            
            # 6. Supprimer l'utilisateur
            cursor.execute("DELETE FROM users WHERE id = %s", (user_id,))
            
            print(f"   ✅ Données de {username} supprimées")
        
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
            print("🧹 Script de nettoyage des données de test")
            print("=" * 40)
            print()
            print("Usage:")
            print("  python3 cleanup_world_test_data.py                    # Nettoie toutes les données de test")
            print("  python3 cleanup_world_test_data.py --user PATTERN     # Nettoie les données d'un utilisateur")
            print("  python3 cleanup_world_test_data.py --help             # Affiche cette aide")
            print()
            print("Exemples:")
            print("  python3 cleanup_world_test_data.py")
            print("  python3 cleanup_world_test_data.py --user test_user_%")
            print("  python3 cleanup_world_test_data.py --user test_admin_1234567890")
            return
        elif sys.argv[1] == "--user" and len(sys.argv) > 2:
            cleanup_user_test_data(sys.argv[2])
            return
    
    # Nettoyage par défaut
    cleanup_test_data()

if __name__ == "__main__":
    main()
