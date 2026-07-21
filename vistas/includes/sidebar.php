<div class="sidebar" id="sidebar"
style="display:flex;flex-direction:column;justify-content:space-between;height:calc(102vh - 70px);">

    <ul style="list-style:none;padding:0;margin:0;">

        <li>
            <a href="/FUNDACITE/vistas/dashboard.php" class="submenu-link">
                <i class="bi bi-house-door-fill"></i>
                <b>INICIO</b>
            </a>
        </li>

        <li>
            <a href="/FUNDACITE/vistas/lista_trabajadores.php" class="submenu-link">
                <i class="bi bi-person-workspace"></i>
                <b>TRABAJADORES</b>
            </a>
        </li>

        <li>
            <a href="/FUNDACITE/vistas/registrar_cargo.php" class="submenu-link">
                <i class="bi bi-briefcase-fill"></i>
                <b>CARGO</b>
            </a>
        </li>

        <li class="submenu" id="menuDireccion">
            <div class="submenu-btn" id="btnDireccion">
                <span>
                    <i class="bi bi-geo-alt-fill"></i>
                    <b>DIRECCIÓN</b>
                </span>
                <i class="bi bi-chevron-down arrow"></i>
            </div>

            <ul class="submenu-content" id="submenuDireccion">
                <li>
                    <a href="/FUNDACITE/vistas/registro_estados.php" class="submenu-link">
                        <i class="bi bi-flag-fill"></i>
                        Estados
                    </a>
                </li>
                <li>
                    <a href="/FUNDACITE/vistas/registro_municipios.php" class="submenu-link">
                        <i class="bi bi-signpost-split-fill"></i>
                        Municipios
                    </a>
                </li>
                <li>
                    <a href="/FUNDACITE/vistas/registro_parroquia.php" class="submenu-link">
                        <i class="bi bi-signpost-2-fill"></i>
                        Parroquias
                    </a>
                </li>
                <li>
                    <a href="/FUNDACITE/vistas/registro_direccion.php" class="submenu-link">
                        <i class="bi bi-house-door-fill"></i>
                        Direcciones
                    </a>
                </li>
            </ul>
        </li>

        <?php include_once __DIR__ . '/permissions.php'; ?>

        <?php if (esAdministradorODirector()): ?>
        <li>
            <a href="/FUNDACITE/vistas/registrar_contrato.php" class="submenu-link">
                <i class="bi bi-file-earmark-text-fill"></i>
                <b>CONTRATOS</b>
            </a>
        </li>
         <?php endif; ?>
        <?php if (esAdministrador()): ?>
        <li>
            <a href="/FUNDACITE/vistas/lista_usuarios.php" class="submenu-link">
                <i class="bi bi-person-circle"></i>
                <b>USUARIOS</b>
            </a>
        </li>
        <?php endif; ?>

        <li>
            <a href="/FUNDACITE/vistas/lista_salario.php" class="submenu-link">
                <i class="bi bi-coin"></i> <b>SALARIO</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_primas.php" class="submenu-link">
                <i class="bi bi-wallet"></i> <b>PRIMA</b>
            </a>
        </li>

        <?php if (esAdministradorODirector()): ?>
        <li>
            <a href="/FUNDACITE/vistas/lista_dias_disfrute.php" class="submenu-link">
                <i class="bi bi-calendar2-check-fill"></i>
                <b>SOLICITUDES</b>
            </a>
        </li>
        <?php endif; ?>

    </ul>

    <ul style="list-style:none;padding:0;margin-bottom:20px;">

        <li>
            <a href="/FUNDACITE/controladores/logout.php" class="submenu-link">
                <i class="bi bi-box-arrow-right"></i>
                <b>CERRAR SESIÓN</b>
            </a>
        </li>

    </ul>

</div>