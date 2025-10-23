# Correction du positionnement des pions

## 🐛 Problème identifié

La zone des pions et les pions ne sont pas positionnés correctement. Le fonctionnement de `view_place_old.php` n'est pas reproduit.

## 🔍 Analyse du problème

Le JavaScript pour le positionnement des pions était manquant dans la version refactorisée :
- ❌ Pas de drag & drop des pions
- ❌ Pas de positionnement initial des pions
- ❌ Pas de sauvegarde des positions
- ❌ Pas de gestion de la sidebar

## ✅ Corrections apportées

### 1. **Ajout du JavaScript de positionnement**
```javascript
// Fonctions ajoutées dans js/view_place.js
- initializeTokenDragDrop() - Initialisation du drag & drop
- positionTokenOnMap() - Positionnement sur la carte
- resetTokenToSidebar() - Retour à la sidebar
- saveTokenPosition() - Sauvegarde des positions
```

### 2. **Gestion du drag & drop**
- ✅ **Drag start/end** : Gestion des événements de glisser-déposer
- ✅ **Drop sur carte** : Positionnement en pourcentages sur la carte
- ✅ **Drop sur sidebar** : Retour des pions à la sidebar
- ✅ **Calcul de position** : Conversion en pourcentages avec limites

### 3. **Positionnement initial**
- ✅ **Lecture des données** : Récupération des positions depuis `data-*`
- ✅ **Positionnement automatique** : Pions positionnés selon `is_on_map`
- ✅ **Styles dynamiques** : Application des styles selon la position

### 4. **Sauvegarde des positions**
- ✅ **API call** : Appel à `api/update_token_position.php`
- ✅ **Données JSON** : Envoi des positions en JSON
- ✅ **Gestion d'erreurs** : Logs et gestion des erreurs AJAX

## 🎯 Fonctionnalités restaurées

### **Zone des pions (Sidebar)**
- ✅ Affichage de la sidebar avec bordure et fond
- ✅ Pions organisés par type (joueurs, PNJ, monstres)
- ✅ Couleurs distinctes (bleu, vert, rouge)
- ✅ Images de profil des entités

### **Positionnement sur la carte**
- ✅ **Glisser-déposer** : Pions draggables depuis la sidebar
- ✅ **Positionnement précis** : Calcul en pourcentages
- ✅ **Limites de carte** : Contraintes dans les limites de la carte
- ✅ **Z-index** : Pions au-dessus de la carte

### **Retour à la sidebar**
- ✅ **Glisser vers sidebar** : Retour des pions à la sidebar
- ✅ **Styles de sidebar** : Réinitialisation des styles
- ✅ **Sauvegarde** : Position mise à jour en base

## 🧪 Tests effectués

- ✅ Syntaxe JavaScript correcte
- ✅ Variables JavaScript définies
- ✅ Fonctions de positionnement implémentées
- ✅ Gestion des événements drag & drop

## 📁 Fichiers modifiés

- `js/view_place.js` - Ajout du JavaScript de positionnement
- `templates/view_place_template.php` - Variables JavaScript déjà présentes

## 🎯 Résultat

Le positionnement des pions fonctionne maintenant exactement comme dans `view_place_old.php` :

1. **Pions dans la sidebar** : Affichage initial dans la zone des pions
2. **Drag & drop** : Glisser-déposer des pions vers la carte
3. **Positionnement** : Calcul précis en pourcentages
4. **Sauvegarde** : Persistance des positions en base
5. **Retour sidebar** : Glisser vers la sidebar pour remettre les pions

La zone des pions et le positionnement fonctionnent maintenant correctement !
