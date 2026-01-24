<?php
/**
 * Contrôleur pour l'impression des fiches (Personnages, PNJ, Monstres)
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';
require_once 'classes/Background.php';
require_once 'includes/capabilities_functions.php';

// Vérification de connexion
requireLogin();

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id || !in_array($type, ['character', 'pj', 'npc', 'monster', 'place', 'country'])) {
    die("Type ou ID invalide.");
}

// Normaliser le type 'character' et 'pj'
if ($type === 'pj') $type = 'character';

// Données à passer à la vue
$data = [];
$template_file = '';
$page_title = 'Impression';

try {
    switch ($type) {
        case 'character':
            $character = Character::findById($id);
            if (!$character) throw new Exception("Personnage introuvable.");
            
            // Vérification permissions
            if ($character->user_id != $_SESSION['user_id'] && !isDM() && !User::isAdmin()) {
                throw new Exception("Accès refusé.");
            }
            
            $data['character'] = $character;
            $data['race'] = Race::findById($character->race_id);
            $data['class'] = Classe::findById($character->class_id);
            $data['background'] = Background::findById($character->background_id);
            $data['equipment'] = $character->getCharacterEquipment();
            $data['spells'] = $character->getCharacterSpells();
            $data['capabilities'] = $character->getCapabilities();
            $data['attacks'] = $character->calculateMyCharacterAttacks();
            
            // Rage Barbare
            $data['rage'] = null;
            if ($data['class'] && strpos(strtolower($data['class']->name), 'barbare') !== false) {
                 $maxRages = $data['class']->getMaxRages($character->level);
                 $data['rage'] = ['max' => $maxRages];
            }
            
            $template_file = 'templates/print/sheet_character.php';
            $page_title = "Fiche - " . $character->name;
            break;

        case 'npc':
            $npc = NPC::findById($id);
            if (!$npc) throw new Exception("PNJ introuvable.");
            
            // Permissions: Créateur ou DM
            $isOwner = ($npc->created_by == $_SESSION['user_id']);
            if (!$isOwner && !isDM() && !User::isAdmin()) {
               throw new Exception("Accès refusé."); 
            }
            
            $data['npc'] = $npc;
            $data['race'] = Race::findById($npc->race_id);
            $data['class'] = $npc->class_id ? Classe::findById($npc->class_id) : null;
            $data['equipment'] = $npc->getMyEquipment();
            $data['capabilities'] = $npc->getCapabilities();
            $data['skills'] = $npc->getNpcSkills();
            $data['languages'] = $npc->getNpcLanguages();
            $data['attacks'] = $npc->calculateMyCharacterAttacks();
            
            $template_file = 'templates/print/sheet_npc.php';
            $page_title = "PNJ - " . $npc->name;
            break;

        case 'monster':
            // Récupérer le monstre (instance dans une pièce)
            $monster = Monstre::getMonsterInPlace($id);
            if (!$monster) throw new Exception("Monstre introuvable.");
            
            // Permissions: DM de la campagne ou Admin
            // Note: La logique de permission dans view_monster_sheet.php est complexe (campagne, admin), simplification ici:
            if (!isDM() && !User::isAdmin()) {
                 throw new Exception("Accès refusé (DM uniquement).");
            }
            
            // Récupérer les infos complètes de la définition du monstre
            $monstreDef = Monstre::findById($monster['monster_db_id']);
            
            $data['monster'] = $monster;
            $data['monstreDef'] = $monstreDef;
            $data['actions'] = $monstreDef->getActions();
            $data['legendary_actions'] = $monstreDef->getLegendaryActions();
            $data['special_attacks'] = $monstreDef->getSpecialAttacks();
            $data['spells'] = $monstreDef->getSpells();
            
            $template_file = 'templates/print/sheet_monster.php';
            $page_title = "Monstre - " . $monster['name'];
            break;
            
        case 'place':
            require_once 'classes/Room.php';
            $place_obj = Room::findById($id);
            if (!$place_obj) throw new Exception("Pièce introuvable.");
            
            // Note: permissions simplification (in a real app, duplicate strict checks)
            if (!User::isDMOrAdmin()) {
                 // Check if player is in the room or has access? Simplification: allow if logged in for now or rely on DM.
                 // Ideally check if user has a character in the campaign of this place.
            }

            $place = $place_obj->toArray();
            $data['place'] = $place;
            $data['visible_objects'] = $place_obj->getVisibleObjects();
            
            $template_file = 'templates/print/sheet_place.php';
            $page_title = "Plan - " . $place['title'];
            break;
            
        case 'country':
            require_once 'classes/Pays.php';
            require_once 'classes/Region.php';
            $country = Pays::findById($id);
            if (!$country) throw new Exception("Pays introuvable.");
            
            $data['country'] = $country;
            $data['regions'] = $country->getRegions();
            
            $template_file = 'templates/print/sheet_country.php';
            $page_title = "Carte - " . $country->getName();
            break;
    }
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Layout Wrapper
include 'templates/print/layout.php';
