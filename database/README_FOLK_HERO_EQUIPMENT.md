# 🛡️ Équipement de Départ du Héros du Peuple

## 📋 Spécifications Enregistrées

L'équipement de départ du Héros du Peuple a été enregistré dans la table `starting_equipment` selon les spécifications exactes demandées :

### 🎯 **Structure des Données Enregistrées**

| Groupe | Choix | Type | Description | Quantité | Object ID |
|--------|-------|------|-------------|----------|-----------|
| **Choix 1** | a | outils | Matériel d'alchimiste | 1 | 66 |
| **Choix 1** | b | outils | Matériel de brasseur | 1 | 67 |
| **Choix 1** | c | outils | Matériel de calligraphe | 1 | 68 |
| **Choix 1** | d | outils | Matériel de peintre | 1 | 69 |
| **Choix 1** | e | outils | Outils de bijoutier | 1 | 70 |
| **Choix 1** | f | outils | Outils de bricoleur | 1 | 71 |
| **Choix 1** | g | outils | Outils de cartographe | 1 | 72 |
| **Choix 1** | h | outils | Outils de charpentier | 1 | 73 |
| **Choix 1** | i | outils | Outils de cordonnier | 1 | 74 |
| **Choix 1** | j | outils | Outils de forgeron | 1 | 75 |
| **Choix 1** | k | outils | Outils de maçon | 1 | 76 |
| **Choix 1** | l | outils | Outils de menuisier | 1 | 77 |
| **Choix 1** | m | outils | Outils de potier | 1 | 78 |
| **Choix 1** | n | outils | Outils de souffleur de verre | 1 | 79 |
| **Choix 1** | o | outils | Outils de tanneur | 1 | 80 |
| **Choix 1** | p | outils | Outils de tisserand | 1 | 81 |
| **Choix 1** | q | outils | Ustensiles de cuisinier | 1 | 82 |
| **Obligatoire** | - | outils | Pelle | 1 | 103 |
| **Obligatoire** | - | outils | Pot en fer | 1 | 104 |
| **Obligatoire** | - | outils | Vêtements communs | 1 | 65 |

## 🎮 **Choix du Joueur**

### **Choix 1 : Outils d'Artisan (Groupe 1) - 17 Options**

#### **Matériel Artistique et Créatif**
- **(a) Matériel d'alchimiste** (Object ID: 66) - Outils pour l'alchimie
- **(b) Matériel de brasseur** (Object ID: 67) - Outils pour la brasserie
- **(c) Matériel de calligraphe** (Object ID: 68) - Outils pour la calligraphie
- **(d) Matériel de peintre** (Object ID: 69) - Outils pour la peinture

#### **Outils de Métiers Précieux**
- **(e) Outils de bijoutier** (Object ID: 70) - Outils pour la bijouterie
- **(f) Outils de bricoleur** (Object ID: 71) - Outils polyvalents
- **(g) Outils de cartographe** (Object ID: 72) - Outils pour la cartographie

#### **Outils de Construction**
- **(h) Outils de charpentier** (Object ID: 73) - Outils pour la charpenterie
- **(i) Outils de cordonnier** (Object ID: 74) - Outils pour la cordonnerie
- **(j) Outils de forgeron** (Object ID: 75) - Outils pour la forge
- **(k) Outils de maçon** (Object ID: 76) - Outils pour la maçonnerie
- **(l) Outils de menuisier** (Object ID: 77) - Outils pour la menuiserie

#### **Outils de Poterie et Verrerie**
- **(m) Outils de potier** (Object ID: 78) - Outils pour la poterie
- **(n) Outils de souffleur de verre** (Object ID: 79) - Outils pour la verrerie

#### **Outils de Transformation**
- **(o) Outils de tanneur** (Object ID: 80) - Outils pour le tannage
- **(p) Outils de tisserand** (Object ID: 81) - Outils pour le tissage
- **(q) Ustensiles de cuisinier** (Object ID: 82) - Outils pour la cuisine

### **Équipement Obligatoire (Groupe 2)**
- **Une pelle** (Object ID: 103) - Outil de travail de la terre
- **Un pot en fer** (Object ID: 104) - Récipient de cuisine
- **Des vêtements communs** (Object ID: 65) - Vêtements de base

## 🔧 **Nouvelles Fonctionnalités Utilisées**

### **1. Réutilisation d'Objets Existants**
- **Vêtements communs** : Réutilisation (Object ID: 65) - Déjà créé pour l'Acolyte, l'Enfant des Rues et l'Ermite

### **2. Nouveaux Objets Créés**
- **17 outils d'artisan** : Nouveaux objets (Object IDs: 66-82)
- **Pelle** : Nouvel objet (Object ID: 103)
- **Pot en fer** : Nouvel objet (Object ID: 104)

### **3. Types d'Équipement**
- **outils** : Équipement d'artisanat et de survie (20 items au total)

## 📊 **Statistiques**

- **Total d'enregistrements** : 20
- **Choix 1** : 17 options d'outils d'artisan (a-q)
- **Équipement obligatoire** : 3 items (pelle + pot en fer + vêtements communs)
- **Types d'équipement** : outils
- **Source** : background (ID: 8 - Héros du Peuple)

## ✅ **Vérification**

- **Base de données** : `u839591438_jdrmj`
- **Table** : `starting_equipment`
- **Source** : `background` (ID: 8 - Héros du Peuple)
- **Total** : 20 enregistrements
- **Statut** : ✅ Enregistré avec succès

## 🚀 **Avantages de la Nouvelle Structure**

1. **Flexibilité** : 17 choix différents d'outils d'artisan
2. **Clarté** : Numérotation et lettres d'option (a-q)
3. **Organisation** : Groupes d'équipement
4. **Extensibilité** : Support de nouveaux types d'équipement
5. **Performance** : Index optimisés
6. **Auto-insertion** : Création automatique des objets dans la table Object
7. **Réutilisation** : Utilisation d'objets existants

## 🔧 **Fichiers Créés**

1. **`insert_folk_hero_equipment.php`** - Script d'insertion du Héros du Peuple
2. **`README_FOLK_HERO_EQUIPMENT.md`** - Documentation complète

## 🛡️ **Spécificités du Héros du Peuple**

### **Équipement d'Artisanat**
- **17 outils différents** : Couvrant tous les métiers artisanaux
- **Spécialisation** : Chaque choix correspond à un métier spécifique
- **Diversité** : Métiers artistiques, de construction, de transformation

### **Équipement de Survie**
- **Pelle** : Pour le travail de la terre et la survie
- **Pot en fer** : Pour la cuisine et la survie
- **Vêtements communs** : Vêtements de base

### **Catégories d'Outils**

#### **Matériel Artistique et Créatif**
- **Matériel d'alchimiste** : Pour l'alchimie et les potions
- **Matériel de brasseur** : Pour la brasserie et les boissons
- **Matériel de calligraphe** : Pour l'écriture et l'art
- **Matériel de peintre** : Pour la peinture et l'art

#### **Outils de Métiers Précieux**
- **Outils de bijoutier** : Pour la bijouterie et les objets précieux
- **Outils de bricoleur** : Outils polyvalents pour les réparations
- **Outils de cartographe** : Pour la cartographie et la navigation

#### **Outils de Construction**
- **Outils de charpentier** : Pour la construction en bois
- **Outils de cordonnier** : Pour la fabrication de chaussures
- **Outils de forgeron** : Pour la forge et le métal
- **Outils de maçon** : Pour la construction en pierre
- **Outils de menuisier** : Pour la menuiserie et l'ébénisterie

#### **Outils de Poterie et Verrerie**
- **Outils de potier** : Pour la poterie et la céramique
- **Outils de souffleur de verre** : Pour la verrerie et le verre

#### **Outils de Transformation**
- **Outils de tanneur** : Pour le tannage du cuir
- **Outils de tisserand** : Pour le tissage et les textiles
- **Ustensiles de cuisinier** : Pour la cuisine et la gastronomie

### **Flexibilité Tactique**
- **Choix 1** : 17 options d'outils d'artisan (a-q)
- **Équipement obligatoire** : Pelle + pot en fer + vêtements communs
- **Spécialisation** : Chaque choix correspond à un métier spécifique

### **Avantages Tactiques**
- **Artisanat** : Outils spécialisés pour un métier
- **Survie** : Pelle et pot en fer pour la survie
- **Polyvalence** : Ensemble complet pour la vie d'artisan
- **Reconnaissance** : Métier reconnu par le peuple
- **Utilité** : Compétences utiles pour la communauté
- **Autonomie** : Capacité à créer et réparer

## 🎯 **Exemples de Combinaisons**

### **Héros du Peuple Forgeron**
- **Choix 1** : Outils de forgeron
- **Obligatoire** : Pelle + pot en fer + vêtements communs

### **Héros du Peuple Cuisinier**
- **Choix 1** : Ustensiles de cuisinier
- **Obligatoire** : Pelle + pot en fer + vêtements communs

### **Héros du Peuple Charpentier**
- **Choix 1** : Outils de charpentier
- **Obligatoire** : Pelle + pot en fer + vêtements communs

### **Héros du Peuple Alchimiste**
- **Choix 1** : Matériel d'alchimiste
- **Obligatoire** : Pelle + pot en fer + vêtements communs

### **Héros du Peuple Bricoleur**
- **Choix 1** : Outils de bricoleur
- **Obligatoire** : Pelle + pot en fer + vêtements communs

## 🔍 **Détails Techniques**

### **Structure de la Table**
```sql
INSERT INTO starting_equipment 
(src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
VALUES 
('background', 8, 'outils', 66, 1, 'a', 1, 'à_choisir', 1),   -- Matériel d'alchimiste
('background', 8, 'outils', 67, 1, 'b', 1, 'à_choisir', 1),   -- Matériel de brasseur
-- ... (autres choix) ...
('background', 8, 'outils', 82, 1, 'q', 1, 'à_choisir', 1),   -- Ustensiles de cuisinier
('background', 8, 'outils', 103, NULL, NULL, 2, 'obligatoire', 1),  -- Pelle
('background', 8, 'outils', 104, NULL, NULL, 2, 'obligatoire', 1),  -- Pot en fer
('background', 8, 'outils', 65, NULL, NULL, 2, 'obligatoire', 1);   -- Vêtements communs
```

### **Colonnes Utilisées**
- **src** : 'background' (source d'origine)
- **src_id** : 8 (ID du Héros du Peuple)
- **type** : 'outils' (type d'équipement)
- **type_id** : ID de l'objet dans la table Object
- **no_choix** : 1 (numéro du choix)
- **option_letter** : a-q (lettres d'option)
- **groupe_id** : 1 (choix) ou 2 (obligatoire)
- **type_choix** : 'à_choisir' ou 'obligatoire'
- **nb** : 1 (quantité)

### **Réutilisation d'Objets**
- **Vêtements communs** (ID: 65) : Réutilisé de l'Acolyte, l'Enfant des Rues et l'Ermite

### **Nouveaux Objets Créés**
- **17 outils d'artisan** (IDs: 66-82) : Nouveaux objets créés
- **Pelle** (ID: 103) : Nouvel objet créé
- **Pot en fer** (ID: 104) : Nouvel objet créé

## 🛡️ **Comparaison avec Autres Backgrounds**

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
- Choix de 17 outils d'artisan + pelle + pot en fer + vêtements communs - **Le plus de choix d'outils**

## 🚀 **Avantages du Système**

1. **Flexibilité** : Support de différents types d'équipement
2. **Réutilisation** : Utilisation d'objets existants
3. **Extensibilité** : Ajout facile de nouveaux backgrounds
4. **Performance** : Index optimisés pour les requêtes
5. **Maintenabilité** : Structure claire et documentée
6. **Cohérence** : Même structure pour tous les backgrounds

## 🎭 **Spécificités du Héros du Peuple**

### **Équipement d'Artisanat**
- **17 choix d'outils** : Le plus grand nombre de choix d'outils
- **Spécialisation** : Chaque choix correspond à un métier spécifique
- **Diversité** : Couvre tous les métiers artisanaux

### **Équipement de Survie**
- **Pelle** : Pour le travail de la terre et la survie
- **Pot en fer** : Pour la cuisine et la survie
- **Vêtements communs** : Vêtements de base

### **Avantages Tactiques**
- **Artisanat** : Outils spécialisés pour un métier
- **Survie** : Pelle et pot en fer pour la survie
- **Polyvalence** : Ensemble complet pour la vie d'artisan
- **Reconnaissance** : Métier reconnu par le peuple
- **Utilité** : Compétences utiles pour la communauté
- **Autonomie** : Capacité à créer et réparer

L'équipement de départ du Héros du Peuple est maintenant enregistré avec la nouvelle structure étendue et prêt à être utilisé dans le système de création de personnages ! Il s'agit du huitième background enregistré dans le système, démontrant la flexibilité de la structure pour gérer les équipements d'historiques avec des équipements d'artisanat. Le Héros du Peuple a le plus grand nombre de choix d'outils d'artisan de tous les backgrounds enregistrés, avec 17 options différentes.
