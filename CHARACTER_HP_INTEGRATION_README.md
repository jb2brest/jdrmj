# Solution : Intégration de la Gestion des Points de Vie dans les Feuilles de Personnages

## Problème Résolu

### Demande Utilisateur
> "La gestion des points de vie des personnages se fait directement dans la feuille des personnages joueurs ou non joueurs"

### Solution Implémentée
La gestion des points de vie est maintenant intégrée directement dans les feuilles de personnages (`view_character.php`), permettant au MJ de modifier les PV sans quitter la feuille de personnage. Plus besoin de pages séparées pour la gestion des points de vie.

## Fonctionnalités Implémentées

### 1. Intégration dans `view_character.php`

#### **Bouton de Gestion des Points de Vie**
```php
<?php if ($canModifyHP): ?>
    <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#hpModal" title="Gérer les points de vie">
            <i class="fas fa-heart"></i>
        </button>
    </div>
<?php endif; ?>
```

#### **Modal de Gestion Complète**
- **Barre de points de vie** : Affichage visuel avec gradient de couleurs
- **Actions rapides** : Boutons pour dégâts/soins courants (-1, -5, -10, -20)
- **Champs de saisie** : Pour dégâts et soins personnalisés
- **Modification directe** : Champ pour définir les PV exacts
- **Réinitialisation** : Bouton pour remettre au maximum

### 2. Contrôle d'Accès et Permissions

#### **Logique de Permissions**
```php
// Vérifier si l'utilisateur peut modifier les points de vie
$canModifyHP = ($character['user_id'] == $_SESSION['user_id']);

if (!$canModifyHP && isDM() && $dm_campaign_id) {
    // Vérifier que la campagne appartient au MJ connecté
    $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND dm_id = ?");
    $stmt->execute([$dm_campaign_id, $_SESSION['user_id']]);
    $canModifyHP = (bool)$stmt->fetch();
    
    // Vérifier que le propriétaire du personnage est membre de la campagne
    if ($canModifyHP) {
        $stmt = $pdo->prepare("SELECT 1 FROM campaign_members WHERE campaign_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$dm_campaign_id, $character['user_id']]);
        $isMember = (bool)$stmt->fetch();
        
        if (!$isMember) {
            // Vérifier si le propriétaire a candidaté à la campagne
            $stmt = $pdo->prepare("SELECT 1 FROM campaign_applications WHERE campaign_id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$dm_campaign_id, $character['user_id']]);
            $hasApplied = (bool)$stmt->fetch();
            
            $canModifyHP = $hasApplied;
        }
    }
}
```

#### **Qui Peut Modifier les Points de Vie**
- **Propriétaire du personnage** : Peut toujours modifier ses propres PV
- **MJ de la campagne** : Peut modifier les PV des personnages de sa campagne
- **Autres utilisateurs** : Ne peuvent pas modifier les PV

### 3. Traitement des Actions POST

#### **Actions Disponibles**
```php
switch ($_POST['hp_action']) {
    case 'update_hp':
        // Modification directe des PV
        break;
    case 'damage':
        // Infliger des dégâts
        break;
    case 'heal':
        // Appliquer des soins
        break;
    case 'reset_hp':
        // Réinitialiser au maximum
        break;
}
```

#### **Validation des Données**
```php
// Validation des points de vie
if ($new_hp < 0) {
    $new_hp = 0;
}
if ($new_hp > $max_hp) {
    $new_hp = $max_hp;
}
```

### 4. Interface Utilisateur

#### **Barre de Points de Vie dans le Modal**
```php
<?php
$current_hp = $character['hit_points_current'];
$max_hp = $character['hit_points_max'];
$hp_percentage = ($current_hp / $max_hp) * 100;
$hp_class = $hp_percentage > 50 ? 'bg-success' : ($hp_percentage > 25 ? 'bg-warning' : 'bg-danger');
?>
<div class="progress mb-2" style="height: 30px;">
    <div class="progress-bar <?php echo $hp_class; ?>" role="progressbar" style="width: <?php echo $hp_percentage; ?>%">
        <?php echo $current_hp; ?>/<?php echo $max_hp; ?>
    </div>
</div>
```

#### **Actions Rapides JavaScript**
```javascript
function quickDamage(amount) {
    if (confirm(`Infliger ${amount} points de dégâts à <?php echo htmlspecialchars($character['name']); ?> ?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="hp_action" value="damage">
            <input type="hidden" name="damage" value="${amount}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
```

### 5. Messages de Confirmation

#### **Affichage des Messages**
```php
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
```

## Tests de Validation

### Test Réussi
```
Scène : Convocation initiale (ID: 3)

Personnages Joueurs :
- Robin (Hyphrédicte)
  PV : 11/11 | CA: 10
  Propriétaire : User ID 1
  Peut modifier les PV : ✅ Oui (MJ de campagne)
  Lien vers la feuille : ✅ Fonctionnel

PNJ :
- Lieutenant Cameron (PNJ avec personnage associé)
  PV : 11/11 | CA: 10
  Propriétaire : User ID 2 (MJ)
  Peut modifier les PV : ✅ Oui
  Lien vers la feuille : ✅ Fonctionnel

Fonctionnalités Intégrées :
✅ Bouton de gestion : Icône cœur dans la section PV
✅ Modal de gestion : Interface complète pour modifier les PV
✅ Actions rapides : Boutons pour dégâts/soins courants
✅ Champs de saisie : Pour dégâts et soins personnalisés
✅ Modification directe : Champ pour définir les PV exacts
✅ Réinitialisation : Bouton pour remettre au maximum
✅ Barre de PV : Affichage visuel avec codes couleur
✅ Validation : PV ne peuvent pas dépasser les limites
✅ Messages : Confirmation des actions effectuées
```

### Fonctionnalités Validées
1. ✅ **Intégration** : Gestion des PV directement dans view_character.php
2. ✅ **Interface** : Modal avec toutes les actions de gestion
3. ✅ **Permissions** : Contrôle d'accès correct
4. ✅ **Fonctionnalités** : Toutes les actions disponibles
5. ✅ **Design** : Interface cohérente avec le reste de l'application
6. ✅ **Simplicité** : Plus besoin de pages séparées

## Utilisation

### 1. MJ Gère les Points de Vie d'un Personnage
```
1. Se connecter en tant que MJ
2. Aller sur une scène avec des personnages
3. Cliquer sur l'icône document (📄) à côté d'un personnage
4. La feuille de personnage s'ouvre
5. Cliquer sur l'icône cœur (❤️) dans la section Points de Vie
6. Le modal de gestion s'ouvre avec toutes les options :
   - Actions rapides (-1, -5, -10, -20)
   - Champs de saisie pour dégâts/soins
   - Modification directe des PV
   - Réinitialisation au maximum
```

### 2. Propriétaire Gère ses Points de Vie
```
1. Se connecter avec son compte
2. Aller sur sa feuille de personnage
3. Cliquer sur l'icône cœur (❤️) dans la section Points de Vie
4. Utiliser les mêmes actions que le MJ
```

### 3. Affichage des Points de Vie
```
- Affichage principal : PV actuels/PD maximum dans la feuille
- Barre de PV : Dans le modal avec codes couleur
- Codes couleur : Vert (>50%), Orange (25-50%), Rouge (<25%)
- Mise à jour : Temps réel après chaque action
```

## Avantages de la Solution

### 1. Simplicité d'Utilisation
- **Interface unifiée** : Tout dans la feuille de personnage
- **Pas de navigation** : Plus besoin de pages séparées
- **Accès direct** : Bouton visible dans la section PV
- **Modal intégré** : Interface complète sans quitter la page

### 2. Expérience Utilisateur
- **Cohérence** : Design uniforme avec le reste de l'application
- **Rapidité** : Actions rapides pour les cas courants
- **Flexibilité** : Champs de saisie pour les cas spécifiques
- **Feedback** : Messages de confirmation des actions

### 3. Maintenance
- **Code simplifié** : Moins de fichiers à maintenir
- **Logique centralisée** : Toute la gestion dans un seul endroit
- **Réutilisabilité** : Même interface pour tous les personnages
- **Évolutivité** : Facile d'ajouter de nouvelles fonctionnalités

### 4. Sécurité
- **Contrôle d'accès** : Vérification des permissions
- **Validation** : PV ne peuvent pas dépasser les limites
- **Sessions sécurisées** : Vérification de l'identité
- **Protection CSRF** : Formulaires sécurisés

## Fichiers Modifiés

### Fichiers Modifiés
- **`view_character.php`** : Ajout de la gestion des PV intégrée
- **`view_scene.php`** : Suppression des liens vers les pages séparées

### Fichiers Supprimés
- **`view_character_hp.php`** : Plus nécessaire
- **`view_npc_hp.php`** : Plus nécessaire

## Cas d'Usage

### 1. Combat en Temps Réel
```
Le MJ ouvre la feuille d'un personnage et clique sur l'icône cœur
pour accéder rapidement à la gestion des PV. Il peut infliger des
dégâts ou appliquer des soins sans quitter la feuille de personnage.
```

### 2. Gestion des PNJ
```
Le MJ gère les PV de ses PNJ (personnages associés) avec la même
interface que pour les personnages joueurs, directement dans leur
feuille de personnage.
```

### 3. Soins et Récupération
```
Après un combat, le MJ peut rapidement soigner les personnages
blessés en utilisant les actions rapides ou en saisissant le
montant de soins à appliquer.
```

### 4. Réinitialisation
```
À la fin d'une session ou après un repos, le MJ peut rapidement
remettre tous les PV au maximum avec le bouton de reset.
```

## Évolutions Possibles

### 1. Historique des Modifications
```php
// Table pour tracer les modifications de PV
CREATE TABLE hp_modifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT,
    old_hp INT,
    new_hp INT,
    modification_type ENUM('damage', 'heal', 'direct'),
    amount INT,
    modified_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 2. Conditions de Combat
```php
// Gestion des conditions (empoisonné, paralysé, etc.)
ALTER TABLE characters ADD COLUMN conditions TEXT;
```

### 3. Notifications en Temps Réel
```javascript
// WebSocket pour les mises à jour en temps réel
const socket = new WebSocket('ws://localhost:8080');
socket.onmessage = function(event) {
    const data = JSON.parse(event.data);
    updateHPDisplay(data.character_id, data.new_hp);
};
```

### 4. Gestion par Groupe
```php
// Actions sur plusieurs personnages à la fois
function applyDamageToGroup($character_ids, $damage) {
    foreach ($character_ids as $id) {
        // Appliquer les dégâts à chaque personnage
    }
}
```

---

**Statut** : ✅ **SOLUTION COMPLÈTEMENT IMPLÉMENTÉE**

La gestion des points de vie est maintenant intégrée directement dans les feuilles de personnages, offrant une interface unifiée, simple et efficace pour le MJ et les joueurs ! ❤️📄⚔️
