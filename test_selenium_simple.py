#!/usr/bin/env python3
"""
Test Selenium simple pour l'historique des jets de dés
"""

import requests
import json

def test_dice_history_api():
    print("🧪 Test de l'API des jets de dés")
    print("=" * 40)
    
    # Test direct de l'API
    api_url = "http://localhost/jdrmj/api/get_dice_rolls_history.php?campaign_id=120&show_hidden=false"
    print(f"🌐 Test de l'API: {api_url}")
    
    try:
        response = requests.get(api_url, timeout=10)
        print(f"📊 Status: {response.status_code}")
        print(f"📄 Response: {response.text}")
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                rolls = data.get('rolls', [])
                print(f"✅ API fonctionne - {len(rolls)} jets trouvés")
                for roll in rolls:
                    print(f"  - {roll.get('dice_type')}: {roll.get('total')} (par {roll.get('username')})")
            else:
                print(f"❌ Erreur API: {data.get('error')}")
        else:
            print(f"❌ Erreur HTTP: {response.status_code}")
            
    except Exception as e:
        print(f"❌ Erreur: {e}")

def test_page_content():
    print("\n🧪 Test du contenu de la page")
    print("=" * 40)
    
    # Test de la page principale
    page_url = "http://localhost/jdrmj/view_place.php?id=154"
    print(f"🌐 Test de la page: {page_url}")
    
    try:
        response = requests.get(page_url, timeout=10)
        print(f"📊 Status: {response.status_code}")
        
        if response.status_code == 200:
            content = response.text
            print("✅ Page accessible")
            
            # Vérifier la présence d'éléments clés
            if 'Jets de dés' in content:
                print("✅ Section 'Jets de dés' trouvée")
            else:
                print("❌ Section 'Jets de dés' manquante")
                
            if 'dice-history' in content:
                print("✅ Div 'dice-history' trouvée")
            else:
                print("❌ Div 'dice-history' manquante")
                
            if 'window.campaignId' in content:
                print("✅ Variable JavaScript 'campaignId' trouvée")
            else:
                print("❌ Variable JavaScript 'campaignId' manquante")
        else:
            print(f"❌ Erreur HTTP: {response.status_code}")
            
    except Exception as e:
        print(f"❌ Erreur: {e}")

if __name__ == "__main__":
    test_dice_history_api()
    test_page_content()
    print("\n🎯 Tests terminés!")
