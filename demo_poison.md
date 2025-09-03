# Démonstration de la Fonctionnalité de Recherche de Poisons

## 🎯 Objectif
Ajouter un bouton "Poison" à côté du titre de la session et du nom du MJ dans l'écran de détail d'une scène, permettant d'afficher une fenêtre modale de recherche de poison.

## ✅ Implémentation Réalisée

### 1. Bouton Poison
- **Position** : À côté de "Session: [Titre] • MJ: [Nom]"
- **Style** : Bouton rouge (`btn-outline-danger`) avec icône de crâne et os croisés
- **Action** : Ouvre la modale `#poisonSearchModal`

### 2. Modale de Recherche
- **Titre** : "Recherche de poisons" avec icône appropriée
- **Champ de recherche** : Recherche en temps réel après 2 caractères
- **Résultats** : Affichage des poisons avec nom, type, source, description et clé
- **Limitation** : Maximum 50 résultats pour les performances

### 3. API de Recherche
- **Fichier** : `search_poisons.php`
- **Source** : Lit `aidednddata/poisons.csv`
- **Recherche** : Dans nom, clé, description et type
- **Format** : Retour JSON pour les requêtes AJAX

## 🔍 Exemples de Recherche

### Recherche par nom
- **"arsenic"** → Poison mortel sans odeur
- **"belladone"** → Préparation à base de baies noires
- **"cyanure"** → Poison noirâtre avec odeur d'amandes amères

### Recherche par type
- **"ingestion"** → Tous les poisons par ingestion
- **"blessure"** → Tous les poisons par blessure
- **"contact"** → Tous les poisons par contact

### Recherche par description
- **"mortel"** → Poisons mortels
- **"paralysie"** → Poisons causant la paralysie
- **"végétal"** → Poisons d'origine végétale

## 🎮 Utilisation

1. **Accéder à une scène** via `view_scene.php?id=X`
2. **Localiser le bouton Poison** (rouge, à côté des infos de session)
3. **Cliquer** pour ouvrir la modale
4. **Taper** au moins 2 caractères dans le champ de recherche
5. **Consulter** les résultats qui s'affichent en temps réel

## 🛡️ Sécurité et Performance

- ✅ Vérification des requêtes AJAX
- ✅ Validation des paramètres d'entrée
- ✅ Limitation des résultats (50 max)
- ✅ Délai de recherche (300ms) pour éviter le spam
- ✅ Gestion des erreurs et états de chargement

## 📱 Compatibilité

- ✅ Bootstrap 5.3.0
- ✅ Font Awesome 6.0.0
- ✅ Navigateurs modernes (ES6+)
- ✅ Responsive design

## 🔧 Maintenance

- **Aucune base de données SQL** requise
- **Mise à jour automatique** via le fichier CSV
- **Configuration simple** dans le fichier CSV
- **Code modulaire** et facilement extensible

---

**Statut** : ✅ **IMPLÉMENTÉ ET TESTÉ**

La fonctionnalité est maintenant complètement intégrée dans l'écran de détail des scènes et permet aux MJ de rechercher rapidement des informations sur les poisons pendant leurs sessions de jeu.
