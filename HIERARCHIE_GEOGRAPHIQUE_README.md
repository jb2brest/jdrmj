# 🌍 Système de Hiérarchie Géographique - Documentation

## 📋 **Vue d'ensemble**

Le système de hiérarchie géographique permet aux MJ de créer une structure complète : **Monde → Pays → Régions → Lieux**. Chaque niveau peut avoir des cartes et des blasons (sauf les lieux qui n'ont que des cartes).

---

## 🏗️ **Structure hiérarchique**

### **1. Monde (Worlds)**
- **Nom** : Nom du monde
- **Description** : Description générale
- **Carte** : Carte du monde entier
- **Créateur** : MJ propriétaire

### **2. Pays (Countries)**
- **Nom** : Nom du pays
- **Description** : Description du pays
- **Carte** : Carte géographique du pays
- **Blason** : Blason héraldique du pays
- **Monde** : Appartient à un monde

### **3. Régions (Regions)**
- **Nom** : Nom de la région
- **Description** : Description de la région
- **Carte** : Carte de la région
- **Blason** : Blason de la région
- **Pays** : Appartient à un pays

### **4. Lieux (Places)**
- **Nom** : Nom du lieu
- **Description** : Description du lieu
- **Carte** : Carte du lieu
- **Région** : Appartient à une région
- **Pays** : Appartient à un pays

---

## 🔧 **Modifications apportées**

### **1. Base de données**
- ✅ **Table regions** : Créée avec colonnes `map_url` et `coat_of_arms_url`
- ✅ **Table countries** : Ajout des colonnes `map_url` et `coat_of_arms_url`
- ✅ **Table places** : Déjà existante avec `country_id` et `region_id`
- ✅ **Contraintes** : Clés étrangères et contraintes d'unicité

### **2. Pages de gestion**
- ✅ **`view_world.php`** - Gestion des mondes et pays
- ✅ **`view_country.php`** - Gestion des pays et régions
- ✅ **`view_region.php`** - Gestion des régions et lieux
- ✅ **Navigation** : Liens entre tous les niveaux

### **3. Système d'upload**
- ✅ **Upload d'images** : Cartes et blasons pour chaque niveau
- ✅ **Validation** : Types, taille, sécurité
- ✅ **Stockage** : Dossiers séparés par type
- ✅ **Nettoyage** : Suppression automatique des anciennes images

---

## 📁 **Fichiers créés/modifiés**

### **Nouveaux fichiers**
- **`view_country.php`** - Page de gestion des pays et régions
- **`view_region.php`** - Page de gestion des régions et lieux
- **`database/update_regions_schema.sql`** - Script de mise à jour
- **`uploads/regions/.htaccess`** - Sécurisation du dossier régions
- **`uploads/places/.htaccess`** - Sécurisation du dossier lieux

### **Fichiers modifiés**
- **`view_world.php`** - Ajout des liens vers les pays
- **`manage_worlds.php`** - Déjà existant pour les mondes

### **Dossiers créés**
- **`uploads/regions/`** - Stockage des images de régions
- **`uploads/places/`** - Stockage des images de lieux

---

## 🎯 **Fonctionnalités**

### **Navigation hiérarchique**
```
Mondes (manage_worlds.php)
    ↓ [Voir les pays]
Pays (view_country.php)
    ↓ [Voir les régions]
Régions (view_region.php)
    ↓ [Voir les lieux]
Lieux (view_place.php)
```

### **Upload d'images**
- **Formats acceptés** : JPG, JPEG, PNG, GIF, WebP
- **Taille maximale** : 5MB par image
- **Stockage** : 
  - Mondes : `uploads/worlds/`
  - Pays : `uploads/countries/`
  - Régions : `uploads/regions/`
  - Lieux : `uploads/places/`

### **Interface utilisateur**
- **Aperçu en temps réel** : Visualisation des images avant validation
- **Images actuelles** : Affichage des images existantes lors de l'édition
- **Plein écran** : Clic sur les images pour voir en grand
- **Navigation** : Boutons de retour et liens entre niveaux

---

## 🔒 **Sécurité**

### **Validation des fichiers**
```php
// Types autorisés
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

// Taille maximale
$maxSize = 5 * 1024 * 1024; // 5MB

// Vérification MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
```

### **Protection des dossiers**
```apache
# .htaccess dans chaque dossier uploads/
php_flag engine off
<Files "*.php">
    Order Deny,Allow
    Deny from all
</Files>
```

### **Contrôle d'accès**
- **Authentification** : Utilisateur connecté requis
- **Autorisation** : MJ ou Admin uniquement
- **Propriété** : Vérification de la propriété à chaque niveau

---

## 📊 **Structure de la base de données**

### **Table worlds**
```sql
CREATE TABLE worlds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    map_url VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);
```

### **Table countries**
```sql
CREATE TABLE countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    world_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    map_url VARCHAR(255),
    coat_of_arms_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (world_id) REFERENCES worlds(id) ON DELETE CASCADE,
    UNIQUE KEY unique_country_per_world (world_id, name)
);
```

### **Table regions**
```sql
CREATE TABLE regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    map_url VARCHAR(255),
    coat_of_arms_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    UNIQUE KEY unique_region_per_country (country_id, name)
);
```

### **Table places**
```sql
CREATE TABLE places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    map_url VARCHAR(255),
    notes TEXT,
    position INT DEFAULT 0,
    country_id INT,
    region_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL
);
```

---

## 🎨 **Interface utilisateur**

### **Navigation entre niveaux**
```html
<!-- Bouton de retour -->
<a href="view_world.php?id=<?php echo (int)$country['world_id']; ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i>Retour au monde
</a>

<!-- Lien vers le niveau inférieur -->
<a href="view_country.php?id=<?php echo (int)$country['id']; ?>" class="btn btn-outline-info btn-sm">
    <i class="fas fa-eye"></i>
</a>
```

### **Affichage des images**
```html
<!-- Image cliquable avec aperçu -->
<img src="<?php echo htmlspecialchars($region['map_url']); ?>" 
     alt="Carte de <?php echo htmlspecialchars($region['name']); ?>" 
     class="img-fluid rounded cursor-pointer" 
     style="max-height: 200px; max-width: 300px;"
     onclick="openMapFullscreen('<?php echo htmlspecialchars($region['map_url']); ?>', '<?php echo htmlspecialchars($region['name']); ?>')"
     title="Cliquer pour voir en plein écran">
```

### **Formulaires d'upload**
```html
<!-- Upload avec aperçu -->
<input type="file" class="form-control" name="map_image" accept="image/*">
<div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
<div id="createRegionMapPreview" class="mt-2" style="display: none;">
    <img id="createRegionMapPreviewImg" src="" alt="Aperçu carte" class="img-fluid rounded">
</div>
```

---

## 🚀 **Utilisation**

### **Créer un monde complet**
1. **Monde** : Créer un monde avec sa carte
2. **Pays** : Créer des pays avec cartes et blasons
3. **Régions** : Créer des régions dans chaque pays
4. **Lieux** : Créer des lieux dans chaque région

### **Navigation**
1. **Mondes** : `manage_worlds.php` - Liste de tous les mondes
2. **Pays** : `view_country.php?id=X` - Régions d'un pays
3. **Régions** : `view_region.php?id=X` - Lieux d'une région
4. **Lieux** : `view_place.php?id=X` - Détails d'un lieu

### **Gestion des images**
1. **Upload** : Sélectionner un fichier image
2. **Aperçu** : Vérifier l'image avant validation
3. **Validation** : Créer/modifier l'élément
4. **Visualisation** : Clic sur l'image pour plein écran

---

## 🎉 **Avantages du système**

### **Organisation claire**
- **Hiérarchie logique** : Monde → Pays → Régions → Lieux
- **Navigation intuitive** : Liens entre tous les niveaux
- **Structure cohérente** : Même interface à tous les niveaux

### **Enrichissement visuel**
- **Cartes détaillées** : Visualisation géographique à tous les niveaux
- **Blasons authentiques** : Symboles héraldiques pour l'immersion
- **Flexibilité** : Upload optionnel, pas obligatoire

### **Expérience utilisateur**
- **Simplicité** : Upload direct sans URL externe
- **Aperçu** : Visualisation avant validation
- **Plein écran** : Visualisation optimale des cartes
- **Performance** : Images servies localement

### **Maintenance**
- **Nettoyage automatique** : Suppression des anciennes images
- **Gestion des erreurs** : Validation complète
- **Évolutivité** : Structure extensible
- **Sécurité** : Protection complète des uploads

---

## 📋 **Fonctions helper**

### **uploadWorldImage()** - Mondes
```php
function uploadWorldImage($file) {
    // Validation et upload pour les mondes
    // Stockage dans uploads/worlds/
}
```

### **uploadCountryImage()** - Pays
```php
function uploadCountryImage($file, $type = 'map') {
    // Validation et upload pour les pays
    // Stockage dans uploads/countries/
    // Types: 'map' ou 'coat_of_arms'
}
```

### **uploadRegionImage()** - Régions
```php
function uploadRegionImage($file, $type = 'map') {
    // Validation et upload pour les régions
    // Stockage dans uploads/regions/
    // Types: 'map' ou 'coat_of_arms'
}
```

### **uploadPlaceImage()** - Lieux
```php
function uploadPlaceImage($file) {
    // Validation et upload pour les lieux
    // Stockage dans uploads/places/
    // Seulement des cartes (pas de blasons)
}
```

---

**🎉 Le système de hiérarchie géographique est opérationnel !**

Les MJ peuvent maintenant créer des mondes complets avec une structure logique et visuellement riche, offrant une expérience de création de mondes immersive et professionnelle.
