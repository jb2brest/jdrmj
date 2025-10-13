# Analyse des Tables de Base de Données - Utilisation Réelle

## Résumé Exécutif

**Total des tables dans la base de données : 77**

Cette analyse identifie les tables réellement utilisées dans le code PHP de l'application vs celles qui pourraient être obsolètes ou non utilisées.

## Tables Activement Utilisées (✅)

### Tables Core - Utilisateur et Authentification
- **users** - Gestion des utilisateurs et authentification
- **characters** - Personnages des joueurs (très utilisée)

### Tables D&D Core - Données de Base
- **races** - Races D&D (humain, elfe, nain, etc.)
- **classes** - Classes D&D (guerrier, magicien, etc.)
- **backgrounds** - Historiques des personnages
- **spells** - Sorts D&D
- **languages** - Langues du jeu

### Tables d'Équipement et Objets
- **weapons** - Armes
- **armor** - Armures et boucliers
- **Object** - Objets génériques (note: nom avec majuscule)
- **items** - Système d'objets des personnages
- **magical_items** - Objets magiques
- **poisons** - Poisons

### Tables de Système de Jeu
- **character_spells** - Sorts connus des personnages
- **spell_slots_usage** - Utilisation des emplacements de sorts
- **character_rage_usage** - Utilisation de la rage (barbare)
- **character_capabilities** - Capacités des personnages
- **character_ability_improvements** - Améliorations de caractéristiques
- **experience_levels** - Niveaux d'expérience
- **class_evolution** - Évolution des classes par niveau

### Tables de Campagne et Monde
- **campaigns** - Campagnes de jeu
- **campaign_members** - Membres des campagnes
- **campaign_applications** - Candidatures aux campagnes
- **worlds** - Mondes de jeu
- **countries** - Pays
- **regions** - Régions
- **places** - Lieux
- **scenes** - Scènes de jeu
- **place_campaigns** - Association lieux-campagnes
- **place_players** - Joueurs dans les lieux
- **place_tokens** - Tokens sur les cartes

### Tables de Monstres et PNJ
- **dnd_monsters** - Monstres D&D
- **monster_actions** - Actions des monstres
- **monster_equipment** - Équipement des monstres
- **monster_legendary_actions** - Actions légendaires
- **monster_special_attacks** - Attaques spéciales
- **monster_spells** - Sorts des monstres
- **npc_equipment** - Équipement des PNJ
- **place_monsters** - Monstres dans les lieux
- **place_npcs** - PNJ dans les lieux

### Tables de Système
- **dice_rolls** - Historique des jets de dés
- **notifications** - Notifications système
- **database_migrations** - Suivi des migrations
- **system_versions** - Versions du système

### Tables d'Équipement de Départ
- **starting_equipment** - Équipement de départ des classes
- **starting_equipment_choix** - Choix d'équipement
- **starting_equipment_options** - Options d'équipement

## Tables Potentiellement Non Utilisées (⚠️)

### Tables d'Archetypes/Spécialisations (Possiblement Obsolètes)
- **barbarian_paths** - Chemins de barbare
- **bard_colleges** - Collèges de barde
- **cleric_domains** - Domaines de clerc
- **druid_circles** - Cercles de druide
- **fighter_archetypes** - Archétypes de guerrier
- **monk_traditions** - Traditions de moine
- **paladin_oaths** - Serments de paladin
- **ranger_archetypes** - Archétypes de rôdeur
- **rogue_archetypes** - Archétypes de roublard
- **sorcerer_origins** - Origines d'ensorceleur
- **warlock_pacts** - Pactes de sorcier
- **wizard_traditions** - Traditions de magicien

### Tables de Liaison Personnage-Archetypes (Possiblement Obsolètes)
- **character_barbarian_path** - Liaison personnage-chemin barbare
- **character_bard_college** - Liaison personnage-collège barde
- **character_cleric_domain** - Liaison personnage-domaine clerc
- **character_druid_circle** - Liaison personnage-cercle druide
- **character_fighter_archetype** - Liaison personnage-archétype guerrier
- **character_monk_tradition** - Liaison personnage-tradition moine
- **character_paladin_oaths** - Liaison personnage-serments paladin
- **character_ranger_archetypes** - Liaison personnage-archétypes rôdeur
- **character_rogue_archetypes** - Liaison personnage-archétypes roublard
- **character_sorcerer_origin** - Liaison personnage-origine ensorceleur
- **character_warlock_pact** - Liaison personnage-pacte sorcier
- **character_wizard_tradition** - Liaison personnage-tradition magicien

### Tables de Système (Possiblement Obsolètes)
- **capabilities** - Capacités (possiblement remplacé par character_capabilities)
- **capability_types** - Types de capacités
- **character_creation_sessions** - Sessions de création de personnage
- **campaign_journal** - Journal de campagne
- **place_objects_backup** - Sauvegarde des objets de lieu
- **rage_usage** - Utilisation de rage (possiblement remplacé par character_rage_usage)

## Recommandations

### 1. Tables à Vérifier en Priorité
Les tables d'archetypes et leurs liaisons semblent être des vestiges d'un ancien système. Il est recommandé de :
- Vérifier si ces tables contiennent des données importantes
- Confirmer qu'elles ne sont pas utilisées dans des fonctionnalités cachées
- Considérer leur suppression si elles sont vraiment obsolètes

### 2. Tables de Sauvegarde
- **place_objects_backup** - Peut probablement être supprimée si la migration est terminée

### 3. Tables de Système
- **character_creation_sessions** - Vérifier si cette fonctionnalité est encore utilisée
- **campaign_journal** - Vérifier si le journal de campagne est implémenté

## Actions Recommandées

1. **Audit des données** : Vérifier le contenu des tables suspectes
2. **Tests de régression** : S'assurer qu'aucune fonctionnalité n'est cassée
3. **Sauvegarde** : Créer une sauvegarde complète avant toute suppression
4. **Suppression progressive** : Supprimer les tables par étapes pour minimiser les risques

## Tables Critiques (Ne Jamais Supprimer)

- **users**, **characters** - Core de l'application
- **campaigns**, **campaign_members** - Système de campagne
- **worlds**, **countries**, **regions**, **places** - Système de monde
- **spells**, **character_spells** - Système de magie
- **items** - Système d'objets
- **dice_rolls** - Historique des actions de jeu

---

*Analyse générée le : 2025-10-13*
*Base de données analysée : u839591438_jdrmj (environnement de test)*
