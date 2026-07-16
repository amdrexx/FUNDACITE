<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Direcciones</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
</head>
<body>

  
    <button class="menu-btn" id="menuBtn" onclick="toggleMenu()">
        <div></div>
        <div></div>
        <div></div>
    </button>

   
    <div class="topbar">
        <img src="/FUNDACITE/vistas/img/logo.png" alt="Logo">
    </div>

   
   <div class="sidebar" id="sidebar" style="display: flex; flex-direction: column; justify-content: space-between; height: calc(102vh - 70px);">
    <ul style="list-style: none; padding: 0; margin: 0;">
        <li>
            <a href="/FUNDACITE/vistas/dashboard.php" class="submenu-link">
                <i class="bi bi-house-door-fill"></i> <b>INICIO</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_trabajadores.php" class="submenu-link">
                <i class="bi bi-person-workspace"></i> <b>TRABAJADORES</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_cargos.php" class="submenu-link">
                <i class="bi bi-briefcase-fill"></i> <b>CARGO</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_direcciones.php" class="submenu-link">
                <i class="bi bi-geo-alt-fill"></i> <b>DIRECCION</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_contratos.php" class="submenu-link">
                <i class="bi bi-file-earmark-text-fill"></i> <b>CONTRATOS</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_usuarios.php" class="submenu-link">
                <i class="bi bi-person-circle"></i> <b>USUARIO</b>
            </a>
        </li>
        <li>
            <a href="/FUNDACITE/vistas/lista_dias_disfrute.php" class="submenu-link">
                <i class="bi bi-calendar2-check-fill"></i> <b>SOLICITUDES DE DIAS DE DISFRUTE</b>
            </a>
        </li>
    </ul>

    <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
        <li>
            <a href="" class="submenu-link">
                <i class="bi bi-box-arrow-right"></i> <b>CERRAR SESIÓN</b>
            </a>
        </li>
    </ul>
</div>

    <div class="overlay"></div>

   
    <div class="main">
        <div class="glass tabla-container">
            <h2 style="text-align:center; color:white;">Lista de días de disfrute</h2>

            <div class="buscador-container">
                <input 
                    type="text" 
                    class="input-busqueda"
                   
                >
                <button class="btn-buscar">Buscar</button>
                <a href="/FUNDACITE/vistas/registrar_dias_disfrute.php" class="btn-persona" style="text-decoration:none;">
                    + Agregar
                </a>
            </div>


            <table class="tabla">
                <thead>
                    <tr>
                        <th>Cedula</th>
                        <th>Cargo</th>
                        <th>Descripcion</th>
                        <th>Desde</th>
                        <th>Hasta</th>
                        <th>Total de días</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
              
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="acciones">
                            <button class="btn-editar" 
                            onclick="editarDireccion('Lara','Iribarren','Catedral','El Centro')">
                            Editar
                        </button>
                        <button class="btn-eliminar" 
                            onclick="eliminarDireccion('Lara','Iribarren','Catedral','El Centro')">
                            Eliminar
                        </button>
                        </td>
                    </tr>
                
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="acciones">
                            <button class="btn-editar" 
                            onclick="editarDireccion('Zulia','Maracaibo','Coquivacoa','La Lago')">
                            Editar
                        </button>
                        <button class="btn-eliminar" 
                            onclick="eliminarDireccion('Zulia','Maracaibo','Coquivacoa','La Lago')">
                            Eliminar
                        </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    
    <script src="/FUNDACITE/vistas/js/boton_desplegable.js"></script>
</html>