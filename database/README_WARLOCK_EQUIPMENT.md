# 🔮 Équipement de Départ de l'Occultiste

## 📋 Spécifications Enregistrées

L'équipement de départ de l'Occultiste a été enregistré dans la table `starting_equipment` selon les spécifications exactes demandées :

### 🎯 **Structure des Données Enregistrées**

| Groupe | Choix | Type | Description | Quantité | Object ID |
|--------|-------|------|-------------|----------|-----------|
| **Choix 1** | a | weapon | Arbalète légère | 1 | 11 |
| **Choix 1** | b | weapon | Armes courantes à distance | 1 | - |
| **Choix 1** | c | weapon | Armes courantes de corps à corps | 1 | - |
| **Choix 2** | a | sac | Sacoche à composantes | 1 | 48 |
| **Choix 2** | b | outils | Focaliseur arcanique | 1 | 49 |
| **Choix 3a** | a | sac | Sac à dos | 1 | 1 |
| **Choix 3a** | a | outils | Livre de connaissance | 1 | 50 |
| **Choix 3a** | a | outils | Bouteille d'encre | 1 | 29 |
| **Choix 3a** | a | outils | Plume d'écriture | 1 | 30 |
| **Choix 3a** | a | outils | Feuilles de parchemin | 10 | 51 |
| **Choix 3a** | a | outils | Petit sac de sable | 1 | 52 |
| **Choix 3a** | a | outils | Petit couteau | 1 | 53 |
| **Choix 3b** | b | sac | Sac à dos | 1 | 1 |
| **Choix 3b** | b | outils | Pied de biche | 1 | 9 |
| **Choix 3b** | b | outils | Marteau | 1 | 10 |
| **Choix 3b** | b | outils | Piton | 10 | 11 |
| **Choix 3b** | b | outils | Torche | 10 | 7 |
| **Choix 3b** | b | outils | Boite d'allume-feu | 1 | 6 |
| **Choix 3b** | b | nourriture | Rations de voyage | 10 | 13 |
| **Choix 3b** | b | nourriture | Gourde d'eau | 1 | 18 |
| **Choix 3b** | b | outils | Corde de chanvre (15m) | 1 | 8 |
| **Choix 4** | a | weapon | Armes courantes à distance | 1 | - |
| **Choix 4** | b | weapon | Armes courantes de corps à corps | 1 | - |
| **Obligatoire** | - | weapon | Dague | 2 | 2 |
| **Obligatoire** | - | armor | Armure de cuir | 1 | 2 |

## 🎮 **Choix du Joueur**

### **Choix 1 : Arme (Groupe 1)**
- **(a) Une arbalète légère** (ID: 11) - Arme à distance
- **(b) N'importe quelle arme courante (distance)** - Filtre générique
- **(c) N'importe quelle arme courante (corps à corps)** - Filtre générique

### **Choix 2 : Sacoche ou Focaliseur (Groupe 2)**
- **(a) Une sacoche à composantes** (Object ID: 48) - Sac spécifique
- **(b) Un focaliseur arcanique** (Object ID: 49) - Outils spécifique

### **Choix 3 : Sac d'Équipement (Groupes 3 et 4)**

#### **Option A : Sac d'Érudit (Groupe 3)**
- **Un sac à dos** - Sac d'équipement
- **Un livre de connaissance** - Outils
- **Une bouteille d'encre** - Outils
- **Une plume d'écriture** - Outils
- **10 feuilles de parchemin** - Outils, quantité 10
- **Un petit sac de sable** - Outils
- **Un petit couteau** - Outils

#### **Option B : Sac d'Exploration Souterraine (Groupe 4)**
- **Un sac à dos** - Sac d'équipement
- **Un pied de biche** - Outils
- **Un marteau** - Outils
- **10 pitons** - Outils, quantité 10
- **10 torches** - Outils, quantité 10
- **Une boite d'allume-feu** - Outils
- **10 jours de rations** - Nourriture, quantité 10
- **Une gourde d'eau** - Nourriture
- **Une corde de 15m** - Outils

### **Choix 4 : Arme Secondaire (Groupe 5)**
- **(a) N'importe quelle arme courante (distance)** - Filtre générique
- **(b) N'importe quelle arme courante (corps à corps)** - Filtre générique

### **Équipement Obligatoire (Groupe 6)**
- **2 dagues** (ID: 2) - Arme spécifique, quantité 2
- **Une armure de cuir** (ID: 2) - Armure spécifique

## 🔧 **Nouvelles Fonctionnalités Utilisées**

### **1. Réutilisation d'Objets Existants**
- **Sacoche à composantes** : Réutilisation (Object ID: 48)
- **Focaliseur arcanique** : Réutilisation (Object ID: 49)
- **Sac à dos** : Réutilisation (Object ID: 1)
- **Livre de connaissance** : Réutilisation (Object ID: 50)
- **Bouteille d'encre** : Réutilisation (Object ID: 29)
- **Plume d'écriture** : Réutilisation (Object ID: 30)
- **Feuilles de parchemin** : Réutilisation (Object ID: 51)
- **Petit sac de sable** : Réutilisation (Object ID: 52)
- **Petit couteau** : Réutilisation (Object ID: 53)
- **Pied de biche** : Réutilisation (Object ID: 9)
- **Marteau** : Réutilisation (Object ID: 10)
- **Piton** : Réutilisation (Object ID: 11)
- **Torche** : Réutilisation (Object ID: 7)
- **Boite d'allume-feu** : Réutilisation (Object ID: 6)
- **Corde de chanvre (15m)** : Réutilisation (Object ID: 8)
- **Rations de voyage** : Réutilisation (Object ID: 13)
- **Gourde d'eau** : Réutilisation (Object ID: 18)

### **2. Gestion des Quantités**
- **10 feuilles de parchemin** : `nb = 10`
- **10 pitons** : `nb = 10`
- **10 torches** : `nb = 10`
- **10 jours de rations** : `nb = 10`
- **2 dagues** : `nb = 2`

### **3. Filtres de Type**
- **"Armes courantes à distance"** : Pour le choix d'arme courante à distance
- **"Armes courantes de corps à corps"** : Pour le choix d'arme courante de corps à corps

### **4. Types d'Équipement Variés**
- **weapon** : Armes (arbalète légère, dagues)
- **armor** : Armures (armure de cuir)
- **sac** : Sacs d'équipement
- **outils** : Outils divers
- **nourriture** : Rations et gourde

## 🎯 **Exemples de Combinaisons**

### **Option A : Occultiste Érudit**
- **Choix 1** : Arbalète légère
- **Choix 2** : Sacoche à composantes
- **Choix 3** : Sac d'érudit
- **Choix 4** : Arme courante à distance
- **Obligatoire** : 2 dagues + Armure de cuir

### **Option B : Occultiste Explorateur**
- **Choix 1** : Arme courante à distance
- **Choix 2** : Focaliseur arcanique
- **Choix 3** : Sac d'exploration souterraine
- **Choix 4** : Arme courante de corps à corps
- **Obligatoire** : 2 dagues + Armure de cuir

### **Option C : Occultiste Polyvalent**
- **Choix 1** : Arbalète légère
- **Choix 2** : Focaliseur arcanique
- **Choix 3** : Sac d'érudit
- **Choix 4** : Arme courante de corps à corps
- **Obligatoire** : 2 dagues + Armure de cuir

### **Option D : Occultiste Combat**
- **Choix 1** : Arme courante de corps à corps
- **Choix 2** : Sacoche à composantes
- **Choix 3** : Sac d'exploration souterraine
- **Choix 4** : Arme courante à distance
- **Obligatoire** : 2 dagues + Armure de cuir

## 📊 **Statistiques**

- **Total d'enregistrements** : 25
- **Choix 1** : 3 options d'armes
- **Choix 2** : 2 options (sacoche ou focaliseur)
- **Choix 3a** : 7 items du sac d'érudit
- **Choix 3b** : 9 items du sac d'exploration souterraine
- **Choix 4** : 2 options d'armes secondaires
- **Équipement obligatoire** : 2 items (2 dagues + armure de cuir)
- **Types d'équipement** : weapon, armor, sac, outils, nourriture
- **Source** : class (ID: 9 - Occultiste)

## ✅ **Vérification**

- **Base de données** : `u839591438_jdrmj`
- **Table** : `starting_equipment`
- **Source** : `class` (ID: 9 - Occultiste)
- **Total** : 25 enregistrements
- **Statut** : ✅ Enregistré avec succès

## 🚀 **Avantages de la Nouvelle Structure**

1. **Flexibilité** : Gestion des quantités et filtres
2. **Clarté** : Numérotation et lettres d'option
3. **Organisation** : Groupes d'équipement
4. **Extensibilité** : Support de nouveaux types d'équipement
5. **Performance** : Index optimisés
6. **Auto-insertion** : Création automatique des objets dans la table Object
7. **Réutilisation** : Utilisation d'objets existants

## 🔧 **Fichiers Créés**

1. **`insert_warlock_equipment.php`** - Script d'insertion de l'Occultiste
2. **`README_WARLOCK_EQUIPMENT.md`** - Documentation complète

## 🔮 **Spécificités de l'Occultiste**

### **Équipement Magique**
- **Sacoche à composantes** : Pour stocker les composantes matérielles des sorts
- **Focaliseur arcanique** : Pour canaliser l'énergie magique
- **Arbalète légère** : Arme de prédilection pour les lanceurs de sorts

### **Équipement d'Érudit**
- **Livre de connaissance** : Pour l'étude et la recherche
- **Bouteille d'encre** : Pour écrire et copier des sorts
- **Plume d'écriture** : Outil d'écriture
- **Feuilles de parchemin** : Support pour l'écriture
- **Petit sac de sable** : Pour sécher l'encre
- **Petit couteau** : Outil polyvalent

### **Équipement de Survie**
- **Sac d'exploration souterraine** : Équipement spécialisé pour les donjons
- **Outils polyvalents** : Marteau, pitons, corde, torches, etc.

### **Flexibilité Tactique**
- **Choix 1** : Arme à distance (arbalète) ou arme courante
- **Choix 2** : Support magique (sacoche ou focaliseur)
- **Choix 3** : Équipement d'aventure (érudit ou souterrain)
- **Choix 4** : Arme secondaire (distance ou corps à corps)
- **Équipement obligatoire** : 2 dagues + armure de cuir

### **Spécialisations Possibles**
- **Occultiste Érudit** : Arbalète + Sacoche + Sac d'érudit + Arme distance
- **Occultiste Explorateur** : Arme distance + Focaliseur + Sac souterrain + Arme corps à corps
- **Occultiste Polyvalent** : Arbalète + Focaliseur + Sac d'érudit + Arme corps à corps
- **Occultiste Combat** : Arme corps à corps + Sacoche + Sac souterrain + Arme distance

### **Avantages Tactiques**
- **Polyvalence** : Combat à distance et en mêlée
- **Protection** : Armure de cuir pour la défense
- **Magie** : Support pour les sorts avec sacoche ou focaliseur
- **Autonomie** : Équipement de survie complet
- **Flexibilité** : 4 choix d'équipement différents

L'équipement de départ de l'Occultiste est maintenant enregistré avec la nouvelle structure étendue et prêt à être utilisé dans le système de création de personnages !
