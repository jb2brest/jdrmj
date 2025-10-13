# 🥋 Équipement de Départ du Moine

## 📋 Spécifications Enregistrées

L'équipement de départ du Moine a été enregistré dans la table `starting_equipment` selon les spécifications exactes demandées :

### 🎯 **Structure des Données Enregistrées**

| Groupe | Choix | Type | Description | Quantité | Object ID |
|--------|-------|------|-------------|----------|-----------|
| **Choix 1** | a | weapon | Épée courte | 1 | 18 |
| **Choix 1** | b | weapon | Armes courantes à distance | 1 | - |
| **Choix 1** | c | weapon | Armes courantes de corps à corps | 1 | - |
| **Choix 2a** | a | sac | Sac à dos | 1 | 1 |
| **Choix 2a** | a | outils | Sac de couchage | 1 | 19 |
| **Choix 2a** | a | outils | Gamelle | 1 | 5 |
| **Choix 2a** | a | outils | Boite d'allume-feu | 1 | 6 |
| **Choix 2a** | a | outils | Torche | 10 | 7 |
| **Choix 2a** | a | nourriture | Rations de voyage | 10 | 13 |
| **Choix 2a** | a | nourriture | Gourde d'eau | 1 | 18 |
| **Choix 2a** | a | outils | Corde de chanvre (15m) | 1 | 8 |
| **Choix 2b** | b | sac | Sac à dos | 1 | 1 |
| **Choix 2b** | b | outils | Pied de biche | 1 | 9 |
| **Choix 2b** | b | outils | Marteau | 1 | 10 |
| **Choix 2b** | b | outils | Piton | 10 | 11 |
| **Choix 2b** | b | outils | Torche | 10 | 7 |
| **Choix 2b** | b | outils | Boite d'allume-feu | 1 | 6 |
| **Choix 2b** | b | nourriture | Rations de voyage | 10 | 13 |
| **Choix 2b** | b | nourriture | Gourde d'eau | 1 | 18 |
| **Choix 2b** | b | outils | Corde de chanvre (15m) | 1 | 8 |
| **Obligatoire** | - | weapon | Fléchettes | 10 | 13 |

## 🎮 **Choix du Joueur**

### **Choix 1 : Arme (Groupe 1)**
- **(a) Une épée courte** (ID: 18) - Arme de mêlée légère
- **(b) N'importe quelle arme courante (distance)** - Filtre générique
- **(c) N'importe quelle arme courante (corps à corps)** - Filtre générique

### **Choix 2 : Sac d'Équipement (Groupes 2 et 3)**

#### **Option A : Sac d'Explorateur (Groupe 2)**
- **Un sac à dos** - Sac d'équipement
- **Un sac de couchage** - Outils
- **Une gamelle** - Outils
- **Une boite d'allume-feu** - Outils
- **10 torches** - Outils, quantité 10
- **10 jours de rations** - Nourriture, quantité 10
- **Une gourde d'eau** - Nourriture
- **Une corde de 15m** - Outils

#### **Option B : Sac d'Exploration Souterraine (Groupe 3)**
- **Un sac à dos** - Sac d'équipement
- **Un pied de biche** - Outils
- **Un marteau** - Outils
- **10 pitons** - Outils, quantité 10
- **10 torches** - Outils, quantité 10
- **Une boite d'allume-feu** - Outils
- **10 jours de rations** - Nourriture, quantité 10
- **Une gourde d'eau** - Nourriture
- **Une corde de 15m** - Outils

### **Équipement Obligatoire (Groupe 4)**
- **10 fléchettes** (ID: 13) - Arme de lancer, quantité 10

## 🔧 **Nouvelles Fonctionnalités Utilisées**

### **1. Réutilisation d'Objets Existants**
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

### **2. Gestion des Quantités**
- **10 torches** : `nb = 10` (dans les deux sacs)
- **10 jours de rations** : `nb = 10` (dans les deux sacs)
- **10 pitons** : `nb = 10`
- **10 fléchettes** : `nb = 10`

### **3. Filtres de Type**
- **"Armes courantes à distance"** : Pour le choix d'arme courante à distance
- **"Armes courantes de corps à corps"** : Pour le choix d'arme courante de corps à corps

### **4. Types d'Équipement Variés**
- **weapon** : Armes (épée courte, fléchettes)
- **sac** : Sacs d'équipement
- **outils** : Outils divers
- **nourriture** : Rations et gourde

## 🎯 **Exemples de Combinaisons**

### **Option A : Moine Explorateur**
- **Choix 1** : Épée courte
- **Choix 2** : Sac d'explorateur
- **Obligatoire** : 10 fléchettes

### **Option B : Moine Distance**
- **Choix 1** : Arme courante à distance
- **Choix 2** : Sac d'exploration souterraine
- **Obligatoire** : 10 fléchettes

### **Option C : Moine Corps à Corps**
- **Choix 1** : Arme courante de corps à corps
- **Choix 2** : Sac d'explorateur
- **Obligatoire** : 10 fléchettes

### **Option D : Moine Polyvalent**
- **Choix 1** : Épée courte
- **Choix 2** : Sac d'exploration souterraine
- **Obligatoire** : 10 fléchettes

## 📊 **Statistiques**

- **Total d'enregistrements** : 21
- **Choix 1** : 3 options d'armes
- **Choix 2a** : 8 items du sac d'explorateur
- **Choix 2b** : 9 items du sac d'exploration souterraine
- **Équipement obligatoire** : 1 item (10 fléchettes)
- **Types d'équipement** : weapon, sac, outils, nourriture
- **Source** : class (ID: 8 - Moine)

## ✅ **Vérification**

- **Base de données** : `u839591438_jdrmj`
- **Table** : `starting_equipment`
- **Source** : `class` (ID: 8 - Moine)
- **Total** : 21 enregistrements
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

1. **`insert_monk_equipment.php`** - Script d'insertion du Moine
2. **`fix_monk_equipment_ids.php`** - Script de correction des IDs
3. **`README_MONK_EQUIPMENT.md`** - Documentation complète

## 🥋 **Spécificités du Moine**

### **Équipement Minimaliste**
- **Épée courte** : Arme de mêlée légère et élégante
- **10 fléchettes** : Armes de lancer obligatoires
- **Pas d'armure** : Le moine se fie à sa dextérité et ses arts martiaux

### **Équipement de Survie**
- **Sac d'explorateur** : Équipement standard pour l'aventure
- **Sac d'exploration souterraine** : Équipement spécialisé pour les donjons
- **Outils polyvalents** : Torches, corde, gamelle, etc.

### **Flexibilité Tactique**
- **Choix 1** : Arme de mêlée (épée courte) ou arme courante
- **Choix 2** : Équipement d'aventure (explorateur ou souterrain)
- **Équipement obligatoire** : 10 fléchettes pour le combat à distance

### **Philosophie Monastique**
- **Simplicité** : Équipement minimal et fonctionnel
- **Mobilité** : Pas d'armure lourde, privilégie la vitesse
- **Polyvalence** : Peut combattre à distance et en mêlée
- **Autonomie** : Équipement de survie complet

### **Spécialisations Possibles**
- **Moine Explorateur** : Épée courte + Sac d'explorateur + Fléchettes
- **Moine Distance** : Arme courante à distance + Sac d'exploration souterraine + Fléchettes
- **Moine Corps à Corps** : Arme courante de corps à corps + Sac d'explorateur + Fléchettes
- **Moine Polyvalent** : Épée courte + Sac d'exploration souterraine + Fléchettes

### **Avantages Tactiques**
- **Mobilité** : Pas d'armure lourde, déplacement rapide
- **Polyvalence** : Combat à distance et en mêlée
- **Autonomie** : Équipement de survie complet
- **Simplicité** : Maintenance facile de l'équipement

L'équipement de départ du Moine est maintenant enregistré avec la nouvelle structure étendue et prêt à être utilisé dans le système de création de personnages !
