# 🗺️ Gestion des Régions depuis view_country.php - Documentation

## 📋 **Fonctionnalités confirmées**

Depuis `view_country.php`, un MJ peut **modifier** et **supprimer** des régions avec une interface complète et sécurisée.

---

## 🎯 **Actions disponibles**

### **1. Créer une région**
- ✅ **Modal de création** : Formulaire complet avec validation
- ✅ **Champs** : Nom, description, carte (optionnelle)
- ✅ **Upload d'image** : Carte de la région avec aperçu
- ✅ **Validation** : Nom requis, formats d'image vérifiés

### **2. Modifier une région**
- ✅ **Bouton d'édition** : Icône ✏️ sur chaque carte de région
- ✅ **Modal d'édition** : Formulaire pré-rempli avec données actuelles
- ✅ **Champs modifiables** : Nom, description, carte
- ✅ **Aperçu** : Image actuelle + aperçu de la nouvelle image
- ✅ **Upload** : Remplacement de la carte existante

### **3. Supprimer une région**
- ✅ **Bouton de suppression** : Icône 🗑️ sur chaque carte de région
- ✅ **Confirmation** : Message de confirmation avec nom de la région
- ✅ **Protection** : Impossible de supprimer si des lieux existent
- ✅ **Nettoyage** : Suppression automatique de l'image associée

### **4. Voir les lieux d'une région**
- ✅ **Bouton "Voir la Région"** : Lien vers `view_region.php`
- ✅ **Navigation** : Accès direct aux lieux de la région
- ✅ **Gestion complète** : CRUD des lieux depuis la région

---

## 🔧 **Interface utilisateur**

### **Affichage des régions :**
```html
<div class="card h-100">
    <!-- Image de la région (si disponible) -->
    <img src="..." class="card-img-top cursor-pointer" 
         onclick="openMapFullscreen(...)" title="Cliquer pour voir en plein écran">
    
    <div class="card-body d-flex flex-column">
        <h5 class="card-title">Nom de la région</h5>
        <p class="card-text">Description...</p>
        
        <div class="d-flex justify-content-between align-items-center">
            <!-- Bouton "Voir la Région" -->
            <a href="view_region.php?id=X" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-eye me-1"></i>Voir la Région
            </a>
            
            <!-- Boutons d'action -->
            <div class="btn-group">
                <!-- Bouton d'édition -->
                <button type="button" class="btn btn-sm btn-outline-secondary" 
                        data-bs-toggle="modal" data-bs-target="#editRegionModal"
                        onclick="editRegion(...)">
                    <i class="fas fa-edit"></i>
                </button>
                
                <!-- Bouton de suppression -->
                <form method="POST" class="d-inline" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer...');">
                    <input type="hidden" name="action" value="delete_region">
                    <input type="hidden" name="region_id" value="X">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
```

### **Modal de création :**
- **Titre** : "Créer une nouvelle région"
- **Champs** : Nom (requis), description, carte
- **Validation** : Formats d'image, taille maximale
- **Aperçu** : Visualisation en temps réel

### **Modal d'édition :**
- **Titre** : "Modifier la région"
- **Pré-remplissage** : Données actuelles de la région
- **Image actuelle** : Affichage de la carte existante
- **Nouvelle image** : Upload avec aperçu

---

## 🛡️ **Sécurité et validation**

### **Contrôle d'accès :**
```php
requireLogin();
requireDMOrAdmin();
```

### **Validation des données :**
```php
// Vérification de propriété
$stmt = $pdo->prepare("SELECT c.*, w.name as world_name, w.created_by 
                      FROM countries c 
                      JOIN worlds w ON c.world_id = w.id 
                      WHERE c.id = ? AND w.created_by = ?");
$stmt->execute([$country_id, $user_id]);
```

### **Protection contre la suppression :**
```php
// Vérifier s'il y a des lieux dans cette région
$stmt = $pdo->prepare("SELECT COUNT(*) FROM places WHERE region_id = ?");
$stmt->execute([$region_id]);
$place_count = $stmt->fetchColumn();

if ($place_count > 0) {
    $error_message = "Impossible de supprimer cette région car elle contient $place_count lieux. Supprimez d'abord les lieux.";
}
```

### **Validation des fichiers :**
```php
// Types autorisés
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

// Taille maximale
$maxSize = 5 * 1024 * 1024; // 5MB

// Vérification MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
```

---

## 📊 **Base de données**

### **Table regions :**
```sql
CREATE TABLE regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,                    -- Référence au pays
    name VARCHAR(100) NOT NULL,                 -- Nom de la région
    description TEXT,                           -- Description
    map_url VARCHAR(255),                       -- Carte de la région
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    UNIQUE KEY unique_region_per_country (country_id, name)
);
```

### **Requêtes utilisées :**

#### **Création :**
```sql
INSERT INTO regions (name, description, map_url, country_id) 
VALUES (?, ?, ?, ?)
```

#### **Mise à jour :**
```sql
UPDATE regions 
SET name = ?, description = ?, map_url = ? 
WHERE id = ? AND country_id = ?
```

#### **Suppression :**
```sql
DELETE FROM regions 
WHERE id = ? AND country_id = ?
```

#### **Vérification des lieux :**
```sql
SELECT COUNT(*) FROM places WHERE region_id = ?
```

---

## 🎨 **Fonctionnalités avancées**

### **Upload d'images :**
- **Fonction** : `uploadRegionImage($file, $type = 'map')`
- **Validation** : Type, taille, MIME
- **Stockage** : `uploads/regions/`
- **Noms uniques** : Prévention des conflits
- **Nettoyage** : Suppression des anciennes images

### **Aperçu en temps réel :**
```javascript
document.getElementById('createRegionMap').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('createRegionMapPreviewImg').src = e.target.result;
            document.getElementById('createRegionMapPreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
```

### **Plein écran :**
```javascript
function openMapFullscreen(mapUrl, regionName) {
    document.getElementById('fullscreenMapImg').src = mapUrl;
    document.getElementById('fullscreenMapTitle').textContent = 'Carte de ' + regionName;
    
    var fullscreenModal = new bootstrap.Modal(document.getElementById('fullscreenMapModal'));
    fullscreenModal.show();
}
```

---

## 🔍 **Tests effectués**

### **Vérifications de base :**
```bash
php -l view_country.php  # ✅ No syntax errors detected
```

### **Test de la base de données :**
```bash
php test_region_management.php
```

**Résultats :**
- ✅ **Connexion** : Base de données accessible
- ✅ **Structure** : Table regions correcte
- ✅ **Données** : 17 régions existantes
- ✅ **Dossiers** : Uploads fonctionnels
- ✅ **Permissions** : Écriture autorisée

### **Fonctionnalités testées :**
- ✅ **Création** : Modal et validation
- ✅ **Édition** : Pré-remplissage et mise à jour
- ✅ **Suppression** : Protection et nettoyage
- ✅ **Navigation** : Liens vers les lieux
- ✅ **Upload** : Images et aperçus

---

## 📋 **Messages d'erreur et de succès**

### **Messages de succès :**
- `"Région 'Nom' créée avec succès."`
- `"Région 'Nom' mise à jour avec succès."`
- `"Région supprimée avec succès."`

### **Messages d'erreur :**
- `"Le nom de la région est requis."`
- `"Une région avec ce nom existe déjà dans ce pays."`
- `"Fichier trop volumineux (max 5MB)."`
- `"Type de fichier non autorisé."`
- `"Impossible de supprimer cette région car elle contient X lieux."`
- `"Région non trouvée."`

---

## 🎯 **Workflow utilisateur**

### **Créer une région :**
1. **Accéder** à `view_country.php?id=X`
2. **Cliquer** sur "Nouvelle Région"
3. **Remplir** le formulaire (nom requis)
4. **Uploader** une carte (optionnel)
5. **Valider** la création

### **Modifier une région :**
1. **Cliquer** sur le bouton ✏️ de la région
2. **Modifier** les champs souhaités
3. **Changer** la carte si nécessaire
4. **Valider** les modifications

### **Supprimer une région :**
1. **Cliquer** sur le bouton 🗑️ de la région
2. **Confirmer** la suppression
3. **Vérifier** qu'aucun lieu n'est associé
4. **Valider** la suppression

### **Voir les lieux :**
1. **Cliquer** sur "Voir la Région"
2. **Accéder** à `view_region.php?id=X`
3. **Gérer** les lieux de la région

---

## ✅ **Résumé des fonctionnalités**

### **Interface complète :**
- ✅ **CRUD complet** : Créer, lire, modifier, supprimer
- ✅ **Upload d'images** : Cartes avec validation
- ✅ **Aperçus** : Visualisation en temps réel
- ✅ **Plein écran** : Visualisation optimale
- ✅ **Navigation** : Liens vers les lieux

### **Sécurité :**
- ✅ **Authentification** : Utilisateur connecté
- ✅ **Autorisation** : MJ ou Admin uniquement
- ✅ **Validation** : Données et fichiers
- ✅ **Protection** : Contre les suppressions dangereuses

### **Expérience utilisateur :**
- ✅ **Interface intuitive** : Boutons clairs
- ✅ **Feedback** : Messages de succès/erreur
- ✅ **Confirmation** : Avant suppression
- ✅ **Responsive** : Design adaptatif

**🎉 Depuis view_country.php, un MJ peut parfaitement modifier et supprimer des régions !**
