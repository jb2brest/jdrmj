# ✅ Nouvelle Fonctionnalité : Annulation du Refus de Candidature

## 🎯 Fonctionnalité Ajoutée

Les maîtres du jeu (MJ) peuvent maintenant annuler un refus de candidature sur leurs campagnes, permettant de remettre la candidature en attente.

## 🔧 Implémentation

### **1. Action PHP pour Annuler le Refus**
```php
// Annuler le refus (revenir à 'pending')
if (isset($_POST['action']) && $_POST['action'] === 'unrevoke_application' && isset($_POST['application_id'])) {
    $application_id = (int)$_POST['application_id'];
    // Vérifier que la candidature est refusée pour cette campagne du MJ
    $stmt = $pdo->prepare("SELECT ca.player_id FROM campaign_applications ca JOIN campaigns c ON ca.campaign_id = c.id WHERE ca.id = ? AND ca.campaign_id = ? AND c.dm_id = ? AND ca.status = 'declined'");
    $stmt->execute([$application_id, $campaign_id, $dm_id]);
    $app = $stmt->fetch();
    if ($app) {
        $player_id = (int)$app['player_id'];
        // Remettre la candidature en attente
        $stmt = $pdo->prepare("UPDATE campaign_applications SET status = 'pending' WHERE id = ?");
        $stmt->execute([$application_id]);
        // Notifier le joueur
        $title = 'Refus annulé';
        $message = 'Votre refus dans la campagne "' . $campaign['title'] . '" a été annulé par le MJ. Votre candidature est de nouveau en attente.';
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
        $stmt->execute([$player_id, $title, $message, $campaign_id]);
        $success_message = "Refus annulé. La candidature est remise en attente.";
    } else {
        $error_message = "Candidature refusée introuvable.";
    }
}
```

### **2. Bouton dans l'Interface**
```php
<?php elseif ($a['status'] === 'declined'): ?>
    <form method="POST" class="d-inline" onsubmit="return confirm('Annuler le refus de cette candidature ? Elle sera remise en attente.');">
        <input type="hidden" name="action" value="unrevoke_application">
        <input type="hidden" name="application_id" value="<?php echo $a['id']; ?>">
        <button class="btn btn-sm btn-outline-success"><i class="fas fa-undo me-1"></i>Annuler le refus</button>
    </form>
<?php else: ?>
    <span class="text-muted">—</span>
<?php endif; ?>
```

## ✅ Fonctionnalités

### **Gestion des Candidatures Complète**
- ✅ **Accepter** : Approuver une candidature en attente
- ✅ **Refuser** : Refuser une candidature en attente
- ✅ **Annuler l'acceptation** : Remettre en attente une candidature acceptée
- ✅ **Annuler le refus** : Remettre en attente une candidature refusée

### **Interface Utilisateur**
- ✅ **Bouton "Annuler le refus"** : Visible pour les candidatures refusées
- ✅ **Confirmation** : Demande de confirmation avant l'action
- ✅ **Style cohérent** : Bouton vert avec icône "undo"
- ✅ **Feedback** : Message de succès après l'action

### **Notifications**
- ✅ **Notification au joueur** : Le joueur est informé que son refus a été annulé
- ✅ **Message explicatif** : Explication claire de ce qui s'est passé
- ✅ **Système de notifications** : Intégré au système existant

## 🔒 Sécurité

### **Contrôle d'Accès**
- **DM Propriétaire** : Seul le MJ de la campagne peut annuler un refus
- **Vérification** : Vérification que la candidature appartient à la campagne du MJ
- **Statut** : Seules les candidatures refusées peuvent être annulées

### **Validation des Données**
- **ID de candidature** : Cast en entier pour éviter les injections
- **Vérification d'existence** : La candidature doit exister et être refusée
- **Permissions** : Vérification des droits du MJ

### **Requête SQL Sécurisée**
```sql
SELECT ca.player_id FROM campaign_applications ca 
JOIN campaigns c ON ca.campaign_id = c.id 
WHERE ca.id = ? AND ca.campaign_id = ? AND c.dm_id = ? AND ca.status = 'declined'
```
- **Paramètres liés** : Protection contre l'injection SQL
- **Triple vérification** : ID candidature, ID campagne, DM propriétaire, statut refusé

## 🎯 Avantages

### **Pour les MJ**
- ✅ **Flexibilité** : Peuvent changer d'avis sur un refus
- ✅ **Gestion complète** : Contrôle total sur les candidatures
- ✅ **Réversibilité** : Toutes les actions peuvent être annulées
- ✅ **Interface claire** : Boutons distincts pour chaque action

### **Pour les Joueurs**
- ✅ **Seconde chance** : Peuvent être reconsidérés après un refus
- ✅ **Notification** : Informés automatiquement des changements
- ✅ **Transparence** : Savent exactement ce qui se passe

### **Pour l'Application**
- ✅ **Cohérence** : Toutes les actions ont leur contraire
- ✅ **Sécurité** : Contrôles d'accès appropriés
- ✅ **Maintenabilité** : Code structuré et logique

## 📋 Fichiers Modifiés

### **view_campaign.php**
- ✅ **Ligne 197-218** : Action PHP `unrevoke_application`
- ✅ **Ligne 963-968** : Bouton "Annuler le refus" dans l'interface
- ✅ **Sécurité** : Vérifications et validations appropriées

## 🔍 Workflow Complet

### **États des Candidatures**
1. **`pending`** : Candidature en attente
   - Actions : Accepter, Refuser
2. **`approved`** : Candidature acceptée
   - Actions : Annuler l'acceptation (retour à `pending`)
3. **`declined`** : Candidature refusée
   - Actions : Annuler le refus (retour à `pending`)

### **Actions Disponibles**
- **Accepter** : `pending` → `approved` + ajout comme membre
- **Refuser** : `pending` → `declined`
- **Annuler l'acceptation** : `approved` → `pending` + retrait du membre
- **Annuler le refus** : `declined` → `pending`

## 🚀 Déploiement

### **Test**
- ✅ **Déployé sur test** : `http://localhost/jdrmj_test`
- ✅ **Fonctionnalité active** : Les MJ peuvent annuler les refus
- ✅ **Interface testée** : Boutons visibles et fonctionnels

### **Production**
- 🔄 **Prêt pour production** : Code testé et sécurisé
- 🔄 **Rétrocompatibilité** : Aucun impact sur l'existant
- 🔄 **Migration** : Aucune migration de base de données requise

## 🎉 Résultat Final

### **Gestion Complète des Candidatures**
- ✅ **Toutes les actions** : Accepter, Refuser, Annuler acceptation, Annuler refus
- ✅ **Interface intuitive** : Boutons clairs pour chaque action
- ✅ **Sécurité robuste** : Contrôles d'accès appropriés
- ✅ **Notifications** : Joueurs informés des changements

### **Expérience Utilisateur**
- ✅ **Flexibilité maximale** : Les MJ peuvent changer d'avis
- ✅ **Interface cohérente** : Tous les états ont leurs actions
- ✅ **Feedback clair** : Messages de confirmation et de succès

**Les MJ peuvent maintenant annuler un refus de candidature et remettre la candidature en attente !** 🎉
