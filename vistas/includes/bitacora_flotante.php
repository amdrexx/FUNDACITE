<?php
// ============================================================================
// ARCHIVO: vistas/includes/bitacora_flotante.php
// DESCRIPCIÓN: Botón flotante (campana) + panel de la bitácora del sistema.
//              Solo se dibuja para el Administrador; para cualquier otro rol
//              no sale nada en el HTML. El endpoint que le da datos
//              (ajax/ajax_bitacora.php) valida el rol por su cuenta.
//
// USO (al final del <body>, antes de los <script>), dentro de un bloque PHP:
//   include "includes/bitacora_flotante.php";
// ============================================================================

if (!function_exists('esAdministrador') || !esAdministrador()) {
    return;
}

$btIdUsuario = (int)($_SESSION['id_usuario'] ?? 0);
?>
<link rel="stylesheet" href="/FUNDACITE/vistas/css/bitacora.css">

<div class="bt-root" id="btRoot"
     data-endpoint="/FUNDACITE/ajax/ajax_bitacora.php"
     data-usuario="<?= $btIdUsuario ?>">

    <!-- ===================== AVISO EMERGENTE ===================== -->
    <div class="bt-toast" id="btToast" role="status" aria-live="polite">
        <button type="button" class="bt-toast-main" id="btToastMain">
            <span class="bt-ico" id="btToastIco" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
            <span class="bt-toast-body">
                <span class="bt-toast-top">
                    <strong id="btToastUser"></strong>
                    <span class="bt-toast-more" id="btToastMore"></span>
                </span>
                <span class="bt-toast-meta" id="btToastMeta"></span>
                <span class="bt-toast-text" id="btToastText"></span>
            </span>
        </button>
        <button type="button" class="bt-toast-x" id="btToastX" aria-label="Descartar aviso">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <!-- ========================= PANEL ========================= -->
    <section class="bt-panel" id="btPanel" role="dialog" aria-labelledby="btTitulo" aria-hidden="true" tabindex="-1">

        <header class="bt-head">
            <span class="bt-head-ico" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
            <div class="bt-head-txt">
                <h2 id="btTitulo">Bitácora</h2>
                <p>Actividad reciente del sistema</p>
            </div>
            <button type="button" class="bt-close" id="btClose" aria-label="Cerrar bitácora">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="bt-tools">
            <label class="bt-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="btBuscar" placeholder="Buscar usuario, módulo o detalle"
                       autocomplete="off" maxlength="80" aria-label="Buscar en la bitácora">
            </label>

            <div class="bt-chips" id="btChips" role="group" aria-label="Filtrar por tipo de movimiento">
                <button type="button" class="bt-chip" data-grupo="" aria-pressed="true">Todo</button>
                <button type="button" class="bt-chip" data-grupo="cambios" aria-pressed="false">Cambios</button>
                <button type="button" class="bt-chip" data-grupo="accesos" aria-pressed="false">Accesos</button>
                <button type="button" class="bt-chip" data-grupo="consultas" aria-pressed="false">Consultas</button>
            </div>
        </div>

        <div class="bt-scroll" id="btScroll">
            <div class="bt-nuevos-wrap">
                <button type="button" class="bt-nuevos" id="btNuevos" tabindex="-1">
                    <i class="bi bi-arrow-up-short" aria-hidden="true"></i> Movimientos nuevos
                </button>
            </div>

            <ol class="bt-list" id="btLista"></ol>

            <div class="bt-estado" id="btEstado" hidden></div>

            <button type="button" class="bt-mas" id="btMas" hidden>Cargar más</button>
        </div>
    </section>

    <!-- ===================== BOTÓN FLOTANTE ===================== -->
    <button type="button" class="bt-fab" id="btFab"
            aria-label="Abrir bitácora" aria-haspopup="dialog" aria-expanded="false" aria-controls="btPanel">
        <i class="bi bi-bell-fill" aria-hidden="true"></i>
        <span class="bt-badge" id="btBadge" aria-hidden="true">0</span>
    </button>
</div>

<script src="/FUNDACITE/vistas/js/bitacora.js" defer></script>
