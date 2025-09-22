# 🦹 Équipement de Départ du Criminel

## 📋 Spécifications Enregistrées

L'équipement de départ du Criminel a été enregistré dans la table `starting_equipment` selon les spécifications exactes demandées :

### 🎯 **Structure des Données Enregistrées**

| Groupe | Type | Description | Quantité | Object ID |
|--------|------|-------------|----------|-----------|
| **Obligatoire** | outils | Pied-de-biche | 1 | 95 |
| **Obligatoire** | outils | Vêtements communs sombres avec une capuche | 1 | 96 |

## 🎮 **Équipement du Joueur**

### **Équipement Obligatoire (Groupe 1)**
- **Un pied-de-biche** (Object ID: 95) - Outil pour forcer les serrures et portes
- **Des vêtements communs sombres avec une capuche** (Object ID: 96) - Vêtements pour se dissimuler

## 🔧 **Nouvelles Fonctionnalités Utilisées**

### **1. Nouveaux Objets Créés**
- **Pied-de-biche** : Nouvel objet (Object ID: 95)
- **Vêtements communs sombres avec une capuche** : Nouvel objet (Object ID: 96)

### **2. Types d'Équipement**
- **outils** : Équipement spécialisé pour le crime (2 obligatoires)

## 📊 **Statistiques**

- **Total d'enregistrements** : 2
- **Équipement obligatoire** : 2 items (pied-de-biche + vêtements sombres avec capuche)
- **Types d'équipement** : outils
- **Source** : background (ID: 5 - Criminel)

## ✅ **Vérification**

- **Base de données** : `u839591438_jdrmj`
- **Table** : `starting_equipment`
- **Source** : `background` (ID: 5 - Criminel)
- **Total** : 2 enregistrements
- **Statut** : ✅ Enregistré avec succès

## 🚀 **Avantages de la Nouvelle Structure**

1. **Simplicité** : Équipement obligatoire uniquement, pas de choix
2. **Clarté** : 2 items spécialisés pour le crime
3. **Organisation** : Groupe d'équipement unique
4. **Extensibilité** : Support de nouveaux types d'équipement
5. **Performance** : Index optimisés
6. **Auto-insertion** : Création automatique des objets dans la table Object

## 🔧 **Fichiers Créés**

1. **`insert_criminal_equipment.php`** - Script d'insertion du Criminel
2. **`README_CRIMINAL_EQUIPMENT.md`** - Documentation complète

## 🦹 **Spécificités du Criminel**

### **Équipement de Crime**
- **Pied-de-biche** : Pour forcer les serrures, portes et coffres
- **Vêtements sombres avec capuche** : Pour se dissimuler et éviter la reconnaissance

### **Équipement Obligatoire**
- **Pas de choix** : Tous les items sont obligatoires
- **Spécialisation** : Chaque item sert au crime
- **Cohérence** : Ensemble cohérent pour la vie criminelle

### **Catégories d'Équipement**

#### **Outils de Forçage**
- **Pied-de-biche** : Outil polyvalent pour forcer les accès

#### **Vêtements de Dissimulation**
- **Vêtements communs sombres avec une capuche** : Pour se dissimuler et éviter la reconnaissance

### **Flexibilité Tactique**
- **Équipement obligatoire** : 2 items spécialisés pour le crime
- **Pas de choix** : Tous les items sont nécessaires
- **Spécialisation** : Chaque item correspond à un aspect du crime

### **Avantages Tactiques**
- **Forçage** : Pied-de-biche pour accéder aux lieux fermés
- **Dissimulation** : Vêtements sombres avec capuche pour éviter la reconnaissance
- **Discrétion** : Équipement adapté à la vie criminelle
- **Polyvalence** : Ensemble complet pour les activités criminelles
- **Survie** : Équipement adapté à la vie de criminel

## 🎯 **Exemples d'Utilisation**

### **Criminel de Rue**
- **Pied-de-biche** : Pour forcer les portes et coffres
- **Vêtements sombres avec capuche** : Pour se dissimuler dans l'obscurité

### **Cambrioleur**
- **Pied-de-biche** : Pour forcer les serrures et accès
- **Vêtements sombres avec capuche** : Pour éviter la reconnaissance

### **Criminel Itinérant**
- **Pied-de-biche** : Pour forcer les accès en voyage
- **Vêtements sombres avec capuche** : Pour se dissimuler en déplacement

## 🔍 **Détails Techniques**

### **Structure de la Table**
```sql
INSERT INTO starting_equipment 
(src, src_id, type, type_id, groupe_id, type_choix, nb) 
VALUES 
('background', 5, 'outils', 95, 1, 'obligatoire', 1),  -- Pied-de-biche
('background', 5, 'outils', 96, 1, 'obligatoire', 1);  -- Vêtements sombres avec capuche
```

### **Colonnes Utilisées**
- **src** : 'background' (source d'origine)
- **src_id** : 5 (ID du Criminel)
- **type** : 'outils' (type d'équipement)
- **type_id** : ID de l'objet dans la table Object
- **groupe_id** : 1 (groupe d'équipement)
- **type_choix** : 'obligatoire' (pas de choix)
- **nb** : 1 (quantité)

### **Nouveaux Objets Créés**
- **Pied-de-biche** (ID: 95) : Nouvel objet créé
- **Vêtements communs sombres avec une capuche** (ID: 96) : Nouvel objet créé

## 🦹 **Comparaison avec Autres Backgrounds**

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

## 🚀 **Avantages du Système**

1. **Flexibilité** : Support de différents types d'équipement
2. **Extensibilité** : Ajout facile de nouveaux backgrounds
3. **Performance** : Index optimisés pour les requêtes
4. **Maintenabilité** : Structure claire et documentée
5. **Cohérence** : Même structure pour tous les backgrounds
6. **Simplicité** : Équipement minimal mais efficace

## 🎭 **Spécificités du Criminel**

### **Équipement Minimal**
- **2 items seulement** : Équipement essentiel pour le crime
- **Pas de choix** : Tous les items sont nécessaires
- **Spécialisation** : Chaque item correspond à un aspect du crime

### **Équipement de Forçage**
- **Pied-de-biche** : Outil polyvalent pour forcer les accès
- **Polyvalence** : Peut servir pour forcer portes, serrures, coffres

### **Équipement de Dissimulation**
- **Vêtements sombres avec capuche** : Pour se dissimuler
- **Discrétion** : Éviter la reconnaissance et la détection

### **Avantages Tactiques**
- **Forçage** : Accès aux lieux fermés
- **Dissimulation** : Éviter la reconnaissance
- **Discrétion** : Se déplacer sans être vu
- **Polyvalence** : Ensemble complet pour les activités criminelles
- **Survie** : Équipement adapté à la vie de criminel

L'équipement de départ du Criminel est maintenant enregistré avec la nouvelle structure étendue et prêt à être utilisé dans le système de création de personnages ! Il s'agit du cinquième background enregistré dans le système, démontrant la flexibilité de la structure pour gérer les équipements d'historiques avec des équipements spécialisés pour le crime.
