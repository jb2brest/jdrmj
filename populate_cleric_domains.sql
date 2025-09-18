-- Peupler la table des domaines divins
INSERT INTO cleric_domains (name, description, level_1_feature, level_2_feature, level_6_feature, level_8_feature, level_17_feature) VALUES
(
    'Domaine de la Vie',
    'Le domaine de la Vie se concentre sur la préservation de la vie et la guérison. Les clercs de ce domaine sont des guérisseurs naturels et des protecteurs de la vie.',
    'Maîtrise d\'armures lourdes : Vous maîtrisez les armures lourdes. Bénédiction de la Vie : Vous pouvez utiliser votre action pour toucher une créature et la soigner d\'un nombre de points de vie égal à 2 + votre niveau de clerc. Vous pouvez utiliser cette aptitude un nombre de fois égal à votre modificateur de Sagesse (minimum 1) entre deux repos longs.',
    'Canalisation de Divinité : Préservation de la Vie : En tant qu\'action, vous présentez votre symbole sacré et invoquez une énergie curative qui se répand sur vous et toutes les créatures amicales dans un rayon de 9 mètres. Chaque créature (vous y compris) récupère un nombre de points de vie égal à la moitié de votre niveau de clerc (minimum 1).',
    'Bénédiction suprême : Quand vous utilisez votre aptitude Bénédiction de la Vie, la créature récupère le maximum de points de vie possible pour cette aptitude.',
    'Amélioration de caractéristique divine : Vous pouvez augmenter votre Force ou votre Sagesse de 2, ou deux autres caractéristiques de 1. Vous ne pouvez pas augmenter une caractéristique au-dessus de 20 avec cette aptitude.',
    'Maître de la Vie : Vous avez appris à utiliser votre magie pour maintenir la vie même dans les situations les plus désespérées. Vous pouvez lancer le sort résurrection sans dépenser d\'emplacement de sort. Une fois que vous avez utilisé cette aptitude, vous ne pouvez plus l\'utiliser tant que vous n\'avez pas terminé un repos long.'
),
(
    'Domaine de la Lumière',
    'Le domaine de la Lumière se concentre sur la lumière, la vérité et la révélation. Les clercs de ce domaine sont des porteurs de lumière et des révélateurs de vérité.',
    'Maîtrise d\'armures lourdes : Vous maîtrisez les armures lourdes. Bénédiction de la Lumière : Vous pouvez utiliser votre action pour toucher une créature et la bénir avec la lumière divine. La créature gagne un avantage aux jets d\'attaque contre les créatures des ténèbres et aux jets de sauvegarde contre les sorts de ténèbres.',
    'Canalisation de Divinité : Radiance du Soleil : En tant qu\'action, vous présentez votre symbole sacré et invoquez une lumière aveuglante. Chaque créature hostile dans un rayon de 9 mètres doit réussir un jet de sauvegarde de Constitution contre votre DD de sort de clerc, ou être aveuglée pendant 1 minute.',
    'Amélioration de sort : Vous pouvez lancer le sort boule de feu comme un sort de clerc. Il compte comme un sort de clerc pour vous, mais il ne compte pas dans le nombre de sorts de clerc que vous connaissez.',
    'Amélioration de caractéristique divine : Vous pouvez augmenter votre Force ou votre Sagesse de 2, ou deux autres caractéristiques de 1. Vous ne pouvez pas augmenter une caractéristique au-dessus de 20 avec cette aptitude.',
    'Corona de Lumière : Vous êtes entouré d\'une aura de lumière divine. Vous émettez une lumière vive dans un rayon de 9 mètres et une lumière faible dans un rayon de 18 mètres. Les créatures des ténèbres ont un désavantage aux jets d\'attaque contre vous.'
),
(
    'Domaine de la Nature',
    'Le domaine de la Nature se concentre sur la nature, les animaux et les éléments. Les clercs de ce domaine sont des protecteurs de la nature et des maîtres des éléments.',
    'Maîtrise d\'armures lourdes : Vous maîtrisez les armures lourdes. Bénédiction de la Nature : Vous pouvez utiliser votre action pour toucher une créature et la bénir avec la force de la nature. La créature gagne un avantage aux jets de sauvegarde contre les sorts et effets magiques.',
    'Canalisation de Divinité : Maîtrise de la Nature : En tant qu\'action, vous présentez votre symbole sacré et invoquez la puissance de la nature. Vous pouvez lancer le sort parler avec les animaux ou le sort parler avec les plantes sans dépenser d\'emplacement de sort.',
    'Amélioration de sort : Vous pouvez lancer le sort mur de feu comme un sort de clerc. Il compte comme un sort de clerc pour vous, mais il ne compte pas dans le nombre de sorts de clerc que vous connaissez.',
    'Amélioration de caractéristique divine : Vous pouvez augmenter votre Force ou votre Sagesse de 2, ou deux autres caractéristiques de 1. Vous ne pouvez pas augmenter une caractéristique au-dessus de 20 avec cette aptitude.',
    'Maître de la Nature : Vous avez appris à utiliser votre magie pour contrôler les éléments. Vous pouvez lancer le sort météore sans dépenser d\'emplacement de sort. Une fois que vous avez utilisé cette aptitude, vous ne pouvez plus l\'utiliser tant que vous n\'avez pas terminé un repos long.'
),
(
    'Domaine de la Tempête',
    'Le domaine de la Tempête se concentre sur la puissance destructrice des tempêtes et des éléments. Les clercs de ce domaine sont des maîtres de la foudre et du tonnerre.',
    'Maîtrise d\'armures lourdes : Vous maîtrisez les armures lourdes. Bénédiction de la Tempête : Vous pouvez utiliser votre action pour toucher une créature et la bénir avec la puissance de la tempête. La créature gagne un avantage aux jets d\'attaque avec les armes de foudre et de tonnerre.',
    'Canalisation de Divinité : Destructeur de Tempête : En tant qu\'action, vous présentez votre symbole sacré et invoquez une tempête destructrice. Chaque créature hostile dans un rayon de 9 mètres doit réussir un jet de sauvegarde de Dextérité contre votre DD de sort de clerc, ou subir 2d8 dégâts de foudre.',
    'Amélioration de sort : Vous pouvez lancer le sort éclair comme un sort de clerc. Il compte comme un sort de clerc pour vous, mais il ne compte pas dans le nombre de sorts de clerc que vous connaissez.',
    'Amélioration de caractéristique divine : Vous pouvez augmenter votre Force ou votre Sagesse de 2, ou deux autres caractéristiques de 1. Vous ne pouvez pas augmenter une caractéristique au-dessus de 20 avec cette aptitude.',
    'Maître de la Tempête : Vous avez appris à utiliser votre magie pour invoquer des tempêtes dévastatrices. Vous pouvez lancer le sort tempête de feu sans dépenser d\'emplacement de sort. Une fois que vous avez utilisé cette aptitude, vous ne pouvez plus l\'utiliser tant que vous n\'avez pas terminé un repos long.'
),
(
    'Domaine de la Guerre',
    'Le domaine de la Guerre se concentre sur la guerre, la stratégie et le combat. Les clercs de ce domaine sont des guerriers divins et des tacticiens.',
    'Maîtrise d\'armures lourdes : Vous maîtrisez les armures lourdes. Bénédiction de la Guerre : Vous pouvez utiliser votre action pour toucher une créature et la bénir avec la puissance de la guerre. La créature gagne un avantage aux jets d\'attaque et aux jets de dégâts.',
    'Canalisation de Divinité : Guerrier Divin : En tant qu\'action, vous présentez votre symbole sacré et invoquez la puissance divine du combat. Vous pouvez faire une attaque d\'arme en action bonus.',
    'Amélioration de sort : Vous pouvez lancer le sort fléau comme un sort de clerc. Il compte comme un sort de clerc pour vous, mais il ne compte pas dans le nombre de sorts de clerc que vous connaissez.',
    'Amélioration de caractéristique divine : Vous pouvez augmenter votre Force ou votre Sagesse de 2, ou deux autres caractéristiques de 1. Vous ne pouvez pas augmenter une caractéristique au-dessus de 20 avec cette aptitude.',
    'Maître de la Guerre : Vous avez appris à utiliser votre magie pour devenir un guerrier divin. Vous pouvez lancer le sort fléau sans dépenser d\'emplacement de sort. Une fois que vous avez utilisé cette aptitude, vous ne pouvez plus l\'utiliser tant que vous n\'avez pas terminé un repos long.'
),
(
    'Domaine de la Mort',
    'Le domaine de la Mort se concentre sur la mort, les morts-vivants et la nécromancie. Les clercs de ce domaine sont des maîtres de la mort et des nécromanciens.',
    'Maîtrise d\'armures lourdes : Vous maîtrisez les armures lourdes. Bénédiction de la Mort : Vous pouvez utiliser votre action pour toucher une créature et la bénir avec la puissance de la mort. La créature gagne un avantage aux jets d\'attaque contre les morts-vivants.',
    'Canalisation de Divinité : Maîtrise de la Mort : En tant qu\'action, vous présentez votre symbole sacré et invoquez la puissance de la mort. Vous pouvez lancer le sort animation des morts sans dépenser d\'emplacement de sort.',
    'Amélioration de sort : Vous pouvez lancer le sort cercle de mort comme un sort de clerc. Il compte comme un sort de clerc pour vous, mais il ne compte pas dans le nombre de sorts de clerc que vous connaissez.',
    'Amélioration de caractéristique divine : Vous pouvez augmenter votre Force ou votre Sagesse de 2, ou deux autres caractéristiques de 1. Vous ne pouvez pas augmenter une caractéristique au-dessus de 20 avec cette aptitude.',
    'Maître de la Mort : Vous avez appris à utiliser votre magie pour contrôler la mort elle-même. Vous pouvez lancer le sort mort sans dépenser d\'emplacement de sort. Une fois que vous avez utilisé cette aptitude, vous ne pouvez plus l\'utiliser tant que vous n\'avez pas terminé un repos long.'
);
