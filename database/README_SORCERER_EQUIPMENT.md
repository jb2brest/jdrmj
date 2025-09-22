# 🔮 Équipement de Départ de l'Ensorceleur

## 📋 Spécifications Enregistrées

L'équipement de départ de l'Ensorceleur a été enregistré dans la table `starting_equipment` selon les spécifications exactes demandées :

### 🎯 **Structure des Données Enregistrées**

| Groupe | Choix | Type | Description | Quantité | Object ID |
|--------|-------|------|-------------|----------|-----------|
| **Choix 1** | a | weapon | Arbalète légère | 1 | 11 |
| **Choix 1** | a | outils | Carreaux | 20 | 46 |
| **Choix 1** | b | weapon | Armes courantes à distance | 1 | - |
| **Choix 1** | c | weapon | Armes courantes de corps à corps | 1 | - |
| **Choix 2** | a | sac | Sacoche à composantes | 1 | 48 |
| **Choix 2** | b | outils | Focaliseur arcanique | 1 | 49 |
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
| **Obligatoire** | - | weapon | Dague | 2 | 2 |

## 🎮 **Choix du Joueur**

### **Choix 1 : Arme (Groupe 1)**

#### **Option A : Arbalète et Carreaux**
- **Arbalète légère** (ID: 11) - Arme spécifique
- **20 carreaux** (Object ID: 46) - Outils, quantité 20

#### **Option B : Arme Courante à Distance**
- **N'importe quelle arme courante (distance)** - Filtre générique

#### **Option C : Arme Courante de Corps à Corps**
- **N'importe quelle arme courante (corps à corps)** - Filtre générique

### **Choix 2 : Sacoche ou Focaliseur (Groupe 2)**
- **(a) Sacoche à composantes** (Object ID: 48) - Sac spécifique
- **(b) Focaliseur arcanique** (Object ID: 49) - Outils spécifique

### **Choix 3 : Sac d'Équipement (Groupes 3 et 4)**

#### **Option A : Sac d'Explorateur (Groupe 3)**
- **Un sac à dos** - Sac d'équipement
- **Un sac de couchage** - Outils
- **Une gamelle** - Outils
- **Une boite d'allume-feu** - Outils
- **10 torches** - Outils, quantité 10
- **10 jours de rations** - Nourriture, quantité 10
- **Une gourde d'eau** - Nourriture
- **Une corde de 15m** - Outils

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

### **Équipement Obligatoire (Groupe 5)**
- **2 dagues** (ID: 2) - Arme spécifique, quantité 2

## 🔧 **Nouvelles Fonctionnalités Utilisées**

### **1. Auto-Insertion des Objets**
- **Sacoche à composantes** : Nouvel objet créé (Object ID: 48)
- **Focaliseur arcanique** : Nouvel objet créé (Object ID: 49)

### **2. Réutilisation d'Objets Existants**
- **Sac à dos** : Réutilisation (Object ID: 1)
- **Sac de couchage** : Réutilisation (Object ID: 19)
- **Gamelle** : Réutilisation (Object ID: 5)
- **Boite d'allume-feu** : Réutilisation (Object ID: 6)
- **Torche** : Réutilisation (Object ID: 7)
- **Corde de chanvre (15m)** : Réutilisation (Object ID: 8)
- **Pied de biche** : Réutilisation (Object ID: 9)
- **Marteau** : Réutilisation (Object ID: 10)
- **Piton** : Réutilisation (Object ID: 11)
- **Rations de voyage** : Réutilisation (Object ID: 13)
- **Gourde d'eau** : Réutilisation (Object ID: 18)
- **Carreaux** : Réutilisation (Object ID: 46)

### **3. Gestion des Quantités**
- **20 carreaux** : `nb = 20`
- **10 torches** : `nb = 10` (dans les deux sacs)
- **10 jours de rations** : `nb = 10` (dans les deux sacs)
- **10 pitons** : `nb = 10`
- **2 dagues** : `nb = 2`

### **4. Filtres de Type**
- **"Armes courantes à distance"** : Pour le choix d'arme courante à distance
- **"Armes courantes de corps à corps"** : Pour le choix d'arme courante de corps à corps

### **5. Types d'Équipement Variés**
- **weapon** : Armes (arbalète, carreaux, dagues)
- **sac** : Sacs d'équipement
- **outils** : Outils divers
- **nourriture** : Rations et gourde

## 🎯 **Exemples de Combinaisons**

### **Option A : Ensorceleur Arbalétrier**
- **Choix 1** : Arbalète + carreaux
- **Choix 2** : Sacoche à composantes
- **Choix 3** : Sac d'explorateur
- **Obligatoire** : 2 dagues

### **Option B : Ensorceleur Polyvalent**
- **Choix 1** : Arme courante à distance
- **Choix 2** : Focaliseur arcanique
- **Choix 3** : Sac d'exploration souterraine
- **Obligatoire** : 2 dagues

### **Option C : Ensorceleur Corps à Corps**
- **Choix 1** : Arme courante de corps à corps
- **Choix 2** : Sacoche à composantes
- **Choix 3** : Sac d'explorateur
- **Obligatoire** : 2 dagues

### **Option D : Ensorceleur Explorateur**
- **Choix 1** : Arbalète + carreaux
- **Choix 2** : Focaliseur arcanique
- **Choix 3** : Sac d'exploration souterraine
- **Obligatoire** : 2 dagues

## 📊 **Statistiques**

- **Total d'enregistrements** : 24
- **Choix 1** : 3 options d'armes
- **Choix 2** : 2 options (sacoche ou focaliseur)
- **Choix 3a** : 8 items du sac d'explorateur
- **Choix 3b** : 9 items du sac d'exploration souterraine
- **Équipement obligatoire** : 1 item (2 dagues)
- **Types d'équipement** : weapon, sac, outils, nourriture
- **Source** : class (ID: 5 - Ensorceleur)

## ✅ **Vérification**

- **Base de données** : `u839591438_jdrmj`
- **Table** : `starting_equipment`
- **Source** : `class` (ID: 5 - Ensorceleur)
- **Total** : 24 enregistrements
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

1. **`insert_sorcerer_equipment.php`** - Script d'insertion de l'Ensorceleur
2. **`README_SORCERER_EQUIPMENT.md`** - Documentation complète

## 🔮 **Spécificités de l'Ensorceleur**

### **Équipement Unique**
- **Sacoche à composantes** : Pour stocker les composantes matérielles des sorts
- **Focaliseur arcanique** : Pour canaliser l'énergie magique
- **Arbalète légère** : Arme de prédilection pour les lanceurs de sorts

### **Équipement de Survie**
- **Sac d'explorateur** : Équipement standard pour l'aventure
- **Sac d'exploration souterraine** : Équipement spécialisé pour les donjons
- **2 dagues** : Armes de secours obligatoires

### **Flexibilité Tactique**
- **Choix 1** : Arme à distance (arbalète) ou arme courante
- **Choix 2** : Support magique (sacoche ou focaliseur)
- **Choix 3** : Équipement d'aventure (explorateur ou souterrain)
- **Équipement obligatoire** : 2 dagues pour la défense rapprochée

### **Spécialisation Magique**
- **Sacoche à composantes** : Essentielle pour les sorts nécessitant des composantes
- **Focaliseur arcanique** : Alternative pour les sorts sans composantes
- **Arbalète légère** : Permet de lancer des sorts à distance tout en restant mobile

L'équipement de départ de l'Ensorceleur est maintenant enregistré avec la nouvelle structure étendue et prêt à être utilisé dans le système de création de personnages !
