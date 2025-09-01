import csv
import requests
import time
import random



class ManifestationOcculte:
    def __init__(self, id):
        self.id = id
        self.nom = ""   
        self.cle = ""   
        self.description = ""
        self.prerequis = ""
        self.source = ""
    
    def afficher_manifestation_occulte(self):
        print("___________")
        print("Nom :" + self.nom)   
        print("Cle :" + self.cle)
        print("Description :" + self.description)   
        print("Prerequis :" + self.prerequis)
        print("Source :" + self.source)
        print("___________")
    
    def exportcsv(self):
        with open('./aidednddata/manifestation_occulte.csv', 'a') as f:
            writer = csv.writer(f)
            writer.writerow([self.id, self.nom, self.cle, self.description, self.prerequis, self.source])
    

    @staticmethod
    def purger_csv():
        with open('./aidednddata/manifestation_occulte.csv', 'w') as f:
            f.truncate(0)
            writer = csv.writer(f)
            writer.writerow(['Id', 'Nom', 'Cle', 'Description', 'Prerequis', 'Source'])
         

class ManifestationOccultes:
    def __init__(self):
        self.manifestation_occulte_last_id = 0   
        self.manifestation_occulte = []  
        self.manifestation_occulte_detail = []

    def afficher_manifestation_occulte(self):
        for manifestation_occulte in self.manifestation_occulte_detail:
            manifestation_occulte.afficher_manifestation_occulte()

    def charger_manifestation_occulte(self):
        # URL à interroger
        url = "https://www.aidedd.org/dnd-filters/manifestations-occultes.php"  # Remplace par l'URL de ton choix
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
                                i4 = i3.split("target='_blank'")
                                for i5 in i4:
                                    if "http" in i5:
                                        i6 = i5.replace('"', '')
                                        if compteur < limite:   
                                            compteur += 1
                                            self.manifestation_occulte.append(i6.split("class=''")[0])
            for manifestation_occulte in self.manifestation_occulte:
                self.manifestation_occulte_detail.append(self.charger_detail_manifestation_occulte(manifestation_occulte))   
                time.sleep(random.randint(500,1000)/1000)
        except requests.exceptions.RequestException as e:
            print(f"❌ Erreur lors de la requête : {e}")

    def charger_detail_manifestation_occulte(self, manifestation_occulte1): 
        try:
            headers = {
                "User-Agent": "Mozilla/5.0"
            }
            print(f"Appel: {manifestation_occulte1}")
            response = requests.get(manifestation_occulte1, headers=headers)
            manifestation_occulte = ManifestationOcculte(self.manifestation_occulte_last_id )
            self.manifestation_occulte_last_id += 1
            manifestation_occulte.cle = manifestation_occulte1.split("https://www.aidedd.org/dnd/invocations.php?vf=")[1]
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
                            manifestation_occulte.nom = i2[1].split("</h1>")[0]    
                        if "class='prerequis'>" in i:
                            i2 = i.split("class='prerequis'>Prérequis : ")[1].split("</div>")[0]
                            manifestation_occulte.prerequis = i2
                        if "class='description'>" in i:
                            description_commencee = 1
                            description = i.split("class='description'>")[1]  
                            description = description.replace("<br>", "\n")
                            description = description.replace("<strong>", "")
                            description = description.replace("</strong>", "") 
                            description = description.replace("<em>", "")
                            description = description.replace("</em>", "") 
                            description = description.replace("</div>", "") 
                            manifestation_occulte.description = description 
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
                                manifestation_occulte.description = manifestation_occulte.description + "\n" + description
                        if "class='source'>" in i:
                            i2 = i.split("class='source'>")
                            source = i2[1].split("</div>")[0]
                            manifestation_occulte.source = source 

            return manifestation_occulte
        except requests.exceptions.RequestException as e:
            print(f"❌ Erreur lors de la requête : {e}")
    
    def exporter_manifestation_occulte(self):
        for manifestation_occulte in self.manifestation_occulte_detail:
            manifestation_occulte.exportcsv()

    def purger_csv(self):
        ManifestationOcculte.purger_csv()
    