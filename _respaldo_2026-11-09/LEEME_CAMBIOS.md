# Respaldo y registro de cambios — SIACEP (09/11/2026)

Esta carpeta contiene las versiones ORIGINALES (antes de la corrección) de los
37 archivos que se modificaron. Para revertir un cambio, copie el archivo de
aquí a la misma ruta dentro de `C:\xampp\htdocs\siacep\`.

## Errores corregidos

1. **Rutas faltantes (404)** — `config/routes.php`
   - Los botones "Eliminar" de Líneas, Productos, Insumos, Equipos y Recetas
     apuntaban a rutas que no existían (`/m0/.../:id/eliminar`).
   - "Ver insumo" (`/m0/insumos/:id`) no tenía ruta.
   - Se quitó una ruta duplicada (`/api/parametros-insumo`) y una que apuntaba
     a un método inexistente (`/api/spc-tiempo-real`, no usada en ninguna vista).

2. **APIs bloqueadas para Calidad y Producción** — `app/Core/Controller.php` y
   4 controladores de M0
   - `/api/parametros-insumo` (usada en M1 Nueva recepción),
     `/api/recetas-por-producto` (M4 Nuevo lote), `/api/productos-por-linea` y
     `/api/parametros-por-producto` exigían permiso de M0, que esos roles no
     tienen. Resultado: en M1 no cargaban los parámetros fisicoquímicos y en M4
     no cargaban las recetas. Ahora las rutas `/api/` solo exigen sesión
     iniciada (nuevo helper `requireModuloOApi()`).

3. **Mi perfil** — faltaba la vista `app/Views/auth/perfil.php` (el enlace del
   sidebar mostraba "Vista no encontrada"). Se creó con edición de datos y
   cambio de contraseña.

4. **Parámetros de insumo (M0)** — las vistas `parametros_insumo/index.php` y
   `form.php` incluían `_nav.php` con una ruta equivocada (`../../`), lo que
   causaba error fatal al abrirlas.

5. **Trazabilidad M5** — `lote.php`, `pdf.php` y `Models/Trazabilidad.php`
   todavía leían columnas eliminadas en la limpieza de BD (`total_pesos`,
   `total_horneado`, `resultado_humedad_pct`, `codif_lote_legible`, `inocuo`,
   etc.). Ahora muestran subgrupos SPC, valores simples e inspección de
   atributos (carta p) agregados por lote.

6. **M2 Registro de proceso** — `sesion.php` cargaba
   `assets/vendor/chart.min.js`, que no existía: los gráficos X̄ de la sesión no
   se dibujaban. Se agregó `public/assets/vendor/chart.min.js` (Chart.js 4.4.0,
   misma versión que M6/M7, funciona sin internet).

7. **M4 MP consumida** — para usuarios de solo lectura (Gerente, Calidad) la
   página lanzaba un error de JavaScript porque el script buscaba un formulario
   que no se muestra a esos roles.

8. **M6 SPC (pantalla)** — con muchos subgrupos la página se ensanchaba más
   que la ventana y el contenido quedaba tapado por el sidebar.
   Corregido en `app.css` (`.main-content { min-width: 0 }`) y en la grilla de
   índices de capacidad de `m6_spc/index.php`.

9. **M3 Mantenimiento** — el detalle mostraba `#id` del lote afectado en lugar
   del código de lote (se agregó el JOIN en `Models/Mantenimiento.php`).

10. **Página de error 500** — el Router la referenciaba pero no existía
    (`app/Views/layouts/error_500.php`).

## Reglas del proyecto aplicadas

- Se reemplazaron los 152 emojis restantes por iconos Bootstrap Icons
  (o se quitaron en mensajes de texto plano como `alert()`/`confirm()`).
- Los reportes imprimibles y el PDF de trazabilidad ahora cargan la hoja de
  Bootstrap Icons para mostrar esos iconos.

## Cambio adicional (09/11/2026, 16:30)

- **Administrador con acceso total** — `app/Core/Auth.php`: el rol
  Administrador pasa a tener lectura y escritura (`rw`) en todos los módulos
  (antes era solo lectura en M1, M2, M5 y M7). Permite hacer la demostración
  completa con un solo usuario. Los demás roles no cambian. Original en
  `_respaldo_2026-11-09\app\Core\Auth.php`.

## Cambio adicional (09/11/2026, 16:50) — Eliminar en Mantenimiento (M3)

- Nueva ruta `POST /m3/:id/eliminar` (`config/routes.php`) y método
  `eliminar()` en `MantenimientoController.php`.
- Botón **Eliminar** (con confirmación) en la lista `m3_mantenimiento/index.php`
  y en el detalle `m3_mantenimiento/ver.php`. Solo aparece para roles con
  escritura en M3 (Administrador y Producción).
- Al eliminar, `Equipo::recalcularFechas()` vuelve a calcular la fecha del
  último/próximo mantenimiento y calibración del equipo con los registros que
  quedan, para que las alertas no sigan usando la fecha del registro borrado.
- Originales en este respaldo (el `index.php` de M3 original ya estaba aquí).

## Cambio adicional (09/11/2026, 17:00) — Eliminar lote en Seguimiento de Producción (M4)

- Nueva ruta `POST /m4/lote/:codigo/eliminar` y método `eliminar()` en
  `LoteController.php`.
- Botón **Eliminar** en la lista de lotes (`m4_seguimiento/index.php`) y botón
  **Eliminar lote** en la ficha del lote (`ver_lote.php`). La ficha muestra en
  la confirmación cuántas sesiones, subgrupos SPC, inspecciones, señales y
  consumos de MP se borrarán. Solo lo ven roles con escritura en M4
  (Administrador y Producción).
- `LoteProduccion::eliminarConDependencias()` borra en una sola transacción:
  consumo de MP (devolviendo la cantidad al stock), sesiones de registro con
  sus subgrupos SPC, valores simples, inspecciones de atributos, liberación y
  señales SPC, tiempos de producción y eventos de trazabilidad. Los
  mantenimientos vinculados al lote NO se borran: solo se desvinculan.
- Originales de estos archivos en este respaldo.
