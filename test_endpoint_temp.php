<?php
session_start();
require_once "classes/init.php";
require_once "includes/user_compatibility.php";

$_SESSION["user_id"] = 1;
$_SESSION["username"] = "Robin";
$_SESSION["role"] = "player";

$_SERVER["REQUEST_METHOD"] = "POST";
$_SERVER["CONTENT_TYPE"] = "application/json";

$GLOBALS["test_input"] = json_encode(["character_id" => 81]);

// Rediriger php://input
function test_input() {
    return $GLOBALS["test_input"];
}

// Remplacer file_get_contents
function file_get_contents($filename) {
    if ($filename === "php://input") {
        return test_input();
    }
    return \file_get_contents($filename);
}

include_once 'api/reset_rages.php';
?>