<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Création de Personnage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-user-plus me-2"></i>Test - Création de Personnage</h1>
        <p class="text-muted">Cette page de test montre les modifications apportées à la création de personnage.</p>
        
        <form>
            <!-- Informations de base -->
            <div class="form-section">
                <h3><i class="fas fa-user me-2"></i>Informations de Base</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du Personnage</label>
                            <input type="text" class="form-control" id="name" name="name" value="Test Personnage">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="race_id" class="form-label">Race</label>
                            <select class="form-select" id="race_id" name="race_id">
                                <option value="">Choisir une race</option>
                                <option value="1">Haut-elfe</option>
                                <option value="2">Elfe des bois</option>
                                <option value="3">Humain</option>
                                <option value="4">Nain des collines</option>
                                <option value="5">Halfelin pied-léger</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="class_id" class="form-label">Classe</label>
                            <select class="form-select" id="class_id" name="class_id">
                                <option value="">Choisir une classe</option>
                                <option value="1">Guerrier</option>
                                <option value="2">Magicien</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Caractéristiques -->
            <div class="form-section">
                <h3><i class="fas fa-dumbbell me-2"></i>Caractéristiques</h3>
                
                <!-- Informations de la race sélectionnée -->
                <div id="race-info" class="alert alert-info" style="display: none;">
                    <h5><i class="fas fa-info-circle me-2"></i>Informations de la race</h5>
                    <div id="race-details"></div>
                </div>
                
                <div class="row">
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="strength" class="form-label">Force</label>
                            <input type="number" class="form-control stat-input" id="strength" name="strength" value="10" min="1" max="20" required>
                            <small class="form-text text-muted" id="strength-bonus"></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="dexterity" class="form-label">Dextérité</label>
                            <input type="number" class="form-control stat-input" id="dexterity" name="dexterity" value="10" min="1" max="20" required>
                            <small class="form-text text-muted" id="dexterity-bonus"></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="constitution" class="form-label">Constitution</label>
                            <input type="number" class="form-control stat-input" id="constitution" name="constitution" value="10" min="1" max="20" required>
                            <small class="form-text text-muted" id="constitution-bonus"></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="intelligence" class="form-label">Intelligence</label>
                            <input type="number" class="form-control stat-input" id="intelligence" name="intelligence" value="10" min="1" max="20" required>
                            <small class="form-text text-muted" id="intelligence-bonus"></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="wisdom" class="form-label">Sagesse</label>
                            <input type="number" class="form-control stat-input" id="wisdom" name="wisdom" value="10" min="1" max="20" required>
                            <small class="form-text text-muted" id="wisdom-bonus"></small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="charisma" class="form-label">Charisme</label>
                            <input type="number" class="form-control stat-input" id="charisma" name="charisma" value="10" min="1" max="20" required>
                            <small class="form-text text-muted" id="charisma-bonus"></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Combat -->
            <div class="form-section">
                <h3><i class="fas fa-sword me-2"></i>Combat</h3>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="armor_class" class="form-label">Classe d'Armure</label>
                            <input type="number" class="form-control" id="armor_class" name="armor_class" value="10" min="1" max="30" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="speed" class="form-label">Vitesse (pieds)</label>
                            <input type="number" class="form-control" id="speed" name="speed" value="30" min="5" max="120" required>
                            <small class="form-text text-muted" id="speed-info">Vitesse de base</small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Données de test pour les races
        const raceData = {
            '1': { // Haut-elfe
                name: 'Haut-elfe',
                description: 'Elfe noble avec des capacités magiques innées',
                strength_bonus: 0,
                dexterity_bonus: 2,
                constitution_bonus: 0,
                intelligence_bonus: 1,
                wisdom_bonus: 0,
                charisma_bonus: 0,
                size: 'M',
                speed: 9,
                vision: '',
                languages: 'commun, elfique, une langue de votre choix',
                traits: 'Sens aiguisés, Ascendance féerique (AV aux JdS vs charme et la magie ne peut pas vous endormir), Transe (4h de méditation remplacent 8h de sommeil), Entraînement aux armes elfiques, Sort mineur'
            },
            '2': { // Elfe des bois
                name: 'Elfe des bois',
                description: 'Elfe adapté à la vie en forêt',
                strength_bonus: 0,
                dexterity_bonus: 2,
                constitution_bonus: 0,
                intelligence_bonus: 0,
                wisdom_bonus: 1,
                charisma_bonus: 0,
                size: 'M',
                speed: 10,
                vision: 'Vision dans le noir (18 m)',
                languages: 'commun, elfique',
                traits: 'Sens aiguisés, Ascendance féerique (AV aux JdS vs charme et la magie ne peut pas vous endormir), Transe (4h de méditation remplacent 8h de sommeil), Entraînement aux armes elfiques, Foulée légère, Cachette naturelle (peut tenter de se cacher dans une zone à visibilité réduite)'
            },
            '3': { // Humain
                name: 'Humain',
                description: 'Race adaptable et polyvalente',
                strength_bonus: 1,
                dexterity_bonus: 1,
                constitution_bonus: 1,
                intelligence_bonus: 1,
                wisdom_bonus: 1,
                charisma_bonus: 1,
                size: 'M',
                speed: 9,
                vision: '',
                languages: 'commun, une langue de votre choix',
                traits: 'Versatilité humaine'
            },
            '4': { // Nain des collines
                name: 'Nain des collines',
                description: 'Nain des collines, sage et résistant',
                strength_bonus: 0,
                dexterity_bonus: 0,
                constitution_bonus: 2,
                intelligence_bonus: 0,
                wisdom_bonus: 1,
                charisma_bonus: 0,
                size: 'M',
                speed: 7,
                vision: 'Vision dans le noir (18 m)',
                languages: 'commun, nain',
                traits: 'Résistance naine (AV aux JdS vs poison), Entraînement aux armes naines, Maîtrise des outils, Connaissance de la pierre (bonus de maîtrise x2 aux jets d\'Int (Histoire) en relation avec la pierre), Ténacité naine (+1 pv/niveau)'
            },
            '5': { // Halfelin pied-léger
                name: 'Halfelin pied-léger',
                description: 'Halfelin agile et discret',
                strength_bonus: 0,
                dexterity_bonus: 2,
                constitution_bonus: 0,
                intelligence_bonus: 0,
                wisdom_bonus: 0,
                charisma_bonus: 1,
                size: 'P',
                speed: 7,
                vision: '',
                languages: 'commun, halfelin',
                traits: 'Chanceux (relancer un 1), Brave (AV aux JdS vs effrayé), Agilité halfeline (peut passer dans l\'espace d\'une créature de taille supérieure), Discrétion naturelle (peut tenter de se cacher derrière une créature de taille supérieure)'
            }
        };

        // Fonction pour afficher les informations de race
        function displayRaceInfo(race) {
            const raceInfo = document.getElementById('race-info');
            const raceDetails = document.getElementById('race-details');
            
            let details = `<div class="row">`;
            
            // Bonus de caractéristiques
            const bonuses = [];
            if (race.strength_bonus > 0) bonuses.push(`<span class="badge bg-primary me-1">Force +${race.strength_bonus}</span>`);
            if (race.dexterity_bonus > 0) bonuses.push(`<span class="badge bg-success me-1">Dextérité +${race.dexterity_bonus}</span>`);
            if (race.constitution_bonus > 0) bonuses.push(`<span class="badge bg-warning me-1">Constitution +${race.constitution_bonus}</span>`);
            if (race.intelligence_bonus > 0) bonuses.push(`<span class="badge bg-info me-1">Intelligence +${race.intelligence_bonus}</span>`);
            if (race.wisdom_bonus > 0) bonuses.push(`<span class="badge bg-secondary me-1">Sagesse +${race.wisdom_bonus}</span>`);
            if (race.charisma_bonus > 0) bonuses.push(`<span class="badge bg-dark me-1">Charisme +${race.charisma_bonus}</span>`);
            
            if (bonuses.length > 0) {
                details += `<div class="col-md-6"><strong>Bonus de caractéristiques :</strong><br>${bonuses.join(' ')}</div>`;
            }
            
            // Informations physiques
            details += `<div class="col-md-6"><strong>Informations physiques :</strong><br>`;
            details += `Taille : ${race.size === 'P' ? 'Petite' : race.size === 'M' ? 'Moyenne' : 'Grande'}<br>`;
            details += `Vitesse : ${race.speed} pieds`;
            if (race.vision) {
                details += `<br>Vision : ${race.vision}`;
            }
            details += `</div>`;
            
            details += `</div>`;
            
            // Langues
            if (race.languages) {
                details += `<div class="mt-2"><strong>Langues :</strong> ${race.languages}</div>`;
            }
            
            // Traits
            if (race.traits) {
                details += `<div class="mt-2"><strong>Traits raciaux :</strong><br><small>${race.traits}</small></div>`;
            }
            
            raceDetails.innerHTML = details;
            raceInfo.style.display = 'block';
            
            // Afficher les bonus sous chaque caractéristique
            displayRaceBonuses(race);
            
            // Mettre à jour la vitesse
            updateSpeed(race.speed);
        }
        
        // Fonction pour afficher les bonus sous chaque caractéristique
        function displayRaceBonuses(race) {
            const bonuses = {
                'strength': race.strength_bonus,
                'dexterity': race.dexterity_bonus,
                'constitution': race.constitution_bonus,
                'intelligence': race.intelligence_bonus,
                'wisdom': race.wisdom_bonus,
                'charisma': race.charisma_bonus
            };
            
            Object.keys(bonuses).forEach(stat => {
                const bonusElement = document.getElementById(`${stat}-bonus`);
                if (bonuses[stat] > 0) {
                    bonusElement.textContent = `+${bonuses[stat]} racial`;
                    bonusElement.style.color = '#28a745';
                } else {
                    bonusElement.textContent = '';
                }
            });
        }
        
        // Fonction pour effacer les bonus
        function clearRaceBonuses() {
            const stats = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
            stats.forEach(stat => {
                const bonusElement = document.getElementById(`${stat}-bonus`);
                bonusElement.textContent = '';
            });
        }
        
        // Fonction pour mettre à jour la vitesse
        function updateSpeed(raceSpeed) {
            const speedInput = document.getElementById('speed');
            const speedInfo = document.getElementById('speed-info');
            
            if (raceSpeed && raceSpeed > 0) {
                speedInput.value = raceSpeed;
                speedInfo.textContent = `Vitesse raciale : ${raceSpeed} pieds`;
                speedInfo.style.color = '#28a745';
            } else {
                speedInput.value = 30;
                speedInfo.textContent = 'Vitesse de base';
                speedInfo.style.color = '#6c757d';
            }
        }
        
        // Événement de changement de race
        document.getElementById('race_id').addEventListener('change', function() {
            const raceId = this.value;
            if (raceId && raceData[raceId]) {
                displayRaceInfo(raceData[raceId]);
            } else {
                document.getElementById('race-info').style.display = 'none';
                clearRaceBonuses();
                updateSpeed(30);
            }
        });
    </script>
</body>
</html>
