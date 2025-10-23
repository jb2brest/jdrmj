# Refactoring JavaScript - Regroupement dans jdrmj.js

## 🎯 Objectif

Regrouper tous les fichiers JavaScript dans un seul fichier `js/jdrmj.js` pour une meilleure réutilisabilité du code.

## 📁 Structure avant

```
js/
├── view_place.js          # JavaScript pour la page view_place.php
└── npc_identification.js  # JavaScript pour l'identification des PNJ
```

## 📁 Structure après

```
js/
└── jdrmj.js              # JavaScript principal regroupé
```

## 🔧 Modifications effectuées

### 1. Création du fichier `js/jdrmj.js`

Le nouveau fichier regroupe toutes les fonctionnalités :

- **Gestion de l'identification des PNJ** (depuis `npc_identification.js`)
- **Gestion des tokens (drag & drop)** (depuis `view_place.js`)
- **Logique des dés** (depuis `view_place.js`)
- **Recherche de monstres** (depuis `view_place.js`)
- **Recherche de poisons** (depuis `view_place.js`)
- **Recherche d'objets magiques** (depuis `view_place.js`)
- **Gestion des joueurs** (depuis `view_place.js`)
- **Gestion des modales d'accès** (depuis `view_place.js`)

### 2. Mise à jour du template

**Fichier modifié** : `templates/view_place_template.php`

**Avant** :
```html
<script src="js/npc_identification.js"></script>
<script src="js/view_place.js"></script>
```

**Après** :
```html
<script src="js/jdrmj.js"></script>
```

### 3. Suppression des anciens fichiers

- ✅ `js/view_place.js` - Supprimé
- ✅ `js/npc_identification.js` - Supprimé

## 🚀 Avantages

### 1. **Réutilisabilité**
- Un seul fichier JavaScript à maintenir
- Fonctionnalités disponibles sur toutes les pages
- Évite la duplication de code

### 2. **Performance**
- Moins de requêtes HTTP (1 au lieu de 2)
- Cache du navigateur plus efficace
- Chargement plus rapide

### 3. **Maintenance**
- Code centralisé
- Plus facile à déboguer
- Évite les conflits entre fichiers

### 4. **Organisation**
- Code bien structuré avec des sections claires
- Commentaires explicatifs
- Fonctions groupées par fonctionnalité

## 📋 Structure du fichier `js/jdrmj.js`

```javascript
// ===== GESTION DE L'IDENTIFICATION DES PNJ =====
function toggleNpcIdentification() { ... }

// ===== GESTION DES TOKENS (DRAG & DROP) =====
function initializeTokenSystem() { ... }
function initializeTokenDragDrop() { ... }
// ... autres fonctions de tokens

// ===== LOGIQUE DES DÉS =====
function initializeDiceSystem() { ... }
function rollDice() { ... }
// ... autres fonctions de dés

// ===== RECHERCHE DE MONSTRES =====
function initializeMonsterSearch() { ... }
// ... autres fonctions de recherche

// ===== INITIALISATION PRINCIPALE =====
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation de tous les systèmes
});
```

## 🧪 Tests

### Test 1: Vérification du chargement
1. Ouvrir `view_place.php?id=154`
2. Vérifier dans la console du navigateur :
   - `🚀 Initialisation de jdrmj.js`
   - `✅ Initialisation terminée`

### Test 2: Fonctionnalités
1. **Jets de dés** : Sélectionner un dé et lancer
2. **Drag & drop** : Déplacer les tokens sur la carte
3. **Identification PNJ** : Cliquer sur les boutons d'identification
4. **Recherches** : Tester les recherches de monstres/poisons/objets

## 📊 Impact

- **Fichiers supprimés** : 2
- **Fichiers créés** : 1
- **Lignes de code** : ~1300 lignes dans un seul fichier
- **Requêtes HTTP** : -1 (2 → 1)
- **Maintenance** : Simplifiée

## 🔄 Migration

La migration est transparente :
- Aucun changement dans les fonctionnalités
- Même API JavaScript
- Même comportement
- Compatible avec l'existant

## 📝 Notes

- Le fichier `js/jdrmj.js` est auto-documenté
- Chaque section a des commentaires explicatifs
- Les fonctions sont groupées par fonctionnalité
- L'initialisation est centralisée dans `DOMContentLoaded`
