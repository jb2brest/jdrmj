# JDR 4 MJ - Gestionnaire de Personnages D&D

Un gestionnaire de feuilles de personnage pour Donjons & Dragons 5e, développé en PHP/MySQL avec une interface moderne et responsive.

## 🎲 Fonctionnalités

### ✅ Implémentées
- **Système d'authentification** : Inscription et connexion sécurisées
- **Création de personnages** : Interface complète pour créer des personnages D&D 5e
- **Photos de profil** : Upload et affichage de photos pour personnages et PNJ
- **Gestion des races et classes** : Toutes les races et classes de base incluses
- **Calcul automatique** : Statistiques, points de vie, bonus de maîtrise
- **Interface responsive** : Design moderne avec Bootstrap 5
- **Visualisation détaillée** : Feuilles de personnage complètes
- **Gestion des personnages** : Liste, modification, suppression
- **Gestion des campagnes et sessions** : Création, invitations, planification
- **Gestion des scènes** : Création de scènes avec PNJ et joueurs
- **Interface modale** : Affichage des détails en fenêtres modales

### 🔄 En développement
- [ ] Édition de personnages
- [ ] Gestion des sorts et emplacements
- [ ] Système de compétences avancé
- [ ] Gestion de l'équipement détaillée
- [ ] Export PDF des feuilles
- [ ] Système de campagnes
- [ ] Gestion des jets de dés

## 🚀 Installation

Consultez le [Guide d'Installation](INSTALL.md) pour une installation détaillée.

### Prérequis rapides
- PHP 7.4+
- MySQL 5.7+
- Serveur web (Apache/Nginx)

### Installation rapide
1. Clonez le projet
2. Configurez la base de données (voir `database/schema.sql`)
3. Modifiez `config/database.php`
4. Accédez à l'application

## 📁 Structure du projet

```
/
├── config/          # Configuration
├── database/        # Schéma de base de données
├── includes/        # Fonctions utilitaires
├── index.php        # Page d'accueil
├── login.php        # Connexion
├── register.php     # Inscription
├── characters.php   # Gestion des personnages
└── ...              # Autres pages
```

## 🛡️ Sécurité

- Mots de passe hashés avec `password_hash()`
- Protection contre les injections SQL (PDO)
- Nettoyage des entrées utilisateur
- Sessions sécurisées
- Headers de sécurité

## 🎨 Interface

- Design moderne et responsive
- Thème D&D avec couleurs appropriées
- Navigation intuitive
- Cartes interactives pour les personnages
- Icônes FontAwesome

## 📊 Base de données

Le système inclut :
- **8 races** : Humain, Elfe, Nain, Halfelin, etc.
- **12 classes** : Guerrier, Magicien, Clerc, etc.
- **Système de personnages** complet
- **Gestion des sorts** (structure préparée)

## 🔧 Technologies

- **Backend** : PHP 7.4+
- **Base de données** : MySQL 5.7+
- **Frontend** : Bootstrap 5, FontAwesome
- **Sécurité** : PDO, password_hash, sessions

## 📝 Changelog

### Version 1.0.0 (Actuelle)
- ✅ Système d'authentification complet
- ✅ Création et gestion de personnages
- ✅ Interface moderne et responsive
- ✅ Base de données avec races et classes
- ✅ Calculs automatiques D&D 5e
- ✅ Sécurité renforcée

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
- Signaler des bugs
- Proposer des améliorations
- Contribuer au code

## 📄 Licence

Ce projet est sous licence MIT. Libre d'utilisation, modification et distribution.

---

**Développé avec ❤️ pour la communauté D&D**


## Changelog
<version_tag>
- 0.0.77 : MAJ couleur
- 0.0.76 : MAJ logo
- 0.0.75 : images monstres
- 0.0.74 : Grimoire : liste des emplacements
- 0.0.74 : Grimoire : liste des emplacements
- 0.0.73 : Grimoire préparation
- 0.0.72 : Grimoire ajout des sorts au grimoire
- 0.0.71 : Grimoire responsive
- 0.0.71 : Grimoire responsive
- 0.0.70 : Grimoire taille fixe
- 0.0.69 : Grimoire début
- 0.0.68 : Edition OK
- 0.0.67 : Edition revue
- 0.0.66 : Amélioration affichage caractéristiques
- 0.0.65 : Dés
- 0.0.64 : détection arme et armures
- 0.0.63 : Revue view_caracter
- 0.0.62 : Ajout équipement d'historique
- 0.0.61 : Ajout équipement d'historique
- 0.0.61 : Ajout équipement de départ.
- 0.0.60 : Langues
- 0.0.60 : Langues
- 0.0.59 : Historique
- 0.0.58 : Armes et armures
- 0.0.57 : Armes et armures
- 0.0.58 : Classes
- 0.0.57 : Tableau de carac
- 0.0.56 : Tableau de carac
- 0.0.55 : Tableau de carac
- 0.0.54 : Vitesse
- 0.0.53 : Langues
- 0.0.52 : Langues
- 0.0.51 : Races : bonus de caractérstique
- 0.0.51 : Races : bonus de caractérstique
- 0.0.50 : Début Races
- 0.0.49 : Journal de campagne
- 0.0.48 : identification du personnage
- 0.0.47 : Bascule de l'affichage en cas de changement de lieu.
- 0.0.46 : Liste des PNJ
- 0.0.45 : Cacher les joueurs
- 0.0.44 : Viewer Ok avec repositionnement
- 0.0.43 : Viewer Ok avec positionnement
- 0.0.42 : Campagne du personnage
- 0.0.41 : Suppression plan url
- 0.0.40 : Transfert de lieux
- 0.0.39 : Bascule des scènes en lieux
- 0.0.38 : Bascule des scènes dans la campagne
- 0.0.37 : Position pions OK
- 0.0.36 : Superposition pions OK
- 0.0.36 : Superposition pions OK
- 0.0.35 : correctifs
- 0.0.34 : transfert objet
- 0.0.33 : objet aux monstres
- 0.0.32 : Affichages des images
- 0.0.32 : Affichages des images
- 0.0.31 : Ajout images
- 0.0.30 : Objet magique dans l'inventaire
- 0.0.29 : Ajout des poisons
- 0.0.28 : Ajout des poisons
- 0.0.27 : Ajout des poisons
- 0.0.26 : Récupération aidedd Objets, poisons, Herbes
- 0.0.25 : Récupération aidedd Dons
- 0.0.24 : Récupération aidedd Sorts
- 0.0.23 : Récupération aidedd Sorts
- 0.0.22 : Récupération aidedd lot 2 utilisation de classes
- 0.0.21 : Récupération aidedd lot 2 utilisation de classes
- 0.0.20 : Récupération aidedd lot 2 utilisation de classes
- 0.0.20 : Récupération aidedd lot 2 utilisation de classes
- 0.0.19 : Récupération aidedd lot 1
- 0.0.18 : Récupération aidedd lot 1
- 0.0.17 : Bestiaire
- 0.0.16 : Modification PNJ
- 0.0.16 : Accès au feuille de personnage
- 0.0.15 : Modification publish
- 0.0.14 : Ajout photo Personnages
- 0.0.13 : Ajout photo Personnages
- 0.0.12 : Gestion de scène
- 0.0.13 : Photos de profil pour personnages et PNJ
- 0.0.11 : Gestion de scène