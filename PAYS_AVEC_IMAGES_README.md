# 🏰 Système de Pays avec Cartes et Blasons - Documentation

## 📋 **Vue d'ensemble**

Le système de pays a été étendu pour inclure des cartes et des blasons en plus du nom et de la description. Les MJ peuvent maintenant uploader des images pour enrichir visuellement leurs pays.

---

## 🔧 **Modifications apportées**

### **1. Base de données**
- ✅ **Nouvelles colonnes** : `map_url` et `coat_of_arms_url` ajoutées à la table `countries`
- ✅ **Stockage sécurisé** : Chemins de fichiers stockés dans la base de données
- ✅ **Gestion des fichiers** : Suppression automatique des images lors de la suppression de pays

### **2. Interface utilisateur**
- ✅ **Upload de cartes** : Champ file pour la carte du pays
- ✅ **Upload de blasons** : Champ file pour le blason du pays
- ✅ **Aperçu en temps réel** : Visualisation des images avant validation
- ✅ **Images actuelles** : Affichage des images existantes lors de l'édition

### **3. Sécurité et validation**
- ✅ **Types de fichiers** : JPG, PNG, GIF, WebP uniquement
- ✅ **Taille maximale** : 5MB par image
- ✅ **Noms uniques** : Génération automatique avec timestamp
- ✅ **Protection du dossier** : .htaccess pour empêcher l'exécution de scripts

---

## 📁 **Fichiers créés/modifiés**

### **Nouveaux fichiers**
- **`database/update_countries_schema.sql`** - Script de mise à jour de la base de données
- **`uploads/countries/.htaccess`** - Sécurisation du dossier d'upload
- **`uploads/countries/`** - Dossier de stockage des images de pays

### **Fichiers modifiés**
- **`view_world.php`** - Interface et logique d'upload pour les pays
- **Formulaires** - Ajout des champs d'upload d'images
- **JavaScript** - Aperçu d'images et gestion des erreurs

---

## 🎯 **Fonctionnalités**

### **Upload d'images**
- **Formats acceptés** : JPG, JPEG, PNG, GIF, WebP
- **Taille maximale** : 5MB par image
- **Validation** : Type MIME, extension, taille
- **Stockage** : `uploads/countries/country_[type]_[timestamp]_[unique].ext`

### **Interface utilisateur**
- **Sélection de fichiers** : Input file avec accept="image/*"
- **Aperçu instantané** : Affichage de l'image avant validation
- **Images actuelles** : Affichage des images existantes lors de l'édition
- **Messages d'erreur** : Feedback clair en cas de problème

### **Gestion des fichiers**
- **Noms uniques** : `country_map_[timestamp]_[uniqid].ext` et `country_coat_of_arms_[timestamp]_[uniqid].ext`
- **Suppression automatique** : Anciennes images supprimées lors de la mise à jour
- **Nettoyage** : Fichiers supprimés avec le pays

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

### **Protection du dossier**
```apache
# .htaccess dans uploads/countries/
php_flag engine off
<Files "*.php">
    Order Deny,Allow
    Deny from all
</Files>
```

### **Contrôle d'accès**
- **Authentification** : Utilisateur connecté requis
- **Autorisation** : MJ ou Admin uniquement
- **Validation** : Vérification des droits sur chaque action

---

## 🎨 **Interface utilisateur**

### **Formulaire de création**
```html
<input type="file" class="form-control" name="map_image" accept="image/*">
<div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
<div id="createCountryMapPreview" class="mt-2" style="display: none;">
    <img id="createCountryMapPreviewImg" src="" alt="Aperçu carte" class="img-fluid rounded">
</div>
```

### **Formulaire d'édition**
```html
<input type="file" class="form-control" name="coat_of_arms_image" accept="image/*">
<div id="editCountryCurrentCoatOfArms" class="mt-2" style="display: none;">
    <label class="form-label">Blason actuel:</label>
    <img id="editCountryCurrentCoatOfArmsImg" src="" alt="Blason actuel" class="img-fluid rounded">
</div>
```

### **JavaScript d'aperçu**
```javascript
document.getElementById('createCountryMap').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('createCountryMapPreviewImg').src = e.target.result;
            document.getElementById('createCountryMapPreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
```

---

## 📊 **Flux de données**

### **Création d'un pays avec images**
1. **Sélection** : Utilisateur choisit les fichiers image (carte et/ou blason)
2. **Aperçu** : JavaScript affiche l'aperçu des images
3. **Validation** : Côté client (format, taille)
4. **Upload** : PHP valide et sauvegarde les fichiers
5. **Base de données** : Chemins des fichiers stockés dans `countries.map_url` et `countries.coat_of_arms_url`
6. **Affichage** : Images affichées dans l'interface

### **Mise à jour d'un pays**
1. **Affichage** : Images actuelles affichées dans le modal
2. **Sélection** : Nouveaux fichiers optionnels
3. **Remplacement** : Anciennes images supprimées, nouvelles sauvegardées
4. **Mise à jour** : Base de données mise à jour

### **Suppression d'un pays**
1. **Vérification** : Aucune région associée
2. **Suppression** : Images supprimées du serveur
3. **Nettoyage** : Enregistrement supprimé de la base

---

## 🛠️ **Fonction helper**

### **uploadCountryImage()**
```php
function uploadCountryImage($file, $type = 'map') {
    // Validation de l'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Erreur d\'upload'];
    }
    
    // Validation de la taille
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Fichier trop volumineux'];
    }
    
    // Validation du type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Type non autorisé'];
    }
    
    // Génération du nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'country_' . $type . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = 'uploads/countries/' . $fileName;
    
    // Sauvegarde
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_path' => $filePath];
    }
    
    return ['success' => false, 'error' => 'Erreur de sauvegarde'];
}
```

---

## 🎉 **Avantages du système**

### **Enrichissement visuel**
- **Cartes détaillées** : Visualisation géographique des pays
- **Blasons authentiques** : Symboles héraldiques pour l'immersion
- **Flexibilité** : Upload optionnel, pas obligatoire

### **Expérience utilisateur**
- **Simplicité** : Upload direct sans URL externe
- **Aperçu** : Visualisation avant validation
- **Feedback** : Messages d'erreur clairs
- **Performance** : Images servies localement

### **Maintenance**
- **Nettoyage automatique** : Suppression des anciennes images
- **Gestion des erreurs** : Validation complète
- **Évolutivité** : Structure extensible

---

## 🚀 **Utilisation**

### **Créer un pays avec images**
1. **Accès** : Clic sur "Nouveau Pays" dans un monde
2. **Saisie** : Nom, description
3. **Upload** : Sélection de la carte et/ou du blason
4. **Aperçu** : Vérification des images
5. **Validation** : Création du pays

### **Modifier les images d'un pays**
1. **Accès** : Clic sur "Modifier" sur un pays
2. **Visualisation** : Images actuelles affichées
3. **Remplacement** : Sélection de nouvelles images
4. **Aperçu** : Vérification des nouvelles images
5. **Sauvegarde** : Mise à jour avec nouvelles images

---

## 📋 **Structure de la base de données**

### **Table countries (mise à jour)**
```sql
CREATE TABLE countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    world_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    map_url VARCHAR(255),           -- NOUVEAU: Carte du pays
    coat_of_arms_url VARCHAR(255),  -- NOUVEAU: Blason du pays
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (world_id) REFERENCES worlds(id) ON DELETE CASCADE
);
```

---

**🎉 Le système de pays avec cartes et blasons est opérationnel !**

Les MJ peuvent maintenant créer des pays visuellement riches avec des cartes géographiques et des blasons héraldiques pour une immersion totale dans leurs mondes de jeu.
