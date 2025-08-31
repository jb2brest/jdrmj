class AttSpecial:

    def __init__(self):
        self.nom = ""
        self.description = ""

class Action:

    def __init__(self):
        self.nom = ""   
        self.description = ""

class Monstre:
    def __init__(self):
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
        self.immunités_aux_états = ""
        self.sens = ""
        self.langues = ""
        self.fp = "" 
        self.att_special = []
        self.action = []

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
        print("Immunités aux états :" + self.immunités_aux_états)
        print("Sens :" + self.sens)
        print("Langues :" + self.langues)
        print("FP :" + self.fp)
        print("___________")
        print("Att_special :")
        for i in self.att_special:
            print("Nom :" + i.nom)
            print("Description :" + i.description)
        print("___________")
        print("Action :")
        for i in self.action:
            print("Nom :" + i.nom)
            print("Description :" + i.description)
        print("___________")