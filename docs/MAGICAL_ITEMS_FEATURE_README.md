# Fonctionnalité de Recherche et d'Attribution d'Objets Magiques

## Description
Cette fonctionnalité ajoute un bouton "Objet Magique" à côté du bouton "Poison" dans l'écran de détail d'une scène. Ce bouton ouvre une fenêtre modale permettant de rechercher des objets magiques et de les attribuer aux PNJ, personnages joueurs ou monstres présents dans la scène.

## Fichiers modifiés/créés

### 1. `search_magical_items.php` (nouveau)
- API PHP qui lit le fichier CSV des objets magiques
- Recherche dans le nom, la clé, la description et le type
- Retourne les résultats au format JSON
- Limite les résultats à 50 pour éviter la surcharge

### 2. `view_scene.php` (modifié)
- Ajout du bouton "Objet Magique" à côté du bouton "Poison"
- Ajout de la modale de recherche d'objets magiques
- Ajout de la modale d'attribution d'objets
- Ajout du JavaScript pour la gestion de la recherche et de l'attribution
- Ajout du traitement POST pour l'attribution d'objets

## Structure de la base de données
Les objets magiques sont stockés dans le fichier `aidednddata/objet_magiques.csv` avec les colonnes suivantes :
- Id : Identifiant unique
- Nom : Nom de l'objet magique
- Cle : Clé de référence
- Description : Description détaillée de l'objet
- Type : Type et rareté de l'objet
- Source : Source de la référence

## Fonctionnalités

### Bouton Objet Magique
- **Apparence** : Bouton bleu (`btn-outline-primary`) avec icône de gemme
- **Position** : À côté du bouton "Poison"
- **Action** : Ouvre la modale `#magicalItemSearchModal`

### Modale de Recherche
- **Titre** : "Recherche d'objets magiques" avec icône de gemme
- **Champ de recherche** : Recherche en temps réel après 2 caractères
- **Résultats** : Affichage des objets avec nom, type, source, description et clé
- **Bouton d'attribution** : Chaque objet a un bouton "Attribuer" vert
- **Limitation** : Maximum 50 résultats pour les performances

### Modale d'Attribution
- **Titre** : "Attribuer un objet magique" avec icône de cadeau
- **Affichage de l'objet** : Nom et type de l'objet sélectionné
- **Sélection du destinataire** : Liste déroulante avec groupes :
  - Personnages joueurs (nom d'utilisateur + nom du personnage)
  - PNJ (nom du PNJ)
  - Monstres (nom du monstre)
- **Notes optionnelles** : Champ de texte pour expliquer l'obtention
- **Validation** : Bouton d'attribution avec confirmation

### Attribution d'Objets
- **Cibles possibles** : PNJ, personnages joueurs, monstres présents dans la scène
- **Traitement** : Validation et message de succès personnalisé
- **Sécurité** : Vérification des permissions (MJ uniquement)
- **Feedback** : Message de confirmation avec détails de l'attribution

## Utilisation

### Recherche d'objets
1. **Accéder à une scène** via `view_scene.php?id=X`
2. **Cliquer sur le bouton "Objet Magique"** (bleu, à côté du bouton Poison)
3. **Taper** au moins 2 caractères dans le champ de recherche
4. **Consulter** les résultats qui s'affichent en temps réel

### Attribution d'objets
1. **Rechercher** un objet magique dans la modale
2. **Cliquer sur "Attribuer"** à côté de l'objet souhaité
3. **Sélectionner** le destinataire dans la liste déroulante
4. **Ajouter des notes** (optionnel) sur l'obtention de l'objet
5. **Confirmer** l'attribution

## Exemples de Recherche

### Recherche par nom
- **"anneau"** → Tous les anneaux magiques
- **"amulette"** → Toutes les amulettes
- **"épée"** → Toutes les épées magiques

### Recherche par type
- **"rare"** → Tous les objets rares
- **"légendaire"** → Tous les objets légendaires
- **"anneau"** → Tous les anneaux

### Recherche par description
- **"invisibilité"** → Objets donnant l'invisibilité
- **"protection"** → Objets de protection
- **"régénération"** → Objets de régénération

## Sécurité et Performance

- ✅ Vérification des requêtes AJAX
- ✅ Validation des paramètres d'entrée
- ✅ Limitation des résultats (50 max)
- ✅ Délai de recherche (300ms) pour éviter le spam
- ✅ Gestion des erreurs et états de chargement
- ✅ Vérification des permissions (MJ uniquement)
- ✅ Validation des cibles d'attribution

## Compatibilité

- ✅ Bootstrap 5.3.0
- ✅ Font Awesome 6.0.0
- ✅ Navigateurs modernes (ES6+)
- ✅ Responsive design
- ✅ Modales Bootstrap

## Maintenance

- **Aucune base de données SQL** requise
- **Mise à jour automatique** via le fichier CSV
- **Configuration simple** dans le fichier CSV
- **Code modulaire** et facilement extensible
- **Gestion des erreurs** robuste

## Intégration avec l'existant

- **Cohérence** : Même style que le bouton Poison
- **Navigation** : Utilise les mêmes modales Bootstrap
- **Données** : Accède aux mêmes PNJ, personnages et monstres
- **Permissions** : Respecte les mêmes règles d'accès
- **Interface** : Suit le même design pattern

---

**Statut** : ✅ **IMPLÉMENTÉ ET TESTÉ**

La fonctionnalité est maintenant complètement intégrée dans l'écran de détail des scènes et permet aux MJ de rechercher et d'attribuer des objets magiques aux participants de leurs sessions de jeu.
