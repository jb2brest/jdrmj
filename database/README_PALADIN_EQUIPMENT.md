# ⚔️ Équipement de Départ du Paladin

## 📋 Spécifications Enregistrées

L'équipement de départ du Paladin a été enregistré dans la table `starting_equipment` selon les spécifications exactes demandées :

### 🎯 **Structure des Données Enregistrées**

| Groupe | Choix | Type | Description | Quantité | Object ID |
|--------|-------|------|-------------|----------|-----------|
| **Choix 1a** | a | weapon | Armes de guerre à distance | 1 | - |
| **Choix 1a** | a | armor | Bouclier | 1 | 13 |
| **Choix 1b** | b | weapon | Armes de guerre de corps à corps | 1 | - |
| **Choix 1b** | b | weapon | Armes de guerre de corps à corps | 1 | - |
| **Choix 1c** | c | weapon | Armes de guerre à distance | 1 | - |
| **Choix 1c** | c | weapon | Armes de guerre à distance | 1 | - |
| **Choix 1d** | d | weapon | Armes de guerre de corps à corps | 1 | - |
| **Choix 1d** | d | weapon | Armes de guerre à distance | 1 | - |
| **Choix 1e** | e | weapon | Armes de guerre de corps à corps | 1 | - |
| **Choix 1e** | e | armor | Bouclier | 1 | 13 |
| **Choix 2** | a | weapon | Épée à deux mains | 5 | 17 |
| **Choix 2** | b | weapon | Armes courantes à distance | 1 | - |
| **Choix 2** | c | weapon | Armes courantes de corps à corps | 1 | - |
| **Choix 3a** | a | sac | Sac à dos | 1 | 1 |
| **Choix 3a** | a | outils | Sac de couchage | 1 | 19 |
| **Choix 3a** | a | outils | Gamelle | 1 | 5 |
| **Choix 3a** | a | outils | Boite d'allume-feu | 1 | 6 |
| **Choix 3a** | a | outils | Torche | 10 | 7 |
| **Choix 3a** | a | nourriture | Rations de voyage | 10 | 13 |
| **Choix 3a** | a | nourriture | Gourde d'eau | 1 | 18 |
| **Choix 3a** | a | outils | Corde de chanvre (15m) | 1 | 8 |
| **Choix 3b** | b | sac | Sac à dos | 1 | 1 |
| **Choix 3b** | b | outils | Couverture | 1 | 40 |
| **Choix 3b** | b | outils | Bougies | 10 | 38 |
| **Choix 3b** | b | outils | Bougies | 5 | 38 |
| **Choix 3b** | b | outils | Boite d'allume-feu | 1 | 6 |
| **Choix 3b** | b | outils | Boîte pour l'aumône | 1 | 41 |
| **Choix 3b** | b | outils | Encensoir | 1 | 42 |
| **Choix 3b** | b | outils | Bâtonnets d'encens | 2 | 43 |
| **Choix 3b** | b | outils | Habits de cérémonie | 1 | 44 |
| **Choix 3b** | b | nourriture | Rations de voyage | 2 | 13 |
| **Choix 3b** | b | nourriture | Gourde d'eau | 1 | 18 |
| **Obligatoire** | - | armor | Cotte de mailles | 1 | 10 |
| **Obligatoire** | - | outils | Symbole sacré | 1 | 45 |

## 🎮 **Choix du Joueur**

### **Choix 1 : Armes Principales (Groupes 1-5)**

#### **Option A : Arme à Distance + Bouclier (Groupe 1)**
- **N'importe quelle arme de guerre à distance** - Filtre générique
- **Un bouclier** (ID: 13) - Armure spécifique

#### **Option B : Double Arme de Corps à Corps (Groupe 2)**
- **N'importe quelle arme de guerre de corps à corps** (1ère) - Filtre générique
- **N'importe quelle arme de guerre de corps à corps** (2ème) - Filtre générique

#### **Option C : Double Arme à Distance (Groupe 3)**
- **N'importe quelle arme de guerre à distance** (1ère) - Filtre générique
- **N'importe quelle arme de guerre à distance** (2ème) - Filtre générique

#### **Option D : Arme Corps à Corps + Arme à Distance (Groupe 4)**
- **N'importe quelle arme de guerre de corps à corps** - Filtre générique
- **N'importe quelle arme de guerre à distance** - Filtre générique

#### **Option E : Arme Corps à Corps + Bouclier (Groupe 5)**
- **N'importe quelle arme de guerre de corps à corps** - Filtre générique
- **Un bouclier** (ID: 13) - Armure spécifique

### **Choix 2 : Armes Secondaires (Groupe 6)**
- **(a) Cinq javelines** (ID: 17) - Arme spécifique, quantité 5
- **(b) N'importe quelle arme courante (distance)** - Filtre générique
- **(c) N'importe quelle arme courante (corps à corps)** - Filtre générique

### **Choix 3 : Sac d'Équipement (Groupes 7 et 8)**

#### **Option A : Sac d'Explorateur (Groupe 7)**
- **Un sac à dos** - Sac d'équipement
- **Un sac de couchage** - Outils
- **Une gamelle** - Outils
- **Une boite d'allume-feu** - Outils
- **10 torches** - Outils, quantité 10
- **10 jours de rations** - Nourriture, quantité 10
- **Une gourde d'eau** - Nourriture
- **Une corde de 15m** - Outils

#### **Option B : Sac d'Ecclésiastique (Groupe 8)**
- **Un sac à dos** - Sac d'équipement
- **Une couverture** - Outils
- **10 bougies** - Outils, quantité 10
- **5 bougies supplémentaires** - Outils, quantité 5
- **Une boite d'allume-feu** - Outils
- **Une boîte pour l'aumône** - Outils
- **Un encensoir** - Outils
- **2 bâtonnets d'encens** - Outils, quantité 2
- **Des habits de cérémonie** - Outils
- **2 jours de rations** - Nourriture, quantité 2
- **Une gourde d'eau** - Nourriture

### **Équipement Obligatoire (Groupe 9)**
- **Une cotte de mailles** (ID: 10) - Armure spécifique
- **Un symbole sacré** (Object ID: 45) - Outils spécifique

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

### **2. Nouveaux Objets Créés**
- **Couverture** : Nouvel objet (Object ID: 40)
- **Bougies** : Nouvel objet (Object ID: 38)
- **Boîte pour l'aumône** : Nouvel objet (Object ID: 41)
- **Encensoir** : Nouvel objet (Object ID: 42)
- **Bâtonnets d'encens** : Nouvel objet (Object ID: 43)
- **Habits de cérémonie** : Nouvel objet (Object ID: 44)
- **Symbole sacré** : Nouvel objet (Object ID: 45)

### **3. Gestion des Quantités**
- **5 javelines** : `nb = 5`
- **10 torches** : `nb = 10`
- **10 jours de rations** : `nb = 10`
- **10 bougies** : `nb = 10`
- **5 bougies supplémentaires** : `nb = 5`
- **2 bâtonnets d'encens** : `nb = 2`
- **2 jours de rations** : `nb = 2`

### **4. Filtres de Type**
- **"Armes de guerre à distance"** : Pour le choix d'arme de guerre à distance
- **"Armes de guerre de corps à corps"** : Pour le choix d'arme de guerre de corps à corps
- **"Armes courantes à distance"** : Pour le choix d'arme courante à distance
- **"Armes courantes de corps à corps"** : Pour le choix d'arme courante de corps à corps

### **5. Types d'Équipement Variés**
- **weapon** : Armes (javelines, armes de guerre, armes courantes)
- **armor** : Armures (cotte de mailles, bouclier)
- **sac** : Sacs d'équipement
- **outils** : Outils divers
- **nourriture** : Rations et gourde

## 🎯 **Exemples de Combinaisons**

### **Option A : Paladin Défenseur**
- **Choix 1** : Arme de guerre à distance + Bouclier
- **Choix 2** : 5 javelines
- **Choix 3** : Sac d'explorateur
- **Obligatoire** : Cotte de mailles + Symbole sacré

### **Option B : Paladin Combattant**
- **Choix 1** : 2 armes de guerre de corps à corps
- **Choix 2** : Arme courante de corps à corps
- **Choix 3** : Sac d'ecclésiastique
- **Obligatoire** : Cotte de mailles + Symbole sacré

### **Option C : Paladin Polyvalent**
- **Choix 1** : Arme de guerre de corps à corps + Arme de guerre à distance
- **Choix 2** : Arme courante à distance
- **Choix 3** : Sac d'explorateur
- **Obligatoire** : Cotte de mailles + Symbole sacré

### **Option D : Paladin Écclésiastique**
- **Choix 1** : Arme de guerre de corps à corps + Bouclier
- **Choix 2** : 5 javelines
- **Choix 3** : Sac d'ecclésiastique
- **Obligatoire** : Cotte de mailles + Symbole sacré

### **Option E : Paladin Archer**
- **Choix 1** : 2 armes de guerre à distance
- **Choix 2** : Arme courante à distance
- **Choix 3** : Sac d'explorateur
- **Obligatoire** : Cotte de mailles + Symbole sacré

## 📊 **Statistiques**

- **Total d'enregistrements** : 34
- **Choix 1** : 5 options d'armes principales (a-e) avec différentes combinaisons
- **Choix 2** : 3 options d'armes secondaires
- **Choix 3a** : 8 items du sac d'explorateur
- **Choix 3b** : 12 items du sac d'ecclésiastique
- **Équipement obligatoire** : 2 items (cotte de mailles + symbole sacré)
- **Types d'équipement** : weapon, armor, sac, outils, nourriture
- **Source** : class (ID: 10 - Paladin)

## ✅ **Vérification**

- **Base de données** : `u839591438_jdrmj`
- **Table** : `starting_equipment`
- **Source** : `class` (ID: 10 - Paladin)
- **Total** : 34 enregistrements
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

1. **`insert_paladin_equipment.php`** - Script d'insertion du Paladin
2. **`README_PALADIN_EQUIPMENT.md`** - Documentation complète

## ⚔️ **Spécificités du Paladin**

### **Équipement de Combat**
- **Cotte de mailles** : Armure lourde pour la protection
- **Bouclier** : Défense supplémentaire
- **Armes de guerre** : Armes spécialisées pour le combat
- **5 javelines** : Armes de jet pour l'engagement à distance

### **Équipement Religieux**
- **Symbole sacré** : Focaliseur pour les sorts divins
- **Sac d'ecclésiastique** : Équipement spécialisé pour les cérémonies
- **Encensoir** : Pour les rituels religieux
- **Bâtonnets d'encens** : Composantes pour les cérémonies
- **Habits de cérémonie** : Vêtements pour les offices
- **Boîte pour l'aumône** : Pour collecter les dons

### **Équipement de Survie**
- **Sac d'explorateur** : Équipement standard pour l'aventure
- **Outils polyvalents** : Torches, corde, gamelle, etc.
- **Rations de voyage** : Nourriture pour les expéditions
- **Gourde d'eau** : Hydratation en voyage

### **Flexibilité Tactique**
- **Choix 1** : 5 options d'armes principales avec différentes combinaisons
- **Choix 2** : 3 options d'armes secondaires (javelines, arme courante)
- **Choix 3** : 2 options d'équipement (explorateur ou ecclésiastique)
- **Équipement obligatoire** : Cotte de mailles + Symbole sacré

### **Spécialisations Possibles**
- **Paladin Défenseur** : Arme distance + Bouclier + 5 javelines + Sac explorateur
- **Paladin Combattant** : 2 armes corps à corps + Arme courante corps à corps + Sac ecclésiastique
- **Paladin Polyvalent** : Arme corps à corps + Arme distance + Arme courante distance + Sac explorateur
- **Paladin Écclésiastique** : Arme corps à corps + Bouclier + 5 javelines + Sac ecclésiastique
- **Paladin Archer** : 2 armes distance + Arme courante distance + Sac explorateur

### **Avantages Tactiques**
- **Protection** : Cotte de mailles + Bouclier pour la défense
- **Polyvalence** : Combat à distance et en mêlée
- **Magie** : Symbole sacré pour les sorts divins
- **Autonomie** : Équipement de survie complet
- **Flexibilité** : 5 choix d'armes principales différents
- **Spécialisation** : Choix entre équipement d'aventure ou religieux

L'équipement de départ du Paladin est maintenant enregistré avec la nouvelle structure étendue et prêt à être utilisé dans le système de création de personnages !
