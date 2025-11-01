# Audit de la classe Character - Version 3
**Date**: $(date)
**Lignes de code**: 1998 lignes

## 📊 Vue d'ensemble

La classe `Character` est la classe centrale pour la gestion des personnages joueurs (PJ) dans le système D&D. Elle a subi plusieurs refactorings pour améliorer la cohérence orientée objet.

### Statistiques
- **Méthodes publiques**: 66
- **Méthodes privées**: 2 (`hydrate`, `generateBaseCapabilities`, `calculateLevelFromExperience`)
- **Méthodes statiques**: 4 (`create`, `findById`, `findByUserId`, `getCharactersByUser`, `getByUserId`)
- **Méthodes d'instance**: 62
- **Propriétés publiques**: 45+

---

## 🔍 Analyse détaillée des méthodes

### Méthodes de base (CRUD)

#### ✅ Méthodes statiques de création/recherche
1. **`create(array $data, PDO $pdo = null)`** - Création d'un personnage ✅ **Utilisée**
2. **`findById($id, PDO $pdo = null)`** - Recherche par ID ✅ **Très utilisée**
3. **`findByUserId($userId, PDO $pdo = null)`** - Recherche par utilisateur ✅ **Utilisée**
4. **`getCharactersByUser($userId, $equippedOnly, PDO $pdo)`** - Liste des personnages avec filtre ✅ **Utilisée**
5. **`getByUserId($userId, PDO $pdo = null)`** - Liste simple par utilisateur ⚠️ **Doublon potentiel avec `findByUserId`**

#### ✅ Méthodes d'instance de modification
6. **`update(array $data)`** - Mise à jour ✅ **Utilisée**
7. **`delete()`** - Suppression simple ✅ **Utilisée**
8. **`deleteCompletely()`** - Suppression complète avec données associées ✅ **Utilisée**

---

### Méthodes de calcul et caractéristiques

#### ✅ Modificateurs de caractéristiques
9. **`getProficiencyBonus()`** - Bonus de compétence ✅ **Utilisée**
10. **`getAbilityModifier($ability)`** - Modificateur générique ✅ **Utilisée**
11. **`getStrengthModifier()`** - Modificateur de force ✅ **Utilisée**
12. **`getDexterityModifier()`** - Modificateur de dextérité ✅ **Utilisée**
13. **`getConstitutionModifier()`** - Modificateur de constitution ✅ **Utilisée**
14. **`getIntelligenceModifier()`** - Modificateur d'intelligence ✅ **Utilisée**
15. **`getWisdomModifier()`** - Modificateur de sagesse ✅ **Utilisée**
16. **`getCharismaModifier()`** - Modificateur de charisme ✅ **Utilisée**
17. **`getMyTotalAbilities()`** - Caractéristiques totales (base + race + améliorations + équipement + temporaires) ✅ **Utilisée**
18. **`getMyAbilityModifiers()`** - Modificateurs basés sur caractéristiques totales ✅ **Utilisée**

#### ✅ Points de vie et expérience
19. **`calculateMaxHitPoints()`** - Calcul des PV max ✅ **Probablement utilisée**
20. **`updateHitPoints($newHitPoints)`** - Mise à jour des PV ✅ **Utilisée**
21. **`updateExperiencePoints($newExperiencePoints)`** - Mise à jour de l'XP ✅ **Utilisée**
22. **`calculateLevelFromExperience($xp)`** - Calcul du niveau depuis XP ⚠️ **Privée, usage interne**

#### ✅ Classe d'armure
23. **`getCA()`** - Calcul de la classe d'armure ✅ **Utilisée**

---

### Méthodes de sorts et magie

#### ✅ Gestion des sorts
24. **`getCharacterSpells()`** - Liste des sorts du personnage ✅ **Utilisée**
25. **`getSpells()`** - Liste des sorts (alternative) ⚠️ **Potentiellement redondante avec `getCharacterSpells()`**
26. **`addSpell($spellId, $prepared, $known)`** - Ajouter un sort ✅ **Utilisée**
27. **`removeSpell($spellId)`** - Retirer un sort ✅ **Utilisée**

#### ✅ Gestion des emplacements de sorts
28. **`getSpellSlotsUsage()`** - Usage des emplacements de sorts ✅ **Utilisée**
29. **`useSpellSlot($level)`** - Utiliser un emplacement ✅ **Utilisée**
30. **`freeSpellSlot($level)`** - Libérer un emplacement ✅ **Utilisée**
31. **`resetSpellSlotsUsage()`** - Réinitialiser l'usage ✅ **Utilisée**
32. **`canCastSpells()`** - Vérifier si le personnage peut lancer des sorts ✅ **Utilisée**

---

### Méthodes d'équipement

#### ✅ Gestion de l'équipement
33. **`getCharacterEquipment()`** - Équipement complet du personnage ✅ **Utilisée**
34. **`getEquipment()`** - Équipement sous forme d'objets Item ✅ **Utilisée**
35. **`getEquippedItems()`** - Items équipés (ancienne table) ⚠️ **Utilise l'ancienne table `character_equipment`**
36. **`equipItem($itemName, $itemType, $slot)`** - Équiper un objet ✅ **Utilisée**
37. **`unequipItem($itemName)`** - Déséquiper un objet ✅ **Utilisée**
38. **`getMyEquippedArmor()`** - Armure équipée ✅ **Utilisée**
39. **`getMyEquippedShield()`** - Bouclier équipé ✅ **Utilisée**
40. **`getMyEquipmentBonuses()`** - Bonus d'équipement ✅ **Utilisée**
41. **`getStartingEquipmentCount()`** - Nombre d'objets d'équipement de départ ✅ **Utilisée**

---

### Méthodes de rage (Barbare)

#### ✅ Gestion de la rage
42. **`getRageUsage()`** - Usage de la rage ✅ **Utilisée**
43. **`useRage()`** - Utiliser la rage ✅ **Utilisée**
44. **`freeRage()`** - Libérer la rage ✅ **Utilisée**
45. **`resetRageUsage()`** - Réinitialiser la rage ✅ **Utilisée**
46. **`isBarbarian()`** - Vérifier si barbare ✅ **Utilisée**
47. **`getMyRageData()`** - Données complètes de rage ✅ **Utilisée**

---

### Méthodes de capacités

#### ✅ Gestion des capacités
48. **`getCapabilities()`** - Liste des capacités ✅ **Utilisée**
49. **`generateBaseCapabilities()`** - Générer les capacités de base ⚠️ **Privée, usage interne**
50. **`addCapability($capabilityId, $source, $sourceId)`** - Ajouter une capacité ✅ **Probablement utilisée**
51. **`removeCapability($capabilityId)`** - Retirer une capacité ✅ **Probablement utilisée**

---

### Méthodes d'améliorations de caractéristiques

#### ✅ Gestion des améliorations
52. **`getAbilityImprovements()`** - Améliorations actuelles ✅ **Utilisée**
53. **`saveAbilityImprovements($improvements)`** - Sauvegarder les améliorations ✅ **Utilisée**
54. **`calculateFinalAbilities($abilityImprovements)`** - Calculer les caractéristiques finales ✅ **Utilisée**

---

### Méthodes de compétences et langues

#### ✅ Génération de compétences
55. **`generateFixedSkills()`** - Compétences obligatoires ✅ **Utilisée lors de la création**
56. **`generateSkillChoices()`** - Compétences au choix ✅ **Utilisée lors de la création**
57. **`generateBaseLanguages()`** - ⚠️ **Méthode référencée mais non définie dans la classe**

---

### Méthodes de relations

#### ✅ Relations avec autres entités
58. **`belongsToUser($userId)`** - Vérifier l'appartenance ✅ **Utilisée**
59. **`getRace()`** - Obtenir l'objet Race ✅ **Utilisée**
60. **`getClass()`** - Obtenir l'objet Classe ✅ **Utilisée**
61. **`getArchetype()`** - Obtenir l'archetype ✅ **Utilisée**
62. **`getCampaignInfo()`** - Informations de campagne ✅ **Probablement utilisée**
63. **`isApprovedInCampaign()`** - Vérifier l'approbation en campagne ✅ **Utilisée**

---

### Méthodes de poisons

#### ✅ Gestion des poisons
64. **`getCharacterPoisons()`** - Liste des poisons ✅ **Utilisée**
65. **`getMyCharacterPoisons()`** - Alias de `getCharacterPoisons()` ⚠️ **Redondante**

---

### Méthodes de profil

#### ✅ Gestion du profil
66. **`updateProfilePhoto($photoPath)`** - Mettre à jour la photo ✅ **Utilisée**

---

### Méthodes de combat

#### ✅ Calculs de combat
67. **`calculateMyCharacterAttacks()`** - Calculer les attaques ✅ **Utilisée**
68. **`getMyTemporaryBonuses()`** - Bonus temporaires ✅ **Utilisée**

---

### Getters simples

#### ✅ Getters de base
69. **`getId()`** - ID du personnage ⚠️ **Redondant (propriété publique)**
70. **`getUserId()`** - ID utilisateur ⚠️ **Redondant (propriété publique)**
71. **`getClassId()`** - ID classe ⚠️ **Redondant (propriété publique)**
72. **`getIsEquipped()`** - Statut équipé ⚠️ **Redondant (propriété publique)**

---

## ⚠️ Problèmes identifiés

### 1. Redondances

#### Méthodes redondantes
- **`getMyCharacterPoisons()`** et **`getCharacterPoisons()`** : `getMyCharacterPoisons()` est un simple alias
- **`getByUserId()`** et **`findByUserId()`** : Fonctionnalité similaire, à vérifier si différence justifiée
- **`getSpells()`** et **`getCharacterSpells()`** : Deux méthodes qui semblent faire la même chose
- **Getters redondants** : `getId()`, `getUserId()`, `getClassId()`, `getIsEquipped()` alors que les propriétés sont publiques

#### Code dupliqué
- Plusieurs méthodes utilisent des patterns similaires pour récupérer des données depuis la base

### 2. Méthodes manquantes

- **`generateBaseLanguages()`** : Référencée dans `create()` (ligne 138) mais non définie dans la classe

### 3. Méthodes utilisant des tables obsolètes

- **`getEquippedItems()`** : Utilise l'ancienne table `character_equipment` au lieu de `items`

### 4. Incohérences de design

#### Mélange de styles de nommage
- Certaines méthodes commencent par `get` (ex: `getCharacterSpells()`)
- D'autres par `getMy` (ex: `getMyTotalAbilities()`, `getMyRageData()`)
- D'autres encore sans préfixe (ex: `updateHitPoints()`)

#### Utilisation de PDO
- Certaines méthodes utilisent `$this->pdo`
- D'autres utilisent `\Database::getInstance()->getPdo()`
- Incohérence dans la gestion de la connexion PDO

### 5. Méthodes privées manquantes/utilisées

- **`hydrate()`** : Privée, utilisée dans le constructeur ✅
- **`generateBaseCapabilities()`** : Privée, utilisée dans `getCapabilities()` ✅
- **`calculateLevelFromExperience()`** : Privée mais jamais appelée ⚠️

---

## 📋 Recommandations

### Priorité haute

1. **Supprimer les redondances** :
   - Supprimer `getMyCharacterPoisons()` et utiliser uniquement `getCharacterSpells()`
   - Vérifier et fusionner `getByUserId()` et `findByUserId()` si identiques
   - Vérifier et fusionner `getSpells()` et `getCharacterSpells()` si identiques
   - Supprimer les getters redondants (`getId()`, `getUserId()`, `getClassId()`, `getIsEquipped()`)

2. **Implémenter la méthode manquante** :
   - Créer `generateBaseLanguages()` ou corriger l'appel dans `create()`

3. **Mettre à jour `getEquippedItems()`** :
   - Migrer vers l'utilisation de la table `items` ou marquer comme obsolète

### Priorité moyenne

4. **Uniformiser le style de nommage** :
   - Choisir un préfixe cohérent (`get` vs `getMy` vs sans préfixe)
   - Appliquer la convention choisie partout

5. **Uniformiser l'utilisation de PDO** :
   - Utiliser systématiquement `$this->pdo` dans les méthodes d'instance
   - Éviter les appels directs à `\Database::getInstance()->getPdo()`

6. **Vérifier l'utilisation de `calculateLevelFromExperience()`** :
   - Si non utilisée, la supprimer
   - Si nécessaire, la rendre publique ou l'intégrer dans une méthode publique

### Priorité basse

7. **Documentation** :
   - Améliorer la documentation PHPDoc pour toutes les méthodes
   - Ajouter des exemples d'utilisation pour les méthodes complexes

8. **Tests** :
   - Créer des tests unitaires pour toutes les méthodes publiques
   - Vérifier les cas limites et les erreurs

---

## 🔄 Évolutions depuis la dernière version

### Méthodes déplacées vers d'autres classes

✅ **Déplacées vers `Classe.php`** :
- `getSpellCapabilities()` → `Classe::getSpellCapabilities()`
- `getSpellsForClass()` → `Classe::getSpells()`
- `addSpellToCharacter()` → `Classe::addSpellToCharacter()`
- `removeSpellFromCharacter()` → `Classe::removeSpellFromCharacter()`
- `updateSpellPrepared()` → `Classe::updateSpellPrepared()`
- `getSpellSlotsUsageStatic()` → `Classe::getSpellSlotsUsage()`
- `getMaxRages()` → `Classe::getMaxRages()`
- `getArchetypeById()` → `Classe::getArchetypeById()`
- `getArchetypeTypeStatic()` → `Classe::getArchetypeType()`

✅ **Déplacées vers `Item.php`** :
- `getPoisonInfo()` → `Item::getPoisonInfo()`

### Méthodes converties en méthodes d'instance

✅ **Converties de statique vers instance** :
- `useSpellSlotStatic()` → `useSpellSlot()`
- `freeSpellSlotStatic()` → `freeSpellSlot()`
- `resetSpellSlotsUsageStatic()` → `resetSpellSlotsUsage()`
- `useRageStatic()` → `useRage()`
- `freeRageStatic()` → `freeRage()`
- `resetRageUsageStatic()` → `resetRageUsage()`
- `unequipItemStatic()` → `unequipItem()`
- `updateHitPoints()` → Méthode d'instance
- `updateExperiencePoints()` → Méthode d'instance
- `getCharacterEquipment()` → Méthode d'instance
- `getCharacterSpells()` → Méthode d'instance
- `getCharacterPoisons()` → Méthode d'instance
- `getStartingEquipmentCount()` → Méthode d'instance
- `updateProfilePhoto()` → Méthode d'instance

### Méthodes supprimées

✅ **Supprimées** :
- `getMyEquipment()` → Remplacée par `getCharacterEquipment()`
- `equipItemStatic()` → Non utilisée, supprimée
- Getters redondants : `getName()`, `getLevel()`, `getRaceId()`, `getBackgroundId()`, `getExperiencePoints()`, `getHitPointsMax()`, `getHitPointsCurrent()`

---

## ✅ Points positifs

1. **Bonne séparation des responsabilités** : Les méthodes liées aux classes ont été déplacées vers `Classe.php`
2. **Amélioration de l'encapsulation** : Conversion de nombreuses méthodes statiques en méthodes d'instance
3. **Cohérence améliorée** : Utilisation de méthodes d'instance pour les opérations sur le personnage
4. **Documentation** : La plupart des méthodes ont des commentaires PHPDoc

---

## 📝 Notes finales

La classe `Character` a été significativement améliorée depuis la dernière analyse. Elle est maintenant plus orientée objet et mieux organisée. Cependant, il reste quelques points à améliorer pour atteindre une parfaite cohérence.

**Score global** : 8/10
- ✅ Architecture orientée objet : Bonne
- ⚠️ Cohérence du code : À améliorer
- ✅ Documentation : Correcte
- ⚠️ Redondances : Quelques redondances mineures
- ✅ Séparation des responsabilités : Excellente après refactoring
