import csv
import requests
import time
import random

class AttSpecial:

    def __init__(self):
        self.nom = ""
        self.description = ""

class Action:

    def __init__(self):
        self.nom = ""   
        self.description = ""


class Sort:

    def __init__(self):
        self.nom = ""   
        self.cle = ""   
        self.description = ""

class Monstre:
    def __init__(self, id):
        self.id = id
        self.nom = ""   
        self.type = ""
        self.taille = ""
        self.alignement = ""
        self.ca = "" 
        self.pv = ""
        self.force = ""
        self.dexterite = ""
        self.constitution = ""
        self.intelligence = ""
        self.sagesse = ""
        self.charisme = ""
        self.competences = ""   
        self.jets_de_sauvegarde = ""
        self.immunités_aux_dégâts = ""
        self.resistances_aux_degats = ""
        self.immunités_aux_états = ""
        self.sens = ""
        self.langues = ""
        self.fp = "" 
        self.att_special = []
        self.action = []
        self.action_legendaire = []
        self.sorts_mineurs = []
        self.sorts_n1 = []
        self.sorts_n2 = []
        self.sorts_n3 = []
        self.sorts_n4 = []
        self.sorts_n5 = []
        self.sorts_n6 = []
        self.sorts_n7 = []
        self.sorts_n8 = []
        self.sorts_n9 = []
        self.sorts_n10 = []
        self.nb_sorts_mineurs = ""
        self.nb_sorts_n1 = ""
        self.nb_sorts_n2 = ""
        self.nb_sorts_n3 = ""
        self.nb_sorts_n4 = ""
        self.nb_sorts_n5 = ""
        self.nb_sorts_n6 = ""
        self.nb_sorts_n7 = ""
        self.nb_sorts_n8 = ""
        self.nb_sorts_n9 = ""
        self.nb_sorts_n10 = ""

    def afficher_monstre(self):
        print("___________")
        print("Nom :" + self.nom)   
        print("Type :" + self.type)
        print("Taille :" + self.taille)
        print("Alignement :" + self.alignement)
        print("CA :" + self.ca)
        print("PV :" + self.pv)
        print("Force :" +       self.force)
        print("Dexterite :" + self.dexterite)
        print("Constitution :" + self.constitution)    
        print("Intelligence :" + self.intelligence)
        print("Sagesse :" + self.sagesse)
        print("Charisme :" + self.charisme)
        print("Competences :" + self.competences)
        print("Jets de sauvegarde :" + self.jets_de_sauvegarde)
        print("Immunités aux dégâts :" + self.immunités_aux_dégâts)
        print("Résistances aux dégâts :" + self.resistances_aux_degats)
        print("Immunités aux états :" + self.immunités_aux_états)
        print("Sens :" + self.sens)
        print("Langues :" + self.langues)
        print("FP :" + self.fp)
        print("___________")
        print("Att_special :")
        for i in self.att_special:
            print("Nom :" + i.nom)
            print("Description :" + i.description)
        print("Sorts mineurs : (" + self.nb_sorts_mineurs + ")")    
        for i in self.sorts_mineurs:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("Sorts Niveau 1 : (" + self.nb_sorts_n1 + ")")    
        for i in self.sorts_n1:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("Sorts Niveau 2 : (" + self.nb_sorts_n2 + ")")    
        for i in self.sorts_n2:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("Sorts Niveau 3 : (" + self.nb_sorts_n3 + ")")    
        for i in self.sorts_n3:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("Sorts Niveau 4 : (" + self.nb_sorts_n4 + ")")    
        for i in self.sorts_n4:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("Sorts Niveau 5 : (" + self.nb_sorts_n5 + ")")    
        for i in self.sorts_n5:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("Sorts Niveau 6 : (" + self.nb_sorts_n6 + ")")    
        for i in self.sorts_n6:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("Sorts Niveau 7 : (" + self.nb_sorts_n7 + ")")    
        for i in self.sorts_n7:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("Sorts Niveau 8 : (" + self.nb_sorts_n8 + ")")    
        for i in self.sorts_n8:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("Sorts Niveau 9 : (" + self.nb_sorts_n9 + ")")    
        for i in self.sorts_n9:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("Sorts Niveau 10 : (" + self.nb_sorts_n10 + ")")    
        for i in self.sorts_n10:
            print("Nom :" + i.nom + " (Cle :" + i.cle + ")")
        print("___________")
        print("___________")
        print("Action :")
        for i in self.action:
            print("Nom :" + i.nom)
            print("Description :" + i.description)
        print("___________")  
        print("Action légendaire :")
        for i in self.action_legendaire:
            print("Nom :" + i.nom)
            print("Description :" + i.description)
        print("___________")  

    def exportcsv(self):
        with open('./aidednddata/monstre.csv', 'a') as f:
            writer = csv.writer(f)
            writer.writerow([self.id, self.nom, self.type, self.taille, self.alignement, self.ca, self.pv, self.force, self.dexterite, self.constitution, self.intelligence, self.sagesse, self.charisme, self.competences, self.jets_de_sauvegarde, self.immunités_aux_dégâts, self.resistances_aux_degats, self.immunités_aux_états, self.sens, self.langues, self.fp])
        with open('./aidednddata/monstres_att_special.csv', 'a') as f:
            writer = csv.writer(f)
            for i in self.att_special:
                writer.writerow([self.id, i.nom, i.description])
        with open('./aidednddata/monstres_sorts.csv', 'a') as f:
            writer = csv.writer(f)
            for i in self.sorts_mineurs:
                writer.writerow([self.id, i.nom, i.description])
            for i in self.sorts_n1:
                writer.writerow([self.id, i.nom, i.description])
            for i in self.sorts_n2:
                writer.writerow([self.id, i.nom, i.description])
            for i in self.sorts_n3:
                writer.writerow([self.id, i.nom, i.description])
            for i in self.sorts_n4:
                writer.writerow([self.id, i.nom, i.description])
            for i in self.sorts_n5:
                writer.writerow([self.id, i.nom, i.description])    
            for i in self.sorts_n6:
                writer.writerow([self.id, i.nom, i.description])
            for i in self.sorts_n7:
                writer.writerow([self.id, i.nom, i.description])
            for i in self.sorts_n8:
                writer.writerow([self.id, i.nom, i.description])
            for i in self.sorts_n9:
                writer.writerow([self.id, i.nom, i.description])
            for i in self.sorts_n10:
                writer.writerow([self.id, i.nom, i.description])
        with open('./aidednddata/monstres_action.csv', 'a') as f:
            writer = csv.writer(f)
            for i in self.action:
                writer.writerow([self.id, i.nom, i.description])
        with open('./aidednddata/monstres_action_legendaire.csv', 'a') as f:
            writer = csv.writer(f)
            for i in self.action_legendaire:
                writer.writerow([self.id, i.nom, i.description])
 
        
    @staticmethod
    def purger_csv():
        with open('./aidednddata/monstre.csv', 'w') as f:
            f.truncate(0)
            writer = csv.writer(f)
            writer.writerow(['Id', 'Nom', 'Type', 'Taille', 'Alignement', 'CA', 'PV', 'Force', 'Dexterite', 'Constitution', 'Intelligence', 'Sagesse', 'Charisme', 'Competences', 'Jets de sauvegarde', 'Immunités aux dégâts', 'Résistances aux dégâts', 'Immunités aux états', 'Sens', 'Langues', 'FP'])
           
        with open('./aidednddata/monstres_att_special.csv', 'w') as f:
            f.truncate(0)
            writer = csv.writer(f)
            writer.writerow(['Id', 'Nom', 'Description'])
        with open('./aidednddata/monstres_sorts.csv', 'w') as f:
            f.truncate(0)
            writer = csv.writer(f)
            writer.writerow(['Id', 'Nom', 'Description'])
        with open('./aidednddata/monstres_action.csv', 'w') as f:
            f.truncate(0)
            writer = csv.writer(f)
            writer.writerow(['Id', 'Nom', 'Description'])
        with open('./aidednddata/monstres_action_legendaire.csv', 'w') as f:
            f.truncate(0)
            writer = csv.writer(f)
            writer.writerow(['Id', 'Nom', 'Description'])

class Monstres:
    def __init__(self):
        self.monstre_last_id = 0
        self.monstres = []  
        self.monstres_detail = []
    
    def afficher_monstres(self):
        for monstre in self.monstres_detail:
            monstre.afficher_monstre()

    def charger_monstres(self):
        # URL à interroger
        url = "https://www.aidedd.org/dnd-filters/monstres.php"  # Remplace par l'URL de ton choix
        limite = 100000
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
                                            self.monstres.append(i6)
            for monstre in self.monstres:
                self.monstres_detail.append(self.charger_detail_monstres(monstre))   
                time.sleep(random.randint(500,1000)/1000)
        except requests.exceptions.RequestException as e:
            print(f"❌ Erreur lors de la requête : {e}")



    def charger_detail_monstres(self, monstre):
        try:
            headers = {
                "User-Agent": "Mozilla/5.0"
            }
            print(f"Appel: {monstre}")
            response = requests.get(monstre, headers=headers)
            monstre = Monstre(self.monstre_last_id )
            self.monstre_last_id += 1
            # Vérification du code HTTP
            print(f"Code HTTP : {response.status_code}")

            # Affichage du contenu brut
            print("Contenu de la réponse :\n")
            body_trouve = 0
            action_trouve = 0
            action_legendaire_trouve = 0
            particularite_trouve = 0
            for line in response.iter_lines(decode_unicode=True):
                if "<body>" in line:
                    body_trouve = 1
                if body_trouve == 1:
                    ligne = line.split("<div")
                    for i in ligne:
                        if "<h1>" in i:
                            i2 = i.split("<h1>")   
                            monstre.nom = i2[1].split("</h1>")[0]    
                        if "class='type'>" in i:
                            i2 = i.split("class='type'>")   
                            i3 = i2[1].split("de taille")
                            monstre.type = i3[0]  
                            monstre.taille = i3[1].split(",")[0]
                            monstre.alignement = i3[1].split(",")[1].split("</div>")[0]              
                        if "<strong>Classe d'armure</strong>" in i:
                            i2 = i.split("<strong>Classe d'armure</strong>")
                            i3 = i2[1].split("<br><strong>Points de vie</strong> ")
                            monstre.ca = i3[0]
                            monstre.pv = i3[1].split("<br><strong>Vitesse</strong> ")[0]
                        if "class='carac'><strong>FOR</strong><br>" in i:
                            i2 = i.split("class='carac'><strong>FOR</strong><br>")
                            monstre.force = i2[1].split("</div>")[0]
                        if "class='carac'><strong>DEX</strong><br>" in i:
                            i2 = i.split("class='carac'><strong>DEX</strong><br>")
                            monstre.dexterite = i2[1].split("</div>")[0]
                        if "class='carac'><strong>CON</strong><br>" in i:
                            i2 = i.split("class='carac'><strong>CON</strong><br>")
                            monstre.constitution = i2[1].split("</div>")[0]
                        if "class='carac'><strong>INT</strong><br>" in i:
                            i2 = i.split("class='carac'><strong>INT</strong><br>")
                            monstre.intelligence = i2[1].split("</div>")[0]
                        if "class='carac'><strong>SAG</strong><br>" in i:
                            i2 = i.split("class='carac'><strong>SAG</strong><br>")
                            monstre.sagesse = i2[1].split("</div>")[0]
                        if "class='carac'><strong>CHA</strong><br>" in i:
                            i2 = i.split("class='carac'><strong>CHA</strong><br>")
                            monstre.charisme = i2[1].split("</div>")[0]
                        if "<strong>Puissance</strong>" in i:
                            particularite_trouve = 1
                            self.charger_particularites(monstre, i)
                        if "class='rub'>Actions</div>" in i:
                            action_trouve = 1                        
                        if "class='rub'>Actions légendaires</div>" in i:
                            action_legendaire_trouve = 1
                        if particularite_trouve == 1 :
                            if action_trouve == 0:
                                self.charger_att_special(monstre, i)
                            else:
                                if action_legendaire_trouve == 0:
                                    self.charger_action(monstre.action, i)
                                else:
                                    self.charger_action(monstre.action_legendaire, i)

            return monstre
        except requests.exceptions.RequestException as e:
            print(f"❌ Erreur lors de la requête : {e}")


    def charger_particularites(self, monstre, i):
        i2 = i.split("<br><strong>")
        for i3 in i2:
            if "Résistances aux dégâts</strong>" in i3:
                monstre.resistances_aux_degats = self.format_particularite(i3)
            if "Jets de sauvegarde</strong>" in i3:
                monstre.jets_de_sauvegarde = self.format_particularite(i3)
            if "Immunités aux dégâts</strong>" in i3:
                monstre.immunités_aux_dégâts = self.format_particularite(i3)
            if "Immunités aux états</strong>" in i3:
                monstre.immunités_aux_états = self.format_particularite(i3)
            if "Sens</strong>" in i3:
                monstre.sens = self.format_particularite(i3)
            if "Langues</strong>" in i3:
                monstre.langues = self.format_particularite(i3)
            if "Puissance</strong>" in i3:
                monstre.fp = self.format_particularite(i3)
            if "Compétences</strong>" in i3:
                monstre.competences = self.format_particularite(i3)
        

    def format_particularite(self, i):
        if "</svg></div>" in i:
            i2 = i.split("</svg></div>")[1]
            i3 = i2.split("</strong>")
            return i3[1].replace("</div>", "")
        else:
            i2 = i.split("</strong>")
            return i2[1].replace("</div>", "")
  
    def charger_action(self, liste_action, i):
        if "<p><strong><em>" in i:
            cpt = 0
            i2 = i.split("<p><strong><em>")
            for j in i2:
                if cpt > 0:
                    i3 = j.split("</em></strong>.")
                    action = Action()
                    action.nom = i3[0]
                    action.description = i3[1].split("</p>")[0]
                    liste_action.append(action)
                cpt += 1
            

    def charger_att_special(self, monstre, i):
        if "<p><strong><em>" in i:
            cpt = 0
            i2 = i.split("<p><strong><em>")
            for j in i2:
                if cpt > 0:
                    i3 = j.split("</em></strong>.")
                    att_special = AttSpecial()
                    att_special.nom = i3[0]
                    att_special.description = i3[1].split("</p>")[0]
                    monstre.att_special.append(att_special)
                    if "sorts" in j and "suivants" in j:
                        self.identifie_sort(monstre, j)
                cpt += 1

    def identifie_sort(self, monstre, i):
        if "Sorts mineurs" in i:
            i2 = i.split("</p>")[1].split("</em>")
            for i3 in i2:
                if "Sorts mineurs" in i3:
                    monstre.nb_sorts_mineurs = self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_mineurs, i3)
                if "Niveau 1" in i3:
                    monstre.nb_sorts_n1= self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_n1, i3)
                if "Niveau 2" in i3:
                    monstre.nb_sorts_n2= self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_n2, i3)
                if "Niveau 3" in i3:
                    monstre.nb_sorts_n3= self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_n3, i3)
                if "Niveau 4" in i3:
                    monstre.nb_sorts_n4= self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_n4, i3)
                if "Niveau 5" in i3:
                    monstre.nb_sorts_n5= self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_n5, i3)
                if "Niveau 6" in i3:
                    monstre.nb_sorts_n6= self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_n6, i3)
                if "Niveau 7" in i3:
                    monstre.nb_sorts_n7= self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_n7, i3)
                if "Niveau 8" in i3:
                    monstre.nb_sorts_n8= self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_n8, i3)
                if "Niveau 9" in i3:
                    monstre.nb_sorts_n9= self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_n9, i3)
                if "Niveau 10" in i3:
                    monstre.nb_sorts_n10= self.cpt_sorts(i3)
                    self.liste_sorts(monstre.sorts_n10, i3)
        else:
            i2 = i.split(":</p>")
            i3 = i2[1].split("<br>")
            cpt = 0
            for i4 in i3:
                i5 = i4.split("<em>")
                if (len(i5) > 1):
                    if cpt == 0:
                        monstre.nb_sorts_mineurs = i5[0]
                        self.liste_sorts(monstre.sorts_mineurs, i5[1])
                    else:
                        monstre.nb_sorts_n1 = i5[0]
                        self.liste_sorts(monstre.sorts_n1, i5[1])
                    cpt += 1


    def cpt_sorts(self, i3):
        i4 = i3.split("(")
        nb_sorts = i4[1].split(")")[0]
        return nb_sorts


    def liste_sorts(self, liste_sorts, i3):        
        i4 = i3.split("<a href=\"https://www.aidedd.org/dnd/sorts.php?vf=")
        cpt=0
        for i5 in i4:
            if cpt > 0:
                cle_sort=i5.split("\">")[0]
                sort = Sort()
                sort.cle = cle_sort
                nom_sort=i5.split("\">")[1].replace("</a>", "").replace(",", "")
                sort.nom = nom_sort.replace("</em>", "")
                liste_sorts.append(sort)
            cpt += 1

    def exporter_monstres(self):
        for monstre in self.monstres_detail:
            monstre.exportcsv()

    def purger_csv(self):
        Monstre.purger_csv()
    