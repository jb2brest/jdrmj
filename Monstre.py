import csv

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
