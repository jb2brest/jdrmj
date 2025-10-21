#!/usr/bin/env python3
"""
Script de nettoyage intelligent des données de test
Ne supprime que les vrais utilisateurs de test, pas les utilisateurs légitimes
"""

import pymysql
import sys
from datetime import datetime, timedelta

def get_database_config():
    """Récupère la configuration de la base de données"""
    return {
        'host': 'localhost',
        'user': 'u839591438_jdrmj',
        'password': 'M8jbsYJUj6FE$;C',
        'database': 'u839591438_jdrmj',
        'charset': 'utf8mb4'
    }

def connect_to_database():
    """Se connecte à la base de données"""
    try:
        config = get_database_config()
        connection = pymysql.connect(**config)
        return connection
    except Exception as e:
        print(f"Erreur de connexion à la base de données: {e}")
        return None

def is_legitimate_user(username, email):
    """Vérifie si un utilisateur est légitime (pas un utilisateur de test)"""
    legitimate_users = [
        'admin',
        'dm_test', 
        'player_test',
        'admin@jdrmj.com',
        'dm@jdrmj.com',
        'player@jdrmj.com'
    ]
    
    return username in legitimate_users or email in legitimate_users

def cleanup_test_users(days_old=1, dry_run=False):
    """Nettoie les utilisateurs de test en excluant les utilisateurs légitimes"""
    connection = connect_to_database()
    if not connection:
        return False
    
    try:
        cursor = connection.cursor()
        
        # Récupérer tous les utilisateurs
        cursor.execute("""
            SELECT id, username, email, created_at, role, is_dm 
            FROM users 
            ORDER BY created_at DESC
        """)
        all_users = cursor.fetchall()
        
        # Filtrer les utilisateurs de test (en excluant les légitimes)
        test_users = []
        for user in all_users:
            user_id, username, email, created_at, role, is_dm = user
            
            # Vérifier si c'est un utilisateur légitime
            if is_legitimate_user(username, email):
                print(f"🛡️  Utilisateur légitime conservé: {username} ({email})")
                continue
            
            # Vérifier si c'est un utilisateur de test
            is_test_user = (
                username.startswith('test_') or 
                email.endswith('@test.com') or 
                email.endswith('@example.com') or
                'test' in username.lower()
            )
            
            if is_test_user:
                # Vérifier l'âge si spécifié
                if days_old > 0:
                    user_date = datetime.strptime(str(created_at), '%Y-%m-%d %H:%M:%S')
                    if user_date > datetime.now() - timedelta(days=days_old):
                        print(f"⏰ Utilisateur de test récent conservé: {username} (créé: {created_at})")
                        continue
                
                test_users.append(user)
        
        if not test_users:
            print("✅ Aucun utilisateur de test trouvé à supprimer")
            return True
        
        print(f"📋 {len(test_users)} utilisateur(s) de test trouvé(s):")
        for user in test_users:
            user_id, username, email, created_at, role, is_dm = user
            print(f"  - ID: {user_id}, Username: {username}, Email: {email}, Créé: {created_at}")
        
        if dry_run:
            print("🔍 Mode dry-run: Aucune suppression effectuée")
            return True
        
        # Supprimer les utilisateurs de test
        deleted_count = 0
        for user in test_users:
            user_id = user[0]
            username = user[1]
            
            try:
                # Supprimer les données liées
                cursor.execute("DELETE FROM characters WHERE user_id = %s", (user_id,))
                cursor.execute("DELETE FROM campaigns WHERE dm_id = %s", (user_id,))
                cursor.execute("DELETE FROM dice_rolls WHERE user_id = %s", (user_id,))
                
                # Tables optionnelles
                try:
                    cursor.execute("DELETE FROM scene_tokens WHERE user_id = %s", (user_id,))
                except:
                    pass
                
                try:
                    cursor.execute("DELETE FROM place_objects WHERE user_id = %s", (user_id,))
                except:
                    pass
                
                try:
                    cursor.execute("DELETE FROM monsters WHERE created_by = %s", (user_id,))
                except:
                    pass
                
                try:
                    cursor.execute("DELETE FROM magical_items WHERE created_by = %s", (user_id,))
                except:
                    pass
                
                try:
                    cursor.execute("DELETE FROM poisons WHERE created_by = %s", (user_id,))
                except:
                    pass
                
                # Supprimer l'utilisateur
                cursor.execute("DELETE FROM users WHERE id = %s", (user_id,))
                
                deleted_count += 1
                print(f"✅ Utilisateur {username} (ID: {user_id}) supprimé avec succès")
                
            except Exception as e:
                print(f"❌ Erreur lors de la suppression de l'utilisateur {username} (ID: {user_id}): {e}")
        
        connection.commit()
        print(f"\n🎉 Nettoyage terminé: {deleted_count}/{len(test_users)} utilisateur(s) supprimé(s)")
        return True
        
    except Exception as e:
        print(f"❌ Erreur lors du nettoyage: {e}")
        connection.rollback()
        return False
    finally:
        connection.close()

def main():
    """Fonction principale"""
    if len(sys.argv) > 1:
        if "--dry-run" in sys.argv:
            print("🔍 Mode dry-run - Affichage des utilisateurs qui seraient supprimés:")
            cleanup_test_users(days_old=1, dry_run=True)
        elif "--all" in sys.argv:
            cleanup_test_users(days_old=0, dry_run=False)
        elif "--days=" in " ".join(sys.argv):
            days = 1
            for arg in sys.argv:
                if arg.startswith("--days="):
                    days = int(arg.split("=")[1])
                    break
            cleanup_test_users(days_old=days, dry_run=False)
        elif "--help" in sys.argv or "-h" in sys.argv:
            print("Usage: python3 smart_cleanup.py [--dry-run] [--all] [--days=N]")
            print("  --dry-run    Afficher sans supprimer")
            print("  --all        Supprimer tous les utilisateurs de test")
            print("  --days=N     Supprimer les utilisateurs > N jours (défaut: 1)")
        else:
            cleanup_test_users(days_old=1, dry_run=False)
    else:
        cleanup_test_users(days_old=1, dry_run=False)

if __name__ == "__main__":
    main()
