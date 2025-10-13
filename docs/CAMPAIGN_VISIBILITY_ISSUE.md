# 🔍 Problème : Utilisateur Admin Ne Voit Pas Sa Campagne

## 🎯 Problème Identifié

L'utilisateur `jean.m.bernard@gmail.com` (admin) ne voit pas sa campagne "Chroniques du dragon" qu'il a créée, malgré le fait qu'elle existe en base de données.

## 📊 État Actuel

### Base de Données
- ✅ **Campagne existe** : ID 1, "Chroniques du dragon"
- ✅ **Propriétaire** : dm_id = 2 (jean.m.bernard@gmail.com)
- ✅ **Utilisateur existe** : ID 2, rôle = admin

### Code Modifié
- ✅ **campaigns.php** : Utilise `requireDMOrAdmin()`
- ✅ **view_campaign.php** : Utilise `requireDMOrAdmin()`
- ✅ **Logique d'accès** : Les admins peuvent voir toutes les campagnes

## 🔧 Causes Possibles

### 1. **Problème de Session**
- La session n'a pas été mise à jour après le changement de rôle
- `$_SESSION['role']` n'est pas défini ou incorrect
- La fonction `getUserRole()` retourne une valeur incorrecte

### 2. **Problème de Fonction isAdmin()**
- La fonction `isAdmin()` ne fonctionne pas correctement
- La fonction `getUserRole()` ne récupère pas le bon rôle
- Cache de session obsolète

### 3. **Problème de Requête SQL**
- La requête SQL dans `campaigns.php` ne fonctionne pas
- Problème de connexion à la base de données
- Erreur dans la logique conditionnelle

## 🛠️ Scripts de Diagnostic Créés

### 1. **debug_campaign_access.php**
- Diagnostic complet de l'accès aux campagnes
- Test des fonctions de rôle
- Vérification de la session
- Test de récupération des campagnes

### 2. **fix_campaign_session.php**
- Correction forcée de la session
- Mise à jour des variables de session
- Test des fonctions après correction

## 🧪 Instructions de Diagnostic

### **Étape 1 : Diagnostic Complet**
```bash
# Accéder au script de diagnostic
http://localhost/jdrmj_test/debug_campaign_access.php
```

### **Étape 2 : Correction de Session**
```bash
# Accéder au script de correction
http://localhost/jdrmj_test/fix_campaign_session.php
```

### **Étape 3 : Test d'Accès**
```bash
# Accéder à la page des campagnes
http://localhost/jdrmj_test/campaigns.php
```

## 🔍 Points à Vérifier

### **1. Session Utilisateur**
- `$_SESSION['user_id']` = 2
- `$_SESSION['role']` = 'admin'
- `$_SESSION['username']` = 'Jean'
- `$_SESSION['email']` = 'jean.m.bernard@gmail.com'

### **2. Fonctions de Rôle**
- `getUserRole()` retourne 'admin'
- `isAdmin()` retourne true
- `isDMOrAdmin()` retourne true

### **3. Requête SQL**
- Pour les admins : `SELECT c.*, u.username as dm_name FROM campaigns c LEFT JOIN users u ON c.dm_id = u.id ORDER BY c.created_at DESC`
- Doit retourner au moins 1 campagne

## 🚀 Solutions Possibles

### **Solution 1 : Rafraîchir la Session**
1. Se déconnecter et se reconnecter
2. Ou utiliser `fix_campaign_session.php`
3. Ou vider le cache du navigateur

### **Solution 2 : Vérifier la Fonction getUserRole()**
- S'assurer que la fonction récupère bien le rôle depuis la base
- Vérifier que la session est mise à jour

### **Solution 3 : Debug de la Requête SQL**
- Vérifier que la requête SQL fonctionne
- Tester la logique conditionnelle

## 📝 Prochaines Étapes

1. **Exécuter** le diagnostic complet
2. **Identifier** la cause exacte du problème
3. **Appliquer** la solution appropriée
4. **Valider** que l'utilisateur voit sa campagne

## 🎯 Résultat Attendu

Après correction, l'utilisateur `jean.m.bernard@gmail.com` devrait :
- ✅ Voir sa campagne "Chroniques du dragon"
- ✅ Pouvoir accéder à `view_campaign.php`
- ✅ Voir "Toutes les Campagnes" (en tant qu'admin)
- ✅ Pouvoir gérer toutes les campagnes

---

**Le problème est identifié et les outils de diagnostic sont prêts !** 🔧
