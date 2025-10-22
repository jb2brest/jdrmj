#!/usr/bin/env python3
"""
Test de comparaison des APIs pour l'historique des jets de dés
"""

import requests
import json

def test_api_comparison():
    print("🧪 Test de comparaison des APIs")
    print("=" * 40)
    
    campaign_id = 120
    
    # Test 1: API à la racine
    print("📊 Test 1: API à la racine (get_dice_rolls_history.php)")
    try:
        url1 = f"http://localhost/jdrmj/get_dice_rolls_history.php?campaign_id={campaign_id}&show_hidden=false"
        response1 = requests.get(url1, timeout=10)
        print(f"  Status: {response1.status_code}")
        
        if response1.status_code == 200:
            try:
                data1 = response1.json()
                print(f"  Success: {data1.get('success')}")
                if data1.get('success'):
                    rolls1 = data1.get('rolls', [])
                    print(f"  Nombre de jets: {len(rolls1)}")
                else:
                    print(f"  Erreur: {data1.get('error')}")
            except json.JSONDecodeError:
                print("  ❌ Réponse n'est pas du JSON (probablement une redirection)")
                print(f"  Contenu: {response1.text[:100]}...")
        else:
            print(f"  ❌ Erreur HTTP: {response1.status_code}")
    except Exception as e:
        print(f"  ❌ Erreur: {e}")
    
    print()
    
    # Test 2: API dans le dossier api/
    print("📊 Test 2: API dans le dossier api/ (api/get_dice_rolls_history.php)")
    try:
        url2 = f"http://localhost/jdrmj/api/get_dice_rolls_history.php?campaign_id={campaign_id}&show_hidden=false"
        response2 = requests.get(url2, timeout=10)
        print(f"  Status: {response2.status_code}")
        
        if response2.status_code == 200:
            try:
                data2 = response2.json()
                print(f"  Success: {data2.get('success')}")
                if data2.get('success'):
                    rolls2 = data2.get('rolls', [])
                    print(f"  Nombre de jets: {len(rolls2)}")
                else:
                    print(f"  Erreur: {data2.get('error')}")
            except json.JSONDecodeError:
                print("  ❌ Réponse n'est pas du JSON")
                print(f"  Contenu: {response2.text[:100]}...")
        else:
            print(f"  ❌ Erreur HTTP: {response2.status_code}")
    except Exception as e:
        print(f"  ❌ Erreur: {e}")
    
    print()
    
    # Test 3: Vérifier les différences
    print("📊 Test 3: Analyse des différences")
    print("  - L'API à la racine nécessite une session active")
    print("  - L'API dans api/ fonctionne sans session (pour les tests)")
    print("  - Le JavaScript dans view_place.js utilise l'API dans api/")
    print("  - L'ancien fichier view_place_old.php utilise l'API à la racine")
    
    print("\n🎯 Solution:")
    print("  - Modifier le JavaScript pour utiliser l'API à la racine")
    print("  - Ou modifier l'API dans api/ pour nécessiter une session")

if __name__ == "__main__":
    test_api_comparison()
