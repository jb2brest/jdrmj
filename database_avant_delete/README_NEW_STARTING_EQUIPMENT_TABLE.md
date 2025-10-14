# 🗄️ Nouvelle Table `starting_equipment`

## 📋 Vue d'ensemble

La table `starting_equipment` a été remplacée par une nouvelle version avec des colonnes étendues pour une gestion plus fine des équipements de départ.

## 🗂️ Structure de la Nouvelle Table

### **Colonnes Principales**

| Colonne | Type | Description |
|---------|------|-------------|
| **id** | INT AUTO_INCREMENT | Identifiant unique |
| **src** | VARCHAR(20) | Source d'origine : class, background, race |
| **src_id** | INT | ID de la source d'origine |
| **type** | VARCHAR(20) | Type d'équipement : Outils, Armure, Bouclier, Arme, Accessoire, Sac |
| **type_id** | INT | ID de l'équipement précis dans la table de description lié au type |
| **type_filter** | VARCHAR(50) | Si c'est une alternative à choisir dans une liste du type défini |
| **no_choix** | INT | Le numéro du choix |
| **option_letter** | CHAR(1) | La lettre d'option : a, b, c |
| **type_choix** | ENUM | Type de choix : obligatoire ou à_choisir |
| **nb** | INT | Le nombre d'objet (défaut: 1) |
| **groupe_id** | INT | ID de groupe pour les items venant en groupe |

### **Colonnes Système**

| Colonne | Type | Description |
|---------|------|-------------|
| **created_at** | TIMESTAMP | Date de création |
| **updated_at** | TIMESTAMP | Date de dernière modification |

## 🔍 **Nouvelles Fonctionnalités**

### **1. Gestion des Filtres (`type_filter`)**
- Permet de spécifier des alternatives dans une liste du type défini
- Exemple : "Armes de guerre de corps à corps", "Armes courantes"

### **2. Numérotation des Choix (`no_choix`)**
- Numéro séquentiel pour organiser les choix
- Facilite l'affichage et la gestion

### **3. Lettres d'Option (`option_letter`)**
- Lettres a, b, c pour les choix multiples
- Remplace l'ancien `option_indice`

### **4. Quantité d'Objets (`nb`)**
- Nombre d'objets pour chaque équipement
- Défaut : 1, mais peut être modifié (ex: "4 javelines")

### **5. Index Optimisés**
- `idx_src_src_id` : Recherche par source
- `idx_type` : Recherche par type d'équipement
- `idx_groupe_id` : Recherche par groupe
- `idx_option_letter` : Recherche par lettre d'option
- `idx_no_choix` : Recherche par numéro de choix

## 📊 **Exemples d'Utilisation**

### **Équipement Obligatoire**
```sql
INSERT INTO starting_equipment (src, src_id, type, type_id, nb, groupe_id, type_choix) 
VALUES ('class', 1, 'Sac', 1, 1, 1, 'obligatoire');
```

### **Choix d'Arme avec Filtre**
```sql
INSERT INTO starting_equipment (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix) 
VALUES ('class', 1, 'Arme', 'Armes de guerre de corps à corps', 1, 'b', 1, 'à_choisir');
```

### **Arme Spécifique**
```sql
INSERT INTO starting_equipment (src, src_id, type, type_id, nb, groupe_id, type_choix) 
VALUES ('class', 1, 'Arme', 5, 4, 2, 'obligatoire');
```

## 🔄 **Migration des Données**

### **Sauvegarde**
- Les données précédentes ont été sauvegardées dans `backup_starting_equipment_current.sql`
- 6 enregistrements de l'équipement du Barbare ont été préservés

### **État Actuel**
- ✅ **Table recréée** avec la nouvelle structure
- ✅ **Vide** et prête à recevoir de nouvelles données
- ✅ **Index optimisés** pour les performances
- ✅ **Commentaires** sur toutes les colonnes

## 🎯 **Avantages de la Nouvelle Structure**

### **1. Flexibilité Accrue**
- Gestion des quantités d'objets
- Filtres pour les choix génériques
- Numérotation des choix

### **2. Performance Améliorée**
- Index sur les colonnes les plus utilisées
- Requêtes optimisées

### **3. Lisibilité**
- Commentaires sur toutes les colonnes
- Structure claire et documentée

### **4. Extensibilité**
- Facile d'ajouter de nouveaux types d'équipement
- Support des équipements de race
- Gestion des backgrounds complexes

## 🚀 **Prochaines Étapes**

1. **Réajouter l'équipement du Barbare** avec la nouvelle structure
2. **Adapter les fonctions PHP** pour utiliser les nouvelles colonnes
3. **Mettre à jour l'interface d'administration**
4. **Tester le système** avec les nouvelles données

La nouvelle table `starting_equipment` est maintenant prête et offre une flexibilité maximale pour gérer les équipements de départ des personnages !
