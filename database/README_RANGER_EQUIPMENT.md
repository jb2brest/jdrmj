# 🏹 Équipement de Départ du Rôdeur

## 📋 Spécifications Enregistrées

L'équipement de départ du Rôdeur a été enregistré dans la table `starting_equipment` selon les spécifications exactes demandées :

### 🎯 **Structure des Données Enregistrées**

| Groupe | Choix | Type | Description | Quantité | Object ID |
|--------|-------|------|-------------|----------|-----------|
| **Choix 1** | a | armor | Armure d'écailles | 1 | 6 |
| **Choix 1** | b | armor | Armure de cuir | 1 | 2 |
| **Choix 2** | a | weapon | Épée courte | 2 | 18 |
| **Choix 2b** | b | weapon | Armes courantes de corps à corps | 1 | - |
| **Choix 2b** | b | weapon | Armes courantes de corps à corps | 1 | - |
| **Choix 2c** | c | weapon | Armes courantes à distance | 1 | - |
| **Choix 2c** | c | weapon | Armes courantes à distance | 1 | - |
| **Choix 2d** | d | weapon | Armes courantes de corps à corps | 1 | - |
| **Choix 2d** | d | weapon | Armes courantes à distance | 1 | - |
| **Choix 3a** | a | sac | Sac à dos | 1 | 1 |
| **Choix 3a** | a | outils | Sac de couchage | 1 | 19 |
| **Choix 3a** | a | outils | Gamelle | 1 | 5 |
| **Choix 3a** | a | outils | Boite d'allume-feu | 1 | 6 |
| **Choix 3a** | a | outils | Torche | 10 | 7 |
| **Choix 3a** | a | nourriture | Rations de voyage | 10 | 13 |
| **Choix 3a** | a | nourriture | Gourde d'eau | 1 | 18 |
| **Choix 3a** | a | outils | Corde de chanvre (15m) | 1 | 8 |
| **Choix 3b** | b | sac | Sac à dos | 1 | 1 |
| **Choix 3b** | b | outils | Pied de biche | 1 | 9 |
| **Choix 3b** | b | outils | Marteau | 1 | 10 |
| **Choix 3b** | b | outils | Piton | 10 | 11 |
| **Choix 3b** | b | outils | Torche | 10 | 7 |
| **Choix 3b** | b | outils | Boite d'allume-feu | 1 | 6 |
| **Choix 3b** | b | nourriture | Rations de voyage | 10 | 13 |
| **Choix 3b** | b | nourriture | Gourde d'eau | 1 | 18 |
| **Choix 3b** | b | outils | Corde de chanvre (15m) | 1 | 8 |
| **Obligatoire** | - | weapon | Arc long | 1 | 35 |
| **Obligatoire** | - | outils | Carquois | 1 | 55 |
| **Obligatoire** | - | weapon | Flèches | 20 | 13 |

## 🎮 **Choix du Joueur**

### **Choix 1 : Armure (Groupe 1)**
- **(a) Armure d'écailles** (ID: 6) - Armure intermédiaire
- **(b) Armure de cuir** (ID: 2) - Armure légère

### **Choix 2 : Armes (Groupes 2-5)**

#### **Option A : Double Épée Courte (Groupe 2)**
- **2 épées courtes** (ID: 18) - Arme spécifique, quantité 2

#### **Option B : Double Arme Courante Corps à Corps (Groupe 3)**
- **N'importe quelle arme courante de corps à corps** (1ère) - Filtre générique
- **N'importe quelle arme courante de corps à corps** (2ème) - Filtre générique

#### **Option C : Double Arme Courante à Distance (Groupe 4)**
- **N'importe quelle arme courante à distance** (1ère) - Filtre générique
- **N'importe quelle arme courante à distance** (2ème) - Filtre générique

#### **Option D : Arme Courante Corps à Corps + Arme Courante à Distance (Groupe 5)**
- **N'importe quelle arme courante de corps à corps** - Filtre générique
- **N'importe quelle arme courante à distance** - Filtre générique

### **Choix 3 : Sac d'Équipement (Groupes 6 et 7)**

#### **Option A : Sac d'Explorateur (Groupe 6)**
- **Un sac à dos** - Sac d'équipement
- **Un sac de couchage** - Outils
- **Une gamelle** - Outils
- **Une boite d'allume-feu** - Outils
- **10 torches** - Outils, quantité 10
- **10 jours de rations** - Nourriture, quantité 10
- **Une gourde d'eau** - Nourriture
- **Une corde de 15m** - Outils

#### **Option B : Sac d'Exploration Souterraine (Groupe 7)**
- **Un sac à dos** - Sac d'équipement
- **Un pied de biche** - Outils
- **Un marteau** - Outils
- **10 pitons** - Outils, quantité 10
- **10 torches** - Outils, quantité 10
- **Une boite d'allume-feu** - Outils
- **10 jours de rations** - Nourriture, quantité 10
- **Une gourde d'eau** - Nourriture
- **Une corde de 15m** - Outils

### **Équipement Obligatoire (Groupe 8)**
- **Un arc long** (ID: 35) - Arme spécifique
- **Un carquois** (Object ID: 55) - Outils spécifique
- **20 flèches** (ID: 13) - Arme spécifique, quantité 20

## 🔧 **Nouvelles Fonctionnalités Utilisées**

### **1. Réutilisation d'Objets Existants**
- **Sac à dos** : Réutilisation (Object ID: 1)
- **Sac de couchage** : Réutilisation (Object ID: 19)
- **Gamelle** : Réutilisation (Object ID: 5)
- **Boite d'allume-feu** : Réutilisation (Object ID: 6)
- **Torche** : Réutilisation (Object ID: 7)
- **Rations de voyage** : Réutilisation (Object ID: 13)
- **Gourde d'eau** : Réutilisation (Object ID: 18)
- **Corde de chanvre (15m)** : Réutilisation (Object ID: 8)
- **Pied de biche** : Réutilisation (Object ID: 9)
- **Marteau** : Réutilisation (Object ID: 10)
- **Piton** : Réutilisation (Object ID: 11)

### **2. Nouveaux Objets Créés**
- **Carquois** : Nouvel objet (Object ID: 55)

### **3. Gestion des Quantités**
- **2 épées courtes** : `nb = 2`
- **10 torches** : `nb = 10`
- **10 jours de rations** : `nb = 10`
- **10 pitons** : `nb = 10`
- **20 flèches** : `nb = 20`

### **4. Filtres de Type**
- **"Armes courantes de corps à corps"** : Pour le choix d'arme courante de corps à corps
- **"Armes courantes à distance"** : Pour le choix d'arme courante à distance

### **5. Types d'Équipement Variés**
- **weapon** : Armes (épées courtes, arc long, flèches, armes courantes)
- **armor** : Armures (armure d'écailles, armure de cuir)
- **sac** : Sacs d'équipement
- **outils** : Outils divers
- **nourriture** : Rations et gourde

## 🎯 **Exemples de Combinaisons**

### **Option A : Rôdeur Éclaireur**
- **Choix 1** : Armure d'écailles
- **Choix 2** : 2 épées courtes
- **Choix 3** : Sac d'explorateur
- **Obligatoire** : Arc long + Carquois + 20 flèches

### **Option B : Rôdeur Combattant**
- **Choix 1** : Armure de cuir
- **Choix 2** : 2 armes courantes de corps à corps
- **Choix 3** : Sac d'exploration souterraine
- **Obligatoire** : Arc long + Carquois + 20 flèches

### **Option C : Rôdeur Archer**
- **Choix 1** : Armure d'écailles
- **Choix 2** : 2 armes courantes à distance
- **Choix 3** : Sac d'explorateur
- **Obligatoire** : Arc long + Carquois + 20 flèches

### **Option D : Rôdeur Polyvalent**
- **Choix 1** : Armure de cuir
- **Choix 2** : Arme courante de corps à corps + Arme courante à distance
- **Choix 3** : Sac d'exploration souterraine
- **Obligatoire** : Arc long + Carquois + 20 flèches

### **Option E : Rôdeur Spécialisé**
- **Choix 1** : Armure d'écailles
- **Choix 2** : 2 épées courtes
- **Choix 3** : Sac d'exploration souterraine
- **Obligatoire** : Arc long + Carquois + 20 flèches

## 📊 **Statistiques**

- **Total d'enregistrements** : 29
- **Choix 1** : 2 options d'armure
- **Choix 2** : 4 options d'armes (a) 2 épées courtes, (b) 2 armes courantes corps à corps, (c) 2 armes courantes distance, (d) arme corps à corps + arme distance
- **Choix 3a** : 8 items du sac d'explorateur
- **Choix 3b** : 9 items du sac d'exploration souterraine
- **Équipement obligatoire** : 3 items (arc long + carquois + 20 flèches)
- **Types d'équipement** : weapon, armor, sac, outils, nourriture
- **Source** : class (ID: 11 - Rôdeur)

## ✅ **Vérification**

- **Base de données** : `u839591438_jdrmj`
- **Table** : `starting_equipment`
- **Source** : `class` (ID: 11 - Rôdeur)
- **Total** : 29 enregistrements
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

1. **`insert_ranger_equipment.php`** - Script d'insertion du Rôdeur
2. **`README_RANGER_EQUIPMENT.md`** - Documentation complète

## 🏹 **Spécificités du Rôdeur**

### **Équipement de Combat**
- **Arc long** : Arme de prédilection pour le combat à distance
- **20 flèches** : Munitions pour l'arc long
- **Carquois** : Pour transporter les flèches
- **2 épées courtes** : Option de combat en mêlée
- **Armes courantes** : Flexibilité dans le choix des armes

### **Équipement de Survie**
- **Sac d'explorateur** : Équipement standard pour l'aventure en extérieur
- **Sac d'exploration souterraine** : Équipement spécialisé pour les donjons
- **Outils polyvalents** : Torches, corde, gamelle, etc.
- **Rations de voyage** : Nourriture pour les expéditions
- **Gourde d'eau** : Hydratation en voyage

### **Protection**
- **Armure d'écailles** : Protection intermédiaire
- **Armure de cuir** : Protection légère pour la mobilité

### **Flexibilité Tactique**
- **Choix 1** : 2 options d'armure (écailles ou cuir)
- **Choix 2** : 4 options d'armes (épées courtes, armes courantes)
- **Choix 3** : 2 options d'équipement (explorateur ou souterrain)
- **Équipement obligatoire** : Arc long + Carquois + 20 flèches

### **Spécialisations Possibles**
- **Rôdeur Éclaireur** : Armure d'écailles + 2 épées courtes + Sac explorateur + Arc long + Carquois + 20 flèches
- **Rôdeur Combattant** : Armure de cuir + 2 armes courantes corps à corps + Sac souterrain + Arc long + Carquois + 20 flèches
- **Rôdeur Archer** : Armure d'écailles + 2 armes courantes distance + Sac explorateur + Arc long + Carquois + 20 flèches
- **Rôdeur Polyvalent** : Armure de cuir + Arme courante corps à corps + Arme courante distance + Sac souterrain + Arc long + Carquois + 20 flèches
- **Rôdeur Spécialisé** : Armure d'écailles + 2 épées courtes + Sac souterrain + Arc long + Carquois + 20 flèches

### **Avantages Tactiques**
- **Combat à distance** : Arc long + 20 flèches pour l'engagement à distance
- **Combat en mêlée** : 2 épées courtes ou armes courantes pour le combat rapproché
- **Mobilité** : Armure de cuir pour la liberté de mouvement
- **Protection** : Armure d'écailles pour la défense
- **Autonomie** : Équipement de survie complet
- **Flexibilité** : 4 choix d'armes différents
- **Spécialisation** : Choix entre équipement d'exploration ou souterrain

L'équipement de départ du Rôdeur est maintenant enregistré avec la nouvelle structure étendue et prêt à être utilisé dans le système de création de personnages !
