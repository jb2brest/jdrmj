# ⛪ Équipement de Départ du Clerc

## 📋 Spécifications Enregistrées

L'équipement de départ du Clerc a été enregistré dans la table `starting_equipment` selon les spécifications exactes demandées :

### 🎯 **Structure des Données Enregistrées**

| Groupe | Choix | Type | Description | Quantité | Object ID |
|--------|-------|------|-------------|----------|-----------|
| **Choix 1** | a | weapon | Masse d'armes | 1 | 8 |
| **Choix 1** | b | weapon | Marteau de guerre | 1 | 27 |
| **Choix 2** | a | armor | Armure d'écailles | 1 | 6 |
| **Choix 2** | b | armor | Armure de cuir | 1 | 2 |
| **Choix 2** | c | armor | Cotte de mailles | 1 | 10 |
| **Choix 3** | a | weapon | Arbalète légère | 1 | 11 |
| **Choix 3** | a | outils | Carreaux | 20 | 46 |
| **Choix 3** | b | weapon | Armes courantes à distance | 1 | - |
| **Choix 3** | c | weapon | Armes courantes de corps à corps | 1 | - |
| **Choix 4a** | a | sac | Sac à dos | 1 | 1 |
| **Choix 4a** | a | outils | Sac de couchage | 1 | 19 |
| **Choix 4a** | a | outils | Gamelle | 1 | 5 |
| **Choix 4a** | a | outils | Boite d'allume-feu | 1 | 6 |
| **Choix 4a** | a | outils | Torche | 10 | 7 |
| **Choix 4a** | a | nourriture | Rations de voyage | 10 | 13 |
| **Choix 4a** | a | nourriture | Gourde d'eau | 1 | 18 |
| **Choix 4a** | a | outils | Corde de chanvre (15m) | 1 | 8 |
| **Choix 4b** | b | sac | Sac à dos | 1 | 1 |
| **Choix 4b** | b | outils | Couverture | 1 | 40 |
| **Choix 4b** | b | outils | Bougies | 10 | 38 |
| **Choix 4b** | b | outils | Bougies | 5 | 38 |
| **Choix 4b** | b | outils | Boite d'allume-feu | 1 | 6 |
| **Choix 4b** | b | outils | Boîte pour l'aumône | 1 | 41 |
| **Choix 4b** | b | outils | Encensoir | 1 | 42 |
| **Choix 4b** | b | outils | Bâtonnets d'encens | 2 | 43 |
| **Choix 4b** | b | outils | Habits de cérémonie | 1 | 44 |
| **Choix 4b** | b | nourriture | Rations de voyage | 2 | 13 |
| **Choix 4b** | b | nourriture | Gourde d'eau | 1 | 18 |
| **Obligatoire** | - | bouclier | Bouclier | 1 | 13 |
| **Obligatoire** | - | outils | Symbole sacré | 1 | 45 |

## 🎮 **Choix du Joueur**

### **Choix 1 : Arme Principale (Groupe 1)**
- **(a) Masse d'armes** (ID: 8) - Arme spécifique
- **(b) Marteau de guerre** (ID: 27) - Arme spécifique

### **Choix 2 : Armure (Groupe 2)**
- **(a) Armure d'écailles** (ID: 6) - Armure spécifique
- **(b) Armure de cuir** (ID: 2) - Armure spécifique
- **(c) Cotte de mailles** (ID: 10) - Armure spécifique

### **Choix 3 : Arme Secondaire (Groupe 3)**

#### **Option A : Arbalète et Carreaux**
- **Arbalète légère** (ID: 11) - Arme spécifique
- **20 carreaux** (Object ID: 46) - Outils, quantité 20

#### **Option B : Arme Courante à Distance**
- **N'importe quelle arme courante (distance)** - Filtre générique

#### **Option C : Arme Courante de Corps à Corps**
- **N'importe quelle arme courante (corps à corps)** - Filtre générique

### **Choix 4 : Sac d'Équipement (Groupes 4 et 5)**

#### **Option A : Sac d'Explorateur (Groupe 4)**
- **Un sac à dos** - Sac d'équipement
- **Un sac de couchage** - Outils
- **Une gamelle** - Outils
- **Une boite d'allume-feu** - Outils
- **10 torches** - Outils, quantité 10
- **10 jours de rations** - Nourriture, quantité 10
- **Une gourde d'eau** - Nourriture
- **Une corde de 15m** - Outils

#### **Option B : Sac d'Ecclésiastique (Groupe 5)**
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

### **Équipement Obligatoire (Groupe 6)**
- **Un bouclier** (ID: 13) - Bouclier spécifique
- **Un symbole sacré** (Object ID: 45) - Outils spécifique

## 🔧 **Nouvelles Fonctionnalités Utilisées**

### **1. Auto-Insertion des Objets**
- **Couverture** : Nouvel objet créé (Object ID: 40)
- **Boîte pour l'aumône** : Nouvel objet créé (Object ID: 41)
- **Encensoir** : Nouvel objet créé (Object ID: 42)
- **Bâtonnets d'encens** : Nouvel objet créé (Object ID: 43)
- **Habits de cérémonie** : Nouvel objet créé (Object ID: 44)
- **Symbole sacré** : Nouvel objet créé (Object ID: 45)
- **Carreaux** : Nouvel objet créé (Object ID: 46)

### **2. Réutilisation d'Objets Existants**
- **Sac à dos** : Réutilisation (Object ID: 1)
- **Sac de couchage** : Réutilisation (Object ID: 19)
- **Gamelle** : Réutilisation (Object ID: 5)
- **Boite d'allume-feu** : Réutilisation (Object ID: 6)
- **Torche** : Réutilisation (Object ID: 7)
- **Corde de chanvre (15m)** : Réutilisation (Object ID: 8)
- **Rations de voyage** : Réutilisation (Object ID: 13)
- **Gourde d'eau** : Réutilisation (Object ID: 18)
- **Bougies** : Réutilisation (Object ID: 38)

### **3. Gestion des Quantités**
- **20 carreaux** : `nb = 20`
- **10 torches** : `nb = 10`
- **10 jours de rations** : `nb = 10`
- **10 bougies** : `nb = 10`
- **5 bougies supplémentaires** : `nb = 5`
- **2 bâtonnets d'encens** : `nb = 2`
- **2 jours de rations** : `nb = 2`

### **4. Filtres de Type**
- **"Armes courantes à distance"** : Pour le choix d'arme courante à distance
- **"Armes courantes de corps à corps"** : Pour le choix d'arme courante de corps à corps

### **5. Types d'Équipement Variés**
- **weapon** : Armes (masse, marteau, arbalète, carreaux)
- **armor** : Armures (écailles, cuir, cotte de mailles)
- **bouclier** : Bouclier
- **sac** : Sacs d'équipement
- **outils** : Outils divers
- **nourriture** : Rations et gourde

## 🎯 **Exemples de Combinaisons**

### **Option A : Clerc Explorateur**
- **Choix 1** : Masse d'armes
- **Choix 2** : Armure d'écailles
- **Choix 3** : Arbalète + carreaux
- **Choix 4** : Sac d'explorateur
- **Obligatoire** : Bouclier + Symbole sacré

### **Option B : Clerc Ecclésiastique**
- **Choix 1** : Marteau de guerre
- **Choix 2** : Cotte de mailles
- **Choix 3** : Arme courante à distance
- **Choix 4** : Sac d'ecclésiastique
- **Obligatoire** : Bouclier + Symbole sacré

### **Option C : Clerc Polyvalent**
- **Choix 1** : Masse d'armes
- **Choix 2** : Armure de cuir
- **Choix 3** : Arme courante de corps à corps
- **Choix 4** : Sac d'explorateur
- **Obligatoire** : Bouclier + Symbole sacré

## 📊 **Statistiques**

- **Total d'enregistrements** : 30
- **Choix 1** : 2 options d'armes
- **Choix 2** : 3 options d'armures
- **Choix 3** : 3 options d'armes secondaires
- **Choix 4a** : 8 items du sac d'explorateur
- **Choix 4b** : 12 items du sac d'ecclésiastique
- **Équipement obligatoire** : 2 items
- **Types d'équipement** : weapon, armor, bouclier, sac, outils, nourriture
- **Source** : class (ID: 3 - Clerc)

## ✅ **Vérification**

- **Base de données** : `u839591438_jdrmj`
- **Table** : `starting_equipment`
- **Source** : `class` (ID: 3 - Clerc)
- **Total** : 30 enregistrements
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

1. **`insert_cleric_equipment.php`** - Script d'insertion du Clerc
2. **`fix_cleric_equipment_ids.php`** - Script de correction des IDs
3. **`README_CLERIC_EQUIPMENT.md`** - Documentation complète

L'équipement de départ du Clerc est maintenant enregistré avec la nouvelle structure étendue et prêt à être utilisé dans le système de création de personnages !
