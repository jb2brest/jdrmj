# 📦 Script de Publication - publish.sh

## 🎯 Vue d'Ensemble

Le script `publish.sh` est un outil de publication automatisé qui gère la mise à jour des versions, la création de commits, de tags Git et la poussée vers le dépôt distant.

## 🚀 Utilisation

### **Syntaxe**
```bash
./publish.sh <type_de_version> <commentaire>
```

### **Paramètres**
- **`type_de_version`** : Type de mise à jour de version
  - `major` - Version majeure (1.0.0 → 2.0.0)
  - `minor` - Version mineure (1.0.0 → 1.1.0)
  - `patch` - Correctif (1.0.0 → 1.0.1)
- **`commentaire`** : Description de la publication

### **Exemples**
```bash
# Correction de bug
./publish.sh patch "Correction bug affichage navbar"

# Nouvelle fonctionnalité
./publish.sh minor "Ajout système de campagnes"

# Refonte majeure
./publish.sh major "Refonte complète interface utilisateur"
```

## 🔧 Fonctionnalités

### **1. Mise à Jour de Version**
- ✅ Utilise le système de versioning existant (`update_version.sh`)
- ✅ Met à jour le fichier `VERSION`
- ✅ Met à jour la base de données avec la nouvelle version
- ✅ Génère un `BUILD_ID` unique

### **2. Gestion Git**
- ✅ Ajoute tous les fichiers modifiés
- ✅ Crée un commit avec le message formaté
- ✅ Crée un tag Git annoté
- ✅ Pousse les commits et tags vers le dépôt distant

### **3. Sécurité**
- ✅ Validation des paramètres d'entrée
- ✅ Confirmation avant publication
- ✅ Gestion d'erreurs avec codes de sortie
- ✅ Messages colorés pour la lisibilité

## 📋 Processus de Publication

### **Étapes Automatiques**
1. **Validation** des paramètres
2. **Calcul** de la nouvelle version
3. **Confirmation** de l'utilisateur
4. **Mise à jour** de la version
5. **Commit** des modifications
6. **Création** du tag Git
7. **Poussée** vers le dépôt distant

### **Format des Messages**
- **Commit** : `v1.0.3: Correction bug affichage navbar`
- **Tag** : `v1.0.3` avec le même message que le commit

## 🛡️ Sécurité et Validation

### **Vérifications**
- ✅ Existence du fichier `VERSION`
- ✅ Type de version valide (`major`, `minor`, `patch`)
- ✅ Paramètres requis fournis
- ✅ Confirmation utilisateur avant publication

### **Gestion d'Erreurs**
- ❌ Paramètres manquants → Affichage de l'aide
- ❌ Type de version invalide → Message d'erreur
- ❌ Échec de mise à jour → Arrêt du processus
- ❌ Échec Git → Arrêt du processus

## 📊 Exemple de Sortie

```bash
$ ./publish.sh patch "Correction bug affichage"

[INFO] === Script de Publication JDR 4 MJ ===
[INFO] Type de version: patch
[INFO] Commentaire: Correction bug affichage
[INFO] Version actuelle: 1.0.2
[INFO] Nouvelle version: 1.0.3

[WARNING] Êtes-vous sûr de vouloir publier la version 1.0.3 ?
Type de version: patch
Commentaire: Correction bug affichage

Continuer ? (oui/non): oui

[INFO] Mise à jour de la version...
[SUCCESS] Version mise à jour vers 1.0.3
[INFO] Ajout des fichiers au commit...
[INFO] Création du commit...
[SUCCESS] Commit créé: v1.0.3: Correction bug affichage
[INFO] Création du tag Git...
[SUCCESS] Tag créé: v1.0.3
[INFO] Poussée vers le dépôt distant...
[SUCCESS] Poussée vers le dépôt distant réussie

[SUCCESS] === Publication Terminée avec Succès ===
[INFO] Version: 1.0.3
[INFO] Type: patch
[INFO] Commentaire: Correction bug affichage
[INFO] Commit: v1.0.3: Correction bug affichage
[INFO] Tag: v1.0.3

[INFO] Commandes utiles:
  git log --oneline -5                    # Voir les 5 derniers commits
  git tag -l | tail -5                    # Voir les 5 derniers tags
  git show v1.0.3                         # Voir les détails du tag
  git checkout v1.0.3                     # Revenir à cette version
```

## 🔗 Intégration

### **Avec le Système de Versioning**
- Utilise `update_version.sh` pour la mise à jour des versions
- Met à jour le fichier `VERSION`
- Met à jour la base de données

### **Avec Git**
- Intègre parfaitement avec le workflow Git
- Crée des tags sémantiques
- Pousse automatiquement vers le dépôt distant

## 📝 Bonnes Pratiques

### **Types de Version**
- **`patch`** : Corrections de bugs, améliorations mineures
- **`minor`** : Nouvelles fonctionnalités, améliorations importantes
- **`major`** : Changements incompatibles, refontes majeures

### **Messages de Commit**
- Soyez descriptif et précis
- Utilisez le présent de l'indicatif
- Mentionnez les fonctionnalités principales ajoutées/modifiées

### **Workflow Recommandé**
1. Développer et tester les modifications
2. Utiliser `./publish.sh` pour publier
3. Vérifier que la publication s'est bien passée
4. Tester la version publiée si nécessaire

## 🚨 Notes Importantes

- ⚠️ **Confirmation requise** : Le script demande toujours confirmation
- ⚠️ **Poussée automatique** : Les commits et tags sont poussés automatiquement
- ⚠️ **Versioning automatique** : La version est calculée automatiquement
- ⚠️ **Irréversible** : Une fois publié, le tag ne peut pas être modifié facilement

---

**Le script `publish.sh` simplifie et automatise le processus de publication !** 🎉
