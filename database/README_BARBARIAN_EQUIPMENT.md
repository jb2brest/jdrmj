# 🪓 Équipement de Départ du Barbare

## 📋 Spécifications Ajoutées

L'équipement de départ du Barbare a été ajouté à la table `starting_equipment` selon les spécifications D&D 5e :

### 🎯 **Choix d'Équipement**

#### **Groupe 1 : Arme Principale (À choisir)**
- **(a) Hache à deux mains** (ID: 22)
- **(b) N'importe quelle arme de guerre de corps à corps** (générique)

#### **Groupe 2 : Arme Secondaire (À choisir)**
- **(a) Hachette** (ID: 4)
- **(b) N'importe quelle arme courante** (générique)

#### **Groupe 3 : Équipement Obligatoire**
- **Sac d'explorateur** (ID: 1)
- **Javeline** (ID: 5)

## 🗄️ **Données en Base**

| ID | Source | Type | Type ID | Option | Groupe | Choix | Nom de l'objet |
|----|--------|------|---------|--------|--------|-------|----------------|
| 140 | class | Arme | 22 | a | 1 | à_choisir | Hache à deux mains |
| 141 | class | Arme | NULL | b | 1 | à_choisir | N'importe quelle arme de guerre |
| 142 | class | Arme | 4 | a | 2 | à_choisir | Hachette |
| 143 | class | Arme | NULL | b | 2 | à_choisir | N'importe quelle arme courante |
| 144 | class | Sac | 1 | NULL | 3 | obligatoire | Sac d'explorateur |
| 145 | class | Arme | 5 | NULL | 3 | obligatoire | Javeline |

## 🎮 **Utilisation en Jeu**

### **Choix du Joueur**
1. **Arme principale** : Le joueur choisit entre une hache à deux mains ou une arme de guerre de corps à corps
2. **Arme secondaire** : Le joueur choisit entre deux hachettes ou une arme courante
3. **Équipement automatique** : Le joueur reçoit automatiquement un sac d'explorateur et une javeline

### **Exemples de Combinaisons**
- **Option A** : Hache à deux mains + Deux hachettes + Sac d'explorateur + Javeline
- **Option B** : Épée longue + Dague + Sac d'explorateur + Javeline
- **Option C** : Hache à deux mains + Bâton + Sac d'explorateur + Javeline

## 🔧 **Fichiers Créés**

### **`add_barbarian_equipment.sql`**
Script SQL pour ajouter l'équipement de départ du Barbare :

```sql
-- Groupe 1: Choix d'arme principale
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES 
('class', 1, 'Arme', 22, 'a', 1, 'à_choisir'),  -- Hache à deux mains
('class', 1, 'Arme', NULL, 'b', 1, 'à_choisir'); -- Arme de guerre générique

-- Groupe 2: Choix d'arme secondaire  
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES 
('class', 1, 'Arme', 4, 'a', 2, 'à_choisir'),   -- Hachette
('class', 1, 'Arme', NULL, 'b', 2, 'à_choisir'); -- Arme courante générique

-- Groupe 3: Équipement obligatoire
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES 
('class', 1, 'Sac', 1, NULL, 3, 'obligatoire'),     -- Sac d'explorateur
('class', 1, 'Arme', 5, NULL, 3, 'obligatoire');    -- Javeline
```

## ✅ **Vérification**

- **Total d'équipements** : 6 enregistrements
- **Classe** : Barbare (ID: 1)
- **Groupes** : 3 groupes d'équipement
- **Choix** : 2 groupes à choisir, 1 groupe obligatoire
- **Statut** : ✅ Ajouté avec succès

## 🎯 **Prochaines Étapes**

L'équipement de départ du Barbare est maintenant configuré et prêt à être utilisé dans le système de création de personnages. Les joueurs pourront choisir leur équipement selon les options disponibles lors de la création de leur personnage Barbare.
