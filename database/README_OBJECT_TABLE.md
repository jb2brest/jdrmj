# 📦 Table `Object`

## 📋 Vue d'ensemble

La table `Object` a été créée pour gérer les objets d'équipement de type sac, outils et nourriture. Cette table permet de référencer les objets utilisés dans l'équipement de départ des personnages.

## 🗂️ Structure de la Table

### **Colonnes Principales**

| Colonne | Type | Description |
|---------|------|-------------|
| **id** | INT AUTO_INCREMENT | Identifiant unique |
| **type** | ENUM | Type d'objet : sac, outils, nourriture |
| **nom** | VARCHAR(100) | Nom de l'objet |

### **Colonnes Système**

| Colonne | Type | Description |
|---------|------|-------------|
| **created_at** | TIMESTAMP | Date de création |
| **updated_at** | TIMESTAMP | Date de dernière modification |

## 🔍 **Contraintes et Index**

### **Contraintes**
- **PRIMARY KEY** : `id`
- **UNIQUE KEY** : `unique_nom_type` (nom, type) - Empêche les doublons
- **NOT NULL** : `type` et `nom`

### **Index**
- `idx_type` : Recherche par type d'objet
- `idx_nom` : Recherche par nom d'objet
- `unique_nom_type` : Contrainte d'unicité

## 📊 **Données Insérées**

### **Objets de Type "sac" (4 objets)**
| ID | Nom |
|----|-----|
| 1 | Sac à dos |
| 2 | Sac de couchage |
| 3 | Sac d'explorateur |
| 4 | Sac d'exploration souterraine |

### **Objets de Type "outils" (8 objets)**
| ID | Nom |
|----|-----|
| 5 | Gamelle |
| 6 | Boite d'allume-feu |
| 7 | Torche |
| 8 | Corde de chanvre (15m) |
| 9 | Pied de biche |
| 10 | Marteau |
| 11 | Piton |
| 12 | Gourde d'eau |

### **Objets de Type "nourriture" (5 objets)**
| ID | Nom |
|----|-----|
| 13 | Rations de voyage |
| 14 | Pain |
| 15 | Fromage |
| 16 | Viande séchée |
| 17 | Fruits secs |

## 🎯 **Utilisation**

### **Relation avec `starting_equipment`**
La table `Object` peut être utilisée pour référencer les objets dans la table `starting_equipment` :

```sql
-- Exemple de jointure
SELECT se.*, o.nom as object_name
FROM starting_equipment se
LEFT JOIN Object o ON se.type = o.type AND se.nom = o.nom
WHERE se.src = 'class' AND se.src_id = 1;
```

### **Recherche par Type**
```sql
-- Tous les sacs
SELECT * FROM Object WHERE type = 'sac';

-- Tous les outils
SELECT * FROM Object WHERE type = 'outils';

-- Toute la nourriture
SELECT * FROM Object WHERE type = 'nourriture';
```

### **Recherche par Nom**
```sql
-- Rechercher un objet spécifique
SELECT * FROM Object WHERE nom LIKE '%sac%';
```

## 🔧 **Fonctionnalités**

### **1. Gestion des Types**
- **sac** : Sacs et contenants
- **outils** : Outils et équipements
- **nourriture** : Nourriture et boissons

### **2. Contrainte d'Unicité**
- Empêche les doublons de nom/type
- Exemple : Un seul "Sac à dos" de type "sac"

### **3. Index Optimisés**
- Recherche rapide par type
- Recherche rapide par nom
- Contrainte d'unicité efficace

## 📈 **Statistiques**

- **Total d'objets** : 17
- **Sacs** : 4 objets
- **Outils** : 8 objets
- **Nourriture** : 5 objets

## 🚀 **Avantages**

### **1. Normalisation**
- Évite la duplication des noms d'objets
- Structure claire et organisée

### **2. Flexibilité**
- Facile d'ajouter de nouveaux objets
- Support de nouveaux types si nécessaire

### **3. Performance**
- Index optimisés pour les recherches
- Contraintes d'unicité efficaces

### **4. Intégrité**
- Contraintes pour éviter les doublons
- Types ENUM pour la cohérence

## 🔮 **Évolutions Possibles**

### **Ajouts Futurs**
1. **Description** : Champ pour décrire l'objet
2. **Poids** : Champ pour le poids de l'objet
3. **Valeur** : Champ pour la valeur monétaire
4. **Rareté** : Champ pour la rareté de l'objet

### **Nouveaux Types**
- **armure** : Armures et protections
- **arme** : Armes (si séparé des weapons)
- **accessoire** : Accessoires divers

## ✅ **État Actuel**

- ✅ **Table créée** avec la structure demandée
- ✅ **17 objets** insérés pour test
- ✅ **Index optimisés** pour les performances
- ✅ **Contraintes** pour l'intégrité des données
- ✅ **Prête** pour l'utilisation dans le système

La table `Object` est maintenant opérationnelle et prête à être utilisée pour référencer les objets d'équipement dans le système !
