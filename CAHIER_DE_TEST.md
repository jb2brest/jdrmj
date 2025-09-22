# Cahier de Test - Application JDR D&D

## Vue d'ensemble

Ce document décrit l'ensemble des tests existants pour l'application de gestion de personnages D&D. Les tests sont organisés en plusieurs catégories : tests d'authentification, tests de gestion des personnages, tests de gestion des campagnes, tests du bestiaire, tests d'intégration et tests de disponibilité.

---

## 1. Tests d'Authentification

### 1.1 Test d'inscription d'un nouvel utilisateur

**Objectif :** Vérifier que le processus d'inscription d'un nouvel utilisateur fonctionne correctement.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à la page d'inscription (/register.php) | La page d'inscription se charge avec le titre "Inscription" ou "Register" |
| 2 | Remplir le formulaire avec nom d'utilisateur, email, mot de passe et confirmation | Les champs sont remplis correctement |
| 3 | Soumettre le formulaire | Redirection vers index.php ou message de succès affiché |
| 4 | Vérifier la redirection ou le message | L'utilisateur est redirigé ou un message de succès est affiché |

### 1.2 Test de connexion d'un utilisateur

**Objectif :** Vérifier que le processus de connexion fonctionne avec des identifiants valides.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à la page de connexion (/login.php) | La page de connexion se charge avec le titre "Connexion" ou "Login" |
| 2 | Remplir le formulaire avec nom d'utilisateur et mot de passe | Les champs sont remplis correctement |
| 3 | Soumettre le formulaire | Redirection vers index.php ou characters.php |
| 4 | Vérifier la connexion | Présence d'éléments de navigation (lien de déconnexion ou lien vers personnages) |

### 1.3 Test de déconnexion d'un utilisateur

**Objectif :** Vérifier que le processus de déconnexion fonctionne correctement.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Chercher le lien de déconnexion | Le lien de déconnexion est trouvé |
| 3 | Cliquer sur le lien de déconnexion | Redirection vers login.php ou index.php |
| 4 | Vérifier la déconnexion | Absence du lien de déconnexion dans la page |

### 1.4 Test de connexion avec des identifiants invalides

**Objectif :** Vérifier que le système rejette correctement les identifiants invalides.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à la page de connexion | La page de connexion se charge |
| 2 | Remplir avec des identifiants invalides | Les champs sont remplis |
| 3 | Soumettre le formulaire | Un message d'erreur apparaît |
| 4 | Vérifier le message d'erreur | Le message contient "incorrect" ou "invalid" |

### 1.5 Test de validation du formulaire d'inscription

**Objectif :** Vérifier que la validation des champs requis fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à la page d'inscription | La page d'inscription se charge |
| 2 | Soumettre le formulaire vide | Le formulaire n'est pas soumis ou des messages de validation apparaissent |
| 3 | Vérifier la validation | Des messages de validation sont affichés ou le formulaire reste sur la page |

### 1.6 Test de validation de confirmation de mot de passe

**Objectif :** Vérifier que la validation de la confirmation de mot de passe fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à la page d'inscription | La page d'inscription se charge |
| 2 | Remplir avec des mots de passe différents | Les champs sont remplis |
| 3 | Soumettre le formulaire | Un message d'erreur apparaît ou le formulaire n'est pas soumis |
| 4 | Vérifier l'erreur | Le message contient "mots de passe" ou "password" |

---

## 2. Tests de Gestion des Personnages

> **Note :** Tous les tests de cette section sont automatisés avec Selenium WebDriver et se trouvent dans le fichier `tests/functional/test_character_creation_steps.py`

### 2.1 Test de création d'un personnage - Parcours complet par étapes

**Objectif :** Vérifier que le processus de création d'un personnage par étapes fonctionne correctement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_complete_character_creation_workflow`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création (/character_create_step1.php) | La page de sélection de classe se charge (étape 1/9) |
| 3 | Sélectionner une classe (ex: Guerrier) | La classe est sélectionnée et le bouton "Continuer" s'active |
| 4 | Cliquer sur "Continuer vers l'étape 2" | Redirection vers character_create_step2.php |
| 5 | Sélectionner une race (ex: Humain) | La race est sélectionnée et le bouton "Continuer" s'active |
| 6 | Cliquer sur "Continuer vers l'étape 3" | Redirection vers character_create_step3.php |
| 7 | Sélectionner un historique (ex: Soldat) | L'historique est sélectionné et le bouton "Continuer" s'active |
| 8 | Cliquer sur "Continuer vers l'étape 4" | Redirection vers character_create_step4.php |
| 9 | Définir les caractéristiques (méthode des 27 points) | Les caractéristiques sont définies et les valeurs dérivées calculées |
| 10 | Cliquer sur "Continuer vers l'étape 5" | Redirection vers character_create_step5.php |
| 11 | Compléter les étapes restantes (5-9) | Toutes les étapes sont complétées |
| 12 | Finaliser la création | Le personnage est créé et visible dans la liste |

### 2.2 Test de création d'un personnage - Classe Guerrier

**Objectif :** Vérifier que la création d'un personnage Guerrier fonctionne spécifiquement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_warrior_class_creation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création étape 1 | La page de sélection de classe se charge |
| 3 | Sélectionner la classe "Guerrier" | La classe Guerrier est sélectionnée |
| 4 | Vérifier les informations affichées | Dé de vie d10, Force ou Dextérité comme caractéristique principale |
| 5 | Continuer vers l'étape 2 | Redirection vers la sélection de race |
| 6 | Sélectionner une race (ex: Nain) | La race est sélectionnée |
| 7 | Continuer vers l'étape 3 | Redirection vers la sélection d'historique |
| 8 | Sélectionner un historique (ex: Soldat) | L'historique est sélectionné |
| 9 | Continuer vers l'étape 4 | Redirection vers les caractéristiques |
| 10 | Définir les caractéristiques (Force élevée) | Les caractéristiques sont définies |
| 11 | Compléter les étapes restantes | Le personnage Guerrier est créé avec succès |

### 2.3 Test de création d'un personnage - Classe Magicien

**Objectif :** Vérifier que la création d'un personnage Magicien fonctionne spécifiquement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_wizard_class_creation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création étape 1 | La page de sélection de classe se charge |
| 3 | Sélectionner la classe "Magicien" | La classe Magicien est sélectionnée |
| 4 | Vérifier les informations affichées | Dé de vie d6, Intelligence comme caractéristique principale |
| 5 | Continuer vers l'étape 2 | Redirection vers la sélection de race |
| 6 | Sélectionner une race (ex: Elfe) | La race est sélectionnée |
| 7 | Continuer vers l'étape 3 | Redirection vers la sélection d'historique |
| 8 | Sélectionner un historique (ex: Sage) | L'historique est sélectionné |
| 9 | Continuer vers l'étape 4 | Redirection vers les caractéristiques |
| 10 | Définir les caractéristiques (Intelligence élevée) | Les caractéristiques sont définies |
| 11 | Compléter les étapes restantes | Le personnage Magicien est créé avec succès |

### 2.4 Test de création d'un personnage - Classe Clerc

**Objectif :** Vérifier que la création d'un personnage Clerc fonctionne spécifiquement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_cleric_class_creation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création étape 1 | La page de sélection de classe se charge |
| 3 | Sélectionner la classe "Clerc" | La classe Clerc est sélectionnée |
| 4 | Vérifier les informations affichées | Dé de vie d8, Sagesse comme caractéristique principale |
| 5 | Continuer vers l'étape 2 | Redirection vers la sélection de race |
| 6 | Sélectionner une race (ex: Demi-elfe) | La race est sélectionnée |
| 7 | Continuer vers l'étape 3 | Redirection vers la sélection d'historique |
| 8 | Sélectionner un historique (ex: Acolyte) | L'historique est sélectionné |
| 9 | Continuer vers l'étape 4 | Redirection vers les caractéristiques |
| 10 | Définir les caractéristiques (Sagesse élevée) | Les caractéristiques sont définies |
| 11 | Compléter les étapes restantes | Le personnage Clerc est créé avec succès |

### 2.5 Test de création d'un personnage - Race Humain

**Objectif :** Vérifier que la création d'un personnage Humain fonctionne spécifiquement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_human_race_creation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création étape 1 | La page de sélection de classe se charge |
| 3 | Sélectionner une classe (ex: Barde) | La classe est sélectionnée |
| 4 | Continuer vers l'étape 2 | Redirection vers la sélection de race |
| 5 | Sélectionner la race "Humain" | La race Humain est sélectionnée |
| 6 | Vérifier les informations affichées | Versatilité humaine affichée |
| 7 | Continuer vers l'étape 3 | Redirection vers la sélection d'historique |
| 8 | Sélectionner un historique (ex: Artisan) | L'historique est sélectionné |
| 9 | Continuer vers l'étape 4 | Redirection vers les caractéristiques |
| 10 | Définir les caractéristiques | Les caractéristiques sont définies |
| 11 | Compléter les étapes restantes | Le personnage Humain est créé avec succès |

### 2.6 Test de création d'un personnage - Race Elfe

**Objectif :** Vérifier que la création d'un personnage Elfe fonctionne spécifiquement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_elf_race_creation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création étape 1 | La page de sélection de classe se charge |
| 3 | Sélectionner une classe (ex: Rôdeur) | La classe est sélectionnée |
| 4 | Continuer vers l'étape 2 | Redirection vers la sélection de race |
| 5 | Sélectionner la race "Elfe" | La race Elfe est sélectionnée |
| 6 | Vérifier les informations affichées | Vision dans le noir, Fey Ancestry affichés |
| 7 | Continuer vers l'étape 3 | Redirection vers la sélection d'historique |
| 8 | Sélectionner un historique (ex: Ermite) | L'historique est sélectionné |
| 9 | Continuer vers l'étape 4 | Redirection vers les caractéristiques |
| 10 | Définir les caractéristiques | Les caractéristiques sont définies |
| 11 | Compléter les étapes restantes | Le personnage Elfe est créé avec succès |

### 2.7 Test de création d'un personnage - Race Nain

**Objectif :** Vérifier que la création d'un personnage Nain fonctionne spécifiquement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_dwarf_race_creation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création étape 1 | La page de sélection de classe se charge |
| 3 | Sélectionner une classe (ex: Paladin) | La classe est sélectionnée |
| 4 | Continuer vers l'étape 2 | Redirection vers la sélection de race |
| 5 | Sélectionner la race "Nain" | La race Nain est sélectionnée |
| 6 | Vérifier les informations affichées | Vision dans le noir, Résistance aux poisons affichés |
| 7 | Continuer vers l'étape 3 | Redirection vers la sélection d'historique |
| 8 | Sélectionner un historique (ex: Guild Artisan) | L'historique est sélectionné |
| 9 | Continuer vers l'étape 4 | Redirection vers les caractéristiques |
| 10 | Définir les caractéristiques | Les caractéristiques sont définies |
| 11 | Compléter les étapes restantes | Le personnage Nain est créé avec succès |

### 2.8 Test de création d'un personnage - Historique Soldat

**Objectif :** Vérifier que la création d'un personnage avec l'historique Soldat fonctionne spécifiquement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_soldier_background_creation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création étape 1 | La page de sélection de classe se charge |
| 3 | Sélectionner une classe (ex: Guerrier) | La classe est sélectionnée |
| 4 | Continuer vers l'étape 2 | Redirection vers la sélection de race |
| 5 | Sélectionner une race (ex: Humain) | La race est sélectionnée |
| 6 | Continuer vers l'étape 3 | Redirection vers la sélection d'historique |
| 7 | Sélectionner l'historique "Soldat" | L'historique Soldat est sélectionné |
| 8 | Vérifier les informations affichées | Compétences, langues et équipement de départ affichés |
| 9 | Continuer vers l'étape 4 | Redirection vers les caractéristiques |
| 10 | Définir les caractéristiques | Les caractéristiques sont définies |
| 11 | Compléter les étapes restantes | Le personnage avec historique Soldat est créé avec succès |

### 2.9 Test de création d'un personnage - Historique Sage

**Objectif :** Vérifier que la création d'un personnage avec l'historique Sage fonctionne spécifiquement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_sage_background_creation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création étape 1 | La page de sélection de classe se charge |
| 3 | Sélectionner une classe (ex: Magicien) | La classe est sélectionnée |
| 4 | Continuer vers l'étape 2 | Redirection vers la sélection de race |
| 5 | Sélectionner une race (ex: Gnome) | La race est sélectionnée |
| 6 | Continuer vers l'étape 3 | Redirection vers la sélection d'historique |
| 7 | Sélectionner l'historique "Sage" | L'historique Sage est sélectionné |
| 8 | Vérifier les informations affichées | Compétences, langues et équipement de départ affichés |
| 9 | Continuer vers l'étape 4 | Redirection vers les caractéristiques |
| 10 | Définir les caractéristiques | Les caractéristiques sont définies |
| 11 | Compléter les étapes restantes | Le personnage avec historique Sage est créé avec succès |

### 2.10 Test de création d'un personnage - Historique Criminel

**Objectif :** Vérifier que la création d'un personnage avec l'historique Criminel fonctionne spécifiquement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_criminal_background_creation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création étape 1 | La page de sélection de classe se charge |
| 3 | Sélectionner une classe (ex: Roublard) | La classe est sélectionnée |
| 4 | Continuer vers l'étape 2 | Redirection vers la sélection de race |
| 5 | Sélectionner une race (ex: Tieffelin) | La race est sélectionnée |
| 6 | Continuer vers l'étape 3 | Redirection vers la sélection d'historique |
| 7 | Sélectionner l'historique "Criminel" | L'historique Criminel est sélectionné |
| 8 | Vérifier les informations affichées | Compétences, langues et équipement de départ affichés |
| 9 | Continuer vers l'étape 4 | Redirection vers les caractéristiques |
| 10 | Définir les caractéristiques | Les caractéristiques sont définies |
| 11 | Compléter les étapes restantes | Le personnage avec historique Criminel est créé avec succès |

### 2.11 Test de navigation entre les étapes de création

**Objectif :** Vérifier que la navigation entre les étapes de création fonctionne correctement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_step_navigation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création étape 1 | La page de sélection de classe se charge |
| 3 | Sélectionner une classe | La classe est sélectionnée |
| 4 | Cliquer sur "Continuer" | Redirection vers l'étape 2 |
| 5 | Cliquer sur "Retour" | Redirection vers l'étape 1 |
| 6 | Vérifier la persistance des données | La classe sélectionnée est toujours présente |
| 7 | Continuer vers l'étape 2 | Redirection vers l'étape 2 |
| 8 | Sélectionner une race | La race est sélectionnée |
| 9 | Cliquer sur "Retour" | Redirection vers l'étape 1 |
| 10 | Vérifier la persistance des données | La classe et la race sont toujours présentes |

### 2.12 Test de validation des caractéristiques (méthode des 27 points)

**Objectif :** Vérifier que la validation des caractéristiques fonctionne correctement.
**Fichier de test :** `test_character_creation_steps.py::TestCharacterCreationSteps::test_characteristics_validation`

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à l'étape 4 de création | La page de caractéristiques se charge |
| 3 | Définir des caractéristiques valides (≤27 points) | Les caractéristiques sont acceptées |
| 4 | Vérifier le calcul des points | Le compteur de points est correct |
| 5 | Définir des caractéristiques invalides (>27 points) | Un message d'erreur apparaît |
| 6 | Vérifier la validation | Le formulaire n'est pas soumis |
| 7 | Corriger les caractéristiques | Les caractéristiques sont acceptées |

### 2.13 Test d'affichage de la liste des personnages

**Objectif :** Vérifier que la liste des personnages s'affiche correctement.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page des personnages (/characters.php) | La page se charge avec le titre "Personnages" ou "Characters" |
| 3 | Vérifier l'affichage | Des cartes de personnages ou un message "aucun personnage" est affiché |

### 2.14 Test de visualisation d'un personnage

**Objectif :** Vérifier que les détails d'un personnage s'affichent correctement.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page des personnages | La liste des personnages s'affiche |
| 3 | Cliquer sur un lien de personnage | Redirection vers view_character.php |
| 4 | Vérifier l'affichage | Les informations du personnage sont affichées (nom, stats, etc.) |

### 2.15 Test d'édition d'un personnage

**Objectif :** Vérifier que l'édition d'un personnage fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page des personnages | La liste des personnages s'affiche |
| 3 | Cliquer sur un lien d'édition | Redirection vers edit_character.php |
| 4 | Vérifier le formulaire | Le formulaire d'édition est présent avec les champs appropriés |

### 2.16 Test de suppression d'un personnage

**Objectif :** Vérifier que la suppression d'un personnage fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page des personnages | La liste des personnages s'affiche |
| 3 | Cliquer sur le bouton de suppression | Une modal de confirmation peut apparaître |
| 4 | Confirmer la suppression | Redirection vers characters.php ou message de succès |
| 5 | Vérifier la suppression | Le personnage n'apparaît plus dans la liste |

### 2.17 Test de gestion de l'équipement d'un personnage

**Objectif :** Vérifier que la gestion de l'équipement fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page des personnages | La liste des personnages s'affiche |
| 3 | Cliquer sur un lien d'équipement | Redirection vers la page d'équipement |
| 4 | Vérifier l'affichage | Les éléments d'équipement sont affichés |

---

## 3. Tests de Gestion des Campagnes

### 3.1 Test de création d'une campagne

**Objectif :** Vérifier que le processus de création d'une campagne fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter en tant que DM | Connexion réussie |
| 2 | Accéder à la page des campagnes (/campaigns.php) | La page se charge avec le titre "Campagne" ou "Campaign" |
| 3 | Cliquer sur le bouton de création | Redirection vers le formulaire de création |
| 4 | Remplir le nom de la campagne | Le champ nom est rempli |
| 5 | Remplir la description | Le champ description est rempli |
| 6 | Soumettre le formulaire | Redirection vers campaigns.php ou message de succès |

### 3.2 Test d'affichage de la liste des campagnes

**Objectif :** Vérifier que la liste des campagnes s'affiche correctement.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page des campagnes | La page se charge avec le titre approprié |
| 3 | Vérifier l'affichage | Des cartes de campagnes ou un message "aucune campagne" est affiché |

### 3.3 Test de visualisation d'une campagne

**Objectif :** Vérifier que les détails d'une campagne s'affichent.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page des campagnes | La liste des campagnes s'affiche |
| 3 | Cliquer sur un lien de campagne | Redirection vers view_campaign.php |
| 4 | Vérifier l'affichage | Les informations de la campagne sont affichées |

### 3.4 Test de création d'une session

**Objectif :** Vérifier que la création d'une session fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page des campagnes | La liste des campagnes s'affiche |
| 3 | Cliquer sur le bouton de création de session | Redirection vers le formulaire de session |
| 4 | Remplir le nom de la session | Le champ nom est rempli |
| 5 | Soumettre le formulaire | Redirection ou message de succès |

### 3.5 Test de gestion des scènes

**Objectif :** Vérifier que la gestion des scènes fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page des campagnes | La liste des campagnes s'affiche |
| 3 | Cliquer sur un lien de scène | Redirection vers la page de scène |
| 4 | Vérifier l'affichage | Les éléments de scène sont affichés |

### 3.6 Test d'affichage des campagnes publiques

**Objectif :** Vérifier que les campagnes publiques s'affichent.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à la page des campagnes publiques (/public_campaigns.php) | La page se charge (peut nécessiter une connexion) |
| 2 | Vérifier l'affichage | Des cartes de campagnes ou un message "aucune campagne" est affiché |

### 3.7 Test de vue joueur d'une campagne

**Objectif :** Vérifier que la vue joueur d'une campagne fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page des campagnes | La liste des campagnes s'affiche |
| 3 | Cliquer sur un lien de vue joueur | Redirection vers view_campaign_player.php |
| 4 | Vérifier l'affichage | Les informations de la campagne sont affichées |

---

## 4. Tests du Bestiaire

### 4.1 Test d'affichage du bestiaire

**Objectif :** Vérifier que le bestiaire s'affiche correctement.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page du bestiaire (/bestiary.php) | La page se charge avec le titre "Bestiaire" ou "Bestiary" |
| 3 | Vérifier l'affichage | Des cartes de monstres ou un message "aucun monstre" est affiché |

### 4.2 Test de recherche de monstres

**Objectif :** Vérifier que la recherche de monstres fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page du bestiaire | Le bestiaire s'affiche |
| 3 | Saisir "dragon" dans le champ de recherche | Le terme est saisi |
| 4 | Cliquer sur le bouton de recherche | Les résultats de recherche s'affichent |

### 4.3 Test de visualisation des détails d'un monstre

**Objectif :** Vérifier que les détails d'un monstre s'affichent.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page du bestiaire | Le bestiaire s'affiche |
| 3 | Cliquer sur un lien de monstre | Redirection vers la page de détail |
| 4 | Vérifier l'affichage | Les informations du monstre sont affichées (nom, stats, capacités) |

### 4.4 Test de création d'un monstre

**Objectif :** Vérifier que la création d'un monstre fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de création (/create_monster_npc.php) | La page se charge avec le titre approprié |
| 3 | Remplir le nom du monstre | Le champ nom est rempli |
| 4 | Remplir les autres champs nécessaires | Les champs sont remplis |
| 5 | Soumettre le formulaire | Redirection ou message de succès |

### 4.5 Test de la collection de monstres personnels

**Objectif :** Vérifier que la collection de monstres s'affiche.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de collection (/my_monsters.php) | La page se charge avec le titre approprié |
| 3 | Vérifier l'affichage | Des cartes de monstres ou un message "aucun monstre" est affiché |

### 4.6 Test de gestion de l'équipement des monstres

**Objectif :** Vérifier que la gestion de l'équipement des monstres fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de collection | La collection s'affiche |
| 3 | Cliquer sur un lien d'équipement | Redirection vers la page d'équipement |
| 4 | Vérifier l'affichage | Les éléments d'équipement sont affichés |

### 4.7 Test de recherche d'objets magiques

**Objectif :** Vérifier que la recherche d'objets magiques fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de recherche (/search_magical_items.php) | La page se charge avec le titre approprié |
| 3 | Saisir "épée" dans le champ de recherche | Le terme est saisi |
| 4 | Cliquer sur le bouton de recherche | Les résultats de recherche s'affichent |

### 4.8 Test de recherche de poisons

**Objectif :** Vérifier que la recherche de poisons fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page de recherche (/search_poisons.php) | La page se charge avec le titre approprié |
| 3 | Saisir "venin" dans le champ de recherche | Le terme est saisi |
| 4 | Cliquer sur le bouton de recherche | Les résultats de recherche s'affichent |

### 4.9 Test d'accès au grimoire

**Objectif :** Vérifier que l'accès au grimoire fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Se connecter avec un utilisateur valide | Connexion réussie |
| 2 | Accéder à la page du grimoire (/grimoire.php) | La page se charge avec le titre "Grimoire" ou "Spell" |
| 3 | Vérifier l'affichage | Des cartes de sorts ou un message "aucun sort" est affiché |

---

## 5. Tests d'Intégration

### 5.1 Test du parcours complet d'un utilisateur

**Objectif :** Vérifier que le parcours complet d'un utilisateur (inscription → connexion → création de personnage → visualisation → déconnexion) fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Inscription d'un nouvel utilisateur | Inscription réussie |
| 2 | Connexion avec les identifiants | Connexion réussie |
| 3 | Création d'un personnage | Personnage créé avec succès |
| 4 | Visualisation du personnage | Détails du personnage affichés |
| 5 | Déconnexion | Déconnexion réussie |

### 5.2 Test du workflow complet d'un MJ

**Objectif :** Vérifier que le workflow complet d'un MJ (connexion → création de campagne → création de session → gestion de scène → ajout de monstres) fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Connexion en tant que MJ | Connexion réussie |
| 2 | Création d'une campagne | Campagne créée avec succès |
| 3 | Création d'une session | Session créée avec succès |
| 4 | Gestion d'une scène | Scène accessible et fonctionnelle |
| 5 | Ajout de monstres au bestiaire | Monstres ajoutés avec succès |

### 5.3 Test du workflow d'équipement d'un personnage

**Objectif :** Vérifier que le workflow d'équipement (connexion → création de personnage → gestion d'équipement → recherche d'objets magiques) fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Connexion avec un utilisateur valide | Connexion réussie |
| 2 | Création d'un personnage | Personnage créé avec succès |
| 3 | Gestion de l'équipement | Page d'équipement accessible |
| 4 | Recherche d'objets magiques | Recherche fonctionnelle |

### 5.4 Test du workflow de gestion du bestiaire

**Objectif :** Vérifier que le workflow de gestion du bestiaire (connexion → parcourir le bestiaire → rechercher des monstres → créer un monstre personnalisé → ajouter à la collection) fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Connexion avec un utilisateur valide | Connexion réussie |
| 2 | Parcourir le bestiaire | Bestiaire accessible et affiché |
| 3 | Rechercher des monstres | Recherche fonctionnelle |
| 4 | Créer un monstre personnalisé | Création réussie |
| 5 | Ajouter à la collection | Ajout à la collection réussi |

### 5.5 Test du workflow de gestion des sorts

**Objectif :** Vérifier que le workflow de gestion des sorts (connexion → accès au grimoire → recherche de sorts → gestion des sorts d'un personnage) fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Connexion avec un utilisateur valide | Connexion réussie |
| 2 | Accéder au grimoire | Grimoire accessible et affiché |
| 3 | Rechercher des sorts | Recherche fonctionnelle |
| 4 | Gérer les sorts d'un personnage | Gestion des sorts accessible |

---

## 6. Tests de Disponibilité

### 6.1 Test d'accessibilité de la page d'accueil

**Objectif :** Vérifier que la page d'accueil est accessible et se charge correctement.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à l'URL de l'application | La page se charge avec un titre contenant "JDR", "D&D" ou "Donjon" |
| 2 | Vérifier le chargement | Le body de la page est présent |

### 6.2 Test d'accessibilité de la page de connexion

**Objectif :** Vérifier que la page de connexion est accessible.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à /login.php | La page se charge avec le titre "Connexion" ou "Login" |
| 2 | Vérifier les champs | Les champs "username" et "password" sont présents |

### 6.3 Test d'accessibilité de la page d'inscription

**Objectif :** Vérifier que la page d'inscription est accessible.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à /register.php | La page se charge avec le titre "Inscription" ou "Register" |
| 2 | Vérifier les champs | Les champs "username", "email" et "password" sont présents |

### 6.4 Test de responsivité de l'application

**Objectif :** Vérifier que l'application est responsive sur différentes tailles d'écran.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à l'application | La page se charge |
| 2 | Tester la taille 1920x1080 | La page est visible |
| 3 | Tester la taille 1366x768 | La page est visible |
| 4 | Tester la taille 768x1024 | La page est visible |
| 5 | Tester la taille 375x667 | La page est visible |

### 6.5 Test d'absence d'erreurs JavaScript

**Objectif :** Vérifier qu'il n'y a pas d'erreurs JavaScript critiques.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Accéder à l'application | La page se charge |
| 2 | Attendre le chargement complet | La page est entièrement chargée |
| 3 | Vérifier les logs de console | Aucune erreur JavaScript critique n'est présente |

---

## 7. Tests du Système de Capacités

### 7.1 Test de vérification des tables

**Objectif :** Vérifier que les tables du système de capacités existent.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Vérifier la table capability_types | La table existe |
| 2 | Vérifier la table capabilities | La table existe |
| 3 | Vérifier la table character_capabilities | La table existe |

### 7.2 Test des types de capacités

**Objectif :** Vérifier que les types de capacités sont correctement définis.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Récupérer la liste des types | Les types sont récupérés avec nom, icône et couleur |
| 2 | Vérifier le contenu | Les types contiennent les informations attendues |

### 7.3 Test des capacités par source

**Objectif :** Vérifier que les capacités sont correctement organisées par source.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Compter les capacités par source_type | Le nombre de capacités par source est affiché |
| 2 | Vérifier les sources | Les sources incluent race, classe, etc. |

### 7.4 Test des fonctions de récupération

**Objectif :** Vérifier que les fonctions de récupération des capacités fonctionnent.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Récupérer les capacités de Barbare niveau 5 | Les capacités sont récupérées |
| 2 | Récupérer les capacités de Guerrier niveau 3 | Les capacités sont récupérées |
| 3 | Récupérer les capacités raciales Humain | Les capacités sont récupérées |

### 7.5 Test de mise à jour des capacités d'un personnage

**Objectif :** Vérifier que la mise à jour des capacités d'un personnage fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Sélectionner un personnage existant | Un personnage est sélectionné |
| 2 | Mettre à jour ses capacités | Les capacités sont mises à jour |
| 3 | Récupérer les capacités du personnage | Les capacités sont récupérées |
| 4 | Vérifier le contenu | Les capacités contiennent les informations attendues |

### 7.6 Test de recherche de capacités

**Objectif :** Vérifier que la recherche de capacités fonctionne.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Rechercher "rage" | Des résultats sont retournés |
| 2 | Rechercher "magie" | Des résultats sont retournés |
| 3 | Vérifier les résultats | Les résultats correspondent aux critères de recherche |

### 7.7 Test des statistiques finales

**Objectif :** Vérifier que les statistiques du système sont correctes.

| N° | Action | Résultat attendu |
|---|---|---|
| 1 | Compter le total des capacités | Le nombre total est affiché |
| 2 | Compter les capacités de personnages | Le nombre total est affiché |
| 3 | Compter les personnages | Le nombre total est affiché |
| 4 | Calculer la moyenne | La moyenne des capacités par personnage est calculée |

---

## Notes d'Exécution

### Prérequis
- Application déployée et accessible
- Base de données configurée
- Données de test disponibles
- Navigateur web configuré pour les tests Selenium

### Environnement de Test
- Tests automatisés avec Selenium WebDriver
- Tests PHP pour le système de capacités
- Rapports HTML générés automatiquement
- Captures d'écran en cas d'échec

### Exécution des Tests Selenium

#### Prérequis
```bash
# Installer les dépendances Python
pip install -r tests/requirements.txt

# Installer le navigateur (Chrome/Firefox)
# Chrome: Télécharger ChromeDriver
# Firefox: Télécharger GeckoDriver
```

#### Commandes d'exécution
```bash
# Exécuter tous les tests de création de personnages
pytest tests/functional/test_character_creation_steps.py -v

# Exécuter un test spécifique
pytest tests/functional/test_character_creation_steps.py::TestCharacterCreationSteps::test_warrior_class_creation -v

# Exécuter avec rapport HTML
pytest tests/functional/test_character_creation_steps.py --html=reports/character_creation_report.html

# Exécuter en mode headless
pytest tests/functional/test_character_creation_steps.py --headless
```

#### Configuration des tests
- **URL de l'application** : Configurée dans `conftest.py`
- **Données de test** : Définies dans `fixtures/test_data.py`
- **Timeouts** : 10 secondes par défaut
- **Captures d'écran** : Automatiques en cas d'échec

### Gestion des Échecs
- Les tests peuvent être ignorés si les fonctionnalités ne sont pas disponibles
- Les erreurs sont capturées et rapportées
- Les captures d'écran sont prises en cas d'échec pour le débogage

### Maintenance
- Les tests doivent être mis à jour lors de modifications de l'interface
- Les sélecteurs CSS peuvent nécessiter des ajustements
- Les données de test doivent être maintenues à jour
