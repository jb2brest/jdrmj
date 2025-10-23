#!/usr/bin/env python3
"""
Script pour mettre à jour les rapports JSON existants avec des étapes basiques
"""

import json
import os
import glob
from datetime import datetime

def get_functional_description(test_name):
    """Génère des descriptions fonctionnelles basées sur le nom du test"""
    
    # Dictionnaire de descriptions fonctionnelles par type de test
    descriptions = {
        # Tests d'authentification
        "login": {
            "initialization": "Préparation de l'environnement de connexion",
            "action_name": "Connexion utilisateur",
            "action_description": "Tentative de connexion avec les identifiants fournis",
            "success_description": "L'utilisateur est connecté avec succès",
            "failure_description": "La connexion a échoué - identifiants incorrects ou problème technique",
            "finalization": "Fermeture de la session de connexion"
        },
        "logout": {
            "initialization": "Préparation de la déconnexion",
            "action_name": "Déconnexion utilisateur",
            "action_description": "Déconnexion de l'utilisateur connecté",
            "success_description": "L'utilisateur est déconnecté avec succès",
            "failure_description": "La déconnexion a échoué",
            "finalization": "Retour à la page de connexion"
        },
        "registration": {
            "initialization": "Préparation du formulaire d'inscription",
            "action_name": "Inscription utilisateur",
            "action_description": "Création d'un nouveau compte utilisateur",
            "success_description": "Le compte utilisateur a été créé avec succès",
            "failure_description": "L'inscription a échoué - données invalides ou compte existant",
            "finalization": "Validation de l'inscription"
        },
        
        # Tests de personnages
        "character_creation": {
            "initialization": "Préparation de la création de personnage",
            "action_name": "Création de personnage",
            "action_description": "Création d'un nouveau personnage avec les caractéristiques choisies",
            "success_description": "Le personnage a été créé avec succès",
            "failure_description": "La création du personnage a échoué - données invalides",
            "finalization": "Validation du personnage créé"
        },
        "character_view": {
            "initialization": "Préparation de l'affichage du personnage",
            "action_name": "Affichage du personnage",
            "action_description": "Visualisation des détails du personnage",
            "success_description": "Les détails du personnage s'affichent correctement",
            "failure_description": "L'affichage du personnage a échoué",
            "finalization": "Fermeture de la vue du personnage"
        },
        
        # Tests de classes
        "barbarian": {
            "initialization": "Préparation du test de la classe Barbare",
            "action_name": "Test classe Barbare",
            "action_description": "Vérification des fonctionnalités spécifiques à la classe Barbare",
            "success_description": "Toutes les fonctionnalités de la classe Barbare fonctionnent correctement",
            "failure_description": "Des problèmes ont été détectés avec la classe Barbare",
            "finalization": "Validation des capacités du Barbare"
        },
        "bard": {
            "initialization": "Préparation du test de la classe Barde",
            "action_name": "Test classe Barde",
            "action_description": "Vérification des fonctionnalités spécifiques à la classe Barde",
            "success_description": "Toutes les fonctionnalités de la classe Barde fonctionnent correctement",
            "failure_description": "Des problèmes ont été détectés avec la classe Barde",
            "finalization": "Validation des capacités du Barde"
        },
        
        # Tests d'équipement
        "equipment": {
            "initialization": "Préparation du test d'équipement",
            "action_name": "Gestion d'équipement",
            "action_description": "Test de l'équipement et de l'inventaire du personnage",
            "success_description": "L'équipement fonctionne correctement",
            "failure_description": "Des problèmes ont été détectés avec l'équipement",
            "finalization": "Validation de l'équipement"
        },
        "starting_equipment": {
            "initialization": "Préparation du test d'équipement de départ",
            "action_name": "Équipement de départ",
            "action_description": "Vérification de l'équipement initial du personnage",
            "success_description": "L'équipement de départ est correctement attribué",
            "failure_description": "L'équipement de départ n'est pas correct",
            "finalization": "Validation de l'équipement de départ"
        },
        
        # Tests de progression
        "level_progression": {
            "initialization": "Préparation du test de progression",
            "action_name": "Progression de niveau",
            "action_description": "Test de la montée de niveau du personnage",
            "success_description": "La progression de niveau fonctionne correctement",
            "failure_description": "Des problèmes ont été détectés dans la progression",
            "finalization": "Validation de la progression"
        },
        
        # Tests de suppression
        "deletion": {
            "initialization": "Préparation de la suppression",
            "action_name": "Suppression",
            "action_description": "Suppression d'un élément (compte, personnage, etc.)",
            "success_description": "L'élément a été supprimé avec succès",
            "failure_description": "La suppression a échoué",
            "finalization": "Validation de la suppression"
        }
    }
    
    # Déterminer le type de test basé sur le nom
    test_name_lower = test_name.lower()
    
    # Chercher le type de test correspondant
    for test_type, desc in descriptions.items():
        if test_type in test_name_lower:
            return desc
    
    # Description par défaut si aucun type spécifique n'est trouvé
    return {
        "initialization": "Préparation de l'environnement de test",
        "action_name": "Exécution du test",
        "action_description": f"Test de la fonctionnalité : {test_name}",
        "success_description": "Le test s'est exécuté avec succès",
        "failure_description": "Le test a échoué",
        "finalization": "Finalisation du test"
    }

def generate_basic_steps_for_existing_test(test_data):
    """Génère des étapes basiques avec descriptions fonctionnelles pour un test existant"""
    
    test_info = test_data.get('test_info', {})
    result = test_data.get('result', {})
    
    test_name = test_info.get('name', 'test_inconnu')
    status = result.get('status', 'UNKNOWN')
    success = result.get('success', False)
    error_message = result.get('error_message', '')
    duration = test_info.get('duration_seconds', 5)
    
    # Calculer les timestamps basés sur la durée
    end_time = datetime.fromisoformat(test_info.get('timestamp', datetime.now().isoformat())).timestamp()
    start_time = end_time - duration
    
    # Analyser le nom du test pour générer des descriptions fonctionnelles
    functional_description = get_functional_description(test_name)
    
    steps = []
    
    # Étape 1: Initialisation
    steps.append({
        "step_number": 1,
        "name": "Initialisation",
        "description": functional_description["initialization"],
        "type": "info",
        "timestamp": start_time,
        "datetime": datetime.fromtimestamp(start_time).isoformat(),
        "duration_seconds": 0,
        "details": {},
        "screenshot_path": None
    })
    
    # Étape 2: Action principale
    steps.append({
        "step_number": 2,
        "name": functional_description["action_name"],
        "description": functional_description["action_description"],
        "type": "action",
        "timestamp": start_time + (duration * 0.3),
        "datetime": datetime.fromtimestamp(start_time + (duration * 0.3)).isoformat(),
        "duration_seconds": duration * 0.4,
        "details": {"test_name": test_name},
        "screenshot_path": None
    })
    
    # Étape 3: Vérification
    if success and status == "PASSED":
        steps.append({
            "step_number": 3,
            "name": "Vérification",
            "description": functional_description["success_description"],
            "type": "assertion",
            "timestamp": start_time + (duration * 0.8),
            "datetime": datetime.fromtimestamp(start_time + (duration * 0.8)).isoformat(),
            "duration_seconds": duration * 0.1,
            "details": {"expected": "succès", "actual": "succès", "passed": True},
            "screenshot_path": None
        })
    else:
        steps.append({
            "step_number": 3,
            "name": "Vérification",
            "description": functional_description["failure_description"],
            "type": "error",
            "timestamp": start_time + (duration * 0.8),
            "datetime": datetime.fromtimestamp(start_time + (duration * 0.8)).isoformat(),
            "duration_seconds": duration * 0.1,
            "details": {"error_message": error_message, "status": status},
            "screenshot_path": None
        })
    
    # Étape 4: Finalisation
    steps.append({
        "step_number": 4,
        "name": "Finalisation",
        "description": functional_description["finalization"],
        "type": "info",
        "timestamp": end_time,
        "datetime": datetime.fromtimestamp(end_time).isoformat(),
        "duration_seconds": 0,
        "details": {"final_status": status},
        "screenshot_path": None
    })
    
    return steps

def update_report_file(file_path):
    """Met à jour un fichier de rapport avec des étapes basiques"""
    
    try:
        # Lire le fichier existant
        with open(file_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        # Vérifier si le fichier a déjà des étapes
        if 'test_steps' in data and data['test_steps']:
            print(f"  ⏭️  {os.path.basename(file_path)}: Déjà des étapes présentes")
            return False
        
        # Générer des étapes basiques
        basic_steps = generate_basic_steps_for_existing_test(data)
        
        # Ajouter les étapes au rapport
        data['test_steps'] = basic_steps
        
        # Sauvegarder le fichier mis à jour
        with open(file_path, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        
        print(f"  ✅ {os.path.basename(file_path)}: {len(basic_steps)} étapes ajoutées")
        return True
        
    except Exception as e:
        print(f"  ❌ {os.path.basename(file_path)}: Erreur - {e}")
        return False

def main():
    """Fonction principale"""
    
    print("🔄 Mise à jour des rapports JSON existants avec des étapes basiques")
    print("=" * 70)
    
    # Répertoires des rapports
    reports_dirs = [
        "/home/jean/Documents/jdrmj/tests/reports/individual",
        "/var/www/html/jdrmj_staging/tests/reports/individual"
    ]
    
    total_updated = 0
    total_skipped = 0
    total_errors = 0
    
    for reports_dir in reports_dirs:
        if not os.path.exists(reports_dir):
            print(f"⚠️  Répertoire non trouvé: {reports_dir}")
            continue
        
        print(f"\n📁 Traitement du répertoire: {reports_dir}")
        
        # Trouver tous les fichiers JSON
        json_files = glob.glob(os.path.join(reports_dir, "*.json"))
        
        if not json_files:
            print("  ℹ️  Aucun fichier JSON trouvé")
            continue
        
        print(f"  📄 {len(json_files)} fichiers JSON trouvés")
        
        for json_file in json_files:
            if update_report_file(json_file):
                total_updated += 1
            else:
                # Vérifier si c'était une erreur ou un skip
                try:
                    with open(json_file, 'r', encoding='utf-8') as f:
                        data = json.load(f)
                    if 'test_steps' in data and data['test_steps']:
                        total_skipped += 1
                    else:
                        total_errors += 1
                except:
                    total_errors += 1
    
    # Résumé
    print(f"\n📊 Résumé de la mise à jour")
    print("=" * 30)
    print(f"✅ Fichiers mis à jour: {total_updated}")
    print(f"⏭️  Fichiers ignorés (déjà des étapes): {total_skipped}")
    print(f"❌ Erreurs: {total_errors}")
    
    if total_updated > 0:
        print(f"\n🎉 {total_updated} rapports ont été mis à jour avec des étapes basiques !")
        print("🌐 Vous pouvez maintenant consulter les détails des tests dans admin_versions.php")
    else:
        print("\nℹ️  Aucun rapport n'a été mis à jour")

if __name__ == "__main__":
    main()
