# Création Automatique de PNJ

Cette fonctionnalité permet aux **Maîtres de Jeu (MJ)** et **Administrateurs** de créer automatiquement des **Personnages Non-Joueurs (PNJ)** avec des caractéristiques générées selon les recommandations D&D.

## 🎯 Fonctionnalités

### **Création Automatique**
- **Sélection manuelle** : Nom (optionnel), Race, Classe, Niveau (1-20)
- **Génération automatique** : Nom (si non fourni), Caractéristiques, Points d'expérience, Historique, Alignement, Histoire, Équipement

### **Caractéristiques selon D&D**
Les caractéristiques sont générées selon les recommandations officielles D&D pour chaque classe :

| Classe | Caractéristiques Prioritaires |
|--------|-------------------------------|
| **Guerrier** | Force, Constitution, Dextérité |
| **Magicien** | Intelligence, Constitution, Dextérité |
| **Clerc** | Sagesse, Constitution, Force |
| **Voleur** | Dextérité, Intelligence, Constitution |
| **Barde** | Charisme, Dextérité, Constitution |
| **Barbare** | Force, Constitution, Dextérité |
| **Moine** | Dextérité, Sagesse, Constitution |
| **Rôdeur** | Dextérité, Sagesse, Constitution |
| **Paladin** | Force, Charisme, Constitution |
| **Ensorceleur** | Charisme, Constitution, Dextérité |
| **Druide** | Sagesse, Constitution, Dextérité |
| **Occultiste** | Charisme, Constitution, Intelligence |

### **Points d'Expérience D&D 5e**
Les points d'expérience sont calculés automatiquement selon la table officielle D&D 5e :

| Niveau | Points d'Expérience | Niveau | Points d'Expérience |
|--------|-------------------|--------|-------------------|
| 1 | 0 XP | 11 | 85,000 XP |
| 2 | 300 XP | 12 | 100,000 XP |
| 3 | 900 XP | 13 | 120,000 XP |
| 4 | 2,700 XP | 14 | 140,000 XP |
| 5 | 6,500 XP | 15 | 165,000 XP |
| 6 | 14,000 XP | 16 | 195,000 XP |
| 7 | 23,000 XP | 17 | 225,000 XP |
| 8 | 34,000 XP | 18 | 265,000 XP |
| 9 | 48,000 XP | 19 | 305,000 XP |
| 10 | 64,000 XP | 20 | 355,000 XP |

### **Gestion des Noms Personnalisés**
Le MJ a le choix entre deux options pour le nom du PNJ :

#### **Option 1 : Nom Personnalisé**
- Le MJ saisit un nom dans le champ "Nom du personnage"
- Le nom saisi sera utilisé tel quel (espaces en début/fin supprimés)
- Exemple : "Gandalf", "Aragorn", "Legolas"

#### **Option 2 : Génération Automatique**
- Le MJ laisse le champ "Nom du personnage" vide
- Un nom sera généré automatiquement selon la race sélectionnée
- Exemple : Humain → "Jean", Elfe → "Aelindra", Nain → "Gimli"

### **Génération Aléatoire**
- **Noms** : Personnalisé par le MJ ou généré selon la race sélectionnée
- **Points d'expérience** : Selon le niveau D&D 5e officiel (0 XP niveau 1, 355,000 XP niveau 20)
- **Historiques** : Selon la classe (ex: Sage pour Magicien, Soldat pour Guerrier)
- **Alignements** : Tous les alignements D&D possibles
- **Traits de personnalité** : Selon la classe (ex: "Courageux et déterminé" pour Guerrier)
- **Idéaux** : Selon l'alignement (ex: "Protection des innocents" pour Loyal Bon)
- **Liens** : Selon la race (ex: "Ma famille est tout pour moi" pour Humain)
- **Défauts** : Selon la classe (ex: "Trop confiant en mes capacités" pour Guerrier)
- **Équipement** : Équipement de départ approprié à la classe

## 🚀 Installation

### **1. Migration de la Base de Données**
```bash
php run_npc_migration.php
```

Cette commande utilise :
- Table `npcs` pour stocker les PNJ
- Table `place_npcs` pour lier les PNJ aux lieux
- Architecture séparée des personnages joueurs

### **2. Vérification**
- Accéder à `characters.php` en tant que MJ/Admin
- Le bouton "Création Automatique" doit être visible

## 📋 Utilisation

### **Accès**
1. Se connecter en tant que **MJ** ou **Admin**
2. Aller sur la page **"Mes Personnages"** (`characters.php`)
3. Cliquer sur **"Création Automatique"**

### **Création d'un PNJ**
1. **Sélectionner la Race** : Humain, Elfe, Nain, etc.
2. **Sélectionner la Classe** : Guerrier, Magicien, Clerc, etc.
3. **Choisir le Niveau** : 1 à 20
4. **Cliquer sur "Créer le PNJ"**

### **Résultat**
- Le PNJ est créé automatiquement
- Redirection vers la fiche du personnage
- Toutes les caractéristiques sont remplies

## 🎲 Détails Techniques

### **Système de Caractéristiques**
- **Point Buy System** : 15, 14, 13, 12, 10, 8
- **Attribution** : Selon les priorités de classe
- **Calculs** : Points de vie, Classe d'armure selon D&D

### **Noms par Race**
```php
'Humain' => ['Aelric', 'Brenna', 'Cedric', 'Dara', ...]
'Elfe' => ['Aelindra', 'Baelor', 'Celebrian', 'Daelin', ...]
'Nain' => ['Balin', 'Dwalin', 'Fili', 'Kili', ...]
'Halfelin' => ['Bilbo', 'Frodo', 'Samwise', 'Pippin', ...]
'Demi-Orc' => ['Grom', 'Thak', 'Zog', 'Morga', ...]
'Tieffelin' => ['Zariel', 'Malphas', 'Belial', 'Asmodeus', ...]
```

### **Historiques par Classe**
```php
'Guerrier' => ['Soldat', 'Noble', 'Criminel', 'Folk Hero']
'Magicien' => ['Sage', 'Acolyte', 'Hermite', 'Noble']
'Clerc' => ['Acolyte', 'Sage', 'Noble', 'Folk Hero']
// ... etc
```

### **Équipement de Départ**
```php
'Guerrier' => 'Épée longue, Bouclier, Armure de cuir, Sac à dos, 10 flèches'
'Magicien' => 'Baguette, Grimoire, Sac à composants, Robe, Sac à dos'
'Clerc' => 'Masse d\'armes, Bouclier, Armure d\'écailles, Symbole sacré, Sac à dos'
// ... etc
```

## 🔒 Sécurité

### **Permissions**
- **Accès restreint** : Seuls les MJ et Admins
- **Vérification** : `User::isDMOrAdmin()`
- **Redirection** : Vers `characters.php` si non autorisé

### **Validation**
- **Race/Classe** : Vérification des IDs en base
- **Niveau** : Entre 1 et 20
- **Données** : Échappement HTML pour la sécurité

## 📁 Fichiers Modifiés

### **Nouveaux Fichiers**
- `create_npc_automatic.php` - Interface de création automatique
- `run_npc_migration.php` - Script de migration
- `add_npc_column.sql` - Script SQL de migration
- `NPC_AUTOMATIC_CREATION_README.md` - Documentation

### **Fichiers Modifiés**
- `characters.php` - Ajout du bouton "Création Automatique"

## 🎮 Interface Utilisateur

### **Bouton d'Accès**
```html
<a href="create_npc_automatic.php" class="btn btn-outline-primary">
    <i class="fas fa-robot me-2"></i>Création Automatique
</a>
```

### **Formulaire de Création**
- **Sélecteurs** : Race, Classe, Niveau
- **Informations** : Explication de la génération automatique
- **Validation** : Messages d'erreur/succès
- **Redirection** : Vers la fiche du personnage créé

## 🔧 Maintenance

### **Ajout de Nouvelles Races/Classes**
1. Ajouter dans la base de données (`races`/`classes`)
2. Ajouter les noms dans `generateRandomName()`
3. Ajouter les historiques dans `selectRandomBackground()`
4. Ajouter l'équipement dans `generateStartingEquipment()`

### **Modification des Caractéristiques**
- Éditer `generateRecommendedStats()`
- Modifier les priorités par classe
- Ajuster les calculs de points de vie/CA

## 🎯 Avantages

### **Pour les MJ**
- **Gain de temps** : Création rapide de PNJ
- **Cohérence** : Caractéristiques selon D&D
- **Variété** : Noms et histoires aléatoires
- **Flexibilité** : Niveaux 1-20

### **Pour les Joueurs**
- **PNJ réalistes** : Caractéristiques équilibrées
- **Immersion** : Noms et histoires cohérents
- **Diversité** : PNJ variés et intéressants

## 🐛 Dépannage

### **Problèmes Courants**
1. **Bouton non visible** : Vérifier les permissions utilisateur
2. **Erreur de création** : Vérifier la table `npcs` et les contraintes de clés étrangères
3. **Noms vides** : Vérifier les données de race/classe

### **Logs**
- Vérifier les logs PHP pour les erreurs
- Contrôler les requêtes SQL dans la base
- Tester avec différents niveaux/races/classes

## 📈 Évolutions Futures

### **Améliorations Possibles**
- **Archétypes** : Sélection d'archétypes par classe
- **Équipement** : Équipement magique aléatoire
- **Sorts** : Sorts automatiques selon la classe
- **Compétences** : Compétences selon l'historique
- **Templates** : Modèles de PNJ prédéfinis

### **Intégrations**
- **Campagnes** : Attribution automatique à une campagne
- **Groupes** : Création de groupes de PNJ
- **Import/Export** : Sauvegarde de PNJ favoris
