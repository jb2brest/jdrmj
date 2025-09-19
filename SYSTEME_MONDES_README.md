# 🌍 Système de Gestion des Mondes - Documentation

## 📋 **Vue d'ensemble**

Le système de gestion des mondes permet aux MJ de créer et organiser leurs univers de campagne avec une hiérarchie géographique complète : **Mondes → Pays → Régions → Lieux**.

---

## 🗃️ **Structure de la base de données**

### **Nouvelle table : `worlds`**
```sql
CREATE TABLE worlds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    map_url VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);
```

### **Table modifiée : `countries`**
```sql
-- Nouvelle colonne ajoutée
ALTER TABLE countries ADD COLUMN world_id INT AFTER id;
ALTER TABLE countries ADD FOREIGN KEY (world_id) REFERENCES worlds(id) ON DELETE CASCADE;
```

### **Hiérarchie géographique complète**
```
worlds (1) ←→ (N) countries
countries (1) ←→ (N) regions  
regions (1) ←→ (N) places
places (1) ←→ (N) place_players
places (1) ←→ (N) place_npcs
places (1) ←→ (N) place_monsters
```

---

## 🎯 **Fonctionnalités**

### **1. Gestion des mondes (`manage_worlds.php`)**
- ✅ **Créer un monde** : Nom, description, carte
- ✅ **Modifier un monde** : Édition complète
- ✅ **Supprimer un monde** : Avec vérification des pays
- ✅ **Visualiser les mondes** : Interface en cartes
- ✅ **Statistiques** : Nombre de pays par monde

### **2. Visualisation d'un monde (`view_world.php`)**
- ✅ **Détails du monde** : Nom, description, carte
- ✅ **Gestion des pays** : CRUD complet
- ✅ **Hiérarchie** : Monde → Pays → Régions
- ✅ **Navigation** : Retour aux mondes, édition

### **3. Intégration dans les campagnes**
- ✅ **Affichage hiérarchique** : Monde → Pays → Région → Lieu
- ✅ **Tableau triable** : Par monde, pays, région
- ✅ **Filtrage** : Par niveau géographique
- ✅ **Compatibilité** : Lieux existants conservés

---

## 🧭 **Navigation**

### **Bouton "Mondes" dans la navbar**
- **Visibilité** : Uniquement pour les MJ et Admin
- **Position** : Entre "Campagnes" et "Admin"
- **Icône** : `fas fa-globe-americas`
- **Lien** : `manage_worlds.php`

### **Pages créées**
1. **`manage_worlds.php`** - Gestion des mondes
2. **`view_world.php`** - Visualisation d'un monde
3. **`database/create_worlds_system.sql`** - Script de création

---

## 🔧 **Fonctions PHP ajoutées**

### **Nouvelles fonctions dans `includes/functions.php`**
```php
// Récupérer tous les mondes d'un utilisateur
function getWorldsByUser($userId)

// Récupérer un monde par ID
function getWorldById($worldId, $userId = null)

// Récupérer tous les pays d'un monde
function getCountriesByWorld($worldId)
```

### **Fonction modifiée**
```php
// Mise à jour pour inclure les mondes
function getPlacesWithGeography($campaignId = null)
// Retourne maintenant : world_name, country_name, region_name
```

---

## 🎨 **Interface utilisateur**

### **Page de gestion des mondes**
- **Layout** : Grille de cartes responsive
- **Actions** : Créer, modifier, supprimer, visualiser
- **Modals** : Création et édition
- **Validation** : Confirmation de suppression
- **Images** : Support des cartes de monde

### **Page de visualisation d'un monde**
- **En-tête** : Nom, description, carte
- **Section pays** : Liste des pays du monde
- **Actions** : Gestion des pays
- **Navigation** : Retour aux mondes

### **Intégration dans les campagnes**
- **Tableau étendu** : Colonne "Monde" ajoutée
- **Hiérarchie visuelle** : Badges colorés par niveau
- **Tri et filtrage** : Par monde, pays, région
- **Compatibilité** : Lieux sans monde affichés

---

## 🛡️ **Sécurité et permissions**

### **Contrôle d'accès**
- **Authentification** : `requireLogin()` requis
- **Autorisation** : `isDMOrAdmin()` uniquement
- **Propriété** : Chaque MJ ne voit que ses mondes
- **Validation** : Vérification des droits sur chaque action

### **Protection des données**
- **Sanitisation** : `sanitizeInput()` sur tous les inputs
- **Préparation** : Requêtes préparées PDO
- **Validation** : Vérification des contraintes
- **Cascade** : Suppression en cascade des relations

---

## 📊 **Exemples d'utilisation**

### **Création d'un monde**
1. **Accès** : Clic sur "Mondes" dans la navbar
2. **Création** : Bouton "Nouveau Monde"
3. **Saisie** : Nom, description, URL de carte
4. **Validation** : Création avec vérification des doublons

### **Organisation géographique**
1. **Monde** : "Terre du Milieu"
2. **Pays** : "Gondor", "Rohan", "Mordor"
3. **Régions** : "Anórien", "Calenardhon", "Gorgoroth"
4. **Lieux** : "Minas Tirith", "Edoras", "Barad-dûr"

### **Utilisation dans les campagnes**
1. **Affichage** : Tableau avec hiérarchie complète
2. **Tri** : Par monde, puis pays, puis région
3. **Filtrage** : Recherche par niveau géographique
4. **Navigation** : Liens vers les lieux

---

## 🔄 **Migration et compatibilité**

### **Données existantes**
- ✅ **Pays existants** : Conservés, `world_id` = NULL
- ✅ **Lieux existants** : Fonctionnent normalement
- ✅ **Régions existantes** : Aucun impact
- ✅ **Campagnes existantes** : Aucun impact

### **Migration progressive**
1. **Création** : Nouveaux pays liés à des mondes
2. **Organisation** : Attribution des pays existants
3. **Nettoyage** : Suppression des pays orphelins
4. **Optimisation** : Hiérarchie complète

---

## 🎉 **Avantages du système**

### **Organisation améliorée**
- **Hiérarchie claire** : Monde → Pays → Région → Lieu
- **Gestion centralisée** : Tous les mondes au même endroit
- **Visualisation** : Cartes de monde intégrées
- **Statistiques** : Compteurs par niveau

### **Expérience utilisateur**
- **Interface intuitive** : Modals et confirmations
- **Navigation fluide** : Liens entre les niveaux
- **Responsive** : Adaptation mobile
- **Accessibilité** : Icônes et labels clairs

### **Maintenabilité**
- **Code modulaire** : Fonctions réutilisables
- **Sécurité** : Validation et sanitisation
- **Performance** : Requêtes optimisées
- **Évolutivité** : Structure extensible

---

## 🚀 **Prochaines étapes possibles**

### **Fonctionnalités avancées**
- **Import/Export** : Sauvegarde des mondes
- **Templates** : Mondes prédéfinis
- **Collaboration** : Partage entre MJ
- **API** : Intégration externe

### **Améliorations UI/UX**
- **Drag & Drop** : Réorganisation des pays
- **Recherche avancée** : Filtres multiples
- **Vue carte** : Visualisation interactive
- **Thèmes** : Personnalisation visuelle

---

**🎉 Le système de gestion des mondes est opérationnel et prêt à l'emploi !**
