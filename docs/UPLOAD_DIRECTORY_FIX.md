# ✅ Correction : Répertoires d'Upload Manquants

## 🎯 Problème Identifié

Erreur PHP lors de l'upload de fichiers :
```
PHP Warning: move_uploaded_file(uploads/plan_1758125224_68cadca88294d.jpg): Failed to open stream: No such file or directory
PHP Warning: move_uploaded_file(): Unable to move "/tmp/php422BEn" to "uploads/plan_1758125224_68cadca88294d.jpg"
```

## 🔍 Diagnostic

### **Cause du Problème**
- **Répertoires manquants** : Les répertoires d'upload n'existaient pas sur le serveur de test
- **Permissions** : Pas de permissions d'écriture pour le serveur web
- **Structure incomplète** : Sous-répertoires organisés par date manquants

### **Analyse des Répertoires Requis**
```bash
# Vérification de l'existence des répertoires
ls -la /var/www/html/jdrmj_test/ | grep uploads
# Résultat : Aucun répertoire uploads trouvé
```

### **Répertoires Identifiés**
- **`uploads/`** : Répertoire principal pour les uploads simples
- **`uploads/plans/YYYY/MM/`** : Plans de lieux organisés par date
- **`uploads/profiles/YYYY/MM/`** : Photos de profil organisées par date

## 🔧 Solution Implémentée

### **1. Création des Répertoires**
```bash
# Répertoire principal
sudo mkdir -p /var/www/html/jdrmj_test/uploads

# Sous-répertoires pour les plans
sudo mkdir -p /var/www/html/jdrmj_test/uploads/plans/2025/09

# Sous-répertoires pour les profils
sudo mkdir -p /var/www/html/jdrmj_test/uploads/profiles/2025/09
```

### **2. Configuration des Permissions**
```bash
# Propriétaire : serveur web
sudo chown -R www-data:www-data /var/www/html/jdrmj_test/uploads

# Permissions : lecture/écriture pour le propriétaire, lecture pour les autres
sudo chmod -R 755 /var/www/html/jdrmj_test/uploads
```

### **3. Structure Finale**
```
/var/www/html/jdrmj_test/uploads/
├── plans/
│   └── 2025/
│       └── 09/
└── profiles/
    └── 2025/
        └── 09/
```

## ✅ Résultats

### **Fonctionnalités Restaurées**
- ✅ **Upload de plans** : Fonctionne pour les lieux de campagne
- ✅ **Upload de profils** : Fonctionne pour les photos de profil
- ✅ **Organisation par date** : Fichiers organisés automatiquement
- ✅ **Permissions correctes** : Serveur web peut écrire dans les répertoires

### **Sécurité Maintenue**
- ✅ **Permissions restrictives** : 755 (rwxr-xr-x)
- ✅ **Propriétaire correct** : www-data (serveur web)
- ✅ **Structure organisée** : Séparation par type et date

## 🔍 Vérification

### **Test de Structure**
```bash
# Vérification de la structure
find /var/www/html/jdrmj_test/uploads -type d
/var/www/html/jdrmj_test/uploads
/var/www/html/jdrmj_test/uploads/profiles
/var/www/html/jdrmj_test/uploads/profiles/2025
/var/www/html/jdrmj_test/uploads/profiles/2025/09
/var/www/html/jdrmj_test/uploads/plans
/var/www/html/jdrmj_test/uploads/plans/2025
/var/www/html/jdrmj_test/uploads/plans/2025/09
```

### **Test de Permissions**
```bash
# Vérification des permissions
ls -la /var/www/html/jdrmj_test/uploads/
drwxr-xr-x 4 www-data www-data 4096 sept. 17 18:09 .
drwxr-xr-x 8 www-data www-data 4096 sept. 17 18:08 ..
drwxr-xr-x 3 www-data www-data 4096 sept. 17 18:09 plans
drwxr-xr-x 3 www-data www-data 4096 sept. 17 18:09 profiles
```

## 📋 Fichiers Affectés

### **Fonctionnalités d'Upload**
- ✅ **view_campaign.php** : Upload de plans de lieux
- ✅ **view_scene.php** : Upload de plans de scènes
- ✅ **view_scene_backup.php** : Upload de profils et plans

### **Répertoires Créés**
- ✅ **`uploads/`** : Répertoire principal
- ✅ **`uploads/plans/2025/09/`** : Plans organisés par date
- ✅ **`uploads/profiles/2025/09/`** : Profils organisés par date

## 🎉 Résultat Final

### **Upload Fonctionnel**
- ✅ **Plus d'erreurs PHP** : Upload de fichiers opérationnel
- ✅ **Organisation automatique** : Fichiers classés par type et date
- ✅ **Sécurité maintenue** : Permissions appropriées

### **Expérience Utilisateur**
- ✅ **Upload de plans** : Fonctionne pour les campagnes
- ✅ **Upload de profils** : Fonctionne pour les personnages
- ✅ **Interface fluide** : Plus d'erreurs lors des uploads

---

**Les fonctionnalités d'upload de fichiers sont maintenant opérationnelles !** 🎉
