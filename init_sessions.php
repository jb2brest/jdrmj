<?php
/**
 * Initialisation des sessions PHP
 * Inclus automatiquement par .htaccess
 */

// Configurer le répertoire des sessions
ini_set('session.save_path', '/tmp/php_sessions');

// S'assurer que le répertoire existe
if (!is_dir('/tmp/php_sessions')) {
    mkdir('/tmp/php_sessions', 0777, true);
}
?>


