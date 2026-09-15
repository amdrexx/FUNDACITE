<?php

session_start();

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/helpers/bitacora_helper.php";

registrarBitacora($conexion, 'Autenticación', 'Logout', 'Cierre de sesión.');

session_unset();
session_destroy();

header("Location: /FUNDACITE/index.php");
exit;