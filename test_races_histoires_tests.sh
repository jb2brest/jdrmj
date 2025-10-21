#!/bin/bash

# Script de test pour vérifier les tests de races et d'historiques

echo "🧬 Test des tests de races et d'historiques"
echo "=========================================="
echo

# Vérifier la structure des répertoires
echo "📁 Vérification de la structure des répertoires..."
if [ -d "tests/functional/races" ]; then
    echo "✅ Répertoire races créé"
else
    echo "❌ Répertoire races manquant"
fi

if [ -d "tests/functional/histoires" ]; then
    echo "✅ Répertoire histoires créé"
else
    echo "❌ Répertoire histoires manquant"
fi

# Vérifier les fichiers de test
echo
echo "📄 Vérification des fichiers de test..."
if [ -f "tests/functional/races/test_races.py" ]; then
    echo "✅ Fichier test_races.py créé"
    echo "   - Nombre de lignes: $(wc -l < tests/functional/races/test_races.py)"
else
    echo "❌ Fichier test_races.py manquant"
fi

if [ -f "tests/functional/histoires/test_histoires.py" ]; then
    echo "✅ Fichier test_histoires.py créé"
    echo "   - Nombre de lignes: $(wc -l < tests/functional/histoires/test_histoires.py)"
else
    echo "❌ Fichier test_histoires.py manquant"
fi

# Vérifier les mises à jour des fichiers de configuration
echo
echo "⚙️ Vérification des mises à jour de configuration..."

# Vérifier advanced_test_menu.py
if grep -q "races" tests/advanced_test_menu.py; then
    echo "✅ Catégorie races ajoutée à advanced_test_menu.py"
else
    echo "❌ Catégorie races manquante dans advanced_test_menu.py"
fi

if grep -q "histoires" tests/advanced_test_menu.py; then
    echo "✅ Catégorie histoires ajoutée à advanced_test_menu.py"
else
    echo "❌ Catégorie histoires manquante dans advanced_test_menu.py"
fi

# Vérifier json_test_reporter.py
if grep -q "races/" tests/json_test_reporter.py; then
    echo "✅ Catégorie races ajoutée à json_test_reporter.py"
else
    echo "❌ Catégorie races manquante dans json_test_reporter.py"
fi

if grep -q "histoires/" tests/json_test_reporter.py; then
    echo "✅ Catégorie histoires ajoutée à json_test_reporter.py"
else
    echo "❌ Catégorie histoires manquante dans json_test_reporter.py"
fi

# Vérifier csv_report_generator.py
if grep -q "races/" tests/csv_report_generator.py; then
    echo "✅ Catégorie races ajoutée à csv_report_generator.py"
else
    echo "❌ Catégorie races manquante dans csv_report_generator.py"
fi

if grep -q "histoires/" tests/csv_report_generator.py; then
    echo "✅ Catégorie histoires ajoutée à csv_report_generator.py"
else
    echo "❌ Catégorie histoires manquante dans csv_report_generator.py"
fi

# Tester l'exécution d'un test simple
echo
echo "🧪 Test d'exécution d'un test simple..."
cd tests
if python3 -c "import functional.races.test_races; print('✅ Import du module test_races réussi')" 2>/dev/null; then
    echo "✅ Module test_races importable"
else
    echo "❌ Erreur d'import du module test_races"
fi

if python3 -c "import functional.histoires.test_histoires; print('✅ Import du module test_histoires réussi')" 2>/dev/null; then
    echo "✅ Module test_histoires importable"
else
    echo "❌ Erreur d'import du module test_histoires"
fi

cd ..

echo
echo "🎯 Résumé des tests créés:"
echo "=========================="
echo "🧬 Tests de races:"
echo "   - test_human_race_selection"
echo "   - test_elf_race_selection"
echo "   - test_dwarf_race_selection"
echo "   - test_halfling_race_selection"
echo "   - test_tiefling_race_selection"
echo "   - test_race_characteristics_display"
echo "   - test_race_selection_workflow"
echo
echo "📚 Tests d'historiques:"
echo "   - test_acolyte_background_selection"
echo "   - test_criminal_background_selection"
echo "   - test_hermit_background_selection"
echo "   - test_noble_background_selection"
echo "   - test_sage_background_selection"
echo "   - test_soldier_background_selection"
echo "   - test_artist_background_selection"
echo "   - test_background_characteristics_display"
echo "   - test_background_selection_workflow"

echo
echo "✅ Vérification terminée !"






