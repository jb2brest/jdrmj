<?php
// Layout commun pour toutes les pages
// Paramètres :
// $page_title - titre de la page
// $current_page - page actuelle pour la navbar
// $show_hero - afficher la section hero (défaut: false)
// $additional_css - CSS additionnel (optionnel)
// $additional_js - JavaScript additionnel (optionnel)
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <?php if (isset($additional_css)): ?>
        <?php echo $additional_css; ?>
    <?php endif; ?>
</head>
<body>
    <?php 
    // Inclure la navbar
    include 'navbar.php'; 
    ?>
    
    <!-- Contenu principal -->
    <main>
        <?php 
        // Le contenu de la page sera inséré ici
        if (isset($page_content)) {
            echo $page_content;
        }
        ?>
    </main>
    
    <!-- Footer (optionnel) -->
    <?php if (isset($show_footer) && $show_footer): ?>
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> JDR 4 MJ. Tous droits réservés.</p>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Scripts Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
</body>
</html>
