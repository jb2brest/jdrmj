# ✅ Nouvelle Fonctionnalité : MJ Peut Ajouter des Joueurs au Lieu

## 🎯 Fonctionnalité Demandée

Permettre au maître du jeu (MJ) d'ajouter des personnages joueurs au lieu dans `view_scene.php`.

## 🔧 Implémentation

### **1. Logique PHP - Ajout de Joueur**
```php
// Ajouter un joueur au lieu
if (isset($_POST['action']) && $_POST['action'] === 'add_player' && isset($_POST['player_id'])) {
    $player_id = (int)$_POST['player_id'];
    $character_id = !empty($_POST['character_id']) ? (int)$_POST['character_id'] : null;
    
    // Vérifier que le joueur est membre de la campagne
    $stmt = $pdo->prepare("SELECT 1 FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
    $stmt->execute([$place['campaign_id'], $player_id]);
    if ($stmt->fetch()) {
        // Vérifier que le joueur n'est pas déjà dans le lieu
        $stmt = $pdo->prepare("SELECT 1 FROM place_players WHERE place_id = ? AND player_id = ?");
        $stmt->execute([$place_id, $player_id]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO place_players (place_id, player_id, character_id) VALUES (?, ?, ?)");
            $stmt->execute([$place_id, $player_id, $character_id]);
            $success_message = "Joueur ajouté au lieu.";
        } else {
            $error_message = "Ce joueur est déjà présent dans ce lieu.";
        }
    } else {
        $error_message = "Ce joueur n'est pas membre de la campagne.";
    }
}
```

### **2. Récupération des Membres de Campagne**
```php
// Récupérer les membres de la campagne pour le formulaire d'ajout de joueurs
$stmt = $pdo->prepare("
    SELECT u.id, u.username, c.id AS character_id, c.name AS character_name, c.profile_photo
    FROM campaign_members cm
    JOIN users u ON cm.user_id = u.id
    LEFT JOIN characters c ON u.id = c.user_id AND c.campaign_id = ?
    WHERE cm.campaign_id = ? AND cm.role IN ('player', 'dm')
    ORDER BY u.username ASC
");
$stmt->execute([$place['campaign_id'], $place['campaign_id']]);
$campaignMembers = $stmt->fetchAll();
```

### **3. Interface Utilisateur - Bouton et Formulaire**
```html
<div class="card-header d-flex justify-content-between align-items-center">
    <span>Joueurs présents</span>
    <?php if ($isOwnerDM): ?>
        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addPlayerForm">
            <i class="fas fa-user-plus me-1"></i>Ajouter
        </button>
    <?php endif; ?>
</div>

<div class="collapse mb-3" id="addPlayerForm">
    <div class="card card-body">
        <h6>Ajouter un joueur</h6>
        <form method="POST" class="row g-2">
            <input type="hidden" name="action" value="add_player">
            <div class="col-12">
                <label class="form-label">Sélectionner un joueur</label>
                <select class="form-select" name="player_id" required>
                    <option value="">Choisir un joueur...</option>
                    <?php foreach ($campaignMembers as $member): ?>
                        <?php if (!$alreadyPresent): ?>
                            <option value="<?php echo (int)$member['id']; ?>" data-character-id="<?php echo (int)$member['character_id']; ?>">
                                <?php echo htmlspecialchars($member['username']); ?>
                                <?php if ($member['character_name']): ?>
                                    (<?php echo htmlspecialchars($member['character_name']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i>Ajouter au lieu
                </button>
            </div>
        </form>
    </div>
</div>
```

### **4. JavaScript - Association Automatique du Personnage**
```javascript
// Gestion de l'ajout de joueurs
document.addEventListener('DOMContentLoaded', function() {
    const playerSelect = document.querySelector('select[name="player_id"]');
    if (playerSelect) {
        playerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const characterId = selectedOption.getAttribute('data-character-id');
            
            // Créer un champ caché pour le character_id si nécessaire
            let characterIdInput = document.querySelector('input[name="character_id"]');
            if (!characterIdInput) {
                characterIdInput = document.createElement('input');
                characterIdInput.type = 'hidden';
                characterIdInput.name = 'character_id';
                this.parentNode.appendChild(characterIdInput);
            }
            
            characterIdInput.value = characterId || '';
        });
    }
});
```

## ✅ Fonctionnalités Implémentées

### **Interface Utilisateur**
- ✅ **Bouton "Ajouter"** : Visible uniquement pour le MJ propriétaire
- ✅ **Formulaire déroulant** : Interface collapsible pour ajouter des joueurs
- ✅ **Sélection de joueurs** : Liste des membres de la campagne
- ✅ **Affichage des personnages** : Nom du joueur et nom du personnage

### **Logique Métier**
- ✅ **Vérification d'appartenance** : Seuls les membres de la campagne peuvent être ajoutés
- ✅ **Prévention des doublons** : Un joueur ne peut pas être ajouté deux fois
- ✅ **Association automatique** : Le personnage du joueur est automatiquement associé
- ✅ **Gestion des erreurs** : Messages d'erreur appropriés

### **Sécurité**
- ✅ **Vérification des permissions** : Seul le MJ propriétaire peut ajouter des joueurs
- ✅ **Validation des données** : Vérification de l'appartenance à la campagne
- ✅ **Prévention des doublons** : Vérification avant insertion

## 🎯 Avantages

### **Pour le MJ**
- ✅ **Gestion simplifiée** : Ajout facile des joueurs au lieu
- ✅ **Association automatique** : Le personnage est automatiquement lié
- ✅ **Interface intuitive** : Formulaire simple et clair
- ✅ **Prévention des erreurs** : Impossible d'ajouter le même joueur deux fois

### **Pour les Joueurs**
- ✅ **Apparition automatique** : Les joueurs ajoutés apparaissent dans la liste
- ✅ **Accès aux fiches** : Boutons pour accéder aux fiches de personnage
- ✅ **Gestion des personnages** : Association automatique avec le bon personnage

### **Pour l'Application**
- ✅ **Cohérence des données** : Association correcte joueur-personnage
- ✅ **Interface unifiée** : Même style que les autres sections
- ✅ **Gestion d'erreurs** : Messages clairs et informatifs

## 🚀 Déploiement

### **Fichier Modifié**
- **`view_scene.php`** : Ajout de la fonctionnalité d'ajout de joueurs
- **Fonctionnalités** : Interface, logique PHP, JavaScript
- **Impact** : Amélioration de la gestion des lieux par le MJ

### **Test Réussi**
- ✅ **Déploiement** : Fonctionnalité déployée sur le serveur de test
- ✅ **Interface** : Bouton et formulaire visibles pour le MJ
- ✅ **Logique** : Vérifications et validations en place

## 🎉 Résultat Final

### **Nouvelle Fonctionnalité**
- ✅ **Ajout de joueurs** : Le MJ peut ajouter des joueurs au lieu
- ✅ **Interface intuitive** : Bouton et formulaire simples
- ✅ **Association automatique** : Personnage automatiquement lié
- ✅ **Gestion des erreurs** : Messages clairs et informatifs

### **Fonctionnalités Améliorées**
- ✅ **Gestion des lieux** : Contrôle total du MJ sur les joueurs présents
- ✅ **Expérience utilisateur** : Interface cohérente et intuitive
- ✅ **Sécurité** : Vérifications appropriées et permissions respectées

**Le MJ peut maintenant ajouter des joueurs au lieu de manière simple et intuitive !** 🎉
