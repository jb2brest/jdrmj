# Déploiement des fichiers de tests sur le serveur de staging

## Modifications apportées au script `push.sh`

### 🎯 Objectif
Permettre l'exécution des tests via `launch_tests.sh` sur le serveur de staging en incluant les fichiers nécessaires lors de la livraison.

### 📁 Fichiers inclus pour le staging

#### Fichiers de tests essentiels :
- ✅ `launch_tests.sh` (à la racine)
- ✅ `tests/advanced_test_menu.py`
- ✅ `tests/json_test_reporter.py`
- ✅ `tests/pytest_json_reporter.py`
- ✅ `tests/version_detector.py`
- ✅ `tests/csv_report_generator.py`
- ✅ `tests/pytest.ini`

#### Fichiers inclus pour le staging :
- ✅ `tests/functional/` (tests fonctionnels)
- ✅ `tests/conftest.py` (configuration pytest)
- ✅ `tests/test_*.py` (fichiers de tests)

#### Fichiers exclus pour le staging :
- ❌ `tests/fixtures/` (fixtures de test)
- ❌ `tests/run_*.py` (scripts de lancement)
- ❌ `tests/demo_*.py` (démonstrations)

### 🔧 Modifications techniques

#### 1. Copie des fichiers (rsync)
```bash
# Pour le staging, inclure les fichiers de tests nécessaires
if [ "$SERVER" = "staging" ]; then
    rsync -av \
        --include="tests/" \
        --include="tests/**" \
        --include="launch_tests.sh" \
        # ... autres inclusions
```

#### 2. Suppression sélective des fichiers
```bash
# Pour le serveur de staging, garder les fichiers nécessaires aux tests
if [ "$SERVER" = "staging" ]; then
    log_info "Mode staging: conservation des fichiers de tests nécessaires"
    # Garder les tests fonctionnels pour le staging
    # rm -rf "$temp_dir/tests/functional"  # Gardé pour staging
    rm -rf "$temp_dir/tests/fixtures"
    # rm -rf "$temp_dir/tests/conftest.py"  # Gardé pour staging
    # ... autres suppressions
    # Garder les fichiers de rapport et de configuration des tests
    # rm -rf "$temp_dir/tests/json_test_reporter.py"  # Gardé pour staging
```

#### 3. Configuration des permissions
```bash
# Configurer les permissions pour les fichiers de tests sur staging
log_info "Configuration des permissions pour les fichiers de tests..."
if [ -f "$DEPLOY_PATH/launch_tests.sh" ]; then
    sudo chmod +x "$DEPLOY_PATH/launch_tests.sh"
    log_success "Permissions d'exécution configurées pour launch_tests.sh"
fi

if [ -d "$DEPLOY_PATH/tests" ]; then
    sudo chown -R www-data:www-data "$DEPLOY_PATH/tests"
    sudo chmod -R 755 "$DEPLOY_PATH/tests"
    sudo chmod -R 644 "$DEPLOY_PATH/tests"/*.py 2>/dev/null || true
    sudo chmod -R 644 "$DEPLOY_PATH/tests"/*.ini 2>/dev/null || true
    log_success "Permissions des fichiers de tests configurées"
fi
```

### 🚀 Utilisation

#### Déploiement en mode staging :
```bash
./push.sh staging "Message de livraison"
```

#### Vérification sur le serveur de staging :
```bash
# Se connecter au serveur de staging
cd /var/www/html/jdrmj_staging

# Vérifier que launch_tests.sh est présent et exécutable
ls -la launch_tests.sh
./launch_tests.sh --help

# Vérifier que les fichiers de tests sont présents
ls -la tests/
```

### ✅ Avantages

1. **Tests disponibles sur staging** : `launch_tests.sh` peut être exécuté sur le serveur de staging
2. **Tests fonctionnels inclus** : Tous les tests de classes (Barbare, Barde, Clerc, Druide, Ensorceleur) sont disponibles
3. **Rapports JSON** : Les rapports de tests peuvent être générés et consultés
4. **Menu interactif** : Le menu avancé des tests est disponible
5. **Environnement configuré** : Support de l'argument `-e` pour spécifier l'environnement
6. **Configuration complète** : `conftest.py` et tous les fichiers de configuration sont inclus

### 🔍 Vérification

Le script a été testé avec un script de simulation qui vérifie :
- ✅ Tous les fichiers nécessaires sont inclus
- ✅ Les fichiers non nécessaires sont exclus
- ✅ Les permissions sont correctement configurées
- ✅ La syntaxe du script est valide

### 📝 Notes importantes

- Les modifications ne s'appliquent qu'au serveur de **staging**
- Les serveurs **test** et **production** conservent leur comportement actuel
- Les fichiers de tests fonctionnels sont maintenant inclus pour le staging
- Le script `launch_tests.sh` est maintenant disponible à la racine du projet sur staging
- Tous les tests de classes (Barbare, Barde, Clerc, Druide, Ensorceleur) sont disponibles sur staging
