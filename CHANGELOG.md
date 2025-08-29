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
