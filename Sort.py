import csv
import requests
import time
import random



class Sort:
    def __init__(self, id):
        self.id = id
        self.nom = ""   
        self.cle = ""   
        self.ecole = ""
        self.description = ""
        self.temps_incantation = ""
        self.portee = ""
        self.composantes = ""
        self.duree = ""
        self.classe = ""
        self.source = ""
    
    def afficher_sort(self):
        print("___________")
        print("Nom :" + self.nom)   
        print("Cle :" + self.cle)
        print("Ecole :" + self.ecole)
        print("Description :" + self.description)   
        print("Temps d'incantation :" + self.temps_incantation)
        print("Portee :" + self.portee)
        print("Composantes :" + self.composantes)
        print("Duree :" + self.duree)
        print("Classe :" + self.classe)
        print("Source :" + self.source)
        print("___________")
    
    def exportcsv(self):
        with open('./aidednddata/sorts.csv', 'a') as f:
            writer = csv.writer(f)
            writer.writerow([self.id, self.nom, self.cle, self.ecole, self.description, self.temps_incantation, self.portee, self.composantes, self.duree, self.classe, self.source])
    

    @staticmethod
    def purger_csv():
        with open('./aidednddata/sorts.csv', 'w') as f:
            f.truncate(0)
            writer = csv.writer(f)
            writer.writerow(['Id', 'Nom', 'Cle', 'Ecole', 'Description', 'Temps d\'incantation', 'Portee', 'Composantes', 'Duree', 'Classe', 'Source'])
         

class Sorts:
    def __init__(self):
        self.sort_last_id = 0
        self.sorts = []  
        self.sorts_detail = []

    def afficher_sorts(self):
        for sort in self.sorts_detail:
            sort.afficher_sort()

    def charger_sorts(self):
        # URL à interroger
        url = "https://www.aidedd.org/dnd-filters/sorts.php"  # Remplace par l'URL de ton choix
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
                                i4 = i3.split("target='_blank'>")
                                for i5 in i4:
                                    if "http" in i5:
                                        i6 = i5.replace('"', '')
                                        if compteur < limite:   
                                            compteur += 1
                                            self.sorts.append(i6.split("class=''")[0])
            for sort in self.sorts:
                self.sorts_detail.append(self.charger_detail_sort(sort))   
                time.sleep(random.randint(500,1000)/1000)
        except requests.exceptions.RequestException as e:
            print(f"❌ Erreur lors de la requête : {e}")

    def charger_detail_sort(self, sort1):
        try:
            headers = {
                "User-Agent": "Mozilla/5.0"
            }
            print(f"Appel: {sort1}")
            response = requests.get(sort1, headers=headers)
            sort = Sort(self.sort_last_id )
            self.sort_last_id += 1
            sort.cle = sort1.split("https://www.aidedd.org/dnd/sorts.php?vf=")[1]
            # Vérification du code HTTP
            print(f"Code HTTP : {response.status_code}")

            # Affichage du contenu brut
            print("Contenu de la réponse :\n")
            body_trouve = 0
            for line in response.iter_lines(decode_unicode=True):
                if "<body" in line:
                    body_trouve = 1
                if body_trouve == 1:
                    ligne = line.split("<div")
                    for i in ligne:
                        if "<h1>" in i:
                            i2 = i.split("<h1>")   
                            sort.nom = i2[1].split("</h1>")[0]  
                        if "class='ecole'>" in i:
                            i2 = i.split("class='ecole'>")   
                            sort.ecole = i2[1].split("</div>")[0]    
                        if "class='t'>" in i:
                            i2 = i.split("class='t'>")   
                            sort.temps_incantation = i2[1].split("</div>")[0].split("</strong> : ")[1]  
                        if "class='r'>" in i:
                            i2 = i.split("class='r'>")   
                            sort.portee = i2[1].split("</div>")[0].split("</strong> : ")[1]   
                        if "class='c'>" in i:
                            i2 = i.split("class='c'>")   
                            sort.composantes = i2[1].split("</div>")[0].split("</strong> : ")[1]    
                        if "class='d'>" in i:
                            i2 = i.split("class='d'>")   
                            sort.duree = i2[1].split("</div>")[0].split("</strong> : ")[1]  
                        if "class='description'>" in i:
                            i2 = i.split("class='description'>")   
                            description = i2[1].split("</div>")[0]
                            description = description.replace("<br>", "\n")
                            description = description.replace("<strong>", "")
                            description = description.replace("</strong>", "") 
                            description = description.replace("<em>", "")
                            description = description.replace("</em>", "") 
                            sort.description = description 
                        if "class='classe'>" in i:
                            i2 = i.split("class='classe'>")   
                            classe = i2[1].split("</div>")[0]
                            sort.classe = sort.classe + " " + classe 
                        if "class='source'>" in i:
                            i2 = i.split("class='source'>")
                            source = i2[1].split("</div>")[0]
                            sort.source = source 

            return sort
        except requests.exceptions.RequestException as e:
            print(f"❌ Erreur lors de la requête : {e}")
    
    def exporter_sorts(self):
        for sort in self.sorts_detail:
            sort.exportcsv()

    def purger_csv(self):
        Sort.purger_csv()
    