# Changelog

## [2025-01-XX] - Nouvelles fonctionnalités pour l'écran de détail d'une scène

### Ajouté
- **Modification du nom de scène par le MJ** : Le maître du jeu peut maintenant modifier le nom de la scène directement depuis l'écran de détail
  - Bouton "Modifier le nom" à côté du titre de la scène (visible uniquement pour le MJ)
  - Formulaire de modification avec validation (nom non vide)
  - Mise à jour en temps réel du titre après modification

- **Affichage des photos de profil des personnages** : Dans la liste des joueurs présents, les photos de profil des personnages sont maintenant affichées
  - Photos de profil de 40x40 pixels avec style arrondi
  - Fallback avec icône utilisateur si aucune photo n'est disponible
  - Affichage du nom du joueur en gras et du nom du personnage en petit texte grisé
  - Mise en page améliorée avec alignement des éléments

### Modifié
- Requête SQL pour récupérer les joueurs présents : ajout du champ `profile_photo` depuis la table `characters`
- Interface utilisateur : amélioration de la présentation des joueurs avec photos de profil
- Cohérence des requêtes de rechargement après suppression de joueurs/PNJ

### Technique
- Ajout du traitement POST pour l'action `update_title`
- Modification de la requête SQL pour inclure `ch.profile_photo`
- Amélioration de l'affichage HTML avec Bootstrap pour les photos de profil
- Gestion des cas où aucune photo de profil n'est disponible

### Corrections
- **Correction du problème de mise à jour du nom de scène** : Ajout de vérifications supplémentaires pour s'assurer que la scène existe et que la mise à jour est effectuée correctement
  - Vérification de l'existence de la scène avant mise à jour
  - Contrôle du nombre de lignes modifiées après l'UPDATE
  - Gestion d'erreur améliorée avec messages explicites
  - Rechargement des données de la scène après mise à jour

- **Correction de l'affichage des photos de profil des PNJ** : Les photos de profil des personnages du MJ sont maintenant correctement affichées dans la liste des PNJ
  - Modification de la requête SQL pour récupérer la photo de profil du personnage associé
  - Logique d'affichage prioritaire : photo du PNJ > photo du personnage > icône par défaut
  - Cohérence des requêtes de rechargement après ajout/suppression de PNJ

## [2025-01-XX] - Fonctionnalité de suppression de scène

### Ajouté
- **Suppression de scène par le MJ** : Le maître du jeu peut maintenant supprimer une scène depuis la liste des scènes de la session
  - Bouton de suppression avec icône poubelle à côté des boutons de déplacement
  - Confirmation obligatoire avec message d'avertissement détaillé
  - Suppression en cascade de toutes les données associées (joueurs, PNJ)
  - Gestion des erreurs avec rollback en cas de problème
  - Rechargement automatique de la liste des scènes après suppression

## [2025-01-XX] - Accès aux fiches de personnages depuis une scène

### Ajouté
- **Boutons de visualisation des fiches de personnages** : Le maître du jeu peut maintenant accéder directement aux fiches des personnages depuis le détail d'une scène
  - Bouton "Voir la fiche" pour les personnages des joueurs (quand un personnage est associé)
  - Bouton "Voir la fiche" pour les personnages du MJ utilisés comme PNJ
  - Ouverture des fiches dans un nouvel onglet pour ne pas perdre le contexte de la scène
  - Icône de document pour identifier facilement l'action
  - Positionnement cohérent à côté des autres actions (retirer de la scène)

## [2025-01-XX] - Système de placement de pions sur le plan

### Ajouté
- **Placement interactif de pions** : Le maître du jeu peut maintenant placer et déplacer des pions sur le plan de la scène
  - Zone de pions disponibles avec photos de profil des joueurs et PNJ
  - Glisser-déposer direct depuis les pions sources vers le plan
  - Pions représentés par les mêmes photos que dans les listes de personnages
  - Déplacement par glisser-déposer des pions placés sur le plan
  - Sauvegarde automatique des positions via AJAX
  - Bouton "Effacer" pour supprimer tous les pions du plan
  - Interface intuitive avec curseur adaptatif

### Corrigé
- **Bug PNJ tokens** : Correction du problème de validation des PNJ lors du placement de pions
  - Ajout de l'ID des PNJ dans toutes les requêtes de récupération
  - Correction de la validation dans `save_token_position.php`
  - Les PNJ peuvent maintenant être correctement positionnés sur le plan
- **Bug persistance tokens** : Amélioration de la gestion des tokens existants
  - Ajout de validation des valeurs de position dans l'initialisation JavaScript
  - Debug amélioré pour identifier les problèmes de chargement des tokens
  - Gestion des cas où les valeurs de position sont invalides
- **Bug positionnement tokens** : Correction du système de glisser-déposer
  - Refactorisation de la fonction de configuration des tokens sources
  - Ajout de logs détaillés pour diagnostiquer les problèmes
  - Amélioration de la gestion des événements de drag & drop
  - Correction de l'initialisation des tokens sources
  - Ajout de debug pour vérifier l'état de isOwnerDM et l'exécution du JavaScript
- **Bug PHP Warning** : Correction de l'erreur "Undefined array key character_id"
  - Ajout de `ch.id AS character_id` dans les requêtes de récupération des joueurs
  - Correction de la condition pour afficher le lien vers la fiche du personnage
  - Gestion des cas où un joueur n'a pas de personnage associé
- **Simplification du système de tokens** : Diagnostic étape par étape
  - Remplacement du JavaScript complexe par un système de test simple
  - Ajout de logs détaillés pour identifier les problèmes
  - Test de clic et de drag & drop basique
  - Suppression temporaire des fonctionnalités avancées pour isoler le problème
- **Bug JavaScript SyntaxError** : Correction de l'erreur "Unexpected token '}'"
  - Suppression de l'accolade fermante en trop dans le JavaScript
  - Correction de la structure des fonctions imbriquées
- **Debug avancé des tokens sources** : Diagnostic approfondi
  - Ajout de logs détaillés pour vérifier l'existence des tokens sources
  - Vérification de la visibilité des éléments
  - Test de deux méthodes d'attachement d'événements (addEventListener et onclick)
  - Affichage des données complètes des joueurs et PNJ
- **Test JavaScript global** : Vérification du fonctionnement de base
  - Ajout de logs immédiats pour vérifier l'exécution du script
  - Test de document.write pour confirmation visuelle
  - Script de test JavaScript simple créé
- **Debug des variables PHP** : Diagnostic des erreurs JavaScript
  - Ajout de try/catch pour capturer les erreurs de variables PHP
  - Test des variables scenePlayers et sceneNpcs
  - Correction de la portée des variables JavaScript
- **Restauration du système de tokens** : Retour à la version fonctionnelle
  - Restauration du fichier view_scene.php depuis la sauvegarde
  - Retour au système de positionnement de pions qui fonctionnait
  - Suppression des modifications de debug qui causaient des problèmes
- **Suppression du système de positionnement des pions** : Simplification de l'interface
  - Suppression complète de la zone "Pions disponibles"
  - Suppression des boutons de contrôle des pions
  - Suppression des pions placés sur le plan
  - Suppression de tout le JavaScript lié aux tokens
  - Suppression des requêtes SQL pour récupérer les tokens
  - Suppression du fichier save_token_position.php
  - Retour à un affichage simple du plan sans interactivité
- **Restriction de l'ajout de PNJ** : Utilisation exclusive des personnages du MJ
  - Suppression de la création de PNJ libres (nom, description, photo)
  - L'ajout de PNJ se fait uniquement via les personnages créés par le MJ
  - Amélioration de l'interface avec labels et messages d'aide
  - Ajout d'un message informatif si aucun personnage n'est disponible
  - Lien direct vers la création de personnages si nécessaire
  - Suppression de la possibilité de renommer les PNJ (utilisation automatique du nom du personnage)
  - Nettoyage des commentaires de debug et simplification de l'interface

### Technique
- **Nouvelle table `scene_tokens`** : Stockage des positions des pions avec coordonnées X/Y
- **API AJAX `save_token_position.php`** : Sauvegarde sécurisée des positions
- **Interface JavaScript interactive** : Gestion du drag & drop avec clones visuels
- **Système de coordonnées relatives** : Positions en pourcentage pour s'adapter à toutes les tailles d'image
- **Validation des permissions** : Seul le MJ peut placer/déplacer les pions
- **Gestion des doublons** : Un seul pion par entité, mise à jour de position si déjà placé
