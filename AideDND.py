import requests
from Monstre import Monstre
from Monstre import AttSpecial
from Monstre import Action

class AideDND:
    def __init__(self):
        self.monstres = []  
        self.monstres_detail = []
        self.classes = []
        self.races = []
        self.spells = []
        self.items = []
        self.npcs = []
        self.scenes = []
        self.campaigns = []
        self.users = []

    def afficher_monstres(self):
        for monstre in self.monstres_detail:
            monstre.afficher_monstre()

    def charger_monstres(self):
        # URL à interroger
        url = "https://www.aidedd.org/dnd-filters/monstres.php"  # Remplace par l'URL de ton choix
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
                                            self.monstres.append(i6)
            for monstre in self.monstres:
                self.monstres_detail.append(self.charger_detail_monstres(monstre))   
        except requests.exceptions.RequestException as e:
            print(f"❌ Erreur lors de la requête : {e}")



    def charger_detail_monstres(self, monstre):
        try:
            headers = {
                "User-Agent": "Mozilla/5.0"
            }
            response = requests.get(monstre, headers=headers)
            monstre = Monstre()
            # Vérification du code HTTP
            print(f"Code HTTP : {response.status_code}")

            # Affichage du contenu brut
            print("Contenu de la réponse :\n")
            body_trouve = 0
            action_trouve = 0
            particularite_trouve = 0
            for line in response.iter_lines(decode_unicode=True):
                if "<body>" in line:
                    body_trouve = 1
                if body_trouve == 1:
                    ligne = line.split("<div")
                    for i in ligne:
                        print("######### 1 ##########")
                        print(i)
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
                            self.test(monstre, i)
                        if "class='rub'>Actions</div>" in i:
                            action_trouve = 1
                        if particularite_trouve == 1 and action_trouve == 0:
                            self.charger_att_special(monstre, i)

                        if particularite_trouve == 1 and action_trouve == 1:
                            self.charger_action(monstre, i)
            return monstre
        except requests.exceptions.RequestException as e:
            print(f"❌ Erreur lors de la requête : {e}")


    def test(self, monstre, i):
        print("######### TEst ##########")
        print(i)
        i2 = i.split("<br><strong>")
        for i3 in i2:
            print(i3)
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
            if "</p>Sorts" in i3:
                print("######### Sorts ##########")
        print(monstre.nom)

    def format_particularite(self, i):
        if "</svg></div>" in i:
            i2 = i.split("</svg></div>")[1]
            i3 = i2.split("</strong>")
            return i3[1].replace("</div>", "")
        else:
            i2 = i.split("</strong>")
            return i2[1].replace("</div>", "")
  

    def charger_particularites(self, monstre, i):
        if "<p><strong><em>" in i:
            cpt = 0
            i2 = i.split("<p><strong><em>")
            for j in i2:
                if cpt > 0:
                    i3 = j.split("</em></strong>.")
                    particularite = Particularite()
                    particularite.nom = i3[0]
                    particularite.description = i3[1].split("</p>")[0]
                    monstre.particularite.append(particularite)
                cpt += 1



    def charger_action(self, monstre, i):
        if "<p><strong><em>" in i:
            cpt = 0
            i2 = i.split("<p><strong><em>")
            for j in i2:
                if cpt > 0:
                    i3 = j.split("</em></strong>.")
                    action = Action()
                    action.nom = i3[0]
                    action.description = i3[1].split("</p>")[0]
                    monstre.action.append(action)
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
                cpt += 1

def main():
    print("Lancement de la récupération des données de AideDND")
    aide_dnd = AideDND()
    #aide_dnd.charger_monstres()    
    nom_monstre = "aarakocra"
    nom_monstre = "naga-gardien"  
    url = "https://www.aidedd.org/dnd/monstres.php?vf=" + nom_monstre
    aide_dnd.monstres_detail.append(aide_dnd.charger_detail_monstres(url))
    print("Données récupérées avec succès")
    aide_dnd.afficher_monstres()

if __name__ == "__main__":
    main()