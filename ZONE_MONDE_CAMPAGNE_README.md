# 🌍 Zone Monde dans view_campaign.php - Documentation

## 📋 **Fonctionnalité ajoutée**

Une zone "Monde" a été ajoutée dans `view_campaign.php` à côté de la zone "Membres" pour permettre de déclarer dans quel monde se passe la campagne.

---

## 🔧 **Modifications apportées**

### **1. Base de données**

#### **Table `campaigns` mise à jour :**
```sql
ALTER TABLE campaigns ADD COLUMN world_id INT NULL AFTER dm_id;
ALTER TABLE campaigns ADD FOREIGN KEY (world_id) REFERENCES worlds(id) ON DELETE SET NULL;
```

#### **Structure finale :**
```sql
CREATE TABLE campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dm_id INT NOT NULL,
    world_id INT NULL,                    -- ✅ Nouvelle colonne
    title VARCHAR(100) NOT NULL,
    description TEXT,
    game_system VARCHAR(50) DEFAULT 'D&D 5e',
    is_public TINYINT(1) DEFAULT 1,
    invite_code VARCHAR(16) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dm_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (world_id) REFERENCES worlds(id) ON DELETE SET NULL  -- ✅ Nouvelle contrainte
);
```

### **2. Requêtes SQL mises à jour**

#### **Récupération des campagnes :**
```sql
-- AVANT
SELECT c.*, u.username AS dm_username FROM campaigns c 
JOIN users u ON c.dm_id = u.id WHERE c.id = ?

-- APRÈS
SELECT c.*, u.username AS dm_username, w.name AS world_name, w.id AS world_id 
FROM campaigns c 
JOIN users u ON c.dm_id = u.id 
LEFT JOIN worlds w ON c.world_id = w.id 
WHERE c.id = ?
```

#### **Récupération des mondes disponibles :**
```sql
SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name
```

### **3. Logique PHP ajoutée**

#### **Traitement de la mise à jour :**
```php
if (isset($_POST['action']) && $_POST['action'] === 'update_campaign_world' && isDMOrAdmin()) {
    $world_id = !empty($_POST['world_id']) ? (int)$_POST['world_id'] : null;
    
    try {
        $stmt = $pdo->prepare("UPDATE campaigns SET world_id = ? WHERE id = ? AND dm_id = ?");
        $stmt->execute([$world_id, $campaign_id, $dm_id]);
        $success_message = "Monde de la campagne mis à jour avec succès.";
        
        // Recharger les données de la campagne
        $stmt = $pdo->prepare("SELECT c.*, u.username AS dm_username, w.name AS world_name, w.id AS world_id FROM campaigns c JOIN users u ON c.dm_id = u.id LEFT JOIN worlds w ON c.world_id = w.id WHERE c.id = ?");
        $stmt->execute([$campaign_id]);
        $campaign = $stmt->fetch();
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la mise à jour du monde: " . $e->getMessage();
    }
}
```

#### **Récupération des mondes :**
```php
// Récupérer les mondes disponibles (pour le MJ/Admin)
$worlds = [];
if (isDMOrAdmin()) {
    $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $worlds = $stmt->fetchAll();
}
```

---

## 🎨 **Interface utilisateur**

### **Zone Monde ajoutée :**
```html
<!-- Zone Monde -->
<div class="col-lg-6">
    <div class="card h-100">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-globe-americas me-2"></i>Monde</h5>
        </div>
        <div class="card-body">
            <!-- Contenu selon le rôle -->
        </div>
    </div>
</div>
```

### **Pour les MJ/Admin (édition) :**
```html
<form method="POST">
    <input type="hidden" name="action" value="update_campaign_world">
    <div class="mb-3">
        <label for="worldSelect" class="form-label">Monde de la campagne</label>
        <select class="form-select" id="worldSelect" name="world_id">
            <option value="">Aucun monde sélectionné</option>
            <?php foreach ($worlds as $world): ?>
                <option value="<?php echo (int)$world['id']; ?>" 
                        <?php echo ($campaign['world_id'] == $world['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($world['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">
            Sélectionnez le monde dans lequel se déroule cette campagne.
        </div>
    </div>
    <button type="submit" class="btn btn-brown">
        <i class="fas fa-save me-2"></i>Mettre à jour
    </button>
</form>
```

### **Pour les joueurs (lecture seule) :**
```html
<?php if (!empty($campaign['world_name'])): ?>
    <div class="d-flex align-items-center">
        <i class="fas fa-globe-americas me-2 text-brown"></i>
        <span class="fw-bold"><?php echo htmlspecialchars($campaign['world_name']); ?></span>
    </div>
    <p class="text-muted mt-2 mb-0">Cette campagne se déroule dans le monde "<?php echo htmlspecialchars($campaign['world_name']); ?>".</p>
<?php else: ?>
    <div class="text-muted">
        <i class="fas fa-info-circle me-2"></i>
        Aucun monde n'a été défini pour cette campagne.
    </div>
<?php endif; ?>
```

---

## 🎯 **Fonctionnalités**

### **Pour les MJ/Admin :**
- ✅ **Sélection** : Liste déroulante des mondes créés par l'utilisateur
- ✅ **Mise à jour** : Bouton pour sauvegarder le monde sélectionné
- ✅ **Suppression** : Option "Aucun monde sélectionné" pour retirer l'association
- ✅ **Validation** : Vérification des permissions avant mise à jour

### **Pour les joueurs :**
- ✅ **Affichage** : Nom du monde de la campagne
- ✅ **Information** : Message explicatif sur le monde
- ✅ **État vide** : Message informatif si aucun monde défini

### **Sécurité :**
- ✅ **Authentification** : Utilisateur connecté requis
- ✅ **Autorisation** : MJ/Admin uniquement pour la modification
- ✅ **Propriété** : Seuls les mondes créés par l'utilisateur sont disponibles
- ✅ **Validation** : Vérification de la propriété de la campagne

---

## 📊 **Données gérées**

### **Informations du monde :**
- **`world_id`** : ID du monde associé à la campagne
- **`world_name`** : Nom du monde (pour l'affichage)
- **`worlds`** : Liste des mondes disponibles pour le MJ

### **Relations :**
- **Campagne → Monde** : Relation optionnelle (peut être NULL)
- **Monde → Campagnes** : Relation 1:N (un monde peut avoir plusieurs campagnes)
- **Suppression en cascade** : Si un monde est supprimé, les campagnes ne sont pas supprimées

---

## 🔍 **Vérifications effectuées**

### **Syntaxe PHP :**
```bash
php -l view_campaign.php  # ✅ No syntax errors detected
```

### **Base de données :**
```sql
DESCRIBE campaigns;  # ✅ Colonne world_id ajoutée
SHOW CREATE TABLE campaigns;  # ✅ Contrainte de clé étrangère ajoutée
```

### **Fonctionnalités :**
- ✅ **Interface** : Zone Monde affichée correctement
- ✅ **Permissions** : Différenciation MJ/Admin vs Joueurs
- ✅ **Formulaires** : Sélection et mise à jour fonctionnelles
- ✅ **Validation** : Gestion des erreurs et messages de succès

---

## 🎨 **Design et UX**

### **Positionnement :**
- **Emplacement** : À côté de la zone "Membres"
- **Layout** : `col-lg-6` pour un affichage en deux colonnes
- **Cohérence** : Même style que les autres cartes

### **Icônes et couleurs :**
- **Icône** : `fas fa-globe-americas` (globe)
- **Couleur** : `text-brown` pour la cohérence
- **Bouton** : `btn-brown` pour la cohérence

### **Responsive :**
- **Mobile** : Colonnes empilées sur petits écrans
- **Desktop** : Affichage côte à côte sur grands écrans

---

## 📋 **Workflow utilisateur**

### **Pour un MJ :**
1. **Accéder** à `view_campaign.php?id=X`
2. **Voir** la zone "Monde" à côté de "Membres"
3. **Sélectionner** un monde dans la liste déroulante
4. **Cliquer** sur "Mettre à jour"
5. **Confirmer** le message de succès

### **Pour un joueur :**
1. **Accéder** à `view_campaign.php?id=X`
2. **Voir** le monde de la campagne (si défini)
3. **Comprendre** dans quel univers se déroule l'aventure

---

## ✅ **Avantages**

### **Organisation :**
- **Hiérarchie claire** : Monde → Campagne → Lieux
- **Contexte** : Les joueurs savent dans quel univers ils évoluent
- **Cohérence** : Toutes les campagnes d'un monde partagent la géographie

### **Fonctionnalité :**
- **Flexibilité** : Association optionnelle (peut être NULL)
- **Simplicité** : Interface intuitive avec liste déroulante
- **Sécurité** : Permissions et validation appropriées

### **Évolutivité :**
- **Extension** : Possibilité d'ajouter d'autres métadonnées
- **Intégration** : Compatible avec le système de mondes existant
- **Performance** : Requêtes optimisées avec LEFT JOIN

---

## 🎉 **Résultat**

### **Fonctionnalité complète :**
- ✅ **Base de données** : Colonne et contrainte ajoutées
- ✅ **Interface** : Zone Monde avec formulaire
- ✅ **Logique** : Traitement des mises à jour
- ✅ **Sécurité** : Permissions et validation
- ✅ **UX** : Interface intuitive et responsive

**🌍 La zone "Monde" est maintenant disponible dans `view_campaign.php` ! Les MJ peuvent déclarer dans quel monde se passe leur campagne.**
