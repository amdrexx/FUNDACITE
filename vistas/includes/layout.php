<?php
include "topbar.php";
include "sidebar.php";

?>
<button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Cerrar menú de navegación"></button>
<?php include __DIR__ . '/alerta.php'; ?>
<script src="/FUNDACITE/vistas/js/sub-menu.js"></script>
<link rel="stylesheet" href="/FUNDACITE/vistas/css/inactividad.css">
<script src="/FUNDACITE/vistas/js/responsive-ui.js"></script>
<script src="/FUNDACITE/vistas/js/paginar_tabla.js?v=20261117"></script>
<script src="/FUNDACITE/vistas/js/inactividad.js"
        data-limite="<?= defined('SESION_INACTIVIDAD_SEG') ? (int) SESION_INACTIVIDAD_SEG : 60 ?>"></script>
