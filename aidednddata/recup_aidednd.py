import requests

# URL à interroger
url = "https://www.aidedd.org/dnd-filters/monstres.php"  # Remplace par l'URL de ton choix
liste_monstres = []
limite = 1
compteur = 0
# Envoi de la requête GET
# on commence par récupérer la liste des monstres avec l'url d'accès à la liste détaillée. 

try:
    headers = {
        "User-Agent": "Mozilla/5.0"
    }
    response = requests.get(url, headers=headers)

    # Vérification du code HTTP
    print(f"Code HTTP : {response.status_code}")

    # Affichage du contenu brut
    print("Contenu de la réponse :\n")
    for line in response.iter_lines(decode_unicode=True):
        if line:  # Ignore les lignes vides
            if "item" in line: 
                ligne = line.split("</tr>")
                for i in ligne:
                    i2 = i.split("<a href=")
                    for i3 in i2:
                        i4 = i3.split("target='_blank'>")
                        for i5 in i4:
                            if "http" in i5:
                                i6 = i5.replace('"', '')
                                if compteur < limite:   
                                    compteur += 1
                                    liste_monstres.append(i6)


except requests.exceptions.RequestException as e:
    print(f"❌ Erreur lors de la requête : {e}")

for monstre in liste_monstres:
    try:
        headers = {
            "User-Agent": "Mozilla/5.0"
        }
        response = requests.get(monstre, headers=headers)
        nom = ""
        type = ""
        taille = ""
        alignement = ""
        ca = "" 
        pv = ""
        force = ""
        dexterite = ""
        constitution = ""
        intelligence = ""
        sagesse = ""
        charisme = ""
        competences = ""
        sens = ""
        langues = ""
        fp = "" 
        att_special = []
        description_att_special = []
        action = []
        description_action = []
        # Vérification du code HTTP
        print(f"Code HTTP : {response.status_code}")

        # Affichage du contenu brut
        print("Contenu de la réponse :\n")
        body_trouve = 0
        action_trouve = 0
        for line in response.iter_lines(decode_unicode=True):
            if "<body>" in line:
                body_trouve = 1
            if body_trouve == 1:
                ligne = line.split("<div")
                for i in ligne:
                    if "<h1>" in i:
                        i2 = i.split("<h1>")   
                        nom = i2[1].split("</h1>")[0]    
                    if "class='type'>" in i:
                        i2 = i.split("class='type'>")   
                        i3 = i2[1].split("de taille")
                        type = i3[0]  
                        taille = i3[1].split(",")[0]
                        alignement = i3[1].split(",")[1].split("</div>")[0]              
                    if "<strong>Classe d'armure</strong>" in i:
                        i2 = i.split("<strong>Classe d'armure</strong>")
                        i3 = i2[1].split("<br><strong>Points de vie</strong> ")
                        ca = i3[0]
                        pv = i3[1].split("<br><strong>Vitesse</strong> ")[0]
                    if "class='carac'><strong>FOR</strong><br>" in i:
                        i2 = i.split("class='carac'><strong>FOR</strong><br>")
                        force = i2[1].split("</div>")[0]
                    if "class='carac'><strong>DEX</strong><br>" in i:
                        i2 = i.split("class='carac'><strong>DEX</strong><br>")
                        dexterite = i2[1].split("</div>")[0]
                    if "class='carac'><strong>CON</strong><br>" in i:
                        i2 = i.split("class='carac'><strong>CON</strong><br>")
                        constitution = i2[1].split("</div>")[0]
                    if "class='carac'><strong>INT</strong><br>" in i:
                        i2 = i.split("class='carac'><strong>INT</strong><br>")
                        intelligence = i2[1].split("</div>")[0]
                    if "class='carac'><strong>SAG</strong><br>" in i:
                        i2 = i.split("class='carac'><strong>SAG</strong><br>")
                        sagesse = i2[1].split("</div>")[0]
                    if "class='carac'><strong>CHA</strong><br>" in i:
                        i2 = i.split("class='carac'><strong>CHA</strong><br>")
                        charisme = i2[1].split("</div>")[0]
                    if "<strong>Compétences</strong>" in i:
                        i2 = i.split("<strong>Compétences</strong>")
                        i3 = i2[1].split("<br><strong>")
                        competences = i3[0]
                        i4 = i.split("Sens</strong>")
                        i5 = i4[1].split("<br><strong>")
                        sens = i5[0]
                        i4 = i.split("Langues</strong>")
                        i5 = i4[1].split("<br><strong>")
                        langues = i5[0]
                        i4 = i.split("Puissance</strong>")
                        i5 = i4[1].split("</div>")
                        fp = i5[0]
                    if "class='rub'>Actions</div>" in i:
                        action_trouve = 1
                    if fp != "" and action_trouve == 0:
                        if "<p><strong><em>" in i:
                            i2 = i.split("<p><strong><em>")
                            i3 = i2[1].split("</em></strong>.")
                            att_special.append(i3[0])
                            description_att_special.append(i3[1].split("</p>")[0])

                    if fp != "" and action_trouve == 1:
                        if "<p><strong><em>" in i:
                            i2 = i.split("<p><strong><em>")
                            i3 = i2[1].split("</em></strong>.")
                            action.append(i3[0])
                            print(i)
                            print("_______")
                            print(action)
                            print(i3[1])
                            description_action.append(i3[1].split("</p>")[0])

        


                    #print(i) 
                    
                 
            print(type, taille, alignement, ca, pv)
            print(force,dexterite,constitution,intelligence,sagesse,charisme)
            print(competences,sens)
            print(langues,fp)
            print(att_special)
            print(description_att_special)
            print(action)
            print(description_action)


                    



    except requests.exceptions.RequestException as e:
        print(f"❌ Erreur lors de la requête : {e}")