# 🗺️ Hiérarchie Régions → Lieux - Documentation

## 📋 **Structure hiérarchique confirmée**

La relation **Région → Lieux** est correctement implémentée dans le système.

---

## 🏗️ **Hiérarchie complète**

### **Structure géographique :**
```
🌍 Monde
    ↓
🏴 Pays
    ↓
🗺️ Région
    ↓
📍 Lieu
```

### **Relations dans la base de données :**
- **`worlds`** → **`countries`** (1:N)
- **`countries`** → **`regions`** (1:N)
- **`regions`** → **`places`** (1:N)
- **`countries`** → **`places`** (1:N) *(relation directe aussi)*

---

## 🗄️ **Structure de la base de données**

### **Table `regions` :**
```sql
CREATE TABLE regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,                    -- Référence au pays
    name VARCHAR(100) NOT NULL,
    description TEXT,
    map_url VARCHAR(255),                       -- Carte de la région
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    UNIQUE KEY unique_region_per_country (country_id, name)
);
```

### **Table `places` :**
```sql
CREATE TABLE places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NULL,                       -- ✅ Modifié : peut être NULL
    title VARCHAR(120) NOT NULL,
    map_url VARCHAR(255),
    notes TEXT,
    position INT DEFAULT 0,
    country_id INT NULL,                        -- Référence directe au pays
    region_id INT NULL,                         -- Référence à la région
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL
);
```

---

## 🔧 **Modifications apportées**

### **1. Contrainte `campaign_id` modifiée :**
```sql
-- AVANT
campaign_id INT NOT NULL

-- APRÈS
campaign_id INT NULL
```

**Raison :** Les lieux peuvent exister indépendamment des campagnes, dans le cadre de la géographie du monde.

### **2. Relations de clés étrangères :**
- **`region_id`** → `regions(id)` avec `ON DELETE SET NULL`
- **`country_id`** → `countries(id)` avec `ON DELETE SET NULL`
- **`campaign_id`** → `campaigns(id)` avec `ON DELETE CASCADE`

---

## 📱 **Interface utilisateur**

### **Page `view_region.php` :**
- ✅ **Affichage des lieux** : Liste des lieux de la région
- ✅ **Création de lieux** : Modal pour créer un nouveau lieu
- ✅ **Édition de lieux** : Modal pour modifier un lieu existant
- ✅ **Suppression de lieux** : Suppression avec confirmation
- ✅ **Navigation** : Liens vers les lieux individuels

### **Création d'un lieu :**
```php
$stmt = $pdo->prepare("INSERT INTO places (title, notes, map_url, region_id, country_id, campaign_id) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$title, $notes, $map_url, $region_id, $region['country_id'], null]);
```

**Paramètres :**
- `$title` : Nom du lieu
- `$notes` : Description du lieu
- `$map_url` : Carte du lieu (optionnelle)
- `$region_id` : ID de la région parente
- `$region['country_id']` : ID du pays parent
- `null` : Pas de campagne associée

---

## 🎯 **Fonctionnalités**

### **Gestion des lieux dans une région :**
1. **Voir les lieux** : Liste de tous les lieux de la région
2. **Créer un lieu** : Ajouter un nouveau lieu à la région
3. **Modifier un lieu** : Éditer les informations d'un lieu
4. **Supprimer un lieu** : Retirer un lieu de la région
5. **Voir le lieu** : Accéder aux détails d'un lieu

### **Upload d'images :**
- **Formats acceptés** : JPG, JPEG, PNG, GIF, WebP
- **Taille maximale** : 5MB
- **Stockage** : `uploads/places/`
- **Aperçu** : Visualisation en temps réel

### **Navigation :**
```
Monde → Pays → Région → Lieux
  ↓       ↓       ↓       ↓
view_   view_   view_   view_
world   country region  place
```

---

## 🔍 **Vérifications effectuées**

### **Base de données :**
```sql
DESCRIBE places;  -- ✅ campaign_id peut être NULL
SHOW CREATE TABLE places;  -- ✅ Contraintes correctes
```

### **Syntaxe PHP :**
```bash
php -l view_region.php  # ✅ No syntax errors detected
```

### **Relations :**
- ✅ **Région → Lieux** : Relation 1:N fonctionnelle
- ✅ **Pays → Lieux** : Relation directe aussi disponible
- ✅ **Suppression en cascade** : Gestion correcte des suppressions

---

## 📊 **Exemples d'utilisation**

### **Créer une région avec des lieux :**
1. **Créer une région** : "Comté de la Marche"
2. **Ajouter des lieux** :
   - "Taverne du Dragon"
   - "Château de la Montagne"
   - "Village de la Rivière"
   - "Forêt des Ombres"

### **Structure résultante :**
```
Monde: Terre du Milieu
├── Pays: Royaume du Nord
    ├── Région: Comté de la Marche
    │   ├── Lieu: Taverne du Dragon
    │   ├── Lieu: Château de la Montagne
    │   ├── Lieu: Village de la Rivière
    │   └── Lieu: Forêt des Ombres
    └── Région: Province du Sud
        ├── Lieu: Port de la Mer
        └── Lieu: Mines de Fer
```

---

## ✅ **Avantages de cette structure**

### **Flexibilité :**
- **Lieux indépendants** : Peuvent exister sans campagne
- **Relations multiples** : Lieux liés à la fois à une région et un pays
- **Géographie cohérente** : Structure logique Monde → Pays → Région → Lieu

### **Gestion simplifiée :**
- **Création intuitive** : Créer des lieux dans le contexte d'une région
- **Navigation claire** : Parcours logique de la hiérarchie
- **Suppression sécurisée** : Gestion des dépendances

### **Évolutivité :**
- **Ajout de campagnes** : Les lieux peuvent être associés à des campagnes plus tard
- **Géographie étendue** : Possibilité d'ajouter d'autres niveaux
- **Réutilisation** : Les lieux peuvent être utilisés dans plusieurs contextes

---

## 🎉 **Conclusion**

La relation **Région → Lieux** est parfaitement implémentée :

- ✅ **Base de données** : Structure et contraintes correctes
- ✅ **Interface** : Gestion complète des lieux dans les régions
- ✅ **Navigation** : Parcours logique de la hiérarchie
- ✅ **Fonctionnalités** : CRUD complet pour les lieux

**🗺️ Les régions sont bien composées de lieux !**
