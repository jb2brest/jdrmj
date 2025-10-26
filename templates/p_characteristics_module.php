<!-- Onglet Caractéristiques -->
<div class="tab-pane fade" id="characteristics" role="tabpanel" aria-labelledby="characteristics-tab">
    <div class="p-4">
        <div class="info-section">
            <h4><i class="fas fa-dumbbell me-2"></i>Caractéristiques</h4>

            <!-- Tableau des caractéristiques -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th class="table-header-narrow">Type</th>
                            <th class="table-header-medium">Force</th>
                            <th class="table-header-medium">Dextérité</th>
                            <th class="table-header-medium">Constitution</th>
                            <th class="table-header-medium">Intelligence</th>
                            <th class="table-header-medium">Sagesse</th>
                            <th class="table-header-medium">Charisme</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Caractéristiques de base -->
                        <tr>
                            <td><strong>Caractéristiques de base</strong></td>
                            <td><strong><?php echo $strength; ?></strong></td>
                            <td><strong><?php echo $dexterity; ?></strong></td>
                            <td><strong><?php echo $constitution; ?></strong></td>
                            <td><strong><?php echo $intelligence; ?></strong></td>
                            <td><strong><?php echo $wisdom; ?></strong></td>
                            <td><strong><?php echo $charisma; ?></strong></td>
                        </tr>
                        <!-- Bonus raciaux -->
                        <tr>
                            <td><strong>Bonus raciaux</strong></td>
                            <td><span class="text-success"><?php echo ($raceObject->strength_bonus > 0 ? '+' : '') . $raceObject->strength_bonus; ?></span></td>
                            <td><span class="text-success"><?php echo ($raceObject->dexterity_bonus > 0 ? '+' : '') . $raceObject->dexterity_bonus; ?></span></td>
                            <td><span class="text-success"><?php echo ($raceObject->constitution_bonus > 0 ? '+' : '') . $raceObject->constitution_bonus; ?></span></td>
                            <td><span class="text-success"><?php echo ($raceObject->intelligence_bonus > 0 ? '+' : '') . $raceObject->intelligence_bonus; ?></span></td>
                            <td><span class="text-success"><?php echo ($raceObject->wisdom_bonus > 0 ? '+' : '') . $raceObject->wisdom_bonus; ?></span></td>
                            <td><span class="text-success"><?php echo ($raceObject->charisma_bonus > 0 ? '+' : '') . $raceObject->charisma_bonus; ?></span></td>
                        </tr>
                        <!-- Bonus de niveau -->
                        <tr>
                            <td><strong>Bonus de niveau (<?php echo $remainingPoints ?? 0; ?> pts restants)</strong></td>
                            <td><span class="text-warning"><?php echo ($abilityImprovementsArray['strength'] ?? 0) > 0 ? '+' . ($abilityImprovementsArray['strength'] ?? 0) : ($abilityImprovementsArray['strength'] ?? 0); ?></span></td>
                            <td><span class="text-warning"><?php echo ($abilityImprovementsArray['dexterity'] ?? 0) > 0 ? '+' . ($abilityImprovementsArray['dexterity'] ?? 0) : ($abilityImprovementsArray['dexterity'] ?? 0); ?></span></td>
                            <td><span class="text-warning"><?php echo ($abilityImprovementsArray['constitution'] ?? 0) > 0 ? '+' . ($abilityImprovementsArray['constitution'] ?? 0) : ($abilityImprovementsArray['constitution'] ?? 0); ?></span></td>
                            <td><span class="text-warning"><?php echo ($abilityImprovementsArray['intelligence'] ?? 0) > 0 ? '+' . ($abilityImprovementsArray['intelligence'] ?? 0) : ($abilityImprovementsArray['intelligence'] ?? 0); ?></span></td>
                            <td><span class="text-warning"><?php echo ($abilityImprovementsArray['wisdom'] ?? 0) > 0 ? '+' . ($abilityImprovementsArray['wisdom'] ?? 0) : ($abilityImprovementsArray['wisdom'] ?? 0); ?></span></td>
                            <td><span class="text-warning"><?php echo ($abilityImprovementsArray['charisma'] ?? 0) > 0 ? '+' . ($abilityImprovementsArray['charisma'] ?? 0) : ($abilityImprovementsArray['charisma'] ?? 0); ?></span></td>
                        </tr>
                        <!-- Bonus d'équipements -->
                        <tr>
                            <td><strong>Bonus d'équipements</strong></td>
                            <td><span class="text-info"><?php echo ($equipmentBonuses['strength'] > 0 ? '+' : '') . $equipmentBonuses['strength']; ?></span></td>
                            <td><span class="text-info"><?php echo ($equipmentBonuses['dexterity'] > 0 ? '+' : '') . $equipmentBonuses['dexterity']; ?></span></td>
                            <td><span class="text-info"><?php echo ($equipmentBonuses['constitution'] > 0 ? '+' : '') . $equipmentBonuses['constitution']; ?></span></td>
                            <td><span class="text-info"><?php echo ($equipmentBonuses['intelligence'] > 0 ? '+' : '') . $equipmentBonuses['intelligence']; ?></span></td>
                            <td><span class="text-info"><?php echo ($equipmentBonuses['wisdom'] > 0 ? '+' : '') . $equipmentBonuses['wisdom']; ?></span></td>
                            <td><span class="text-info"><?php echo ($equipmentBonuses['charisma'] > 0 ? '+' : '') . $equipmentBonuses['charisma']; ?></span></td>
                        </tr>
                        <!-- Bonus temporaires -->
                        <tr>
                            <td><strong>Bonus temporaires</strong></td>
                            <td><span class="text-warning"><?php echo ($temporaryBonuses['strength'] > 0 ? '+' : '') . $temporaryBonuses['strength']; ?></span></td>
                            <td><span class="text-warning"><?php echo ($temporaryBonuses['dexterity'] > 0 ? '+' : '') . $temporaryBonuses['dexterity']; ?></span></td>
                            <td><span class="text-warning"><?php echo ($temporaryBonuses['constitution'] > 0 ? '+' : '') . $temporaryBonuses['constitution']; ?></span></td>
                            <td><span class="text-warning"><?php echo ($temporaryBonuses['intelligence'] > 0 ? '+' : '') . $temporaryBonuses['intelligence']; ?></span></td>
                            <td><span class="text-warning"><?php echo ($temporaryBonuses['wisdom'] > 0 ? '+' : '') . $temporaryBonuses['wisdom']; ?></span></td>
                            <td><span class="text-warning"><?php echo ($temporaryBonuses['charisma'] > 0 ? '+' : '') . $temporaryBonuses['charisma']; ?></span></td>
                        </tr>
                        <!-- Total -->
                        <tr class="table-success">
                            <td><strong>Total</strong></td>
                            <td><strong><?php echo $totalAbilities['strength']; ?></strong></td>
                            <td><strong><?php echo $totalAbilities['dexterity']; ?></strong></td>
                            <td><strong><?php echo $totalAbilities['constitution']; ?></strong></td>
                            <td><strong><?php echo $totalAbilities['intelligence']; ?></strong></td>
                            <td><strong><?php echo $totalAbilities['wisdom']; ?></strong></td>
                            <td><strong><?php echo $totalAbilities['charisma']; ?></strong></td>
                        </tr>
                        <!-- Modificateurs -->
                        <tr class="table-primary">
                            <td><strong>Modificateurs</strong></td>
                            <td><strong><?php echo ($strengthModifier >= 0 ? '+' : '') . $strengthModifier; ?></strong></td>
                            <td><strong><?php echo ($dexterityModifier >= 0 ? '+' : '') . $dexterityModifier; ?></strong></td>
                            <td><strong><?php echo ($constitutionModifier >= 0 ? '+' : '') . $constitutionModifier; ?></strong></td>
                            <td><strong><?php echo ($intelligenceModifier >= 0 ? '+' : '') . $intelligenceModifier; ?></strong></td>
                            <td><strong><?php echo ($wisdomModifier >= 0 ? '+' : '') . $wisdomModifier; ?></strong></td>
                            <td><strong><?php echo ($charismaModifier >= 0 ? '+' : '') . $charismaModifier; ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
