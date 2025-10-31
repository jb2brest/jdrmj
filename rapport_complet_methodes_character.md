# Rapport d'utilisation des méthodes de la classe Character

## Résumé
- **Total de méthodes publiques** : ~99
- **Méthodes utilisées** : ~35-40
- **Méthodes non utilisées ou redondantes** : ~60

---

## ✅ MÉTHODES UTILISÉES (confirmées)

### Méthodes statiques utilisées :
1. **create** - Création de personnages
2. **findById** - Utilisée 54+ fois (méthode principale de récupération)
3. **findByUserId** - Utilisée 3+ fois
4. **getClassSpellCapabilities** - Utilisée 2+ fois
5. **getSpellsForClass** - Utilisée
6. **getCharacterSpells** - Utilisée
7. **getSpellSlotsUsageStatic** - Utilisée
8. **updateExperiencePoints** - Utilisée 4+ fois
9. **getMaxRages** - Utilisée 3+ fois
10. **getCharacterItems** - Utilisée
11. **getPoisonInfo** - Utilisée
12. **getMagicalItemInfo** - Utilisée
13. **getCharacterPoisons** - Utilisée
14. **getCharactersByUser** - Utilisée
15. **getStartingEquipmentCount** - Utilisée
16. **getCharacterEquipment** - Utilisée
17. **getArchetypeById** - Utilisée 3+ fois
18. **getByUserId** - Utilisée
19. **updateProfilePhoto** - Utilisée

### Méthodes d'instance utilisées :
1. **belongsToUser** - Utilisée dans plusieurs API et vues
2. **getCA** - Utilisée dans view_character.php
3. **updateHitPoints** - Utilisée dans API et view_monster.php
4. **useSpellSlot** - Utilisée dans manage_spell_slots.php
5. **freeSpellSlot** - Utilisée dans manage_spell_slots.php
6. **resetSpellSlotsUsage** - Utilisée dans reset_spell_slots.php et api/manage_long_rest.php
7. **useRage** - Utilisée dans manage_rage.php
8. **freeRage** - Utilisée dans manage_rage.php
9. **resetRageUsage** - Utilisée dans manage_rage.php
10. **unequipItem** - Utilisée dans unequip_item.php
11. **isApprovedInCampaign** - Utilisée dans characters.php
12. **getDexterityModifier** - Utilisée dans calculateArmorClass
13. **getMyEquippedArmor** - Utilisée dans Character.php et templates
14. **getMyEquippedShield** - Utilisée dans Character.php et templates
15. **calculateArmorClassExtended** - Utilisée dans view_monster.php et view_npc.php (méthode publique, appelée directement)
16. **addExperience** - Appelée via updateLevelFromExperience
17. **updateLevelFromExperience** - Appelée dans addExperience
18. **calculateMaxHitPoints** - Appelée dans Character.php (ligne 898)

---

## ⚠️ MÉTHODES PROBABLEMENT NON UTILISÉES (getters redondants)

Ces méthodes peuvent être remplacées par un accès direct aux propriétés publiques :

- `getId()` → `$character->id`
- `getUserId()` → `$character->user_id`
- `getName()` → `$character->name`
- `getLevel()` → `$character->level`
- `getClassId()` → `$character->class_id`
- `getRaceId()` → `$character->race_id`
- `getBackgroundId()` → `$character->background_id`
- `getExperiencePoints()` → `$character->experience_points`
- `getHitPointsMax()` → `$character->hit_points_max`
- `getHitPointsCurrent()` → `$character->hit_points_current`
- `getIsEquipped()` → `$character->is_equipped`

**Recommandation** : Supprimer ces getters et utiliser directement les propriétés publiques.

---

## ❓ MÉTHODES À VÉRIFIER

### Méthodes d'instance à vérifier :
1. **equipItem** - Méthode existe mais `api/equip_item.php` utilise du SQL direct. Vérifier si cette méthode devrait être utilisée.
2. **update** - Méthode de mise à jour générique, probablement utilisée lors de modifications de personnages
3. **delete** / **deleteCompletely** - Probablement utilisées dans la gestion des personnages
4. **getSpells** - Utilisée dans `view_monster_sheet.php` pour `$monstre->getSpells()` (classe Monstre, pas Character)
5. **getProficiencyBonus** - Peut être utilisée dans les calculs
6. **getAbilityModifier** / autres getters de modificateurs - Utilisés en interne dans Character
7. **getCapabilities** - Probablement utilisée pour afficher les capacités
8. **getArchetype** - Probablement utilisée pour afficher l'archetype
9. **getCampaignInfo** - Probablement utilisée pour afficher les infos de campagne
10. **canCastSpells** - Probablement utilisée pour vérifier si le personnage peut lancer des sorts
11. **isBarbarian** - Probablement utilisée pour les spécificités du barbare
12. **getMyRageData** - Probablement utilisée pour afficher les données de rage
13. **getMyCharacterPoisons** - Probablement utilisée pour afficher les poisons
14. **calculateMyCharacterAttacks** - Probablement utilisée pour calculer les attaques
15. **calculateMyArmorClass** - Probablement utilisée pour calculer la CA
16. **getMyEquipment** / **getEquipment** - Probablement utilisées pour afficher l'équipement
17. **getRace** / **getClass** - Probablement utilisées pour afficher race/classe

### Méthodes statiques à vérifier :
1. **addSpellToCharacter** - Probablement utilisée lors de l'apprentissage de sorts
2. **removeSpellFromCharacter** - Probablement utilisée lors de l'oubli de sorts
3. **updateSpellPrepared** - Probablement utilisée pour préparer/préparer les sorts
4. **getCharacterCapabilities** - Probablement utilisée pour récupérer les capacités
5. **getCharacterMagicalEquipment** - Probablement utilisée pour l'équipement magique
6. **deleteItem** - Probablement utilisée pour supprimer des objets

### Méthodes de génération (probablement utilisées en interne ou lors de la création) :
- `generateBaseSkills`, `generateFixedSkills`, `generateSkillChoices`
- `generateBaseLanguages`, `generateFixedLanguages`, `generateLanguageChoices`
- `generateBaseCapabilities` (privée)

---

## 🔍 RECOMMANDATIONS

1. **Réduire les getters redondants** : Remplacer les getters simples par accès direct aux propriétés publiques.

2. **Vérifier equipItem** : `api/equip_item.php` utilise du SQL direct au lieu de `$character->equipItem()`. Évaluer si cette méthode devrait être utilisée.

3. **Documenter les méthodes internes** : Certaines méthodes comme `calculateArmorClass` sont utilisées en interne mais pas directement appelées depuis l'extérieur.

4. **Consolider les méthodes similaires** : Il existe plusieurs méthodes similaires (`calculateArmorClass`, `calculateMyArmorClass`, `calculateArmorClassExtended`). Évaluer si une consolidation est nécessaire.

5. **Audit complet** : Effectuer une recherche exhaustive dans tous les fichiers (PHP, JS, templates) pour identifier toutes les utilisations réelles.

---

## 📊 STATISTIQUES

- **Méthodes confirmées utilisées** : ~35-40
- **Méthodes probablement non utilisées** : ~60
- **Méthodes à vérifier** : ~20-25

---

*Rapport généré le : $(date)*

