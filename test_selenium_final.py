#!/usr/bin/env python3
"""
Test Selenium final pour l'historique des jets de dés
"""

import requests
import json
import time

def test_dice_history_comparison():
    print("🧪 Test de comparaison des APIs des jets de dés")
    print("=" * 50)
    
    campaign_id = 120
    
    # Test 1: API à la racine (ancien fichier)
    print("📊 Test 1: API à la racine (get_dice_rolls_history.php)")
    try:
        url1 = f"http://localhost/jdrmj/get_dice_rolls_history.php?campaign_id={campaign_id}&show_hidden=false"
        response1 = requests.get(url1, timeout=10)
        print(f"  Status: {response1.status_code}")
        if response1.status_code == 200:
            data1 = response1.json()
            print(f"  Success: {data1.get('success')}")
            if data1.get('success'):
                rolls1 = data1.get('rolls', [])
                print(f"  Nombre de jets: {len(rolls1)}")
                for roll in rolls1:
                    print(f"    - {roll.get('dice_type')}: {roll.get('total')} (par {roll.get('username')})")
            else:
                print(f"  Erreur: {data1.get('error')}")
        else:
            print(f"  Erreur HTTP: {response1.status_code}")
    except Exception as e:
        print(f"  Erreur: {e}")
    
    print()
    
    # Test 2: API dans le dossier api/ (nouveau fichier)
    print("📊 Test 2: API dans le dossier api/ (api/get_dice_rolls_history.php)")
    try:
        url2 = f"http://localhost/jdrmj/api/get_dice_rolls_history.php?campaign_id={campaign_id}&show_hidden=false"
        response2 = requests.get(url2, timeout=10)
        print(f"  Status: {response2.status_code}")
        if response2.status_code == 200:
            data2 = response2.json()
            print(f"  Success: {data2.get('success')}")
            if data2.get('success'):
                rolls2 = data2.get('rolls', [])
                print(f"  Nombre de jets: {len(rolls2)}")
                for roll in rolls2:
                    print(f"    - {roll.get('dice_type')}: {roll.get('total')} (par {roll.get('username')})")
            else:
                print(f"  Erreur: {data2.get('error')}")
        else:
            print(f"  Erreur HTTP: {response2.status_code}")
    except Exception as e:
        print(f"  Erreur: {e}")
    
    print()
    
    # Test 3: Comparaison des réponses
    print("📊 Test 3: Comparaison des réponses")
    try:
        if response1.status_code == 200 and response2.status_code == 200:
            data1 = response1.json()
            data2 = response2.json()
            
            if data1.get('success') and data2.get('success'):
                rolls1 = data1.get('rolls', [])
                rolls2 = data2.get('rolls', [])
                
                print(f"  API racine: {len(rolls1)} jets")
                print(f"  API api/: {len(rolls2)} jets")
                
                if len(rolls1) == len(rolls2):
                    print("  ✅ Même nombre de jets")
                else:
                    print("  ❌ Nombre de jets différent")
            else:
                print("  ❌ Une ou les deux APIs ont échoué")
        else:
            print("  ❌ Une ou les deux APIs ne sont pas accessibles")
    except Exception as e:
        print(f"  Erreur: {e}")

def test_page_content():
    print("\n🧪 Test du contenu des pages")
    print("=" * 30)
    
    # Test de l'ancienne page
    print("📊 Test de view_place_old.php")
    try:
        response = requests.get("http://localhost/jdrmj/view_place_old.php?id=154", timeout=10)
        print(f"  Status: {response.status_code}")
        
        if response.status_code == 200:
            content = response.text
            if 'Jets de dés' in content:
                print("  ✅ Section 'Jets de dés' trouvée")
            else:
                print("  ❌ Section 'Jets de dés' manquante")
                
            if 'dice-history' in content:
                print("  ✅ Div 'dice-history' trouvée")
            else:
                print("  ❌ Div 'dice-history' manquante")
                
            if 'window.campaignId' in content:
                print("  ✅ Variable JavaScript 'campaignId' trouvée")
            else:
                print("  ❌ Variable JavaScript 'campaignId' manquante")
        else:
            print(f"  ❌ Erreur HTTP: {response.status_code}")
    except Exception as e:
        print(f"  ❌ Erreur: {e}")
    
    print()
    
    # Test de la nouvelle page
    print("📊 Test de view_place.php")
    try:
        response = requests.get("http://localhost/jdrmj/view_place.php?id=154", timeout=10)
        print(f"  Status: {response.status_code}")
        
        if response.status_code == 200:
            content = response.text
            if 'Jets de dés' in content:
                print("  ✅ Section 'Jets de dés' trouvée")
            else:
                print("  ❌ Section 'Jets de dés' manquante")
                
            if 'dice-history' in content:
                print("  ✅ Div 'dice-history' trouvée")
            else:
                print("  ❌ Div 'dice-history' manquante")
                
            if 'window.campaignId' in content:
                print("  ✅ Variable JavaScript 'campaignId' trouvée")
            else:
                print("  ❌ Variable JavaScript 'campaignId' manquante")
        else:
            print(f"  ❌ Erreur HTTP: {response.status_code}")
    except Exception as e:
        print(f"  ❌ Erreur: {e}")

if __name__ == "__main__":
    test_dice_history_comparison()
    test_page_content()
    print("\n🎯 Tests terminés!")
