#!/usr/bin/env python3
"""
Script de nettoyage des utilisateurs de test de la base de données
"""
import os
import sys
import pymysql
from datetime import datetime, timedelta

# Ajouter le répertoire parent au path pour importer les modules
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

def get_database_config():
    """Récupère la configuration de la base de données de test"""
    try:
        # Essayer d'importer la configuration PHP
        import subprocess
        result = subprocess.run([
            'php', '-r', 
            'include "config/database.test.php"; $config = include "config/database.test.php"; echo json_encode($config);'
        ], capture_output=True, text=True, cwd=os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
        
        if result.returncode == 0:
            import json
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

def connect_to_database():
    """Établit une connexion à la base de données"""
    config = get_database_config()
    
    try:
        connection = pymysql.connect(
            host=config['host'],
            user=config['username'],
            password=config['password'],
            database=config['dbname'],
            charset=config.get('charset', 'utf8mb4'),
            autocommit=False
        )
        return connection
    except Exception as e:
        print(f"Erreur de connexion à la base de données: {e}")
        return None

def cleanup_test_users(days_old=1, dry_run=False, auto_confirm=False):
    """
    Nettoie les utilisateurs de test de la base de données
    
    Args:
        days_old (int): Supprimer les utilisateurs créés il y a plus de X jours
        dry_run (bool): Si True, affiche seulement ce qui serait supprimé
    """
    connection = connect_to_database()
    if not connection:
        return False
    
    try:
        cursor = connection.cursor()
        
        # Calculer la date limite
        cutoff_date = datetime.now() - timedelta(days=days_old)
        
        # Identifier les utilisateurs de test à supprimer
        test_patterns = [
            'test_%',
            'test_user_%',
            'test_dm_%',
            'test_player_%',
            'test_admin_%',
            'test_delete_%',
            '%@test.com',
            '%@example.com'
        ]
        
        print(f"🔍 Recherche des utilisateurs de test créés avant le {cutoff_date.strftime('%Y-%m-%d %H:%M:%S')}")
        
        # Construire la requête pour trouver les utilisateurs de test
        where_conditions = []
        params = []
        
        for pattern in test_patterns:
            where_conditions.append("(username LIKE %s OR email LIKE %s)")
            params.extend([pattern, pattern])
        
        where_conditions.append("created_at < %s")
        params.append(cutoff_date)
        
        query = f"""
        SELECT id, username, email, created_at, role, is_dm 
        FROM users 
        WHERE ({' OR '.join(where_conditions)})
        ORDER BY created_at DESC
        """
        
        cursor.execute(query, params)
        test_users = cursor.fetchall()
        
        if not test_users:
            print("✅ Aucun utilisateur de test trouvé à supprimer")
            return True
        
        print(f"📋 {len(test_users)} utilisateur(s) de test trouvé(s):")
        for user in test_users:
            user_id, username, email, created_at, role, is_dm = user
            print(f"  - ID: {user_id}, Username: {username}, Email: {email}, Créé: {created_at}, Rôle: {role}, DM: {is_dm}")
        
        if dry_run:
            print("🔍 Mode dry-run: Aucune suppression effectuée")
            return True
        
        # Demander confirmation (sauf en mode auto_confirm)
        if not auto_confirm:
            try:
                response = input(f"\n❓ Voulez-vous supprimer ces {len(test_users)} utilisateur(s) de test? (oui/non): ")
                if response.lower() not in ['oui', 'o', 'yes', 'y']:
                    print("❌ Suppression annulée")
                    return False
            except (EOFError, KeyboardInterrupt):
                print("\n❌ Suppression annulée")
                return False
        else:
            print(f"\n✅ Suppression automatique de {len(test_users)} utilisateur(s) de test...")
        
        # Supprimer les utilisateurs de test
        deleted_count = 0
        for user in test_users:
            user_id = user[0]
            username = user[1]
            
            try:
                # Supprimer d'abord les données liées (personnages, campagnes, etc.)
                # Supprimer les personnages de l'utilisateur
                cursor.execute("DELETE FROM characters WHERE user_id = %s", (user_id,))
                
                # Supprimer les campagnes créées par l'utilisateur
                cursor.execute("DELETE FROM campaigns WHERE dm_id = %s", (user_id,))
                
                # Supprimer les sessions de campagne (si la table existe)
                try:
                    cursor.execute("DELETE FROM campaign_sessions WHERE dm_id = %s", (user_id,))
                except Exception:
                    pass  # La table n'existe peut-être pas
                
                # Supprimer les jets de dés
                cursor.execute("DELETE FROM dice_rolls WHERE user_id = %s", (user_id,))
                
                # Supprimer les tokens de scène (si la table existe)
                try:
                    cursor.execute("DELETE FROM scene_tokens WHERE user_id = %s", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les objets de lieu (si la table existe)
                try:
                    cursor.execute("DELETE FROM place_objects WHERE user_id = %s", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les monstres créés par l'utilisateur (si la table existe)
                try:
                    cursor.execute("DELETE FROM monsters WHERE created_by = %s", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les objets magiques créés par l'utilisateur (si la table existe)
                try:
                    cursor.execute("DELETE FROM magical_items WHERE created_by = %s", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les poisons créés par l'utilisateur (si la table existe)
                try:
                    cursor.execute("DELETE FROM poisons WHERE created_by = %s", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les sorts appris (si la table existe)
                try:
                    cursor.execute("DELETE FROM character_spells WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les équipements des personnages (si la table existe)
                try:
                    cursor.execute("DELETE FROM character_equipment WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les capacités des personnages (si la table existe)
                try:
                    cursor.execute("DELETE FROM character_capabilities WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les langues des personnages (si la table existe)
                try:
                    cursor.execute("DELETE FROM character_languages WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les sorts de classe (si la table existe)
                try:
                    cursor.execute("DELETE FROM class_spells WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les emplacements de sorts (si la table existe)
                try:
                    cursor.execute("DELETE FROM spell_slots WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                # Supprimer les personnages (cascade)
                cursor.execute("DELETE FROM characters WHERE user_id = %s", (user_id,))
                
                # Enfin, supprimer l'utilisateur
                cursor.execute("DELETE FROM users WHERE id = %s", (user_id,))
                
                deleted_count += 1
                print(f"✅ Utilisateur {username} (ID: {user_id}) supprimé avec succès")
                
            except Exception as e:
                print(f"❌ Erreur lors de la suppression de l'utilisateur {username} (ID: {user_id}): {e}")
                connection.rollback()
                continue
        
        # Valider les changements
        connection.commit()
        print(f"\n🎉 Nettoyage terminé: {deleted_count}/{len(test_users)} utilisateur(s) supprimé(s)")
        
        return True
        
    except Exception as e:
        print(f"❌ Erreur lors du nettoyage: {e}")
        connection.rollback()
        return False
    finally:
        connection.close()

def cleanup_all_test_users(dry_run=False, auto_confirm=False):
    """Nettoie tous les utilisateurs de test (peu importe l'âge)"""
    connection = connect_to_database()
    if not connection:
        return False
    
    try:
        cursor = connection.cursor()
        
        # Identifier tous les utilisateurs de test
        test_patterns = [
            'test_%',
            'test_user_%',
            'test_dm_%',
            'test_player_%',
            'test_admin_%',
            'test_delete_%',
            '%@test.com',
            '%@example.com'
        ]
        
        print("🔍 Recherche de tous les utilisateurs de test...")
        
        # Construire la requête pour trouver tous les utilisateurs de test
        where_conditions = []
        params = []
        
        for pattern in test_patterns:
            where_conditions.append("(username LIKE %s OR email LIKE %s)")
            params.extend([pattern, pattern])
        
        query = f"""
        SELECT id, username, email, created_at, role, is_dm 
        FROM users 
        WHERE {' OR '.join(where_conditions)}
        ORDER BY created_at DESC
        """
        
        cursor.execute(query, params)
        test_users = cursor.fetchall()
        
        if not test_users:
            print("✅ Aucun utilisateur de test trouvé")
            return True
        
        print(f"📋 {len(test_users)} utilisateur(s) de test trouvé(s):")
        for user in test_users:
            user_id, username, email, created_at, role, is_dm = user
            print(f"  - ID: {user_id}, Username: {username}, Email: {email}, Créé: {created_at}, Rôle: {role}, DM: {is_dm}")
        
        if dry_run:
            print("🔍 Mode dry-run: Aucune suppression effectuée")
            return True
        
        # Demander confirmation (sauf en mode auto_confirm)
        if not auto_confirm:
            try:
                response = input(f"\n❓ Voulez-vous supprimer TOUS ces {len(test_users)} utilisateur(s) de test? (oui/non): ")
                if response.lower() not in ['oui', 'o', 'yes', 'y']:
                    print("❌ Suppression annulée")
                    return False
            except (EOFError, KeyboardInterrupt):
                print("\n❌ Suppression annulée")
                return False
        else:
            print(f"\n✅ Suppression automatique de TOUS les {len(test_users)} utilisateur(s) de test...")
        
        # Supprimer tous les utilisateurs de test
        deleted_count = 0
        for user in test_users:
            user_id = user[0]
            username = user[1]
            
            try:
                # Supprimer toutes les données liées (même logique que cleanup_test_users)
                cursor.execute("DELETE FROM characters WHERE user_id = %s", (user_id,))
                cursor.execute("DELETE FROM campaigns WHERE dm_id = %s", (user_id,))
                
                # Tables optionnelles
                try:
                    cursor.execute("DELETE FROM campaign_sessions WHERE dm_id = %s", (user_id,))
                except Exception:
                    pass
                
                cursor.execute("DELETE FROM dice_rolls WHERE user_id = %s", (user_id,))
                
                try:
                    cursor.execute("DELETE FROM scene_tokens WHERE user_id = %s", (user_id,))
                except Exception:
                    pass
                
                try:
                    cursor.execute("DELETE FROM place_objects WHERE user_id = %s", (user_id,))
                except Exception:
                    pass
                
                try:
                    cursor.execute("DELETE FROM monsters WHERE created_by = %s", (user_id,))
                except Exception:
                    pass
                
                try:
                    cursor.execute("DELETE FROM magical_items WHERE created_by = %s", (user_id,))
                except Exception:
                    pass
                
                try:
                    cursor.execute("DELETE FROM poisons WHERE created_by = %s", (user_id,))
                except Exception:
                    pass
                try:
                    cursor.execute("DELETE FROM character_spells WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                try:
                    cursor.execute("DELETE FROM character_equipment WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                try:
                    cursor.execute("DELETE FROM character_capabilities WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                try:
                    cursor.execute("DELETE FROM character_languages WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                try:
                    cursor.execute("DELETE FROM class_spells WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                
                try:
                    cursor.execute("DELETE FROM spell_slots WHERE character_id IN (SELECT id FROM characters WHERE user_id = %s)", (user_id,))
                except Exception:
                    pass
                cursor.execute("DELETE FROM characters WHERE user_id = %s", (user_id,))
                cursor.execute("DELETE FROM users WHERE id = %s", (user_id,))
                
                deleted_count += 1
                print(f"✅ Utilisateur {username} (ID: {user_id}) supprimé avec succès")
                
            except Exception as e:
                print(f"❌ Erreur lors de la suppression de l'utilisateur {username} (ID: {user_id}): {e}")
                connection.rollback()
                continue
        
        # Valider les changements
        connection.commit()
        print(f"\n🎉 Nettoyage terminé: {deleted_count}/{len(test_users)} utilisateur(s) supprimé(s)")
        
        return True
        
    except Exception as e:
        print(f"❌ Erreur lors du nettoyage: {e}")
        connection.rollback()
        return False
    finally:
        connection.close()

def show_test_users():
    """Affiche tous les utilisateurs de test sans les supprimer"""
    connection = connect_to_database()
    if not connection:
        return False
    
    try:
        cursor = connection.cursor()
        
        # Identifier tous les utilisateurs de test
        test_patterns = [
            'test_%',
            'test_user_%',
            'test_dm_%',
            'test_player_%',
            'test_admin_%',
            'test_delete_%',
            '%@test.com',
            '%@example.com'
        ]
        
        # Exclure les utilisateurs légitimes
        exclude_patterns = [
            'admin',
            'dm_test',
            'player_test',
            'admin@jdrmj.com',
            'dm@jdrmj.com',
            'player@jdrmj.com'
        ]
        
        # Construire la requête pour trouver tous les utilisateurs de test
        where_conditions = []
        params = []
        
        for pattern in test_patterns:
            where_conditions.append("(username LIKE %s OR email LIKE %s)")
            params.extend([pattern, pattern])
        
        query = f"""
        SELECT id, username, email, created_at, role, is_dm 
        FROM users 
        WHERE {' OR '.join(where_conditions)}
        ORDER BY created_at DESC
        """
        
        cursor.execute(query, params)
        test_users = cursor.fetchall()
        
        if not test_users:
            print("✅ Aucun utilisateur de test trouvé")
            return True
        
        print(f"📋 {len(test_users)} utilisateur(s) de test trouvé(s):")
        print("-" * 80)
        for user in test_users:
            user_id, username, email, created_at, role, is_dm = user
            print(f"ID: {user_id:3d} | Username: {username:20s} | Email: {email:25s} | Créé: {created_at} | Rôle: {role:7s} | DM: {is_dm}")
        
        return True
        
    except Exception as e:
        print(f"❌ Erreur lors de l'affichage: {e}")
        return False
    finally:
        connection.close()

def main():
    """Fonction principale avec support des arguments en ligne de commande"""
    import sys
    
    # Vérifier les arguments en ligne de commande
    if len(sys.argv) > 1:
        if "--dry-run" in sys.argv:
            print("🔍 Mode dry-run - Affichage des utilisateurs qui seraient supprimés:")
            cleanup_test_users(days_old=1, dry_run=True)
            return
        elif "--all" in sys.argv:
            cleanup_all_test_users(dry_run=False, auto_confirm=True)
            return
        elif "--days=" in " ".join(sys.argv):
            days = 1
            for arg in sys.argv:
                if arg.startswith("--days="):
                    days = int(arg.split("=")[1])
                    break
            cleanup_test_users(days_old=days, dry_run=False, auto_confirm=True)
            return
        elif "--help" in sys.argv or "-h" in sys.argv:
            print("Usage: python3 cleanup_test_users.py [--dry-run] [--all] [--days=N]")
            print("  --dry-run    Afficher sans supprimer")
            print("  --all        Supprimer tous les utilisateurs de test")
            print("  --days=N     Supprimer les utilisateurs > N jours (défaut: 1)")
            return
    
    # Mode interactif si aucun argument
    print("🧹 Script de nettoyage des utilisateurs de test")
    print("=" * 50)
    
    while True:
        print("\nOptions disponibles:")
        print("1. Afficher tous les utilisateurs de test")
        print("2. Nettoyer les utilisateurs de test anciens (> 1 jour)")
        print("3. Nettoyer les utilisateurs de test anciens (> 7 jours)")
        print("4. Nettoyer TOUS les utilisateurs de test")
        print("5. Mode dry-run (afficher sans supprimer)")
        print("0. Quitter")
        
        try:
            choice = input("\nVotre choix (0-5): ").strip()
        except EOFError:
            # Mode non-interactif, sortir
            break
        
        if choice == "0":
            print("👋 Au revoir!")
            break
        elif choice == "1":
            show_test_users()
        elif choice == "2":
            cleanup_test_users(days_old=1)
        elif choice == "3":
            cleanup_test_users(days_old=7)
        elif choice == "4":
            cleanup_all_test_users()
        elif choice == "5":
            print("\nMode dry-run - Affichage des utilisateurs qui seraient supprimés:")
            cleanup_test_users(days_old=1, dry_run=True)
        else:
            print("❌ Choix invalide")

if __name__ == "__main__":
    main()
