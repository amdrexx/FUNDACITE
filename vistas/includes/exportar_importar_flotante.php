<?php
// ============================================================================
// ARCHIVO: vistas/includes/exportar_importar_flotante.php
// DESCRIPCIÓN: Botón flotante (flecha hacia arriba) + panel para exportar e
//              importar la base de datos del sistema. Se dibuja justo encima
//              de la campana de la bitácora y, al igual que ella, SOLO para
//              el Administrador; para cualquier otro rol no sale nada en el
//              HTML. Los modelos de importación/exportación validan el rol.
//
// USO (al final del <body>, junto a bitacora_flotante.php), dentro de un
// bloque PHP:
//   include "includes/exportar_importar_flotante.php";
// ============================================================================

if (!function_exists('esAdministrador') || !esAdministrador()) {
    return;
}
$eiRespaldos = glob(__DIR__ . '/../../Respaldo_BD/*.sql') ?: [];
usort($eiRespaldos, static fn($a, $b) => filemtime($b) <=> filemtime($a));
$eiEstado = $_GET['respaldo_estado'] ?? '';
$eiMensaje = '';
if ($eiEstado === 'exportado') {
    $eiMensaje = 'Exportación exitosa. El respaldo quedó guardado en el servidor.';
} elseif ($eiEstado === 'importado') {
    $eiMensaje = 'Importación exitosa. Se ejecutaron ' . (int) ($_GET['sentencias'] ?? 0) . ' sentencias.';
} elseif ($eiEstado === 'error') {
    $eiMensaje = (string) ($_GET['respaldo_mensaje'] ?? 'No se pudo completar la operación.');
}
?>
<link rel="stylesheet" href="/FUNDACITE/vistas/css/exportar_importar.css">

<div class="ei-root" id="eiRoot">

    <!-- ========================= PANEL ========================= -->
    <section class="ei-panel" id="eiPanel" role="dialog" aria-labelledby="eiTitulo" aria-hidden="true" tabindex="-1">

        <header class="ei-head">
            <span class="ei-head-ico" aria-hidden="true"><i class="bi bi-database-fill-up"></i></span>
            <div class="ei-head-txt">
                <h2 id="eiTitulo">Base de Datos</h2>
                <p>Exportar e importar respaldos del sistema</p>
            </div>
            <button type="button" class="ei-close" id="eiClose" aria-label="Cerrar panel de base de datos">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="ei-tabs" id="eiTabs" role="tablist" aria-label="Exportar o importar">
            <button type="button" class="ei-tab" id="eiTabExportar" role="tab" aria-selected="true" aria-controls="eiPanelExportar">
                <i class="bi bi-cloud-arrow-up-fill" aria-hidden="true"></i> Exportar
            </button>
            <button type="button" class="ei-tab" id="eiTabImportar" role="tab" aria-selected="false" aria-controls="eiPanelImportar">
                <i class="bi bi-cloud-arrow-down-fill" aria-hidden="true"></i> Importar
            </button>
        </div>

        <div class="ei-scroll" id="eiScroll">

            <!-- ===================== EXPORTAR ===================== -->
            <div class="ei-sec" id="eiPanelExportar" role="tabpanel" aria-labelledby="eiTabExportar">
                <p class="ei-texto">
                    Genera un respaldo completo (estructura y datos) de la base de datos actual.
                    Se guarda una copia en el servidor y además se descarga automáticamente en formato
                    <code>.sql</code>.
                </p>

                <form id="eiFormExportar" method="POST" action="/FUNDACITE/modelos/exportar.php">
                    <button type="submit" class="ei-btn ei-btn-primary" id="eiBtnExportar">
                        <i class="bi bi-cloud-arrow-up-fill" aria-hidden="true"></i>
                        Generar y descargar respaldo
                    </button>
                </form>

                <?php if ($eiEstado === 'exportado'): ?>
                    <div class="ei-estado is-ok" role="status" aria-live="polite">
                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($eiMensaje, ENT_QUOTES, 'UTF-8') ?>
                            <?php $eiArchivo = basename((string) ($_GET['respaldo_archivo'] ?? '')); ?>
                            <?php if ($eiArchivo !== ''): ?>
                                <a class="ei-descarga" href="/FUNDACITE/Respaldo_BD/<?= rawurlencode($eiArchivo) ?>" download>Descargar <?= htmlspecialchars($eiArchivo, ENT_QUOTES, 'UTF-8') ?></a>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="ei-divisor"><span>Respaldos guardados en el servidor</span></div>

                <ul class="ei-lista">
                    <?php if (!$eiRespaldos): ?>
                        <li class="ei-vacio">Todavía no hay respaldos guardados.</li>
                    <?php else: foreach ($eiRespaldos as $eiRuta): ?>
                        <li>
                            <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>
                            <span class="ei-lista-nombre"><?= htmlspecialchars(basename($eiRuta), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="ei-lista-meta"><?= date('d/m/Y H:i', filemtime($eiRuta)) ?> · <?= round(filesize($eiRuta) / 1024, 1) ?> KB</span>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>

            <!-- ===================== IMPORTAR ===================== -->
            <div class="ei-sec" id="eiPanelImportar" role="tabpanel" aria-labelledby="eiTabImportar" hidden>

                <div class="ei-aviso">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    <span>Importar reemplaza la información actual del sistema con la del respaldo. Esta acción no se puede deshacer.</span>
                </div>

                <p class="ei-texto">Subir un archivo <code>.sql</code> desde el equipo:</p>

                <form id="eiFormArchivo" method="POST" enctype="multipart/form-data" action="/FUNDACITE/modelos/importar.php">
                    <label class="ei-file" id="eiFileLabel" for="eiArchivo">
                        <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                        <span id="eiFileTexto">Elegir archivo .sql&hellip;</span>
                    </label>
                    <input type="file" id="eiArchivo" name="archivo" accept=".sql" hidden>

                    <button type="submit" class="ei-btn ei-btn-primary" id="eiBtnImportarArchivo" disabled>
                        <i class="bi bi-cloud-arrow-down-fill" aria-hidden="true"></i>
                        Importar archivo
                    </button>
                </form>

                <div class="ei-divisor"><span>o restaurar un respaldo guardado</span></div>

                <form id="eiFormRespaldo" method="POST" action="/FUNDACITE/modelos/importar.php">
                    <select class="ei-select" id="eiSelectRespaldo" name="respaldo">
                        <option value="">Selecciona un respaldo&hellip;</option>
                        <?php foreach ($eiRespaldos as $eiRuta): ?>
                            <?php $eiNombre = basename($eiRuta); ?>
                            <option value="<?= htmlspecialchars($eiNombre, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($eiNombre, ENT_QUOTES, 'UTF-8') ?> (<?= date('d/m/Y H:i', filemtime($eiRuta)) ?>)</option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="ei-btn ei-btn-secundario" id="eiBtnImportarRespaldo" disabled>
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        Restaurar este respaldo
                    </button>
                </form>

                <?php if (in_array($eiEstado, ['importado', 'error'], true)): ?>
                    <div class="ei-estado <?= $eiEstado === 'importado' ? 'is-ok' : 'is-error' ?>" role="status" aria-live="polite">
                        <i class="bi <?= $eiEstado === 'importado' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($eiMensaje, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- ===================== BOTÓN FLOTANTE ===================== -->
    <button type="button" class="ei-fab" id="eiFab"
            aria-label="Exportar o importar base de datos" aria-haspopup="dialog" aria-expanded="false" aria-controls="eiPanel">
        <i class="bi bi-database-fill-up" aria-hidden="true"></i>
    </button>
</div>

<script src="/FUNDACITE/vistas/js/exportar_importar.js" defer></script>
