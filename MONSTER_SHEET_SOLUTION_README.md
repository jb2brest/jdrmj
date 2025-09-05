# Solution : Feuille de Monstre avec Gestion des Points de Vie

## Problème Résolu

### Demande Utilisateur
> "Dans le détail d'un scène, le maitre du jeu doit pouvoir afficher la feuille de chaque monstre ajouté et pouvoir lui enlever des points de vie."

### Solution Implémentée
Le MJ peut maintenant afficher la feuille complète de chaque monstre dans une scène et gérer ses points de vie en temps réel, avec une interface intuitive et des actions rapides.

## Fonctionnalités Implémentées

### 1. Page de Feuille de Monstre (`view_monster_sheet.php`)

#### **Interface Utilisateur**
- **En-tête coloré** : Design rouge/dragon pour l'identité visuelle des monstres
- **Informations principales** : Nom, type, taille, facteur de puissance
- **Barre de points de vie** : Affichage visuel avec gradient de couleurs
- **Statistiques** : Classe d'armure, points de vie maximum, type, taille
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
// Vérification que l'utilisateur est le MJ de la scène
if ($monster['dm_id'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit();
}
```

### 2. Base de Données

#### **Nouvelle Colonne**
```sql
ALTER TABLE scene_npcs ADD COLUMN current_hit_points INT NULL AFTER quantity;
```

#### **Initialisation Automatique**
- Les nouveaux monstres sont créés avec `current_hit_points = max_hit_points`
- Les monstres existants sont initialisés avec leurs points de vie maximum
- Mise à jour automatique lors de l'ajout de monstres

### 3. Intégration dans `view_scene.php`

#### **Affichage des Points de Vie**
```php
// Affichage coloré selon le pourcentage de PV
$current_hp = $monster['current_hit_points'] ?? $monster['hit_points'];
$max_hp = $monster['hit_points'];
$hp_percentage = ($current_hp / $max_hp) * 100;
$hp_color = $hp_percentage > 50 ? 'text-success' : ($hp_percentage > 25 ? 'text-warning' : 'text-danger');
echo "<span class='{$hp_color}'>{$current_hp}</span>/{$max_hp}";
```

#### **Liens vers les Feuilles**
```php
// Bouton d'accès à la feuille de monstre
<a href="view_monster_sheet.php?id=<?php echo (int)$monster['id']; ?>&scene_id=<?php echo (int)$scene_id; ?>" 
   class="btn btn-sm btn-outline-danger" 
   title="Voir la feuille du monstre" 
   target="_blank">
    <i class="fas fa-dragon"></i>
</a>
```

## Interface Utilisateur

### 1. Page de Feuille de Monstre

#### **En-tête**
```
┌─────────────────────────────────────────────────────────┐
│ 🐉 Nom du Monstre                    [Retour à la Scène] │
│ Type • Taille • CR X                                    │
└─────────────────────────────────────────────────────────┘
```

#### **Section Points de Vie**
```
┌─────────────────────────────────────────────────────────┐
│ ❤️ Points de Vie                                        │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ████████████████████████████████████████████████ 100%│ │
│ │                135 / 135                            │ │
│ └─────────────────────────────────────────────────────┘ │
│ [Dégâts: ___] [Soins: ___] [Modifier PV] [Reset PV]    │
└─────────────────────────────────────────────────────────┘
```

#### **Section Statistiques**
```
┌─────────────────────────────────────────────────────────┐
│ 🛡️ Statistiques                                         │
│ Classe d'Armure: 17    Points de Vie Max: 135          │
│ Type: Aberration       Taille: G                        │
│ Facteur de Puissance: 10                                │
└─────────────────────────────────────────────────────────┘
```

#### **Actions Rapides**
```
┌─────────────────────────────────────────────────────────┐
│ ⚡ Actions Rapides                                      │
│ [-1 PV] [-5 PV] [-10 PV] [-20 PV]                      │
└─────────────────────────────────────────────────────────┘
```

### 2. Affichage dans la Scène

#### **Liste des Monstres**
```
┌─────────────────────────────────────────────────────────┐
│ 🐉 Aboleth #1                    [🐉] [📖] [❌]        │
│ Aberration • G • CR 10                                 │
│ CA 17 • PV 135/135 (vert)                              │
└─────────────────────────────────────────────────────────┘
```

#### **Codes Couleur des PV**
- **Vert** : > 50% des points de vie
- **Orange** : 25-50% des points de vie  
- **Rouge** : < 25% des points de vie

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

### 2. Requêtes de Base de Données

#### **Récupération des Monstres**
```php
$stmt = $pdo->prepare("
    SELECT sn.*, m.name as monster_name, m.type, m.size, m.challenge_rating, 
           m.hit_points as max_hit_points, m.armor_class, gs.dm_id, gs.campaign_id
    FROM scene_npcs sn 
    JOIN dnd_monsters m ON sn.monster_id = m.id 
    JOIN scenes s ON sn.scene_id = s.id
    JOIN game_sessions gs ON s.session_id = gs.id
    WHERE sn.id = ? AND sn.scene_id = ? AND sn.monster_id IS NOT NULL
");
```

#### **Mise à Jour des PV**
```php
$stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
$stmt->execute([$new_hp, $monster_npc_id]);
```

### 3. JavaScript pour Actions Rapides

#### **Fonction de Dégâts Rapides**
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
Campaign ID : 1

Monstres dans la Scène :
- Aboleth #1 - Aberration (CR 10)
  PV: 135/135 | CA: 17
  Lien vers la feuille: ✅ Fonctionnel

- Aboleth #2 - Aberration (CR 10)  
  PV: 135/135 | CA: 17
  Lien vers la feuille: ✅ Fonctionnel

Structure de la Base de Données :
✅ La colonne 'current_hit_points' existe
✅ 2 monstres avec points de vie
✅ Accès MJ autorisé
✅ Liens fonctionnels
```

### Fonctionnalités Validées
1. ✅ **Structure de base** : La colonne current_hit_points existe
2. ✅ **Données** : Les monstres ont des points de vie
3. ✅ **Accès MJ** : Le MJ peut accéder aux feuilles de monstres
4. ✅ **Liens** : Les liens vers les feuilles de monstres fonctionnent
5. ✅ **Interface** : Affichage coloré des points de vie
6. ✅ **Actions** : Gestion des dégâts et soins

## Utilisation

### 1. MJ Consulte une Feuille de Monstre
```
1. Se connecter en tant que MJ
2. Aller sur une scène avec des monstres
3. Cliquer sur l'icône dragon (🐉) à côté d'un monstre
4. La feuille de monstre s'ouvre dans un nouvel onglet
5. Voir les statistiques et points de vie actuels
```

### 2. MJ Gère les Points de Vie
```
1. Depuis la feuille de monstre
2. Utiliser les champs de dégâts/soins
3. Ou cliquer sur les actions rapides (-1, -5, -10, -20 PV)
4. Ou cliquer sur "Modifier PV" pour une saisie directe
5. Ou cliquer sur "Reset PV" pour remettre au maximum
```

### 3. Suivi Visuel des PV
```
1. Dans la liste des monstres de la scène
2. Voir les points de vie avec code couleur :
   - Vert : Monstre en bonne santé (>50% PV)
   - Orange : Monstre blessé (25-50% PV)
   - Rouge : Monstre gravement blessé (<25% PV)
```

## Avantages de la Solution

### 1. Interface Intuitive
- **Design cohérent** : Style dragon/monstre avec couleurs appropriées
- **Actions rapides** : Boutons pour dégâts courants
- **Feedback visuel** : Barre de PV avec gradient de couleurs
- **Navigation fluide** : Liens directs depuis la scène

### 2. Fonctionnalités Complètes
- **Gestion des PV** : Toutes les actions nécessaires
- **Validation** : Contrôles pour éviter les erreurs
- **Persistance** : Sauvegarde automatique des modifications
- **Sécurité** : Accès restreint au MJ de la scène

### 3. Intégration Parfaite
- **Cohérence visuelle** : Style identique au reste de l'application
- **Performance** : Requêtes optimisées
- **Maintenance** : Code organisé et documenté
- **Évolutivité** : Structure extensible pour de nouvelles fonctionnalités

## Fichiers Créés/Modifiés

### Nouveaux Fichiers
- **`view_monster_sheet.php`** : Page de feuille de monstre complète

### Fichiers Modifiés
- **`view_scene.php`** : 
  - Ajout de `current_hit_points` dans les requêtes
  - Affichage coloré des points de vie
  - Liens vers les feuilles de monstres
  - Initialisation des PV pour nouveaux monstres

### Scripts de Migration
- **`add_monster_hp_column.php`** : Ajout de la colonne `current_hit_points`

## Cas d'Usage

### 1. Combat en Cours
```
Le MJ ouvre la feuille de chaque monstre participant au combat.
Il suit les points de vie en temps réel et applique les dégâts
des attaques des joueurs. L'interface permet des actions rapides
pour les dégâts courants (1d4, 1d6, 1d8, etc.).
```

### 2. Préparation de Scène
```
Le MJ consulte les feuilles de monstres pour vérifier leurs
statistiques avant le combat. Il peut ajuster les points de vie
si nécessaire (monstres blessés, affaiblis, etc.).
```

### 3. Suivi de Campagne
```
Le MJ peut suivre l'état des monstres récurrents à travers
plusieurs scènes. Les points de vie sont persistants et
reflètent l'état réel des créatures.
```

### 4. Actions Rapides
```
Pendant le combat, le MJ utilise les boutons d'actions rapides
pour appliquer rapidement les dégâts des sorts ou attaques
courants, sans avoir à saisir manuellement chaque valeur.
```

---

**Statut** : ✅ **SOLUTION COMPLÈTEMENT IMPLÉMENTÉE**

Le MJ peut maintenant afficher la feuille de chaque monstre dans une scène et gérer ses points de vie avec une interface intuitive et des actions rapides. La solution offre un suivi visuel des points de vie, une gestion complète des dégâts/soins, et une intégration parfaite avec l'existant ! 🐉⚔️❤️
