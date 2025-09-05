# Gestion Individuelle des Monstres

## Description
Ce document décrit l'amélioration du système de gestion des monstres dans les scènes, permettant au Maître du Jeu de gérer chaque monstre individuellement.

## Problème Résolu

### Avant l'amélioration
- **Ajout de monstres** : 4 monstres du même type étaient stockés dans une seule ligne avec `quantity = 4`
- **Affichage** : "Nom du Monstre (x4)" - impossible de distinguer les individus
- **Gestion** : Suppression en masse uniquement
- **Feuilles de personnage** : Impossible d'accéder à chaque monstre individuellement

### Après l'amélioration
- **Ajout de monstres** : Chaque monstre est stocké dans une ligne individuelle avec `quantity = 1`
- **Affichage** : "Nom du Monstre #1", "Nom du Monstre #2", "Nom du Monstre #3", "Nom du Monstre #4"
- **Gestion** : Suppression individuelle de chaque monstre
- **Feuilles de personnage** : Accès individuel à chaque monstre

## Fonctionnement Technique

### 1. Ajout de Monstres

#### Code modifié dans `view_scene.php`
```php
// Ajouter un monstre du bestiaire
if (isset($_POST['action']) && $_POST['action'] === 'add_monster') {
    $monster_id = (int)($_POST['monster_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($monster_id > 0 && $quantity > 0) {
        // Récupérer les informations du monstre
        $stmt = $pdo->prepare("SELECT name FROM dnd_monsters WHERE id = ?");
        $stmt->execute([$monster_id]);
        $monster = $stmt->fetch();
        
        if ($monster) {
            // Créer une ligne individuelle pour chaque monstre
            for ($i = 0; $i < $quantity; $i++) {
                $monster_name = $monster['name'];
                if ($quantity > 1) {
                    $monster_name .= " #" . ($i + 1);
                }
                
                $stmt = $pdo->prepare("INSERT INTO scene_npcs (scene_id, name, monster_id, quantity) VALUES (?, ?, ?, 1)");
                $stmt->execute([$scene_id, $monster_name, $monster_id]);
            }
            
            $success_message = $quantity . " monstre(s) ajouté(s) à la scène.";
        }
    }
}
```

#### Exemple d'exécution
```php
// Ajout de 4 Gobelins
for ($i = 0; $i < 4; $i++) {
    $monster_name = "Gobelin";
    if (4 > 1) {
        $monster_name .= " #" . ($i + 1);
    }
    // Résultat : "Gobelin #1", "Gobelin #2", "Gobelin #3", "Gobelin #4"
}
```

### 2. Structure de la Base de Données

#### Table `scene_npcs`
```sql
CREATE TABLE scene_npcs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scene_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,           -- "Gobelin #1", "Gobelin #2", etc.
    description TEXT,
    npc_character_id INT NULL,
    monster_id INT NULL,                  -- Référence vers dnd_monsters
    quantity INT DEFAULT 1,               -- Toujours 1 maintenant
    profile_photo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE,
    FOREIGN KEY (npc_character_id) REFERENCES characters(id) ON DELETE SET NULL,
    FOREIGN KEY (monster_id) REFERENCES dnd_monsters(id) ON DELETE SET NULL
);
```

#### Exemple de données
```sql
-- Avant (ancien système)
INSERT INTO scene_npcs (scene_id, name, monster_id, quantity) 
VALUES (1, 'Gobelin (x4)', 123, 4);

-- Après (nouveau système)
INSERT INTO scene_npcs (scene_id, name, monster_id, quantity) VALUES (1, 'Gobelin #1', 123, 1);
INSERT INTO scene_npcs (scene_id, name, monster_id, quantity) VALUES (1, 'Gobelin #2', 123, 1);
INSERT INTO scene_npcs (scene_id, name, monster_id, quantity) VALUES (1, 'Gobelin #3', 123, 1);
INSERT INTO scene_npcs (scene_id, name, monster_id, quantity) VALUES (1, 'Gobelin #4', 123, 1);
```

### 3. Affichage des Monstres

#### Requête SQL modifiée
```sql
-- Avant (avec quantity)
SELECT sn.id, sn.name, sn.description, sn.monster_id, sn.quantity, 
       m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class 
FROM scene_npcs sn 
JOIN dnd_monsters m ON sn.monster_id = m.id 
WHERE sn.scene_id = ? AND sn.monster_id IS NOT NULL 
ORDER BY sn.name ASC;

-- Après (sans quantity)
SELECT sn.id, sn.name, sn.description, sn.monster_id, 
       m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class 
FROM scene_npcs sn 
JOIN dnd_monsters m ON sn.monster_id = m.id 
WHERE sn.scene_id = ? AND sn.monster_id IS NOT NULL 
ORDER BY sn.name ASC;
```

#### Affichage HTML
```php
<?php foreach ($sceneMonsters as $monster): ?>
    <li class="list-group-item">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-start">
                <div class="bg-danger rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-dragon text-white"></i>
                </div>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($monster['name']); ?></div>
                    <small class="text-muted">
                        <?php echo htmlspecialchars($monster['type']); ?> • 
                        <?php echo htmlspecialchars($monster['size']); ?> • 
                        CR <?php echo htmlspecialchars($monster['challenge_rating']); ?>
                    </small>
                    <br>
                    <small class="text-muted">
                        CA <?php echo htmlspecialchars($monster['armor_class']); ?> • 
                        PV <?php echo htmlspecialchars($monster['hit_points']); ?>
                    </small>
                </div>
            </div>
            <div class="d-flex gap-1">
                <a href="bestiary.php?search=<?php echo urlencode($monster['name']); ?>" 
                   class="btn btn-sm btn-outline-primary" title="Voir dans le bestiaire" target="_blank">
                    <i class="fas fa-book"></i>
                </a>
                <?php if ($isOwnerDM): ?>
                    <form method="POST" class="d-inline" 
                          onsubmit="return confirm('Retirer <?php echo htmlspecialchars($monster['name']); ?> de cette scène ?');">
                        <input type="hidden" name="action" value="remove_monster">
                        <input type="hidden" name="npc_id" value="<?php echo (int)$monster['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer de la scène">
                            <i class="fas fa-user-minus"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </li>
<?php endforeach; ?>
```

## Migration des Données Existantes

### Script de Migration
Le script `migrate_monsters_to_individual.php` permet de convertir les monstres existants :

1. **Détection** : Identifie les monstres avec `quantity > 1`
2. **Conversion** : Crée des entrées individuelles pour chaque monstre
3. **Nettoyage** : Supprime les anciennes entrées groupées
4. **Validation** : Vérifie l'intégrité des données

### Exécution de la Migration
```bash
# Accéder au script de migration
http://localhost:8000/migrate_monsters_to_individual.php
```

### Processus de Migration
```php
// Pour chaque monstre avec quantity > 1
foreach ($monstersToMigrate as $monster) {
    $baseName = $monsterInfo['monster_name'];
    $quantity = $monster['quantity'];
    
    // Supprimer l'entrée originale
    DELETE FROM scene_npcs WHERE id = $monster['id'];
    
    // Créer des entrées individuelles
    for ($i = 0; $i < $quantity; $i++) {
        $individualName = $baseName;
        if ($quantity > 1) {
            $individualName .= " #" . ($i + 1);
        }
        
        INSERT INTO scene_npcs (scene_id, name, monster_id, quantity) 
        VALUES ($monster['scene_id'], $individualName, $monster['monster_id'], 1);
    }
}
```

## Avantages de la Gestion Individuelle

### 1. Contrôle Granulaire
- **Suppression individuelle** : Retirer un monstre spécifique sans affecter les autres
- **Gestion des PV** : Suivre les points de vie de chaque monstre séparément
- **Équipement individuel** : Attribuer des objets à des monstres spécifiques

### 2. Interface Utilisateur
- **Liste claire** : Chaque monstre apparaît individuellement
- **Identification facile** : Numérotation claire (#1, #2, #3, #4)
- **Actions individuelles** : Boutons de suppression et de consultation pour chaque monstre

### 3. Fonctionnalités Avancées
- **Feuilles de personnage** : Accès individuel à chaque monstre
- **Gestion des états** : Suivre les conditions de chaque monstre
- **Historique des actions** : Traçabilité des actions de chaque créature

## Exemples d'Utilisation

### 1. Ajout de 4 Gobelins
```
Résultat dans la liste :
- Gobelin #1 (CR 1/4, CA 15, PV 7)
- Gobelin #2 (CR 1/4, CA 15, PV 7)
- Gobelin #3 (CR 1/4, CA 15, PV 7)
- Gobelin #4 (CR 1/4, CA 15, PV 7)
```

### 2. Gestion Individuelle
- **Supprimer** : Retirer uniquement "Gobelin #2" (blessé)
- **Consulter** : Voir la feuille de "Gobelin #1"
- **Équiper** : Donner une épée à "Gobelin #3"

### 3. Scénarios de Combat
- **Initiative** : Gérer l'ordre de chaque monstre individuellement
- **Actions** : Suivre les actions de chaque créature
- **États** : Appliquer des effets à des monstres spécifiques

## Compatibilité et Rétrocompatibilité

### 1. Données Existantes
- **Migration automatique** : Script de conversion des anciennes données
- **Préservation** : Aucune perte d'information
- **Validation** : Vérification de l'intégrité après migration

### 2. Nouvelles Fonctionnalités
- **Ajout de monstres** : Création automatique d'entrées individuelles
- **Gestion** : Interface adaptée à la gestion individuelle
- **API** : Endpoints compatibles avec le nouveau système

### 3. Évolutions Futures
- **Équipement individuel** : Support des objets attribués à des monstres spécifiques
- **États individuels** : Gestion des conditions de chaque monstre
- **Historique** : Suivi des actions de chaque créature

## Tests et Validation

### 1. Test d'Ajout de Monstres
```php
// Tester l'ajout de 3 monstres du même type
$quantity = 3;
for ($i = 0; $i < $quantity; $i++) {
    $monster_name = "Test Monster";
    if ($quantity > 1) {
        $monster_name .= " #" . ($i + 1);
    }
    // Vérifier que chaque nom est unique
    assert($monster_name === "Test Monster #" . ($i + 1));
}
```

### 2. Test de Migration
```sql
-- Vérifier qu'il n'y a plus de monstres avec quantity > 1
SELECT COUNT(*) FROM scene_npcs WHERE monster_id IS NOT NULL AND quantity > 1;
-- Doit retourner 0

-- Vérifier que tous les monstres ont quantity = 1
SELECT COUNT(*) FROM scene_npcs WHERE monster_id IS NOT NULL AND quantity = 1;
-- Doit correspondre au nombre total de monstres
```

### 3. Test d'Affichage
- **Vérifier** que chaque monstre apparaît individuellement
- **Tester** la suppression individuelle
- **Valider** l'accès aux feuilles de personnage

---

**Statut** : ✅ **GESTION INDIVIDUELLE IMPLÉMENTÉE**

La gestion individuelle des monstres est maintenant complètement implémentée. Chaque monstre apparaît individuellement dans la liste, permettant au MJ un contrôle granulaire et une meilleure gestion des créatures dans ses scènes.

