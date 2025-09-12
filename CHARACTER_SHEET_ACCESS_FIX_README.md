# Correction : Accès aux Feuilles de Personnage depuis les Scènes

## Problème Identifié

### Symptôme
- **Problème** : Dans l'écran de détail d'une scène, lorsque le Maître du Jeu clique sur la feuille de personnage d'un joueur, celle-ci ne s'affiche pas
- **Comportement attendu** : La feuille de personnage devrait s'ouvrir dans un nouvel onglet
- **Comportement observé** : Redirection vers `characters.php` ou erreur d'accès

### Cause Racine
Le fichier `view_character.php` a un contrôle d'accès strict qui vérifie :
1. Si l'utilisateur est le propriétaire du personnage OU
2. Si l'utilisateur est le MJ de la campagne ET que le paramètre `dm_campaign_id` est fourni

**Problème** : Les liens depuis `view_scene.php` ne passaient pas le paramètre `dm_campaign_id` nécessaire pour que le MJ puisse voir les feuilles de personnage des joueurs.

## Solution Implémentée

### 1. Modification de la Requête de Récupération de la Scène

#### Avant
```sql
SELECT s.*, gs.title AS session_title, gs.id AS session_id, gs.dm_id, u.username AS dm_username 
FROM scenes s 
JOIN game_sessions gs ON s.session_id = gs.id 
JOIN users u ON gs.dm_id = u.id 
WHERE s.id = ?
```

#### Après
```sql
SELECT s.*, gs.title AS session_title, gs.id AS session_id, gs.dm_id, gs.campaign_id, u.username AS dm_username 
FROM scenes s 
JOIN game_sessions gs ON s.session_id = gs.id 
JOIN users u ON gs.dm_id = u.id 
WHERE s.id = ?
```

**Changement** : Ajout de `gs.campaign_id` dans la requête pour récupérer l'ID de la campagne.

### 2. Modification des Liens vers les Feuilles de Personnage

#### Pour les Joueurs
```php
// Avant
<a href="view_character.php?id=<?php echo (int)$player['character_id']; ?>" 
   class="btn btn-sm btn-outline-primary" title="Voir la fiche du personnage" target="_blank">
    <i class="fas fa-file-alt"></i>
</a>

// Après
<a href="view_character.php?id=<?php echo (int)$player['character_id']; ?>&dm_campaign_id=<?php echo (int)$scene['campaign_id']; ?>" 
   class="btn btn-sm btn-outline-primary" title="Voir la fiche du personnage" target="_blank">
    <i class="fas fa-file-alt"></i>
</a>
```

#### Pour les PNJ
```php
// Avant
<a href="view_character.php?id=<?php echo (int)$npc['npc_character_id']; ?>" 
   class="btn btn-sm btn-outline-primary" title="Voir la fiche du personnage" target="_blank">
    <i class="fas fa-file-alt"></i>
</a>

// Après
<a href="view_character.php?id=<?php echo (int)$npc['npc_character_id']; ?>&dm_campaign_id=<?php echo (int)$scene['campaign_id']; ?>" 
   class="btn btn-sm btn-outline-primary" title="Voir la fiche du personnage" target="_blank">
    <i class="fas fa-file-alt"></i>
</a>
```

**Changement** : Ajout du paramètre `&dm_campaign_id=<?php echo (int)$scene['campaign_id']; ?>` dans tous les liens.

## Logique de Contrôle d'Accès dans `view_character.php`

### Code de Contrôle d'Accès
```php
// Contrôle d'accès: propriétaire OU MJ de la campagne liée
$canView = ($character['user_id'] == $_SESSION['user_id']);

if (!$canView && isDM() && $dm_campaign_id) {
    // Vérifier que la campagne appartient au MJ connecté
    $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND dm_id = ?");
    $stmt->execute([$dm_campaign_id, $_SESSION['user_id']]);
    $ownsCampaign = (bool)$stmt->fetch();

    if ($ownsCampaign) {
        // Vérifier que le joueur propriétaire du personnage est membre ou a candidaté à cette campagne
        $owner_user_id = (int)$character['user_id'];
        $isMember = false;
        $hasApplied = false;

        $stmt = $pdo->prepare("SELECT 1 FROM campaign_members WHERE campaign_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$dm_campaign_id, $owner_user_id]);
        $isMember = (bool)$stmt->fetch();

        if ($isMember) {
            $canView = true;
        }
    }
}
```

### Conditions d'Accès
1. **Propriétaire du personnage** : Peut toujours voir sa propre feuille
2. **MJ de la campagne** : Peut voir les feuilles des personnages des joueurs membres de sa campagne
3. **Paramètre requis** : Le paramètre `dm_campaign_id` doit être fourni pour l'accès MJ

## Test de la Correction

### Script de Test
Le fichier `test_character_links.php` permet de :
1. **Identifier** une scène avec des joueurs
2. **Vérifier** les liens générés
3. **Tester** la logique d'accès
4. **Valider** que les paramètres sont correctement passés

### Exécution du Test
```bash
# Accéder au script de test
http://localhost:8000/test_character_links.php
```

### Résultats Attendus
- ✅ Les liens incluent le paramètre `dm_campaign_id`
- ✅ Le MJ peut accéder aux feuilles de personnage des joueurs
- ✅ Les feuilles s'ouvrent dans un nouvel onglet
- ✅ Aucune redirection non désirée vers `characters.php`

## Exemples d'URLs Générées

### Avant la Correction
```
view_character.php?id=123
```
**Résultat** : Accès refusé pour le MJ → Redirection vers `characters.php`

### Après la Correction
```
view_character.php?id=123&dm_campaign_id=456
```
**Résultat** : Accès autorisé pour le MJ → Affichage de la feuille de personnage

## Impact de la Correction

### 1. Fonctionnalités Restaurées
- **Accès MJ** : Le MJ peut maintenant voir les feuilles de personnage des joueurs
- **Navigation fluide** : Clic direct depuis la scène vers la feuille
- **Sécurité maintenue** : Contrôle d'accès strict préservé

### 2. Sécurité
- **Validation** : Vérification que le MJ est propriétaire de la campagne
- **Membres uniquement** : Seuls les joueurs membres de la campagne sont visibles
- **Paramètres requis** : Le paramètre `dm_campaign_id` est obligatoire

### 3. Expérience Utilisateur
- **Intuitivité** : Clic direct sur l'icône pour voir la feuille
- **Ouverture dans nouvel onglet** : Navigation non interrompue
- **Feedback visuel** : Icône claire pour identifier l'action

## Fichiers Modifiés

### `view_scene.php`
- **Ligne 16** : Ajout de `gs.campaign_id` dans la requête SQL
- **Ligne 632** : Ajout du paramètre `dm_campaign_id` pour les joueurs
- **Ligne 732** : Ajout du paramètre `dm_campaign_id` pour les PNJ

### Fichiers Créés
- **`test_character_links.php`** : Script de test et validation
- **`CHARACTER_SHEET_ACCESS_FIX_README.md`** : Documentation de la correction

## Validation et Tests

### 1. Test Manuel
1. **Connectez-vous** en tant que MJ
2. **Accédez** à une scène avec des joueurs
3. **Cliquez** sur l'icône de feuille de personnage
4. **Vérifiez** que la feuille s'ouvre dans un nouvel onglet

### 2. Test Automatisé
```bash
# Exécuter le script de test
php test_character_links.php
```

### 3. Vérifications
- ✅ Paramètre `dm_campaign_id` présent dans l'URL
- ✅ Feuille de personnage s'affiche correctement
- ✅ Aucune erreur d'accès
- ✅ Navigation fluide

## Cas d'Usage

### 1. MJ Consulte une Feuille de Joueur
```
Scénario : Le MJ veut vérifier les stats d'un joueur pendant le combat
Action : Clic sur l'icône de feuille depuis la scène
Résultat : Feuille de personnage s'ouvre instantanément
```

### 2. MJ Consulte une Feuille de PNJ
```
Scénario : Le MJ veut vérifier les capacités d'un PNJ
Action : Clic sur l'icône de feuille depuis la scène
Résultat : Feuille de personnage du PNJ s'ouvre
```

### 3. Gestion des Droits d'Accès
```
Scénario : Un joueur essaie d'accéder à la feuille d'un autre joueur
Action : Clic sur l'icône (si visible)
Résultat : Accès refusé, redirection vers characters.php
```

---

**Statut** : ✅ **CORRECTION APPLIQUÉE**

Le problème d'accès aux feuilles de personnage depuis les scènes est maintenant résolu. Le MJ peut consulter les feuilles de personnage des joueurs et PNJ directement depuis l'écran de détail d'une scène.




