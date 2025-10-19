# Système de Sauvegarde et Restauration - Admin

## Vue d'ensemble

Le système de sauvegarde et restauration permet aux administrateurs de sauvegarder et restaurer les données de l'application JDR 4 MJ. Il se compose de quatre fonctionnalités principales accessibles depuis l'onglet "Actions" de la page `admin_versions.php`.

## Fonctionnalités

### 1. Save Data
**Fichier :** `admin_save_data.php`
**Bouton :** Save Data (Bleu)

**Fonctionnalité :**
- Exporte toutes les tables de la base de données **sauf la table `users`**
- Inclut tous les fichiers uploadés dans les dossiers :
  - `uploads/`
  - `images/`
  - `aidednddata/`
- Génère un fichier ZIP avec :
  - `database_backup.sql` : Sauvegarde de la base de données
  - `uploads/` : Fichiers uploadés (structure conservée)
  - `images/` : Images du système (structure conservée)
  - `aidednddata/` : Données D&D (structure conservée)
  - `backup_info.txt` : Informations sur la sauvegarde

**Utilisation :**
1. Cliquer sur le bouton "Save Data"
2. Le fichier ZIP se télécharge automatiquement
3. Nom du fichier : `backup_data_YYYY-MM-DD_HH-MM-SS.zip`

### 2. Save Users
**Fichier :** `admin_save_users.php`
**Bouton :** Save Users (Vert)

**Fonctionnalité :**
- Exporte uniquement la table `users`
- Inclut les statistiques des utilisateurs
- Génère un fichier ZIP avec :
  - `users_backup.sql` : Sauvegarde de la table users
  - `users_info.txt` : Statistiques des utilisateurs

**Utilisation :**
1. Cliquer sur le bouton "Save Users"
2. Le fichier ZIP se télécharge automatiquement
3. Nom du fichier : `backup_users_YYYY-MM-DD_HH-MM-SS.zip`

### 3. Load Data
**Fichier :** `admin_load_data.php`
**Bouton :** Load Data (Orange)

**Fonctionnalité :**
- Restaure les données à partir d'un fichier généré par "Save Data"
- Remplace les données existantes (sauf la table `users`)
- Restaure les fichiers uploadés

**Utilisation :**
1. Cliquer sur le bouton "Load Data"
2. Sélectionner un fichier ZIP généré par "Save Data"
3. Cliquer sur "Charger les Données"
4. Le système restaure automatiquement les données

**⚠️ Attention :** Cette opération écrase les données existantes !

### 4. Load Users
**Fichier :** `admin_load_users.php`
**Bouton :** Load Users (Cyan)

**Fonctionnalité :**
- Restaure les utilisateurs à partir d'un fichier généré par "Save Users"
- Deux modes de restauration :
  - **Remplacer :** Vide la table et restaure tous les utilisateurs
  - **Ajouter :** Garde les utilisateurs existants et ajoute les nouveaux

**Utilisation :**
1. Cliquer sur le bouton "Load Users"
2. Sélectionner un fichier ZIP généré par "Save Users"
3. Choisir le mode de restauration
4. Cliquer sur "Charger les Utilisateurs"

**⚠️ Attention :** Cette opération affecte directement les comptes utilisateurs !

## Sécurité

### Contrôles d'accès
- Toutes les fonctionnalités nécessitent des privilèges administrateur
- Vérification via `User::requireAdmin()`

### Validation des fichiers
- **Save Data :** Taille maximale 100MB
- **Save Users :** Taille maximale 50MB
- Validation du type MIME (ZIP uniquement)
- Validation de la structure des archives

### Séparation des données
- **Save Data** exclut la table `users` pour des raisons de sécurité
- **Save Users** ne contient que les données utilisateurs
- Permet une gestion séparée des données et des comptes

## Structure des fichiers générés

### Fichier Save Data
```
backup_data_YYYY-MM-DD_HH-MM-SS.zip
├── database_backup.sql          # Toutes les tables sauf users
├── uploads/                     # Fichiers uploadés (structure conservée)
│   ├── image1.jpg
│   ├── document.pdf
│   └── ...
├── images/                      # Images du système (structure conservée)
│   ├── logo.png
│   └── ...
├── aidednddata/                 # Données D&D (structure conservée)
│   ├── spells.json
│   └── ...
└── backup_info.txt             # Informations de sauvegarde
```

### Fichier Save Users
```
backup_users_YYYY-MM-DD_HH-MM-SS.zip
├── users_backup.sql            # Table users complète
└── users_info.txt              # Statistiques des utilisateurs
```

## Gestion des erreurs

### Erreurs courantes
- **Fichier trop volumineux :** Vérifier la taille du fichier
- **Type de fichier invalide :** Utiliser uniquement des fichiers ZIP
- **Archive corrompue :** Régénérer la sauvegarde
- **Permissions insuffisantes :** Vérifier les droits d'écriture

### Logs
- Les erreurs sont affichées dans l'interface utilisateur
- Les fichiers temporaires sont automatiquement supprimés
- Les opérations sont tracées dans les fichiers d'information

## Recommandations

### Sauvegarde régulière
- Effectuer des sauvegardes "Save Data" régulières
- Sauvegarder les utilisateurs avant les mises à jour importantes
- Stocker les sauvegardes dans un endroit sécurisé

### Tests de restauration
- Tester régulièrement la restauration sur un environnement de test
- Vérifier l'intégrité des données après restauration
- Documenter les procédures de récupération

### Sécurité
- Ne jamais partager les fichiers de sauvegarde des utilisateurs
- Chiffrer les sauvegardes sensibles
- Maintenir des sauvegardes hors site

## Support technique

En cas de problème :
1. Vérifier les logs d'erreur PHP
2. Contrôler les permissions des dossiers
3. Vérifier l'espace disque disponible
4. Tester avec des fichiers plus petits

## Historique des versions

- **v1.0** (2024-10-19) : Implémentation initiale
  - Save Data et Save Users
  - Load Data et Load Users
  - Interface d'administration intégrée
