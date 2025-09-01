import csv
import requests
import time
import random



class Herbe:
    def __init__(self, id):
        self.id = id
        self.nom = ""   
        self.cle = ""   
        self.description = ""   
        self.type = ""
        self.source = ""
    
    def afficher_herbe(self):
        print("___________")
        print("Nom :" + self.nom)   
        print("Cle :" + self.cle)
        print("Description :" + self.description)   
        print("Type :" + self.type)
        print("Source :" + self.source)
        print("___________")
    
    def exportcsv(self):
        with open('./aidednddata/herbes.csv', 'a') as f:
            writer = csv.writer(f)
            writer.writerow([self.id, self.nom, self.cle, self.description, self.type, self.source])
    

    @staticmethod
    def purger_csv():
        with open('./aidednddata/herbes.csv', 'w') as f:
            f.truncate(0)
            writer = csv.writer(f)
            writer.writerow(['Id', 'Nom', 'Cle', 'Description', 'type', 'Source'])
         

class Herbes:
    def __init__(self):
        self.herbe_last_id = 0   
        self.herbes = []  
        self.herbes_detail = []

    def afficher_herbes(self):
        for herbe in self.herbes_detail:
            herbe.afficher_herbe()

    def charger_herbes(self):
        # URL à interroger
        url = "https://www.aidedd.org/dnd-filters/herbes.php"  # Remplace par l'URL de ton choix
        limite = 10000
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
                                i4 = i3.split("target='_blank'")
                                for i5 in i4:
                                    if "http" in i5:
                                        i6 = i5.replace('"', '')
                                        if compteur < limite:   
                                            compteur += 1
                                            self.herbes.append(i6.split("class=''")[0])
            for herbe in self.herbes:
                self.herbes_detail.append(self.charger_detail_herbe(herbe))   
                time.sleep(random.randint(500,1000)/1000)
        except requests.exceptions.RequestException as e:
            print(f"❌ Erreur lors de la requête : {e}")

    def charger_detail_herbe(self, herbe1): 
        try:
            headers = {
                "User-Agent": "Mozilla/5.0"
            }
            print(f"Appel: {herbe1}")
            response = requests.get(herbe1, headers=headers)
            herbe = Herbe(self.herbe_last_id )
            self.herbe_last_id += 1
            herbe.cle = herbe1.split("https://www.aidedd.org/dnd/herbes.php?vf=")[1]
            # Vérification du code HTTP
            print(f"Code HTTP : {response.status_code}")

            # Affichage du contenu brut
            print("Contenu de la réponse :\n")
            body_trouve = 0
            description_commencee = 0
            for line in response.iter_lines(decode_unicode=True):
                if "<body" in line:
                    body_trouve = 1
                if body_trouve == 1:
                    ligne = line.split("<div")
                    for i in ligne:
                        if "<h1>" in i:
                            i2 = i.split("<h1>")   
                            herbe.nom = i2[1].split("</h1>")[0]   
                        if "class='type'>" in i:
                            i2 = i.split("class='type'>")[1].split("</div>")[0]
                            i2 = i2.replace("<br>&#x21AA;", "\n")
                            herbe.type = i2 
                        if "class='description'>" in i:
                            description_commencee = 1
                            description = i.split("class='description'>")[1]  
                            description = description.split("height='64'>")[1]
                            description = description.replace("<br>", "\n")
                            description = description.replace("<strong>", "")
                            description = description.replace("</strong>", "") 
                            description = description.replace("<em>", "")
                            description = description.replace("</em>", "") 
                            description = description.replace("</div>", "") 
                            herbe.description = description 
                        if description_commencee == 1:
                            if "</div>" in i:
                                description_commencee = 0
                                description = i 
                                description = description.replace("<br>", "\n")
                                description = description.replace("<strong>", "")
                                description = description.replace("</strong>", "") 
                                description = description.replace("<em>", "")
                                description = description.replace("</em>", "") 
                                description = description.replace("</div>", "") 
                                herbe.description = herbe.description + "\n" + description
                        if "class='source'>" in i:
                            i2 = i.split("class='source'>")
                            source = i2[1].split("</div>")[0]
                            herbe.source = source 

            return herbe
        except requests.exceptions.RequestException as e:
            print(f"❌ Erreur lors de la requête : {e}")
    
    def exporter_herbes(self):
        for herbe in self.herbes_detail:
            herbe.exportcsv()

    def purger_csv(self):
        Herbe.purger_csv()
    