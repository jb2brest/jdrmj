# 📸 Système d'Upload d'Images pour les Mondes - Documentation

## 📋 **Vue d'ensemble**

Le système d'upload d'images permet aux MJ d'uploader directement des cartes de monde au lieu de fournir des URLs. Cette fonctionnalité améliore la sécurité et la facilité d'utilisation.

---

## 🔧 **Modifications apportées**

### **1. Nouveau système d'upload**
- ✅ **Upload direct** : Fichiers images uploadés sur le serveur
- ✅ **Validation** : Types de fichiers, taille, sécurité
- ✅ **Stockage sécurisé** : Dossier `uploads/worlds/` avec protection
- ✅ **Noms uniques** : Prévention des conflits de noms

### **2. Interface utilisateur améliorée**
- ✅ **Input file** : Remplacement des champs URL par des inputs file
- ✅ **Aperçu en temps réel** : Visualisation de l'image avant upload
- ✅ **Validation côté client** : Formats acceptés, taille maximale
- ✅ **Gestion des erreurs** : Messages d'erreur clairs

### **3. Sécurité renforcée**
- ✅ **Validation des types** : JPG, PNG, GIF, WebP uniquement
- ✅ **Limite de taille** : Maximum 5MB par image
- ✅ **Protection du dossier** : .htaccess pour empêcher l'exécution de scripts
- ✅ **Noms de fichiers sécurisés** : Génération automatique avec timestamp

---

## 📁 **Fichiers créés/modifiés**

### **Nouveaux fichiers**
- **`upload_world_image.php`** - API d'upload d'images (non utilisé directement)
- **`uploads/.htaccess`** - Sécurisation du dossier uploads
- **`uploads/worlds/`** - Dossier de stockage des cartes de monde

### **Fichiers modifiés**
- **`manage_worlds.php`** - Interface et logique d'upload
- **Formulaires** - Remplacement URL → Upload de fichiers
- **JavaScript** - Aperçu d'images et gestion des erreurs

---

## 🎯 **Fonctionnalités**

### **Upload d'images**
- **Formats acceptés** : JPG, JPEG, PNG, GIF, WebP
- **Taille maximale** : 5MB
- **Validation** : Type MIME, extension, taille
- **Stockage** : `uploads/worlds/world_[timestamp]_[unique].ext`

### **Interface utilisateur**
- **Sélection de fichier** : Input file avec accept="image/*"
- **Aperçu instantané** : Affichage de l'image avant validation
- **Messages d'erreur** : Feedback clair en cas de problème
- **Carte actuelle** : Affichage de l'image existante lors de l'édition

### **Gestion des fichiers**
- **Noms uniques** : `world_[timestamp]_[uniqid].ext`
- **Suppression automatique** : Ancienne image supprimée lors de la mise à jour
- **Nettoyage** : Fichiers supprimés avec le monde

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
# .htaccess dans uploads/
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
<div id="createMapPreview" class="mt-2" style="display: none;">
    <img id="createMapPreviewImg" src="" alt="Aperçu" class="img-fluid rounded">
</div>
```

### **Formulaire d'édition**
```html
<input type="file" class="form-control" name="map_image" accept="image/*">
<div id="editCurrentMap" class="mt-2" style="display: none;">
    <label class="form-label">Carte actuelle:</label>
    <img id="editCurrentMapImg" src="" alt="Carte actuelle" class="img-fluid rounded">
</div>
```

### **JavaScript d'aperçu**
```javascript
document.getElementById('createMapImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('createMapPreviewImg').src = e.target.result;
            document.getElementById('createMapPreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
```

---

## 📊 **Flux de données**

### **Création d'un monde avec image**
1. **Sélection** : Utilisateur choisit un fichier image
2. **Aperçu** : JavaScript affiche l'aperçu
3. **Validation** : Côté client (format, taille)
4. **Upload** : PHP valide et sauvegarde le fichier
5. **Base de données** : Chemin du fichier stocké dans `worlds.map_url`
6. **Affichage** : Image affichée dans l'interface

### **Mise à jour d'un monde**
1. **Affichage** : Carte actuelle affichée dans le modal
2. **Sélection** : Nouveau fichier optionnel
3. **Remplacement** : Ancienne image supprimée, nouvelle sauvegardée
4. **Mise à jour** : Base de données mise à jour

### **Suppression d'un monde**
1. **Vérification** : Aucun pays associé
2. **Suppression** : Image supprimée du serveur
3. **Nettoyage** : Enregistrement supprimé de la base

---

## 🛠️ **Fonction helper**

### **uploadWorldImage()**
```php
function uploadWorldImage($file) {
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
    $fileName = 'world_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = 'uploads/worlds/' . $fileName;
    
    // Sauvegarde
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_path' => $filePath];
    }
    
    return ['success' => false, 'error' => 'Erreur de sauvegarde'];
}
```

---

## 🎉 **Avantages du système**

### **Sécurité améliorée**
- **Contrôle total** : Images stockées sur le serveur
- **Validation stricte** : Types et tailles contrôlés
- **Protection** : Dossier sécurisé contre l'exécution de scripts

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

### **Créer un monde avec carte**
1. **Accès** : Clic sur "Mondes" → "Nouveau Monde"
2. **Saisie** : Nom, description
3. **Upload** : Sélection d'une image de carte
4. **Aperçu** : Vérification de l'image
5. **Validation** : Création du monde

### **Modifier la carte d'un monde**
1. **Accès** : Clic sur "Modifier" sur un monde
2. **Visualisation** : Carte actuelle affichée
3. **Remplacement** : Sélection d'une nouvelle image
4. **Aperçu** : Vérification de la nouvelle image
5. **Sauvegarde** : Mise à jour avec nouvelle carte

---

**🎉 Le système d'upload d'images pour les mondes est opérationnel !**

Les MJ peuvent maintenant uploader directement leurs cartes de monde avec une interface intuitive et sécurisée.
