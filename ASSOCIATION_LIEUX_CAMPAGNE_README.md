# 🔗 Association de lieux à la campagne - Documentation

## 📋 **Fonctionnalité ajoutée**

Remplacement des boutons "Nouveau pays", "Nouvelle région", "Nouveau lieu" par un bouton "Associer un lieu" qui permet de rechercher et ajouter des lieux existants du monde à la campagne.

---

## 🔧 **Modifications apportées**

### **1. Interface utilisateur**

#### **Boutons remplacés :**
```html
<!-- AVANT -->
<div class="btn-group" role="group">
    <button class="btn btn-brown btn-sm" data-bs-toggle="modal" data-bs-target="#createCountryModal">
        <i class="fas fa-globe"></i> Nouveau Pays
    </button>
    <button class="btn btn-brown btn-sm" data-bs-toggle="modal" data-bs-target="#createRegionModal">
        <i class="fas fa-map-marker-alt"></i> Nouvelle région
    </button>
    <button class="btn btn-brown btn-sm" data-bs-toggle="modal" data-bs-target="#createSceneModal">
        <i class="fas fa-plus"></i> Nouveau lieu
    </button>
</div>

<!-- APRÈS -->
<button class="btn btn-brown btn-sm" data-bs-toggle="modal" data-bs-target="#associatePlaceModal">
    <i class="fas fa-link"></i> Associer un lieu
</button>
```

### **2. Logique PHP ajoutée**

#### **Récupération des lieux disponibles :**
```php
// Récupérer les lieux disponibles dans le monde de la campagne (pour l'association)
$available_places = [];
if (isDMOrAdmin() && !empty($campaign['world_id'])) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.description, p.notes, p.map_url, 
               c.name as country_name, r.name as region_name
        FROM places p
        LEFT JOIN countries c ON p.country_id = c.id
        LEFT JOIN regions r ON p.region_id = r.id
        WHERE c.world_id = ? AND p.campaign_id IS NULL
        ORDER BY c.name, r.name, p.name
    ");
    $stmt->execute([$campaign['world_id']]);
    $available_places = $stmt->fetchAll();
}
```

#### **Traitement de l'association :**
```php
// Gestion de l'association d'un lieu à la campagne (MJ/Admin uniquement)
if (isset($_POST['action']) && $_POST['action'] === 'associate_place' && isDMOrAdmin()) {
    $place_id = (int)($_POST['place_id'] ?? 0);
    
    if ($place_id > 0) {
        try {
            // Vérifier que le lieu appartient au monde de la campagne et n'est pas déjà associé
            $stmt = $pdo->prepare("
                SELECT p.id FROM places p
                LEFT JOIN countries c ON p.country_id = c.id
                WHERE p.id = ? AND c.world_id = ? AND p.campaign_id IS NULL
            ");
            $stmt->execute([$place_id, $campaign['world_id']]);
            $place = $stmt->fetch();
            
            if ($place) {
                // Associer le lieu à la campagne
                $stmt = $pdo->prepare("UPDATE places SET campaign_id = ? WHERE id = ?");
                $stmt->execute([$campaign_id, $place_id]);
                $success_message = "Lieu associé à la campagne avec succès.";
                
                // Recharger les lieux de la campagne
                $places = getPlacesWithGeography($campaign_id);
            } else {
                $error_message = "Ce lieu ne peut pas être associé à cette campagne.";
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'association du lieu: " . $e->getMessage();
        }
    } else {
        $error_message = "Lieu invalide sélectionné.";
    }
}
```

### **3. Modal d'association**

#### **Structure du modal :**
```html
<!-- Modal Associer un lieu -->
<div class="modal fade" id="associatePlaceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Associer un lieu à la campagne</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="associate_place">
                    
                    <!-- Contenu conditionnel selon l'état -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-brown">
                        <i class="fas fa-link me-2"></i>Associer le lieu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

#### **États du modal :**

**1. Aucun monde défini :**
```html
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Attention :</strong> Aucun monde n'est défini pour cette campagne. 
    Veuillez d'abord sélectionner un monde dans la zone "Monde" pour pouvoir associer des lieux.
</div>
```

**2. Aucun lieu disponible :**
```html
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Information :</strong> Aucun lieu disponible dans le monde "<?php echo htmlspecialchars($campaign['world_name']); ?>".
    Tous les lieux de ce monde sont déjà associés à des campagnes ou il n'y a pas encore de lieux créés.
</div>
```

**3. Lieux disponibles :**
```html
<div class="row g-3">
    <?php foreach ($available_places as $place): ?>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="place_id" 
                               id="place_<?php echo $place['id']; ?>" 
                               value="<?php echo $place['id']; ?>">
                        <label class="form-check-label w-100" for="place_<?php echo $place['id']; ?>">
                            <!-- Informations du lieu -->
                        </label>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

---

## 🎯 **Fonctionnalités**

### **Recherche et sélection :**
- ✅ **Filtrage** : Seuls les lieux du monde de la campagne sont affichés
- ✅ **Disponibilité** : Seuls les lieux non associés (`campaign_id IS NULL`) sont proposés
- ✅ **Affichage** : Informations complètes (nom, pays, région, description, plan)
- ✅ **Sélection** : Interface radio button pour choisir un lieu

### **Association :**
- ✅ **Validation** : Vérification de la propriété du monde et de la disponibilité
- ✅ **Sécurité** : Seuls les MJ/Admin peuvent associer des lieux
- ✅ **Mise à jour** : Rechargement automatique de la liste des lieux
- ✅ **Feedback** : Messages de succès/erreur appropriés

### **Interface :**
- ✅ **Responsive** : Modal en largeur (`modal-lg`) avec grille adaptative
- ✅ **Visuel** : Cartes avec images miniatures des plans
- ✅ **Navigation** : Boutons d'annulation et de validation
- ✅ **États** : Gestion des cas d'erreur et d'information

---

## 🔍 **Logique de filtrage**

### **Requête SQL :**
```sql
SELECT p.id, p.name, p.description, p.notes, p.map_url, 
       c.name as country_name, r.name as region_name
FROM places p
LEFT JOIN countries c ON p.country_id = c.id
LEFT JOIN regions r ON p.region_id = r.id
WHERE c.world_id = ? AND p.campaign_id IS NULL
ORDER BY c.name, r.name, p.name
```

### **Conditions :**
- **`c.world_id = ?`** : Le lieu doit appartenir au monde de la campagne
- **`p.campaign_id IS NULL`** : Le lieu ne doit pas être déjà associé à une campagne
- **Tri** : Par pays, puis région, puis nom du lieu

---

## 🛡️ **Sécurité**

### **Authentification :**
- ✅ **Connexion requise** : Utilisateur connecté obligatoire
- ✅ **Permissions** : Seuls les MJ/Admin peuvent associer des lieux

### **Validation :**
- ✅ **Propriété du monde** : Vérification que le lieu appartient au monde de la campagne
- ✅ **Disponibilité** : Vérification que le lieu n'est pas déjà associé
- ✅ **Intégrité** : Validation de l'ID du lieu et de la campagne

### **Protection :**
- ✅ **SQL Injection** : Requêtes préparées
- ✅ **XSS** : Échappement HTML des données affichées
- ✅ **CSRF** : Formulaire POST avec validation côté serveur

---

## 📊 **Données gérées**

### **Informations des lieux :**
- **`id`** : Identifiant unique du lieu
- **`name`** : Nom du lieu
- **`description`** : Description du lieu
- **`notes`** : Notes additionnelles
- **`map_url`** : URL de l'image du plan
- **`country_name`** : Nom du pays
- **`region_name`** : Nom de la région

### **Relations :**
- **Lieu → Campagne** : Association via `campaign_id`
- **Lieu → Monde** : Via pays (`country.world_id`)
- **Unicité** : Un lieu ne peut être associé qu'à une campagne

---

## 🎨 **Design et UX**

### **Interface :**
- **Modal large** : `modal-lg` pour afficher les cartes côte à côte
- **Grille responsive** : `col-md-6` pour 2 colonnes sur desktop
- **Cartes uniformes** : `h-100` pour une hauteur égale
- **Images miniatures** : 60x60px avec `object-fit: cover`

### **Navigation :**
- **Bouton principal** : Icône `fa-link` pour l'association
- **Boutons modal** : Annuler (secondary) et Associer (brown)
- **États visuels** : Alerts pour les cas d'erreur/information

### **Accessibilité :**
- **Labels** : Association correcte des labels aux inputs radio
- **ARIA** : Attributs `aria-label` sur les boutons
- **Contraste** : Couleurs Bootstrap standard

---

## 📋 **Workflow utilisateur**

### **Pour un MJ :**
1. **Accéder** à `view_campaign.php?id=X`
2. **Vérifier** qu'un monde est défini pour la campagne
3. **Cliquer** sur "Associer un lieu"
4. **Voir** la liste des lieux disponibles du monde
5. **Sélectionner** un lieu via le bouton radio
6. **Cliquer** sur "Associer le lieu"
7. **Confirmer** le message de succès

### **Cas d'erreur :**
- **Pas de monde** : Message d'avertissement avec instruction
- **Pas de lieux** : Message d'information explicatif
- **Erreur technique** : Message d'erreur avec détails

---

## ✅ **Avantages**

### **Simplicité :**
- **Un seul bouton** au lieu de trois
- **Interface claire** avec sélection visuelle
- **Workflow linéaire** sans étapes multiples

### **Cohérence :**
- **Réutilisation** des lieux existants du monde
- **Hiérarchie respectée** : Monde → Pays → Région → Lieu
- **Pas de duplication** de contenu géographique

### **Flexibilité :**
- **Association optionnelle** : Les lieux peuvent rester libres
- **Réutilisation** : Un lieu peut être associé à différentes campagnes
- **Gestion centralisée** : Création des lieux dans le système de mondes

---

## 🎉 **Résultat**

### **Fonctionnalité complète :**
- ✅ **Interface** : Bouton unique "Associer un lieu"
- ✅ **Recherche** : Filtrage des lieux par monde et disponibilité
- ✅ **Sélection** : Interface visuelle avec cartes et images
- ✅ **Association** : Logique de validation et mise à jour
- ✅ **Sécurité** : Permissions et validation appropriées
- ✅ **UX** : Gestion des états d'erreur et d'information

**🔗 Le système d'association de lieux est maintenant opérationnel ! Les MJ peuvent facilement associer des lieux existants de leur monde à leurs campagnes.**
