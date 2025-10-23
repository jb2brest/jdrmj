# Solution - Problème de Permissions d'Upload

## 🚨 Problème Identifié

```
PHP Warning: move_uploaded_file(uploads/profiles/profile_2_1760382041.png): 
Failed to open stream: Permission denied in /var/www/html/jdrmj/character_create_step7.php on line 80
```

## 🔍 Cause du Problème

- Le serveur Apache fonctionne avec l'utilisateur `www-data`
- Les dossiers `uploads/` appartiennent à l'utilisateur `jean`
- Le serveur web n'avait pas les permissions d'écriture nécessaires

## ✅ Solutions Appliquées

### 1. Correction des Permissions
```bash
chmod -R 777 uploads/
```

### 2. Modification du Code PHP
**Fichier**: `character_create_step7.php` (ligne 70)
```php
// AVANT
mkdir($upload_dir, 0755, true);

// APRÈS  
mkdir($upload_dir, 0777, true);
```

### 3. Script de Correction Automatique
**Fichier**: `fix_upload_permissions.sh`
- Crée automatiquement tous les dossiers d'upload nécessaires
- Définit les bonnes permissions (777)
- Vérifie la structure des dossiers

## 📁 Dossiers d'Upload Gérés

- `uploads/profiles/` - Photos de profil des personnages
- `uploads/countries/` - Images des pays
- `uploads/places/` - Images des lieux
- `uploads/regions/` - Images des régions
- `uploads/worlds/` - Images des mondes
- `uploads/plans/` - Plans et cartes

## 🔧 Utilisation du Script de Correction

```bash
# Exécuter depuis la racine du projet
./fix_upload_permissions.sh
```

## ⚠️ Recommandations de Sécurité

### Pour l'Environnement de Développement
- Permissions 777 acceptables pour le développement local
- Permissions larges pour faciliter les tests

### Pour l'Environnement de Production
```bash
# Changer le propriétaire vers www-data
sudo chown -R www-data:www-data uploads/

# Permissions plus restrictives
sudo chmod -R 755 uploads/
```

## 🧪 Test de Validation

Le problème a été testé et validé :
- ✅ Création de fichiers dans `uploads/profiles/`
- ✅ Permissions correctes (0777)
- ✅ Code PHP modifié pour créer les dossiers avec les bonnes permissions

## 📋 Fichiers Modifiés

1. `character_create_step7.php` - Permissions de création de dossier
2. `fix_upload_permissions.sh` - Script de correction automatique
3. `uploads/` - Permissions corrigées sur tous les dossiers

---

**Date de résolution**: 2025-10-13  
**Statut**: ✅ Résolu
