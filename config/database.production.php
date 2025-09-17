<?php
// Configuration de la base de données - ENVIRONNEMENT PRODUCTION
return [
    'host' => 'localhost',
    'dbname' => 'u839591438_jdrmj',
    'username' => 'u839591438_jdrmj',
    'password' => 'M8jbsYJUj6FE$;C',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
?>
