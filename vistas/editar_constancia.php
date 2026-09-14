<?php
session_start();
include_once "includes/guardian.php";

if (function_exists('requireAdministradorODirector')) {
    requireAdministradorODirector();
}

require_once '../conexion.php';
require_once '../modelos/clase_solicitud.php';

$solicitudObj = new Solicitud($conexion);

$id_constancia = intval($_GET['id'] ?? 0);

if ($id_constancia <= 0) {
    header("Location: lista_constancias.php");
    exit;
}

$constancia = $solicitudObj->buscarPorId($id_constancia);

if (!$constancia) {
    header("Location: lista_constancias.php?status=error");
    exit;
}

$errores = $_SESSION['errores_constancia'] ?? [];
unset($_SESSION['errores_constancia']);

// --- Datos actuales para precargar el formulario ---
$nombreCompleto = trim(($constancia['apellidos'] ?? '') . ' ' . ($constancia['nombres'] ?? ''));
$cedula         = $constancia['cedula'] ?? '';
$cargo          = $constancia['nombre_cargo'] ?? '';
$fechaIngreso   = !empty($constancia['fecha_ingreso'])
    ? date('d/m/Y', strtotime($constancia['fecha_ingreso']))
    : '';
$salarioMonto   = $constancia['salario_monto'] ?? null;
$fechaEmision   = !empty($constancia['fecha'])
    ? date('Y-m-d', strtotime($constancia['fecha']))
    : date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Constancia de Trabajo</title>
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/style_dashboard.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/FUNDACITE/vistas/css/bootstrap-icons.scss">
    <script src="/FUNDACITE/vistas/js/bootstrap.min.js"></script>
    <style>
        #formconstancia .contenedor-botones {
            justify-content: center;
        }
        #formconstancia .btn-accion {
            flex: 0 0 auto;
            width: 180px;
        }
        .campo-auto {
            background-color: rgba(255, 255, 255, 0.1);
            cursor: not-allowed;
        }

        /* --- Encabezado de edición: coherente con la ficha de ver_constancia --- */
        .edit-kicker {
            margin: 0 0 6px;
            color: #b8dcf5;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        .edit-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .edit-heading h2 {
            margin: 0;
        }

        /* --- Foco visible consistente en todos los campos --- */
        #formconstancia select:focus-visible,
        #formconstancia input:not([readonly]):focus-visible,
        #formconstancia textarea:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(142, 205, 245, 0.55);
        }

        /* --- Botón principal: mismo lenguaje que la acción "Editar" de ver_constancia --- */
        #formconstancia button[value="actualizar"] {
            transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1),
                        box-shadow 200ms cubic-bezier(0.16, 1, 0.3, 1);
        }
        #formconstancia button[value="actualizar"]:hover {
            transform: translateY(-2px);
        }
        #formconstancia button[value="actualizar"]:active {
            transform: translateY(0) scale(0.98);
        }
    </style>
</head>
<body>

<?php include "includes/layout.php"; ?>

<div class="main">
    <div class="form-card">

        <div class="edit-heading">
            <div>
                <p class="edit-kicker">Editando información registrada</p>
                <h2>Constancia N° #<?php echo str_pad((string)$id_constancia, 5, '0', STR_PAD_LEFT); ?></h2>
            </div>
        </div>

        <form id="formconstancia" method="POST" action="../controladores/ctrl_constancia.php">

            <input type="hidden" name="id_constancia" value="<?php echo htmlspecialchars($id_constancia); ?>">

            <div class="form-grid full-width">

                <!-- Trabajador fijo: no se puede reasignar desde la edición -->
                <input type="hidden" name="id_trabajador" value="<?= (int)($constancia['id_trabajador'] ?? 0) ?>">

                <!-- 1. Fecha emisión -->
                <div class="field">
                    <label>1. Fecha de Emisión</label>
                    <input type="date" name="fecha" value="<?= htmlspecialchars($fechaEmision) ?>" required>
                </div>

                <!-- 2. Nombre completo -->
                <div class="field full-width">
                    <label>2. Apellido y Nombres del Trabajador(a)</label>
                    <input type="text" class="campo-auto"
                           value="<?= htmlspecialchars($nombreCompleto) ?>"
                           readonly>
                </div>

                <!-- 3. Cédula -->
                <div class="field">
                    <label>3. N° Cédula de Identidad</label>
                    <input type="text" class="campo-auto"
                           value="<?= htmlspecialchars($cedula) ?>"
                           readonly placeholder="---">
                </div>

                <!-- 4. Cargo -->
                <div class="field">
                    <label>4. Cargo Actual</label>
                    <input type="text" class="campo-auto"
                           value="<?= htmlspecialchars($cargo) ?>"
                           readonly placeholder="---">
                </div>

                <!-- 5. Fecha de ingreso -->
                <div class="field">
                    <label>5. Fecha de Ingreso</label>
                    <input type="text" class="campo-auto"
                           value="<?= htmlspecialchars($fechaIngreso) ?>"
                           readonly placeholder="---">
                </div>

                <!-- 6. Salario -->
                <div class="field">
                    <label>6. Salario Mensual (Bs.)</label>
                    <input type="text" class="campo-auto"
                           value="<?= $salarioMonto !== null ? number_format((float)$salarioMonto, 2, ',', '.') : '' ?>"
                           readonly placeholder="---">
                </div>

                <!-- 7. Tipo de personal -->
                <div class="field">
                    <label>7. Tipo de Personal</label>
                    <select name="tipo_personal" required>
                        <option value="" disabled>Seleccione...</option>
                        <?php
                        $tiposPersonal = [
                            'Fijo'       => 'Empleado Fijo',
                            'Contratado' => 'Contratado',
                            'Obrero'     => 'Obrero',
                            'Empleado'   => 'Empleado',
                        ];
                        foreach ($tiposPersonal as $valor => $etiqueta):
                        ?>
                            <option value="<?= $valor ?>" <?= ($constancia['tipo_personal'] ?? '') === $valor ? 'selected' : '' ?>>
                                <?= $etiqueta ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 8. Nombre Director -->
                <div class="field full-width">
                    <label>8. Nombre del Director de Departamento (quien firma)</label>
                    <input type="text" name="nombre_director"
                           value="<?= htmlspecialchars($constancia['nombre_director'] ?? '') ?>"
                           required placeholder="Ej: MSc. KARLA Y. MONTANEZ O">
                </div>

                <!-- 9. Motivo -->
                <div class="field full-width">
                    <label>9. Motivo de la Solicitud</label>
                    <textarea name="motivo" rows="3" required
                              placeholder="Ej: A solicitud de la parte interesada..."><?= htmlspecialchars($constancia['motivo'] ?? '') ?></textarea>
                </div>

            </div>

            <div class="contenedor-botones full-width">
                <button type="submit" name="accion" value="actualizar" class="btn-accion">
                    Actualizar
                </button>
                <a href="ver_constancia.php?id=<?php echo urlencode($id_constancia); ?>" class="btn-accion btn-eliminar" style="display:inline-flex; align-items:center; justify-content:center; text-decoration:none;">
                    Cancelar
                </a>
            </div>

        </form>

    </div>
</div>

<div id="customAlert" class="custom-alert hidden">
    <div class="alert-box">
        <p id="alertMessage"></p>
        <button onclick="document.getElementById('customAlert').classList.add('hidden')">Cerrar</button>
    </div>
</div>

<?php if (!empty($errores)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('alertMessage').textContent = <?= json_encode(implode("\n", $errores)) ?>;
        document.getElementById('customAlert').classList.remove('hidden');
    });
</script>
<?php endif; ?>

</body>
</html>