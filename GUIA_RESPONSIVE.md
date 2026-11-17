# Guía de adaptación responsive — FUNDACITE

## Objetivo de implementación

La interfaz conserva la composición de escritorio: formularios centrados, dimensiones base de los paneles, jerarquía visual institucional, tablas con sus columnas y botones originales. En tabletas y teléfonos se reduce el ancho disponible, se apilan los campos cuando ya no caben con comodidad y las tablas mantienen su estructura y solo muestran desplazamiento horizontal dentro de su contenedor cuando el contenido supera el ancho disponible.

## Puntos de adaptación

- **Escritorio (más de 992 px):** se conserva la disposición de escritorio. Los formularios `.form-card` mantienen su ancho base de 850 px y su límite original; las restricciones específicas que cada vista coloca en línea siguen teniendo prioridad.
- **Tablet (hasta 992 px):** el menú lateral se convierte en panel deslizable, el contenido deja de reservar espacio para la barra lateral y las tarjetas/listados ocupan el ancho útil.
- **Teléfono (hasta 768 px):** los formularios y sus campos pasan a una columna, los buscadores y acciones se apilan, y las tablas se desplazan horizontalmente sin convertirse en tarjetas ni perder columnas.
- **Teléfono pequeño (hasta 480 px):** se compactan los márgenes y el relleno de los paneles para aprovechar el ancho sin que los controles se salgan de pantalla.

## Ajuste final de `main` y botones

Se añadió un bloque final de reglas en `vistas/css/style_dashboard.css` para que los últimos ajustes tengan prioridad sobre las reglas responsive históricas sin modificar el escritorio:

- **Escritorio (más de 992 px):** no se agregan reglas que cambien el ancho, alto, padding, tipografía o posición normal de los botones. La composición original del `.main` y los tamaños de los formularios se conservan.
- **Tabletas (hasta 992 px):** `.main` ocupa el ancho disponible, elimina el margen reservado al sidebar y mantiene los paneles centrados, con un límite de ancho para evitar desbordamientos.
- **Móviles (hasta 768 px):** `.main` centra sus módulos y usa todo el ancho útil. Las acciones de formularios, búsquedas y accesos se ordenan en filas verticales con altura táctil consistente.
- **Botones de tablas:** Ver, Editar, Inactivar, Aprobar, Desaprobar y Desvincular se apilan dentro de la celda de acciones; la tabla mantiene sus columnas y su desplazamiento interno.
- **Botones especiales:** Asignar cargo, Quitar hijo, acciones de constancias y controles de alertas reciben medidas y alineación específicas para pantallas pequeñas.
- **Pulso táctil:** los controles principales responden al toque con una escala breve, sin cambiar su tamaño de reposo.

## Revisión por módulos

| Módulo | Vistas revisadas | Comportamiento responsive aplicado |
|---|---|---|
| Inicio y acceso | `index.php`, `dashboard.php` | Login adaptable; menú lateral móvil; tarjetas y gráfico que reorganizan sus columnas. |
| Trabajadores | `lista_trabajadores.php`, `registrar_trabajadores.php`, `editar_trabajador.php`, `ver_trabajador.php` | Formularios centrados; grupos de campos apilados; secciones de detalle fluidas; listado con scroll horizontal. |
| Cargo y asignaciones | `registrar_cargo.php`, `editar_cargo.php`, `asignar_cargo.php`, `registrar_asignacion_cargo.php`, `historial_asignaciones_cargo.php` | Formularios de cargo y asignación adaptables; historial y listados conservan columnas y se desplazan en móvil. |
| Dirección | `registro_estados.php`, `registro_municipios.php`, `registro_parroquia.php`, `registro_direccion.php`, `editar_parroquia.php` | Contenedores centrados; pares Estado/Municipio apilados en móvil; tablas de ubicación con scroll interno. |
| Contratos | `lista_contrato.php`, `registrar_contrato.php`, `editar_contrato.php`, `ver_contrato.php` | Campos del contrato en una columna en pantallas estrechas; vista de detalle fluida; tabla desplazable. |
| Gremios y convenios | `lista_gremio.php`, `registrar_gremio.php`, `lista_convenio.php`, `registrar_convenio.php`, `editar_convenio.php` | Listados, filtros, formularios y botones reorganizados sin alterar sus acciones. |
| Usuarios | `lista_usuarios.php`, `registrar_usuario.php`, `editar_usuario.php` | Campos, controles de contraseña, buscadores y acciones ajustados al ancho disponible. |
| Salarios | `lista_salario.php`, `registrar_salario.php`, `editar_salario.php`, `actualizar_salario.php` | Formularios centrados; controles de montos fluidos; tabla de actualización con marco de desplazamiento en móvil. |
| Primas | `lista_primas.php`, `lista_nuevas_primas.php`, `registrar_prima.php`, `registrar_primas_nuevo.php`, `ver_prima.php`, `actualizar_porcentaje_prima.php` | Listados con scroll interno; campos de montos y grupos dinámicos adaptados; detalles fluidos. |
| Constancias | `lista_constancias.php`, `registrar_constancia.php`, `editar_constancia.php`, `ver_constancia.php` | Formularios y tarjetas de detalle ajustados al ancho; acciones apiladas en móvil. |
| Días de disfrute | `lista_dias_disfrute.php`, `registrar_dias_disfrute.php`, `aprobacion_dias_disfrute.php`, `ver_dias_disfrute.php` | Solicitudes y paneles de aprobación fluidos; filtros y botones en columna; listados desplazables. |

## Archivos compartidos modificados

- `vistas/css/style_dashboard.css`: reglas globales para anchos, centrado, `.main`, formularios, tablas, listados, botones de todos los módulos y puntos de adaptación.
- `vistas/css/styles.css`: adaptación del acceso sin modificar el diseño de escritorio.
- `vistas/js/responsive-ui.js`: menú accesible en móvil y envoltura de tablas para desplazamiento; no transforma filas ni columnas.
- `vistas/includes/topbar.php` y `vistas/includes/layout.php`: botón de navegación móvil y capa de cierre del menú.
- Vistas PHP que cargan `style_dashboard.css`: metadato `viewport` para que el navegador use el ancho real del dispositivo.

## Cómo comprobarlo

Probar como mínimo en 1440 px, 1366 px, 1024 px, 820 px, 768 px, 390 px y 320 px. Confirmar que en escritorio se mantienen los tamaños originales de formularios y botones; que en tablet/celular el `.main` mantiene los módulos centrados; que las acciones de formularios/listados se apilan sin salirse de pantalla; que el menú puede abrirse y cerrarse; y que el scroll horizontal aparece en la tabla, no en toda la página. La sintaxis CSS y los archivos PHP/JavaScript fueron comprobados; la vista real debe verificarse también en los navegadores de destino.
