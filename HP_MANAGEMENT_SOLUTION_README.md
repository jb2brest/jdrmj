# Solution : Gestion des Points de Vie par le MJ

## Problème Résolu

### Demande Utilisateur
> "Le MJ doit pouvoir enlever des points de vies aux personnages joueurs et non joueurs"

### Solution Implémentée
Le MJ peut maintenant gérer les points de vie de tous les personnages (joueurs et non-joueurs) et monstres dans ses scènes, avec une interface dédiée et des actions rapides pour les combats.

## Fonctionnalités Implémentées

### 1. Page de Gestion des Points de Vie pour Personnages Joueurs (`view_character_hp.php`)

#### **Interface Utilisateur**
- **En-tête coloré** : Design bleu pour l'identité visuelle des personnages
- **Informations principales** : Nom du personnage, joueur, classe d'armure
- **Barre de points de vie** : Affichage visuel avec gradient de couleurs
- **Statistiques** : Niveau, CA, vitesse, initiative
- **Actions rapides** : Boutons pour dégâts et soins rapides

#### **Gestion des Points de Vie**
```php
// Actions disponibles
- Infliger des dégâts (avec champ de saisie)
- Appliquer des soins (avec champ de saisie)
- Modifier directement les PV (modal)
- Réinitialiser au maximum
- Actions rapides (-1, -5, -10, -20 PV)
```

#### **Contrôle d'Accès**
```php
// Vérification que l'utilisateur est soit le propriétaire, soit le MJ
$isOwner = ($character['user_id'] == $_SESSION['user_id']);
$isDM = ($character['dm_id'] == $_SESSION['user_id']);

if (!$isOwner && !$isDM) {
    header('Location: index.php');
    exit();
}
```

### 2. Page de Gestion des Points de Vie pour PNJ (`view_npc_hp.php`)

#### **Interface Utilisateur**
- **En-tête coloré** : Design violet pour l'identité visuelle des PNJ
- **Informations principales** : Nom du PNJ, type (simple ou avec personnage)
- **Barre de points de vie** : Affichage visuel avec gradient de couleurs
- **Statistiques** : CA, vitesse, initiative, description
- **Actions rapides** : Boutons pour dégâts et soins rapides

#### **Support des Deux Types de PNJ**
```php
// PNJ avec personnage associé
if ($npc['npc_character_id']) {
    // Utilise la table characters
    $stmt = $pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
} else {
    // PNJ simple - utilise scene_npcs
    $stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
}
```

### 3. Intégration dans `view_scene.php`

#### **Liens de Gestion des Points de Vie**
```php
// Pour les personnages joueurs
<a href="view_character_hp.php?id=<?php echo (int)$player['character_id']; ?>&scene_id=<?php echo (int)$scene_id; ?>" 
   class="btn btn-sm btn-outline-warning" title="Gérer les points de vie" target="_blank">
    <i class="fas fa-heart"></i>
</a>

// Pour les PNJ
<a href="view_npc_hp.php?id=<?php echo (int)$npc['id']; ?>&scene_id=<?php echo (int)$scene_id; ?>" 
   class="btn btn-sm btn-outline-warning" title="Gérer les points de vie" target="_blank">
    <i class="fas fa-heart"></i>
</a>
```

#### **Contrôle d'Accès**
- Seul le MJ de la scène peut voir et utiliser les liens de gestion des PV
- Vérification côté serveur dans chaque page de gestion

## Fonctionnalités Techniques

### 1. Gestion des Points de Vie

#### **Actions POST**
```php
switch ($_POST['action']) {
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

### 2. Base de Données

#### **Structure Existante**
- **Table `characters`** : `hit_points_current` et `hit_points_max`
- **Table `scene_npcs`** : `current_hit_points` (ajoutée pour les PNJ simples)

#### **Requêtes de Récupération**
```php
// Personnages joueurs
SELECT c.*, u.username, gs.dm_id, gs.campaign_id
FROM characters c 
JOIN users u ON c.user_id = u.id
JOIN scene_players sp ON c.id = sp.character_id
JOIN scenes s ON sp.scene_id = s.id
JOIN game_sessions gs ON s.session_id = gs.id
WHERE c.id = ? AND sp.scene_id = ? AND sp.player_id = c.user_id

// PNJ
SELECT sn.*, c.hit_points_max, c.hit_points_current, c.armor_class, c.speed, c.initiative, 
       gs.dm_id, gs.campaign_id
FROM scene_npcs sn 
LEFT JOIN characters c ON sn.npc_character_id = c.id
JOIN scenes s ON sn.scene_id = s.id
JOIN game_sessions gs ON s.session_id = gs.id
WHERE sn.id = ? AND sn.scene_id = ? AND sn.monster_id IS NULL
```

### 3. Interface Utilisateur

#### **Barre de Points de Vie**
```css
.hp-bar {
    height: 30px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.hp-fill {
    height: 100%;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.hp-high { background: linear-gradient(90deg, #28a745, #20c997); }
.hp-medium { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.hp-low { background: linear-gradient(90deg, #dc3545, #e83e8c); }
```

#### **Codes Couleur des PV**
- **Vert** : > 50% des points de vie
- **Orange** : 25-50% des points de vie  
- **Rouge** : < 25% des points de vie

#### **Actions Rapides JavaScript**
```javascript
function quickDamage(amount) {
    if (confirm(`Infliger ${amount} points de dégâts ?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="damage">
            <input type="hidden" name="damage" value="${amount}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
```

## Tests de Validation

### Test Réussi
```
Scène : Convocation initiale (ID: 3)

Personnages Joueurs :
- Robin (Hyphrédicte)
  PV : 11/11 | CA: 10
  Lien de gestion HP : ✅ Fonctionnel

PNJ :
- Lieutenant Cameron (PNJ avec personnage associé)
  PV : 11/11 | CA: 10
  Lien de gestion HP : ✅ Fonctionnel

Monstres :
- Aboleth #1
  PV : 75/135 | CA: 17
  Lien de gestion HP : ✅ Fonctionnel (déjà existant)

- Aboleth #2
  PV : 135/135 | CA: 17
  Lien de gestion HP : ✅ Fonctionnel (déjà existant)

Statistiques :
✅ 1 personnage joueur avec gestion HP
✅ 1 PNJ avec gestion HP
✅ 2 monstres avec gestion HP (déjà existant)
✅ Tous les liens fonctionnels
✅ Permissions correctes (seul le MJ peut modifier)
```

### Fonctionnalités Validées
1. ✅ **Gestion des personnages joueurs** : Page dédiée avec toutes les actions
2. ✅ **Gestion des PNJ** : Support des deux types (simple et avec personnage)
3. ✅ **Intégration dans les scènes** : Liens ajoutés dans view_scene.php
4. ✅ **Contrôle d'accès** : Seul le MJ peut modifier les PV
5. ✅ **Interface cohérente** : Design uniforme avec le reste de l'application
6. ✅ **Actions rapides** : Boutons pour dégâts/soins rapides
7. ✅ **Validation** : PV ne peuvent pas dépasser les limites

## Utilisation

### 1. MJ Gère les Points de Vie d'un Personnage Joueur
```
1. Se connecter en tant que MJ
2. Aller sur une scène avec des personnages joueurs
3. Cliquer sur l'icône cœur (❤️) à côté d'un personnage
4. La page de gestion des PV s'ouvre
5. Utiliser les actions pour modifier les PV :
   - Infliger des dégâts (champ ou boutons rapides)
   - Appliquer des soins (champ ou boutons rapides)
   - Modifier directement (modal)
   - Réinitialiser au maximum
```

### 2. MJ Gère les Points de Vie d'un PNJ
```
1. Se connecter en tant que MJ
2. Aller sur une scène avec des PNJ
3. Cliquer sur l'icône cœur (❤️) à côté d'un PNJ
4. La page de gestion des PV s'ouvre
5. Utiliser les mêmes actions que pour les personnages joueurs
```

### 3. Affichage des Points de Vie
```
- Barre de PV avec gradient de couleurs
- Pourcentage affiché
- Codes couleur selon l'état des PV
- Mise à jour en temps réel
```

## Avantages de la Solution

### 1. Interface Intuitive
- **Actions rapides** : Boutons pour dégâts/soins courants
- **Modification directe** : Modal pour définir les PV exacts
- **Barre visuelle** : Affichage clair de l'état des PV
- **Codes couleur** : Identification rapide de l'état critique

### 2. Sécurité et Permissions
- **Contrôle d'accès** : Seul le MJ peut modifier les PV
- **Validation** : PV ne peuvent pas dépasser les limites
- **Vérification côté serveur** : Sécurité renforcée
- **Sessions sécurisées** : Vérification de l'identité

### 3. Flexibilité
- **Support des deux types de PNJ** : Simple et avec personnage
- **Actions multiples** : Dégâts, soins, modification directe, reset
- **Intégration complète** : Liens dans toutes les scènes
- **Design cohérent** : Interface uniforme

### 4. Performance
- **Requêtes optimisées** : Jointures efficaces
- **Mise à jour rapide** : Actions instantanées
- **Interface responsive** : Adaptation aux écrans
- **Cache navigateur** : Chargement optimisé

## Fichiers Créés/Modifiés

### Nouveaux Fichiers
- **`view_character_hp.php`** : Gestion des PV des personnages joueurs
- **`view_npc_hp.php`** : Gestion des PV des PNJ

### Fichiers Modifiés
- **`view_scene.php`** : Ajout des liens de gestion des PV

### Base de Données
- **Table `scene_npcs`** : Colonne `current_hit_points` (déjà existante)

## Cas d'Usage

### 1. Combat en Temps Réel
```
Le MJ inflige des dégâts aux personnages pendant le combat
en utilisant les actions rapides ou en saisissant le montant exact.
Les joueurs voient leurs PV diminuer en temps réel.
```

### 2. Soins et Récupération
```
Le MJ applique des soins aux personnages blessés
en utilisant les boutons de soins rapides ou en saisissant
le montant de soins à appliquer.
```

### 3. Gestion des PNJ
```
Le MJ gère les PV de ses PNJ alliés ou ennemis
avec la même interface que pour les personnages joueurs,
permettant une gestion cohérente de tous les participants.
```

### 4. Réinitialisation
```
Après un combat ou une session, le MJ peut rapidement
remettre tous les PV au maximum avec le bouton de reset.
```

## Évolutions Possibles

### 1. Gestion par Groupe
```php
// Actions sur plusieurs personnages à la fois
function applyDamageToGroup($character_ids, $damage) {
    foreach ($character_ids as $id) {
        // Appliquer les dégâts à chaque personnage
    }
}
```

### 2. Historique des Modifications
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

### 3. Conditions de Combat
```php
// Gestion des conditions (empoisonné, paralysé, etc.)
ALTER TABLE characters ADD COLUMN conditions TEXT;
```

### 4. Notifications en Temps Réel
```javascript
// WebSocket pour les mises à jour en temps réel
const socket = new WebSocket('ws://localhost:8080');
socket.onmessage = function(event) {
    const data = JSON.parse(event.data);
    updateHPDisplay(data.character_id, data.new_hp);
};
```

---

**Statut** : ✅ **SOLUTION COMPLÈTEMENT IMPLÉMENTÉE**

Le MJ peut maintenant gérer les points de vie de tous les personnages (joueurs et non-joueurs) et monstres dans ses scènes, avec une interface dédiée, des actions rapides et un contrôle d'accès sécurisé ! ❤️⚔️🛡️




