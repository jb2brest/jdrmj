# Makefile pour les tests Selenium JDR 4 MJ

.PHONY: help install test test-smoke test-auth test-char test-camp test-best test-all clean setup

# Variables
PYTHON = $(shell pwd)/../testenv/bin/python
TEST_URL = http://localhost/jdrmj

help: ## Affiche l'aide
	@echo "Tests Selenium pour JDR 4 MJ"
	@echo "=============================="
	@echo ""
	@echo "Commandes disponibles:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

install: ## Installe les dépendances
	$(PYTHON) -m pip install -r requirements.txt

setup: ## Configure l'environnement de test
	$(PYTHON) setup_test_environment.py

test-smoke: ## Lance les tests de fumée
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --type smoke

test-auth: ## Lance les tests d'authentification
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --type authentication

test-char: ## Lance les tests de gestion des personnages
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --type character

test-camp: ## Lance les tests de gestion des campagnes
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --type campaign

test-best: ## Lance les tests du bestiaire
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --type bestiary

test-functional: ## Lance tous les tests fonctionnels
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --type functional

test-all: ## Lance tous les tests
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --type all

test-headless: ## Lance tous les tests en mode headless
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --headless

test-parallel: ## Lance les tests en parallèle
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --parallel

test-verbose: ## Lance les tests en mode verbeux
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --verbose

test: test-smoke ## Alias pour les tests de fumée

clean: ## Nettoie les fichiers temporaires
	rm -rf reports/*.html
	rm -rf reports/*.png
	rm -rf logs/*.log
	rm -rf __pycache__/
	rm -rf *.pyc

# Tests avec URL personnalisée
test-local: ## Lance les tests sur localhost
	TEST_BASE_URL=http://localhost/jdrmj $(PYTHON) run_tests.py

test-staging: ## Lance les tests sur l'environnement de staging
	TEST_BASE_URL=https://staging.jdrmj.com $(PYTHON) run_tests.py

test-prod: ## Lance les tests sur l'environnement de production
	TEST_BASE_URL=https://jdrmj.com $(PYTHON) run_tests.py

# Tests spécifiques
test-integration: ## Lance les tests d'intégration
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) -m pytest functional/test_integration.py -v

test-regression: ## Lance les tests de régression
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) -m pytest -m regression -v

# Développement
dev-install: ## Installation pour le développement
	$(PYTHON) -m pip install -r requirements.txt
	$(PYTHON) -m pip install pytest-xdist pytest-html allure-pytest

dev-test: ## Tests de développement (rapides)
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --type smoke --headless --verbose

# CI/CD
ci-test: ## Tests pour CI/CD
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --headless --parallel

ci-report: ## Génère un rapport pour CI/CD
	TEST_BASE_URL=$(TEST_URL) $(PYTHON) run_tests.py --headless --parallel
	@echo "Rapport généré dans reports/report.html"
