<?php
include "topbar.php";
include "sidebar.php";
?>
<script src="/FUNDACITE/vistas/js/sub-menu.js"></script>
<link rel="stylesheet" href="/FUNDACITE/vistas/css/inactividad.css">
<script src="/FUNDACITE/vistas/js/inactividad.js"
        data-limite="<?= defined('SESION_INACTIVIDAD_SEG') ? (int) SESION_INACTIVIDAD_SEG : 60 ?>"></script>
