from Monstre import Monstres
from Sort import Sorts
from Don import Dons
from ManifestationOcculte import ManifestationOccultes
from ObjetMagique import ObjetMagiques
from Poison import Poisons
from Herbe import Herbes

class AideDND:
    def __init__(self):
        self.monstres = Monstres()
        self.classes = []
        self.races = []
        self.sorts = Sorts()
        self.dons = Dons()
        self.manifestation_occulte = ManifestationOccultes()
        self.objet_magique = ObjetMagiques()
        self.poison = Poisons()
        self.herbe = Herbes()
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
    #aide_dnd.dons.charger_dons()
    #aide_dnd.dons.purger_csv()
    #aide_dnd.dons.exporter_dons()
    #url_don = "https://www.aidedd.org/dnd/dons.php?vf=adepte-elementaire" 
    #don = aide_dnd.dons.charger_detail_don(url_don)
    #don.afficher_don()
    print("Lancement de la récupération des données de Manifestation Occulte")
    #aide_dnd.manifestation_occulte.charger_manifestation_occulte()
    #aide_dnd.manifestation_occulte.purger_csv()
    #aide_dnd.manifestation_occulte.afficher_manifestation_occulte()
    #aide_dnd.manifestation_occulte.exporter_manifestation_occulte()
    #url_don = "https://www.aidedd.org/dnd/dons.php?vf=telekinesiste" 
    #don = aide_dnd.dons.charger_detail_don(url_don)
    #don.afficher_don()
    print("Lancement de la récupération des données de Objet Magique")
    #aide_dnd.objet_magique.charger_objet_magique()
    #aide_dnd.objet_magique.purger_csv()
    #aide_dnd.objet_magique.exporter_objet_magique()
    #url_objet_magique = "https://www.aidedd.org/dnd/om.php?vf=amulette-d-antidetection" 
    #objet_magique = aide_dnd.objet_magique.charger_detail_objet_magique(url_objet_magique)
    #objet_magique.afficher_objet_magique()
    print("Lancement de la récupération des données de Poison")
    #aide_dnd.poison.charger_poison()
    #aide_dnd.poison.purger_csv()
    #aide_dnd.poison.exporter_poison()
    #url_poison = "https://www.aidedd.org/dnd/poisons.php?vf=arsenic" 
    #poison = aide_dnd.poison.charger_detail_poison(url_poison)
    #poison.afficher_poison()
    print("Lancement de la récupération des données de Herbe")
    #aide_dnd.herbe.charger_herbe()
    #aide_dnd.herbe.purger_csv()
    #aide_dnd.herbe.exporter_herbe()
    url_herbe = "https://www.aidedd.org/dnd/herbes.php?vf=aldaka" 
    herbe = aide_dnd.herbe.charger_detail_herbe(url_herbe)
    herbe.afficher_herbe()
    print("Données récupérées avec succès")

if __name__ == "__main__":
    main()