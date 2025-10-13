# 📚 Équipement de Départ du Sage

## 📋 Spécifications Enregistrées

L'équipement de départ du Sage a été enregistré dans la table `starting_equipment` selon les spécifications exactes demandées :

### 🎯 **Structure des Données Enregistrées**

| Groupe | Type | Description | Quantité | Object ID |
|--------|------|-------------|----------|-----------|
| **Obligatoire** | outils | Bouteille d'encre noire | 1 | 112 |
| **Obligatoire** | outils | Plume | 1 | 113 |
| **Obligatoire** | outils | Petit couteau | 1 | 53 |
| **Obligatoire** | outils | Lettre d'un collègue mort | 1 | 114 |
| **Obligatoire** | outils | Vêtements communs | 1 | 65 |

## 🎮 **Équipement du Joueur**

### **Équipement Obligatoire (Groupe 1)**
- **Une bouteille d'encre noire** (Object ID: 112) - Matériel d'écriture
- **Une plume** (Object ID: 113) - Outil d'écriture
- **Un petit couteau** (Object ID: 53) - Outil polyvalent
- **Une lettre d'un collègue mort** (Object ID: 114) - Document de recherche
- **Des vêtements communs** (Object ID: 65) - Vêtements de base

## 🔧 **Nouvelles Fonctionnalités Utilisées**

### **1. Réutilisation d'Objets Existants**
- **Petit couteau** : Réutilisation (Object ID: 53) - Déjà créé pour le Magicien et l'Enfant des Rues
- **Vêtements communs** : Réutilisation (Object ID: 65) - Déjà créé pour l'Acolyte, l'Enfant des Rues, l'Ermite, le Héros du Peuple et le Marin

### **2. Nouveaux Objets Créés**
- **Bouteille d'encre noire** : Nouvel objet (Object ID: 112)
- **Plume** : Nouvel objet (Object ID: 113)
- **Lettre d'un collègue mort** : Nouvel objet (Object ID: 114)

### **3. Types d'Équipement**
- **outils** : Équipement d'érudition et de recherche (5 obligatoires)

## 📊 **Statistiques**

- **Total d'enregistrements** : 5
- **Équipement obligatoire** : 5 items (bouteille d'encre + plume + petit couteau + lettre + vêtements)
- **Types d'équipement** : outils
- **Source** : background (ID: 11 - Sage)

## ✅ **Vérification**

- **Base de données** : `u839591438_jdrmj`
- **Table** : `starting_equipment`
- **Source** : `background` (ID: 11 - Sage)
- **Total** : 5 enregistrements
- **Statut** : ✅ Enregistré avec succès

## 🚀 **Avantages de la Nouvelle Structure**

1. **Simplicité** : Équipement obligatoire uniquement, pas de choix
2. **Clarté** : 5 items spécialisés pour l'érudition
3. **Organisation** : Groupe d'équipement unique
4. **Extensibilité** : Support de nouveaux types d'équipement
5. **Performance** : Index optimisés
6. **Auto-insertion** : Création automatique des objets dans la table Object
7. **Réutilisation** : Utilisation d'objets existants

## 🔧 **Fichiers Créés**

1. **`insert_sage_equipment.php`** - Script d'insertion du Sage
2. **`README_SAGE_EQUIPMENT.md`** - Documentation complète

## 📚 **Spécificités du Sage**

### **Équipement d'Érudition**
- **Bouteille d'encre noire** : Matériel d'écriture pour les recherches
- **Plume** : Outil d'écriture pour noter les découvertes
- **Petit couteau** : Outil polyvalent pour les tâches
- **Lettre d'un collègue mort** : Document de recherche et motivation
- **Vêtements communs** : Vêtements de base pour la vie d'érudit

### **Équipement Obligatoire**
- **Pas de choix** : Tous les items sont obligatoires
- **Spécialisation** : Chaque item correspond à l'érudition
- **Cohérence** : Ensemble cohérent pour la vie de sage

### **Catégories d'Équipement**

#### **Matériel d'Écriture**
- **Bouteille d'encre noire** : Matériel d'écriture pour les recherches et notes
- **Plume** : Outil d'écriture pour noter les découvertes

#### **Outils Polyvalents**
- **Petit couteau** : Outil polyvalent pour les tâches quotidiennes

#### **Documents de Recherche**
- **Lettre d'un collègue mort** : Document de recherche et motivation pour continuer les études

#### **Vêtements de Base**
- **Vêtements communs** : Vêtements de base pour la vie d'érudit

### **Flexibilité Tactique**
- **Équipement obligatoire** : 5 items spécialisés pour l'érudition
- **Pas de choix** : Tous les items sont nécessaires
- **Spécialisation** : Chaque item correspond à un aspect de l'érudition

### **Avantages Tactiques**
- **Recherche** : Matériel d'écriture pour les études
- **Documentation** : Plume et encre pour noter les découvertes
- **Utilité** : Petit couteau pour les tâches polyvalentes
- **Motivation** : Lettre d'un collègue mort pour continuer les recherches
- **Survie** : Vêtements communs pour la vie quotidienne
- **Polyvalence** : Ensemble complet pour la vie d'érudit

## 🎯 **Exemples d'Utilisation**

### **Sage Bibliothécaire**
- **Bouteille d'encre noire** : Pour cataloguer les livres
- **Plume** : Pour noter les références
- **Petit couteau** : Pour réparer les livres
- **Lettre d'un collègue mort** : Pour continuer ses recherches
- **Vêtements communs** : Pour la vie quotidienne

### **Sage Alchimiste**
- **Bouteille d'encre noire** : Pour noter les formules
- **Plume** : Pour documenter les expériences
- **Petit couteau** : Pour préparer les ingrédients
- **Lettre d'un collègue mort** : Pour résoudre l'énigme
- **Vêtements communs** : Pour la vie quotidienne

### **Sage Historien**
- **Bouteille d'encre noire** : Pour écrire l'histoire
- **Plume** : Pour documenter les événements
- **Petit couteau** : Pour les tâches quotidiennes
- **Lettre d'un collègue mort** : Pour continuer ses recherches
- **Vêtements communs** : Pour la vie quotidienne

## 🔍 **Détails Techniques**

### **Structure de la Table**
```sql
INSERT INTO starting_equipment 
(src, src_id, type, type_id, groupe_id, type_choix, nb) 
VALUES 
('background', 11, 'outils', 112, 1, 'obligatoire', 1),  -- Bouteille d'encre noire
('background', 11, 'outils', 113, 1, 'obligatoire', 1),  -- Plume
('background', 11, 'outils', 53, 1, 'obligatoire', 1),   -- Petit couteau
('background', 11, 'outils', 114, 1, 'obligatoire', 1),  -- Lettre d'un collègue mort
('background', 11, 'outils', 65, 1, 'obligatoire', 1);   -- Vêtements communs
```

### **Colonnes Utilisées**
- **src** : 'background' (source d'origine)
- **src_id** : 11 (ID du Sage)
- **type** : 'outils' (type d'équipement)
- **type_id** : ID de l'objet dans la table Object
- **groupe_id** : 1 (groupe d'équipement)
- **type_choix** : 'obligatoire' (pas de choix)
- **nb** : 1 (quantité)

### **Réutilisation d'Objets**
- **Petit couteau** (ID: 53) : Réutilisé du Magicien et de l'Enfant des Rues
- **Vêtements communs** (ID: 65) : Réutilisé de l'Acolyte, l'Enfant des Rues, l'Ermite, le Héros du Peuple et le Marin

### **Nouveaux Objets Créés**
- **Bouteille d'encre noire** (ID: 112) : Nouvel objet créé
- **Plume** (ID: 113) : Nouvel objet créé
- **Lettre d'un collègue mort** (ID: 114) : Nouvel objet créé

## 📚 **Comparaison avec Autres Backgrounds**

### **Acolyte** (5 items obligatoires)
- Symbole sacré, livre de prières, bâtons d'encens, habits de cérémonie, vêtements communs

### **Guild Artisan** (1 choix + 2 obligatoires)
- Choix de 17 outils d'artisan + lettre de recommandation + vêtements de voyage

### **Artiste** (1 choix + 2 obligatoires)
- Choix de 10 instruments + cadeau d'un admirateur + costume

### **Charlatan** (3 obligatoires)
- Vêtements fins + kit de déguisement + outils d'escroquerie

### **Criminel** (2 obligatoires)
- Pied-de-biche + vêtements sombres avec capuche

### **Enfant des Rues** (5 obligatoires)
- Petit couteau + carte de la ville + souris domestiquée + souvenir des parents + vêtements communs

### **Ermite** (4 obligatoires)
- Étui à parchemin + couverture pour l'hiver + vêtements communs + kit d'herboriste

### **Héros du Peuple** (1 choix + 3 obligatoires)
- Choix de 17 outils d'artisan + pelle + pot en fer + vêtements communs

### **Marin** (4 obligatoires)
- Cabillot d'amarrage + corde en soie de 15m + porte bonheur + vêtements communs

### **Noble** (3 obligatoires)
- Vêtements fins + chevalière + lettre de noblesse

### **Sage** (5 obligatoires)
- Bouteille d'encre noire + plume + petit couteau + lettre d'un collègue mort + vêtements communs

## 🚀 **Avantages du Système**

1. **Flexibilité** : Support de différents types d'équipement
2. **Réutilisation** : Utilisation d'objets existants
3. **Extensibilité** : Ajout facile de nouveaux backgrounds
4. **Performance** : Index optimisés pour les requêtes
5. **Maintenabilité** : Structure claire et documentée
6. **Cohérence** : Même structure pour tous les backgrounds

## 🎭 **Spécificités du Sage**

### **Équipement d'Érudition**
- **5 items obligatoires** : Ensemble spécialisé pour l'érudition
- **Pas de choix** : Tous les items sont nécessaires
- **Spécialisation** : Chaque item correspond à l'érudition

### **Équipement d'Écriture**
- **Bouteille d'encre noire** : Matériel d'écriture pour les recherches
- **Plume** : Outil d'écriture pour noter les découvertes

### **Équipement Polyvalent**
- **Petit couteau** : Outil polyvalent pour les tâches quotidiennes

### **Équipement de Motivation**
- **Lettre d'un collègue mort** : Document de recherche et motivation

### **Équipement de Base**
- **Vêtements communs** : Vêtements de base pour la vie d'érudit

### **Avantages Tactiques**
- **Recherche** : Matériel d'écriture pour les études
- **Documentation** : Plume et encre pour noter les découvertes
- **Utilité** : Petit couteau pour les tâches polyvalentes
- **Motivation** : Lettre d'un collègue mort pour continuer les recherches
- **Survie** : Vêtements communs pour la vie quotidienne
- **Polyvalence** : Ensemble complet pour la vie d'érudit

L'équipement de départ du Sage est maintenant enregistré avec la nouvelle structure étendue et prêt à être utilisé dans le système de création de personnages ! Il s'agit du onzième background enregistré dans le système, démontrant la flexibilité de la structure pour gérer les équipements d'historiques avec des équipements spécialisés pour l'érudition.
