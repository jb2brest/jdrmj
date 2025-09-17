# ✅ Système de Candidature aux Campagnes

## 🎯 Fonctionnalité Ajoutée

Robin (et tous les joueurs) peuvent maintenant postuler aux campagnes publiques depuis l'écran de détail de la campagne.

## 🔍 Analyse du Besoin

### **Problème Identifié**
- **Pas de système de candidature** : Les joueurs ne pouvaient pas postuler aux campagnes
- **Accès limité** : Seuls les DM pouvaient ajouter des joueurs manuellement
- **Processus complexe** : Pas de moyen simple pour les joueurs de rejoindre une campagne

### **Solution Implémentée**
- **Formulaire de candidature** : Interface simple pour postuler
- **Gestion des personnages** : Possibilité d'associer un personnage à la candidature
- **Suivi du statut** : Affichage du statut de la candidature
- **Prévention des doublons** : Vérification des candidatures existantes

## 🔧 Implémentation Technique

### **1. Logique PHP - Gestion des Candidatures**
```php
// Traitements POST: candidatures (tous les utilisateurs connectés)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'apply_to_campaign') {
        $message = sanitizeInput($_POST['message'] ?? '');
        $character_id = !empty($_POST['character_id']) ? (int)$_POST['character_id'] : null;
        
        // Vérifier si l'utilisateur n'est pas déjà membre
        $stmt = $pdo->prepare("SELECT id FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
        $stmt->execute([$campaign_id, $user_id]);
        $is_member = $stmt->fetch();
        
        if ($is_member) {
            $error_message = "Vous êtes déjà membre de cette campagne.";
        } else {
            // Vérifier si l'utilisateur n'a pas déjà postulé
            $stmt = $pdo->prepare("SELECT id FROM campaign_applications WHERE campaign_id = ? AND player_id = ? AND status = 'pending'");
            $stmt->execute([$campaign_id, $user_id]);
            $existing_application = $stmt->fetch();
            
            if ($existing_application) {
                $error_message = "Vous avez déjà postulé à cette campagne.";
            } else {
                // Créer la candidature
                $stmt = $pdo->prepare("INSERT INTO campaign_applications (campaign_id, player_id, character_id, message, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$campaign_id, $user_id, $character_id, $message]);
                $success_message = "Votre candidature a été envoyée avec succès !";
            }
        }
    }
}
```

### **2. Récupération des Données**
```php
// Récupérer les personnages de l'utilisateur pour la candidature
$stmt = $pdo->prepare("SELECT id, name FROM characters WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$user_id]);
$user_characters = $stmt->fetchAll();

// Vérifier si l'utilisateur a déjà postulé
$stmt = $pdo->prepare("SELECT id, status, created_at FROM campaign_applications WHERE campaign_id = ? AND player_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$campaign_id, $user_id]);
$user_application = $stmt->fetch();

// Vérifier si l'utilisateur est déjà membre
$is_member = false;
foreach ($members as $member) {
    if ($member['id'] == $user_id) {
        $is_member = true;
        break;
    }
}
```

### **3. Interface Utilisateur**

#### **Formulaire de Candidature**
```php
<?php if (!$is_member && !$user_application): ?>
<!-- Formulaire de candidature pour les joueurs -->
<div class="mt-4 p-3 border rounded bg-light">
    <h6 class="mb-3"><i class="fas fa-paper-plane me-2"></i>Postuler à cette campagne</h6>
    <form method="POST">
        <input type="hidden" name="action" value="apply_to_campaign">
        <div class="mb-3">
            <label class="form-label">Personnage (optionnel)</label>
            <select name="character_id" class="form-select">
                <option value="">Aucun personnage spécifique</option>
                <?php foreach ($user_characters as $char): ?>
                    <option value="<?php echo $char['id']; ?>"><?php echo htmlspecialchars($char['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Message de candidature</label>
            <textarea name="message" class="form-control" rows="3" placeholder="Présentez-vous et expliquez pourquoi vous souhaitez rejoindre cette campagne..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane me-2"></i>Envoyer ma candidature
        </button>
    </form>
</div>
<?php endif; ?>
```

#### **Affichage du Statut de Candidature**
```php
<?php elseif ($user_application): ?>
<!-- Statut de la candidature -->
<div class="mt-4 p-3 border rounded">
    <h6 class="mb-2"><i class="fas fa-clock me-2"></i>Votre candidature</h6>
    <div class="d-flex align-items-center">
        <span class="badge bg-<?php 
            echo $user_application['status'] === 'pending' ? 'warning' : 
                ($user_application['status'] === 'approved' ? 'success' : 'danger'); 
        ?> me-2">
            <?php 
            echo $user_application['status'] === 'pending' ? 'En attente' : 
                ($user_application['status'] === 'approved' ? 'Acceptée' : 'Refusée'); 
            ?>
        </span>
        <small class="text-muted">Envoyée le <?php echo date('d/m/Y H:i', strtotime($user_application['created_at'])); ?></small>
    </div>
</div>
<?php endif; ?>
```

## ✅ Fonctionnalités

### **Pour les Joueurs**
- ✅ **Formulaire de candidature** : Interface simple et intuitive
- ✅ **Sélection de personnage** : Possibilité d'associer un personnage existant
- ✅ **Message personnalisé** : Présentation et motivation
- ✅ **Suivi du statut** : Affichage du statut de la candidature
- ✅ **Prévention des doublons** : Impossible de postuler plusieurs fois

### **Pour les DM/Admin**
- ✅ **Gestion des candidatures** : Système existant pour approuver/refuser
- ✅ **Notifications** : Notifications automatiques aux joueurs
- ✅ **Ajout automatique** : Les candidatures approuvées ajoutent automatiquement le joueur

### **Sécurité et Validation**
- ✅ **Vérification des droits** : Seuls les joueurs non-membres peuvent postuler
- ✅ **Prévention des doublons** : Vérification des candidatures en attente
- ✅ **Validation des données** : Sanitisation des entrées utilisateur
- ✅ **Gestion des erreurs** : Messages d'erreur appropriés

## 🎨 Expérience Utilisateur

### **Workflow de Candidature**
1. **Joueur visite** la page de détail d'une campagne publique
2. **Formulaire affiché** si le joueur n'est pas membre et n'a pas postulé
3. **Sélection optionnelle** d'un personnage existant
4. **Rédaction d'un message** de présentation
5. **Envoi de la candidature** avec confirmation
6. **Suivi du statut** avec badge coloré et date

### **États de l'Interface**
- **Pas de candidature** : Formulaire de candidature visible
- **Candidature en attente** : Badge orange "En attente" avec date
- **Candidature acceptée** : Badge vert "Acceptée" avec date
- **Candidature refusée** : Badge rouge "Refusée" avec date
- **Déjà membre** : Aucun formulaire (déjà dans la campagne)

## 📊 Base de Données

### **Table `campaign_applications`**
```sql
CREATE TABLE campaign_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    player_id INT NOT NULL,
    character_id INT NULL,
    message TEXT NULL,
    status ENUM('pending','approved','declined','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
    FOREIGN KEY (player_id) REFERENCES users(id),
    FOREIGN KEY (character_id) REFERENCES characters(id)
);
```

### **Statuts des Candidatures**
- **`pending`** : En attente de réponse du DM
- **`approved`** : Acceptée par le DM
- **`declined`** : Refusée par le DM
- **`cancelled`** : Annulée par le joueur

## 🔍 Vérification

### **Test de Fonctionnalité**
- ✅ **Robin (player)** : Peut postuler à la campagne "L'oublié"
- ✅ **Formulaire complet** : Personnage + message + envoi
- ✅ **Statut affiché** : Badge de statut avec date
- ✅ **Prévention doublons** : Impossible de postuler plusieurs fois

### **URL de Test**
- **Environnement** : http://localhost/jdrmj_test
- **Page** : view_campaign.php?id=2
- **Résultat** : Formulaire de candidature visible pour Robin

## 📋 Fichiers Modifiés

### **view_campaign.php**
- ✅ **Logique de candidature** : Gestion des candidatures POST
- ✅ **Récupération des données** : Personnages et statut de candidature
- ✅ **Interface utilisateur** : Formulaire et affichage du statut
- ✅ **Validation** : Vérifications de sécurité et prévention des doublons

## 🎉 Résultat Final

### **Système Complet**
- ✅ **Candidature simple** : Interface intuitive pour les joueurs
- ✅ **Gestion DM** : Système existant pour approuver/refuser
- ✅ **Suivi complet** : Statut et historique des candidatures
- ✅ **Sécurité** : Validation et prévention des erreurs

### **Expérience Optimisée**
- ✅ **Processus fluide** : Candidature en quelques clics
- ✅ **Feedback clair** : Statut visible et compréhensible
- ✅ **Flexibilité** : Personnage optionnel, message personnalisé
- ✅ **Intégration** : Fonctionne avec le système existant

---

**Robin peut maintenant postuler aux campagnes publiques !** 🎉
