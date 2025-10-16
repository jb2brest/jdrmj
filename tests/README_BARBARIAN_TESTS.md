# 🪓 Tests de la Classe Barbare

Ce document décrit les tests fonctionnels spécifiques à la classe Barbare dans l'application JDR MJ.

## 📁 Fichier de Test

- **`test_barbarian_class.py`** - Tests fonctionnels complets pour la classe Barbare

## 🧪 Tests Disponibles

### 1. **Création de Personnage Barbare**
- **`test_barbarian_character_creation`** - Test de création d'un personnage barbare
- Vérifie la sélection de la classe Barbare dans l'étape 1
- Teste la navigation vers l'étape suivante

### 2. **Sélection de Race**
- **`test_barbarian_race_selection`** - Test de sélection de race pour un barbare
- Vérifie la sélection d'une race appropriée (ex: Humain)
- Teste la compatibilité race/classe

### 3. **Sélection d'Archetype**
- **`test_barbarian_archetype_selection`** - Test de sélection d'archetype (voie primitive)
- Vérifie la sélection des voies primitives disponibles :
  - Voie de la magie sauvage
  - Voie du berserker
  - Voie du totem

### 4. **Équipement de Départ**
- **`test_barbarian_starting_equipment`** - Test de sélection de l'équipement de départ
- Vérifie la présence des choix d'équipement spécifiques au barbare :
  - **Groupe 1** : Hache à deux mains OU arme de guerre de corps à corps
  - **Groupe 2** : Deux hachettes OU arme courante
  - **Groupe 3** : Équipement obligatoire (javelines, sac, etc.)

### 5. **Visualisation de Personnage**
- **`test_barbarian_character_view`** - Test de visualisation d'un personnage barbare
- Vérifie l'affichage des informations spécifiques au barbare
- Contrôle l'affichage de la voie primitive (archetype)

### 6. **Mécanisme de Rage**
- **`test_barbarian_rage_mechanism`** - Test du mécanisme de rage
- Vérifie l'accessibilité de la page de gestion de la rage
- Teste l'activation/désactivation de la rage
- Contrôle les éléments d'interface de la rage

### 7. **Gestion d'Équipement**
- **`test_barbarian_equipment_management`** - Test de gestion de l'équipement
- Vérifie la section équipement des personnages barbares
- Contrôle la présence d'armes typiques du barbare

## 🎯 Fonctionnalités Testées

### **Caractéristiques de Classe**
- ✅ Sélection de la classe Barbare
- ✅ Affichage des caractéristiques de base
- ✅ Gestion des points de vie (12 + modificateur de Constitution)
- ✅ Classe d'armure de base (10 + modificateur de Dextérité)

### **Archetypes (Voies Primitives)**
- ✅ **Voie de la magie sauvage** - Capacités magiques sauvages
- ✅ **Voie du berserker** - Rage destructrice
- ✅ **Voie du totem** - Connexion spirituelle avec les animaux

### **Équipement Spécifique**
- ✅ **Armes** : Hache à deux mains, hachettes, javelines
- ✅ **Équipement** : Sac d'explorateur, équipement de survie
- ✅ **Choix multiples** : Options d'armement selon les préférences

### **Mécaniques de Jeu**
- ✅ **Rage** : Activation/désactivation, bonus de dégâts
- ✅ **Défense sans armure** : Bonus de CA basé sur Constitution
- ✅ **Résistance aux dégâts** : Réduction des dégâts en rage

## 🚀 Exécution des Tests

### **Via le Menu Interactif**
```bash
# Depuis la racine du projet
./launch_tests.sh

# Sélectionner : 1. Lancer par catégorie de tests
# Puis : 2. 👤 Gestion des Personnages
# Le fichier test_barbarian_class.py sera inclus automatiquement
```

### **Via Pytest Direct**
```bash
# Tous les tests barbare
python3 -m pytest tests/functional/test_barbarian_class.py -v

# Test spécifique
python3 -m pytest tests/functional/test_barbarian_class.py::TestBarbarianClass::test_barbarian_character_creation -v

# Avec rapports JSON
python3 -m pytest tests/functional/test_barbarian_class.py -v -p pytest_json_reporter
```

### **Tests Individuels**
```bash
# Test de création
python3 -m pytest tests/functional/test_barbarian_class.py::TestBarbarianClass::test_barbarian_character_creation -v

# Test d'équipement
python3 -m pytest tests/functional/test_barbarian_class.py::TestBarbarianClass::test_barbarian_starting_equipment -v

# Test de rage
python3 -m pytest tests/functional/test_barbarian_class.py::TestBarbarianClass::test_barbarian_rage_mechanism -v
```

## 📊 Fixtures Disponibles

### **`test_barbarian`**
Personnage barbare de test avec toutes les caractéristiques :
```python
{
    'name': 'Test Barbarian',
    'race': 'Humain',
    'class': 'Barbare',
    'level': 1,
    'background': 'Soldat',
    'archetype': 'Voie de la magie sauvage',
    'hit_points': 12,
    'armor_class': 10,
    'speed': 30,
    'strength': 15,
    'dexterity': 14,
    'constitution': 13,
    'intelligence': 8,
    'wisdom': 12,
    'charisma': 10
}
```

## 🔧 Configuration Requise

### **Prérequis**
- Application JDR MJ en cours d'exécution
- Base de données avec les données du barbare
- Selenium et ChromeDriver installés
- Utilisateur de test avec permissions appropriées

### **Variables d'Environnement**
```bash
export TEST_BASE_URL="http://localhost/jdrmj"
export HEADLESS="true"  # Optionnel pour les tests en arrière-plan
```

## 🐛 Dépannage

### **Problèmes Courants**

1. **Classe Barbare non trouvée**
   - Vérifier que la classe Barbare existe dans la base de données
   - Contrôler les sélecteurs CSS/XPath dans le test

2. **Équipement manquant**
   - Vérifier la table `starting_equipment` pour le barbare
   - Contrôler les noms d'équipement dans la base de données

3. **Archetype non affiché**
   - Vérifier la table des archetypes barbares
   - Contrôler la logique d'affichage dans `view_character.php`

4. **Page de rage inaccessible**
   - Vérifier que `manage_rage.php` existe
   - Contrôler les permissions utilisateur

### **Logs de Debug**
Les tests incluent des logs détaillés :
- ✅ Actions réussies
- ⚠️ Éléments non trouvés mais non critiques
- ❌ Erreurs bloquantes

## 📈 Résultats Attendus

### **Tests de Réussite**
- ✅ Création de personnage barbare fonctionnelle
- ✅ Sélection d'archetype opérationnelle
- ✅ Équipement de départ disponible
- ✅ Mécanisme de rage accessible
- ✅ Affichage correct des informations

### **Tests d'Échec Gérés**
- ⏭️ Pages non accessibles (skip automatique)
- ⏭️ Éléments manquants (skip avec message)
- ⏭️ Permissions insuffisantes (skip avec explication)

## 🎉 Intégration

Les tests barbare s'intègrent parfaitement dans la suite de tests existante :
- **Nettoyage automatique** des utilisateurs de test
- **Rapports JSON** pour chaque test
- **Menu interactif** avec sélection par catégorie
- **Compatibilité** avec tous les autres tests

## 📚 Documentation Associée

- **`SOLUTION_BARBARIAN_PATH_FINAL.md`** - Solution d'affichage des voies primitives
- **`README_BARBARIAN_EQUIPMENT.md`** - Documentation de l'équipement de départ
- **`tests/README_CLEANUP.md`** - Nettoyage des utilisateurs de test
