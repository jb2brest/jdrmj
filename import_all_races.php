<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté et est un DM
requireLogin();
if ($_SESSION['role'] !== 'dm') {
    die('Accès refusé. Seuls les MJ peuvent importer des races.');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_races'])) {
    try {
        // Races à importer (basées sur le fichier CSV original)
        $racesData = [
            [
                'name' => 'Haut-elfe',
                'description' => 'Elfe noble avec des capacités magiques innées',
                'strength_bonus' => 0,
                'dexterity_bonus' => 2,
                'constitution_bonus' => 0,
                'intelligence_bonus' => 1,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 0,
                'size' => 'M',
                'speed' => 9,
                'vision' => '',
                'languages' => 'commun, elfique, une langue de votre choix',
                'traits' => 'Sens aiguisés, Ascendance féerique (AV aux JdS vs charme et la magie ne peut pas vous endormir), Transe (4h de méditation remplacent 8h de sommeil), Entraînement aux armes elfiques, Sort mineur'
            ],
            [
                'name' => 'Elfe des bois',
                'description' => 'Elfe adapté à la vie en forêt',
                'strength_bonus' => 0,
                'dexterity_bonus' => 2,
                'constitution_bonus' => 0,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 1,
                'charisma_bonus' => 0,
                'size' => 'M',
                'speed' => 10,
                'vision' => 'Vision dans le noir (18 m)',
                'languages' => 'commun, elfique',
                'traits' => 'Sens aiguisés, Ascendance féerique (AV aux JdS vs charme et la magie ne peut pas vous endormir), Transe (4h de méditation remplacent 8h de sommeil), Entraînement aux armes elfiques, Foulée légère, Cachette naturelle (peut tenter de se cacher dans une zone à visibilité réduite)'
            ],
            [
                'name' => 'Elfe noir',
                'description' => 'Elfe des profondeurs avec des pouvoirs magiques sombres',
                'strength_bonus' => 0,
                'dexterity_bonus' => 2,
                'constitution_bonus' => 0,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 1,
                'size' => 'M',
                'speed' => 9,
                'vision' => 'Vision dans le noir (18 m)',
                'languages' => 'commun, elfique',
                'traits' => 'Sens aiguisés, Ascendance féerique (AV aux JdS vs charme et la magie ne peut pas vous endormir), Transe (4h de méditation remplacent 8h de sommeil), Entraînement aux armes drows, Sensibilité au soleil, Magie drow'
            ],
            [
                'name' => 'Halfelin pied-léger',
                'description' => 'Halfelin agile et discret',
                'strength_bonus' => 0,
                'dexterity_bonus' => 2,
                'constitution_bonus' => 0,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 1,
                'size' => 'P',
                'speed' => 7,
                'vision' => '',
                'languages' => 'commun, halfelin',
                'traits' => 'Chanceux (relancer un 1), Brave (AV aux JdS vs effrayé), Agilité halfeline (peut passer dans l\'espace d\'une créature de taille supérieure), Discrétion naturelle (peut tenter de se cacher derrière une créature de taille supérieure)'
            ],
            [
                'name' => 'Halfelin robuste',
                'description' => 'Halfelin résistant et robuste',
                'strength_bonus' => 0,
                'dexterity_bonus' => 2,
                'constitution_bonus' => 1,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 1,
                'size' => 'P',
                'speed' => 7,
                'vision' => '',
                'languages' => 'commun, halfelin',
                'traits' => 'Chanceux (relancer un 1), Brave (AV aux JdS vs effrayé), Agilité halfeline (peut passer dans l\'espace d\'une créature de taille supérieure), Résistance des robustes (AV aux JdS contre le poison et résistance contre les dégâts de poison)'
            ],
            [
                'name' => 'Humain',
                'description' => 'Race adaptable et polyvalente',
                'strength_bonus' => 1,
                'dexterity_bonus' => 1,
                'constitution_bonus' => 1,
                'intelligence_bonus' => 1,
                'wisdom_bonus' => 1,
                'charisma_bonus' => 1,
                'size' => 'M',
                'speed' => 9,
                'vision' => '',
                'languages' => 'commun, une langue de votre choix',
                'traits' => 'Versatilité humaine'
            ],
            [
                'name' => 'Nain des collines',
                'description' => 'Nain des collines, sage et résistant',
                'strength_bonus' => 0,
                'dexterity_bonus' => 0,
                'constitution_bonus' => 2,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 1,
                'charisma_bonus' => 0,
                'size' => 'M',
                'speed' => 7,
                'vision' => 'Vision dans le noir (18 m)',
                'languages' => 'commun, nain',
                'traits' => 'Résistance naine (AV aux JdS vs poison), Entraînement aux armes naines, Maîtrise des outils, Connaissance de la pierre (bonus de maîtrise x2 aux jets d\'Int (Histoire) en relation avec la pierre), Ténacité naine (+1 pv/niveau)'
            ],
            [
                'name' => 'Nain des montagnes',
                'description' => 'Nain des montagnes, fort et guerrier',
                'strength_bonus' => 2,
                'dexterity_bonus' => 0,
                'constitution_bonus' => 2,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 1,
                'charisma_bonus' => 0,
                'size' => 'M',
                'speed' => 7,
                'vision' => 'Vision dans le noir (18 m)',
                'languages' => 'commun, nain',
                'traits' => 'Résistance naine (AV aux JdS vs poison), Entraînement aux armes naines, Maîtrise des outils, Connaissance de la pierre (bonus de maîtrise x2 aux jets d\'Int (Histoire) en relation avec la pierre), Formation au port des armures naines'
            ],
            [
                'name' => 'Demi-elfe',
                'description' => 'Hybride entre humain et elfe',
                'strength_bonus' => 0,
                'dexterity_bonus' => 0,
                'constitution_bonus' => 0,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 2,
                'size' => 'M',
                'speed' => 9,
                'vision' => 'Vision dans le noir (18 m)',
                'languages' => 'commun, elfique, une langue de votre choix',
                'traits' => 'Ascendance féerique (AV aux JdS contre les effets de charme et la magie ne peut pas vous endormir), Polyvalence'
            ],
            [
                'name' => 'Demi-orc',
                'description' => 'Hybride entre humain et orc',
                'strength_bonus' => 2,
                'dexterity_bonus' => 0,
                'constitution_bonus' => 1,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 0,
                'size' => 'M',
                'speed' => 9,
                'vision' => 'Vision dans le noir (18 m)',
                'languages' => 'commun, orc',
                'traits' => 'Menaçant, Endurance implacable, Attaques sauvages'
            ],
            [
                'name' => 'Drakéide',
                'description' => 'Descendant des dragons',
                'strength_bonus' => 2,
                'dexterity_bonus' => 0,
                'constitution_bonus' => 0,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 1,
                'size' => 'M',
                'speed' => 9,
                'vision' => '',
                'languages' => 'commun, draconique',
                'traits' => 'Ascendance draconique'
            ],
            [
                'name' => 'Gnome des forêts',
                'description' => 'Gnome des forêts, rusé et magique',
                'strength_bonus' => 0,
                'dexterity_bonus' => 1,
                'constitution_bonus' => 0,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 2,
                'size' => 'P',
                'speed' => 7,
                'vision' => 'Vision dans le noir (18 m)',
                'languages' => 'commun, gnome',
                'traits' => 'Ruse gnome, Communication avec les petits animaux, Illusionniste-né'
            ],
            [
                'name' => 'Gnome des roches',
                'description' => 'Gnome des roches, ingénieux et inventif',
                'strength_bonus' => 0,
                'dexterity_bonus' => 0,
                'constitution_bonus' => 1,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 2,
                'size' => 'P',
                'speed' => 7,
                'vision' => 'Vision dans le noir (18 m)',
                'languages' => 'commun, gnome',
                'traits' => 'Ruse gnome, Connaissance en ingénierie, Bricoleur'
            ],
            [
                'name' => 'Tieffelin',
                'description' => 'Descendant des créatures infernales',
                'strength_bonus' => 0,
                'dexterity_bonus' => 0,
                'constitution_bonus' => 0,
                'intelligence_bonus' => 1,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 2,
                'size' => 'M',
                'speed' => 9,
                'vision' => 'Vision dans le noir (18 m)',
                'languages' => 'commun, infernal',
                'traits' => 'Résistance infernale (feu), Ascendance infernale'
            ],
            [
                'name' => 'Aarakocra',
                'description' => 'Créature ailée des montagnes',
                'strength_bonus' => 0,
                'dexterity_bonus' => 0,
                'constitution_bonus' => 0,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 0,
                'size' => 'M',
                'speed' => 9,
                'vision' => '',
                'languages' => 'commun, aarakocra',
                'traits' => 'Vol, Griffes, Langage des oiseaux'
            ]
        ];
        
        // Vider la table races existante
        $pdo->exec("DELETE FROM races");
        
        // Préparer la requête d'insertion
        $stmt = $pdo->prepare("
            INSERT INTO races (
                name, description, 
                strength_bonus, dexterity_bonus, constitution_bonus, 
                intelligence_bonus, wisdom_bonus, charisma_bonus,
                size, speed, vision, languages, traits
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $importedCount = 0;
        foreach ($racesData as $race) {
            $stmt->execute([
                $race['name'],
                $race['description'],
                $race['strength_bonus'],
                $race['dexterity_bonus'],
                $race['constitution_bonus'],
                $race['intelligence_bonus'],
                $race['wisdom_bonus'],
                $race['charisma_bonus'],
                $race['size'],
                $race['speed'],
                $race['vision'],
                $race['languages'],
                $race['traits']
            ]);
            
            $importedCount++;
        }
        
        $message = "Importation réussie ! $importedCount races ont été importées.";
        
    } catch (Exception $e) {
        $error = "Erreur lors de l'importation : " . $e->getMessage();
        error_log("Erreur import races : " . $e->getMessage());
    }
}

// Récupérer les races actuelles pour affichage
$currentRaces = $pdo->query("SELECT * FROM races ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importation des Races - JDR MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h2><i class="fas fa-upload me-2"></i>Importation des Races D&D</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Importer les races D&D 5e</h5>
                    </div>
                    <div class="card-body">
                        <p>Ce script va importer les races D&D 5e depuis le fichier <code>aidednddata/Aidedd - Race.csv</code> et remplacer complètement le système de races existant.</p>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Attention :</strong> Cette opération va supprimer toutes les races existantes et les remplacer par les races D&D 5e standard.
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Races incluses :</strong> Haut-elfe, Elfe des bois, Elfe noir, Halfelin pied-léger, Halfelin robuste, Humain, Nain des collines, Nain des montagnes, Demi-elfe, Demi-orc, Drakéide, Gnome des forêts, Gnome des roches, Tieffelin, Aarakocra
                        </div>
                        
                        <form method="POST">
                            <button type="submit" name="import_races" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Importer les Races D&D 5e
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Races Actuelles (<?php echo count($currentRaces); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($currentRaces)): ?>
                            <p class="text-muted">Aucune race importée.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($currentRaces as $race): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($race['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php if ($race['size']): ?>
                                                    Taille: <?php echo htmlspecialchars($race['size']); ?> | 
                                                <?php endif; ?>
                                                Vitesse: <?php echo htmlspecialchars($race['speed']); ?>
                                                <?php if ($race['vision']): ?>
                                                    | <?php echo htmlspecialchars($race['vision']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($race['strength_bonus'] != 0): ?>
                                                <span class="badge bg-primary me-1">F+<?php echo $race['strength_bonus']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($race['dexterity_bonus'] != 0): ?>
                                                <span class="badge bg-success me-1">D+<?php echo $race['dexterity_bonus']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($race['constitution_bonus'] != 0): ?>
                                                <span class="badge bg-warning me-1">C+<?php echo $race['constitution_bonus']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($race['intelligence_bonus'] != 0): ?>
                                                <span class="badge bg-info me-1">I+<?php echo $race['intelligence_bonus']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($race['wisdom_bonus'] != 0): ?>
                                                <span class="badge bg-secondary me-1">S+<?php echo $race['wisdom_bonus']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($race['charisma_bonus'] != 0): ?>
                                                <span class="badge bg-dark me-1">Ch+<?php echo $race['charisma_bonus']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="view_campaign.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour aux Campagnes
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
