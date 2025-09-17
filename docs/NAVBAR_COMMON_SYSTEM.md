# 🧭 Système de Navbar Commune

## 🎯 Vue d'Ensemble

Le système de navbar commune centralise la navigation de l'application dans un seul fichier réutilisable, garantissant une expérience utilisateur cohérente sur toutes les pages.

## 📁 Structure des Fichiers

### **Fichiers Principaux**
- **`includes/navbar.php`** - Navbar commune avec logique conditionnelle
- **`includes/layout.php`** - Layout commun (optionnel, pour usage futur)

### **Pages Mises à Jour**
Toutes les pages PHP principales utilisent maintenant la navbar commune :
- ✅ `index.php`
- ✅ `characters.php`
- ✅ `campaigns.php`
- ✅ `profile.php`
- ✅ `admin_versions.php`
- ✅ `create_character.php`
- ✅ `edit_character.php`
- ✅ `view_character.php`
- ✅ `view_campaign.php`
- ✅ `view_scene.php`
- ✅ `bestiary.php`
- ✅ `my_monsters.php`
- ✅ `public_campaigns.php`
- ✅ `view_session.php`
- ✅ `manage_experience.php`
- ✅ `view_character_equipment.php`
- ✅ `view_monster_equipment.php`
- ✅ `view_npc_equipment.php`
- ✅ `view_scene_equipment.php`
- ✅ `view_campaign_player.php`
- ✅ `view_scene_backup.php`
- ✅ `view_monster_sheet.php`
- ✅ `login.php`
- ✅ `register.php`

## 🎨 Structure de la Navbar

### **Boutons Principaux**
1. **🏠 Accueil** - Lien vers `index.php`
2. **👥 Personnages** - Lien vers `characters.php` (utilisateurs connectés)
3. **📚 Campagnes** - Lien vers `campaigns.php` (utilisateurs connectés)
4. **🛡️ Admin** - Lien vers `admin_versions.php` (administrateurs uniquement)

### **Menu Utilisateur (Dropdown)**
- **👤 Profil** - Lien vers `profile.php`
- **📖 Campagnes Publiques** - Lien vers `public_campaigns.php`
- **➕ Créer un Personnage** - Lien vers `create_character.php`
- **🐉 Mes Monstres** - Lien vers `my_monsters.php` (MJ/Admin)
- **📚 Bestiaire** - Lien vers `bestiary.php` (MJ/Admin)
- **🚪 Déconnexion** - Lien vers `logout.php`

### **Menu Non Connecté**
- **🔑 Connexion** - Lien vers `login.php`
- **📝 Inscription** - Lien vers `register.php`

## 🔧 Fonctionnalités

### **1. Navigation Conditionnelle**
```php
<?php if (isLoggedIn()): ?>
    <!-- Boutons pour utilisateurs connectés -->
<?php endif; ?>

<?php if (isAdmin()): ?>
    <!-- Bouton Admin uniquement pour les administrateurs -->
<?php endif; ?>
```

### **2. Liens Actifs**
```php
<a class="nav-link <?php echo (isset($current_page) && $current_page === 'characters') ? 'active' : ''; ?>" href="characters.php">
    <i class="fas fa-users me-1"></i>Personnages
</a>
```

### **3. Labels Dynamiques**
```php
<?php echo isAdmin() ? 'Toutes les Campagnes' : 'Campagnes'; ?>
```

### **4. Badges de Rôle**
```php
<?php if (isAdmin()): ?>
    <span class="badge bg-danger ms-1">Admin</span>
<?php elseif (isDM()): ?>
    <span class="badge bg-warning ms-1">MJ</span>
<?php endif; ?>
```

## 📋 Utilisation dans les Pages

### **Variables Requises**
Chaque page doit définir ces variables avant d'inclure la navbar :
```php
<?php
$page_title = "Titre de la Page";
$current_page = "nom_de_la_page"; // Pour marquer le lien actif
?>
```

### **Include de la Navbar**
```php
<?php include 'includes/navbar.php'; ?>
```

### **Exemple Complet**
```php
<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Mes Personnages";
$current_page = "characters";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <!-- CSS et autres meta tags -->
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Contenu de la page -->
</body>
</html>
```

## 🎨 Design et Responsivité

### **Bootstrap 5**
- **Navbar responsive** avec collapse sur mobile
- **Dropdown menus** pour le menu utilisateur
- **Icônes Font Awesome** pour une meilleure UX
- **Badges colorés** pour les rôles utilisateur

### **Couleurs et Styles**
- **Background** : `navbar-dark bg-dark`
- **Badge Admin** : `bg-danger` (rouge)
- **Badge MJ** : `bg-warning` (jaune)
- **Liens actifs** : Classe `active` Bootstrap

### **Responsivité**
- **Mobile** : Menu hamburger avec collapse
- **Desktop** : Navigation horizontale complète
- **Tablet** : Adaptation automatique Bootstrap

## 🔐 Sécurité et Permissions

### **Contrôle d'Accès**
- ✅ **Utilisateurs non connectés** : Seulement Accueil, Connexion, Inscription
- ✅ **Utilisateurs connectés** : Accueil, Personnages, Campagnes
- ✅ **MJ** : Tous les boutons + Monstres et Bestiaire
- ✅ **Administrateurs** : Tous les boutons + Admin

### **Fonctions de Vérification**
- `isLoggedIn()` - Utilisateur connecté
- `isAdmin()` - Utilisateur administrateur
- `isDM()` - Utilisateur Maître de Jeu
- `isDMOrAdmin()` - MJ ou Administrateur

## 🚀 Avantages

### **Maintenance**
- ✅ **Centralisation** : Une seule navbar à maintenir
- ✅ **Cohérence** : Navigation identique sur toutes les pages
- ✅ **Évolutivité** : Facile d'ajouter de nouveaux liens

### **Développement**
- ✅ **Réutilisabilité** : Include simple dans chaque page
- ✅ **DRY Principle** : Pas de duplication de code
- ✅ **Maintenance** : Modifications centralisées

### **Expérience Utilisateur**
- ✅ **Navigation cohérente** : Même structure partout
- ✅ **Liens actifs** : Indication claire de la page courante
- ✅ **Responsive** : Fonctionne sur tous les appareils

## 📝 Bonnes Pratiques

### **Pour les Développeurs**
1. **Toujours définir** `$page_title` et `$current_page`
2. **Utiliser des noms cohérents** pour `$current_page`
3. **Tester la responsivité** sur mobile
4. **Vérifier les permissions** pour chaque rôle

### **Pour les Nouvelles Pages**
1. **Copier le pattern** des pages existantes
2. **Définir les variables** de page
3. **Inclure la navbar** commune
4. **Tester la navigation** et les liens actifs

## 🔍 Tests Recommandés

### **Navigation**
- ✅ Tous les liens fonctionnent
- ✅ Liens actifs corrects
- ✅ Responsivité mobile
- ✅ Dropdown menus

### **Permissions**
- ✅ Bouton Admin visible uniquement pour les admins
- ✅ Boutons MJ visibles pour MJ et Admin
- ✅ Menu utilisateur complet pour tous les rôles
- ✅ Menu non connecté pour les visiteurs

### **Design**
- ✅ Cohérence visuelle
- ✅ Icônes et badges
- ✅ Couleurs et styles
- ✅ Responsivité

---

**La navbar commune garantit une navigation cohérente et professionnelle !** 🎉
