from Monstre import Monstres
from Sort import Sorts

class AideDND:
    def __init__(self):
        self.monstres = Monstres()
        self.classes = []
        self.races = []
        self.sorts = Sorts()
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
    #aide_dnd.sorts.charger_sorts()
    url_sort = "https://www.aidedd.org/dnd/sorts.php?vf=amelioration-de-caracteristique"
    sort = aide_dnd.sorts.charger_detail_sort(url_sort)
    sort.afficher_sort()
    aide_dnd.sorts.afficher_sorts()
    print("Données récupérées avec succès")

if __name__ == "__main__":
    main()