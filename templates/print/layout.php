<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="css/print.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes (optionnel à l'impression mais garde la cohérence) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body onload="window.print()">

    <div class="no-print" style="position: fixed; top: 0; left: 0; width: 100%; background: #333; color: white; padding: 10px; text-align: center; z-index: 1000;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #4CAF50; color: white; border: none; border-radius: 4px;">Lancer l'impression</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #f44336; color: white; border: none; border-radius: 4px; margin-left: 10px;">Fermer</button>
        <div style="margin-top: 5px; font-size: 12px; color: #ccc;">Pour enregistrer en PDF, sélectionnez "Enregistrer au format PDF" dans la destination de l'imprimante.</div>
    </div>

    <div class="print-page">
        <?php include $template_file; ?>
    </div>

</body>
</html>
