from Monstre import Monstres
from Sort import Sorts
from Don import Dons

class AideDND:
    def __init__(self):
        self.monstres = Monstres()
        self.classes = []
        self.races = []
        self.sorts = Sorts()
        self.dons = Dons()
        self.items = []
        self.npcs = []
        self.scenes = []
        self.campaigns = []
        self.users = []

    

def main():
    print("Lancement de la récupération des données de AideDND")
    aide_dnd = AideDND()
    print("Lancement de la récupération des données de Monstres")
    #aide_dnd.monstres.charger_monstres()    
    #aide_dnd.monstres.purger_csv()
    #aide_dnd.monstres.exporter_monstres()
    print("Lancement de la récupération des données de Sorts")
    #url_sort = "https://www.aidedd.org/dnd/sorts.php?vf=amelioration-de-caracteristique"
    #url_sort = "https://www.aidedd.org/dnd/sorts.php?vf=animation-d-objets"
    #url_sort = "https://www.aidedd.org/dnd/sorts.php?vf=convocation-de-fee"
    #sort = aide_dnd.sorts.charger_detail_sort(url_sort)
    #sort.afficher_sort()
    #aide_dnd.sorts.charger_sorts()
    #aide_dnd.sorts.purger_csv()
    #aide_dnd.sorts.exporter_sorts() 
    print("Lancement de la récupération des données de Dons")
    aide_dnd.dons.charger_dons()
    aide_dnd.dons.purger_csv()
    aide_dnd.dons.exporter_dons()
    #url_don = "https://www.aidedd.org/dnd/dons.php?vf=telekinesiste" 
    #don = aide_dnd.dons.charger_detail_don(url_don)
    #don.afficher_don()
    print("Données récupérées avec succès")

if __name__ == "__main__":
    main()