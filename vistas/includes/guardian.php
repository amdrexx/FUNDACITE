<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["id_usuario"])) {

    header("Location: /FUNDACITE/index.php");
    exit;
}

require_once __DIR__ . '/roles.php';