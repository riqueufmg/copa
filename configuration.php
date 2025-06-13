<?php
// Define timezone
date_default_timezone_set('America/Sao_Paulo');

// GLOBAL VARIABLES
define('MAX_PAIRS', 30);
define('MULTIPLE_ANNOTATION_QUANTITY', 1);

// Database connection configuration
$servername = "db.face.ufmg.br";
$username = "refactoring";
$password = "Sfg2sOD8Yz";
$dbname = "wh_refactorings";

// Create the connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
