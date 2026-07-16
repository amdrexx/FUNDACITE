<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Direcciones</title>
     <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
</head>
<body>

  
  <?php include "includes/layout.php"; ?>

   
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