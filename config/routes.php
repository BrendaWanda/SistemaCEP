<?php
/** @var \App\Core\Router $router */
// =============================================================================
//  SIACEP — Definición de rutas
//  Archivo: config/routes.php
//  Todas las URLs del sistema se definen aquí.
//  Formato: $router->get('/ruta', 'Carpeta\Controlador@metodo')
//           $router->post('/ruta', 'Carpeta\Controlador@metodo')
// =============================================================================

// ── AUTH ──────────────────────────────────────────────────────────────────────
$router->get( '/auth/login',   'Auth\AuthController@showLogin');
$router->post('/auth/login',   'Auth\AuthController@login');
$router->get( '/auth/logout',  'Auth\AuthController@logout');
$router->get( '/auth/perfil',  'Auth\AuthController@perfil');
$router->post('/auth/perfil',  'Auth\AuthController@actualizarPerfil');
$router->post('/auth/password','Auth\AuthController@cambiarPassword');

// ── HOME / DASHBOARD DE BIENVENIDA ────────────────────────────────────────────
$router->get('/',              'M7_Dashboard\DashboardController@index');
$router->get('/dashboard',     'M7_Dashboard\DashboardController@index');

// ── USUARIOS (solo Administrador) ─────────────────────────────────────────────
$router->get( '/usuarios',              'Usuarios\UsuarioController@index');
$router->get( '/usuarios/nuevo',        'Usuarios\UsuarioController@nuevo');
$router->post('/usuarios/nuevo',        'Usuarios\UsuarioController@crear');
$router->get( '/usuarios/:id/editar',   'Usuarios\UsuarioController@editar');
$router->post('/usuarios/:id/editar',   'Usuarios\UsuarioController@actualizar');
$router->post('/usuarios/:id/toggle',   'Usuarios\UsuarioController@toggleActivo');

// ── M0 — CONFIGURACIÓN MAESTRA ───────────────────────────────────────────────

// Líneas de producción
$router->get( '/m0/lineas',                    'M0_Configuracion\LineaController@index');
$router->get( '/m0/lineas/nueva',              'M0_Configuracion\LineaController@nueva');
$router->post('/m0/lineas/nueva',              'M0_Configuracion\LineaController@crear');
$router->get( '/m0/lineas/:id/editar',         'M0_Configuracion\LineaController@editar');
$router->post('/m0/lineas/:id/editar',         'M0_Configuracion\LineaController@actualizar');
$router->post('/m0/lineas/:id/toggle',         'M0_Configuracion\LineaController@toggleActivo');

// Productos
$router->get( '/m0/productos',                 'M0_Configuracion\ProductoController@index');
$router->get( '/m0/productos/nuevo',           'M0_Configuracion\ProductoController@nuevo');
$router->post('/m0/productos/nuevo',           'M0_Configuracion\ProductoController@crear');
$router->get( '/m0/productos/:id',             'M0_Configuracion\ProductoController@ver');
$router->get( '/m0/productos/:id/editar',      'M0_Configuracion\ProductoController@editar');
$router->post('/m0/productos/:id/editar',      'M0_Configuracion\ProductoController@actualizar');
$router->post('/m0/productos/:id/toggle',      'M0_Configuracion\ProductoController@toggleActivo');

// Parámetros de proceso
$router->get( '/m0/productos/:id/parametros',         'M0_Configuracion\ParametroController@index');
$router->get( '/m0/productos/:id/parametros/nuevo',   'M0_Configuracion\ParametroController@nuevo');
$router->post('/m0/productos/:id/parametros/nuevo',   'M0_Configuracion\ParametroController@crear');
$router->get( '/m0/parametros/:id/editar',            'M0_Configuracion\ParametroController@editar');
$router->post('/m0/parametros/:id/editar',            'M0_Configuracion\ParametroController@actualizar');
$router->post('/m0/parametros/:id/eliminar',          'M0_Configuracion\ParametroController@eliminar');

// Insumos / catálogo MP
$router->get( '/m0/insumos',                   'M0_Configuracion\InsumoController@index');
$router->get( '/m0/insumos/nuevo',             'M0_Configuracion\InsumoController@nuevo');
$router->post('/m0/insumos/nuevo',             'M0_Configuracion\InsumoController@crear');
$router->get( '/m0/insumos/:id/editar',        'M0_Configuracion\InsumoController@editar');
$router->post('/m0/insumos/:id/editar',        'M0_Configuracion\InsumoController@actualizar');

// Equipos y maquinaria
$router->get( '/m0/equipos',                   'M0_Configuracion\EquipoController@index');
$router->get( '/m0/equipos/nuevo',             'M0_Configuracion\EquipoController@nuevo');
$router->post('/m0/equipos/nuevo',             'M0_Configuracion\EquipoController@crear');
$router->get( '/m0/equipos/:id/editar',        'M0_Configuracion\EquipoController@editar');
$router->post('/m0/equipos/:id/editar',        'M0_Configuracion\EquipoController@actualizar');

// Recetas / BOM
$router->get( '/m0/recetas',                   'M0_Configuracion\RecetaController@index');
$router->get( '/m0/recetas/nueva',             'M0_Configuracion\RecetaController@nueva');
$router->post('/m0/recetas/nueva',             'M0_Configuracion\RecetaController@crear');
$router->get( '/m0/recetas/:id',               'M0_Configuracion\RecetaController@ver');
$router->get( '/m0/recetas/:id/editar',        'M0_Configuracion\RecetaController@editar');
$router->post('/m0/recetas/:id/editar',        'M0_Configuracion\RecetaController@actualizar');

// ── M1 — RECEPCIÓN DE MATERIA PRIMA ──────────────────────────────────────────

$router->get( '/m1',                           'M1_RecepcionMP\RecepcionController@index');
$router->get( '/m1/nueva',                     'M1_RecepcionMP\RecepcionController@nueva');
$router->post('/m1/nueva',                     'M1_RecepcionMP\RecepcionController@guardar');
$router->get( '/m1/historial',                 'M1_RecepcionMP\RecepcionController@historial');
$router->get( '/m1/stock',                     'M1_RecepcionMP\RecepcionController@stock');
$router->get( '/m1/alertas-vencimiento',       'M1_RecepcionMP\RecepcionController@alertasVencimiento');

// Proveedores
$router->get( '/m1/proveedores',               'M1_RecepcionMP\ProveedorController@index');
$router->get( '/m1/proveedores/nuevo',         'M1_RecepcionMP\ProveedorController@nuevo');
$router->post('/m1/proveedores/nuevo',         'M1_RecepcionMP\ProveedorController@crear');
$router->get( '/m1/proveedores/:id/editar',    'M1_RecepcionMP\ProveedorController@editar');
$router->post('/m1/proveedores/:id/editar',    'M1_RecepcionMP\ProveedorController@actualizar');

// Rutas con :id AL FINAL
$router->get( '/m1/:id',                       'M1_RecepcionMP\RecepcionController@ver');
$router->get( '/m1/:id/imprimir',              'M1_RecepcionMP\RecepcionController@imprimir');
// ── M2 — REGISTRO DE PROCESO ──────────────────────────────────────────────────
// ── M2 — REGISTRO DE PROCESO ──
$router->get( '/m2',                        'M2_RegistroProceso\SesionRegistroController@index');
$router->get( '/m2/nueva-sesion',           'M2_RegistroProceso\SesionRegistroController@nuevaSesion');
$router->post('/m2/nueva-sesion',           'M2_RegistroProceso\SesionRegistroController@crearSesion');
// Rutas con :id AL FINAL — igual que en M1
$router->get( '/m2/sesion/:id',             'M2_RegistroProceso\SesionRegistroController@ver');
$router->get( '/m2/sesion/:id/imprimir',    'M2_RegistroProceso\SesionRegistroController@imprimir');
$router->post('/m2/sesion/:id/amasado',     'M2_RegistroProceso\AmasadoController@guardar');
$router->post('/m2/sesion/:id/pesos',       'M2_RegistroProceso\PesosController@guardar');
$router->get( '/m2/sesion/:id/pesos/datos', 'M2_RegistroProceso\PesosController@datos');
$router->post('/m2/sesion/:id/horneado',    'M2_RegistroProceso\HorneadoController@guardar');
$router->post('/m2/sesion/:id/envasado',    'M2_RegistroProceso\EnvasadoController@guardar');
$router->post('/m2/sesion/:id/liberacion',  'M2_RegistroProceso\LiberacionController@guardar');

// ── M3 — MANTENIMIENTO ────────────────────────────────────────────────────────
// ── M3 — MANTENIMIENTO ────────────────────────────────────────────────────────
$router->get( '/m3',                'M3_Mantenimiento\MantenimientoController@index');
$router->get( '/m3/nuevo',          'M3_Mantenimiento\MantenimientoController@nuevo');
$router->post('/m3/nuevo',          'M3_Mantenimiento\MantenimientoController@guardar');
$router->get( '/m3/alertas',        'M3_Mantenimiento\MantenimientoController@alertas');
$router->get( '/m3/calendario',     'M3_Mantenimiento\MantenimientoController@calendario');
// Rutas con :id AL FINAL
$router->get( '/m3/:id',            'M3_Mantenimiento\MantenimientoController@ver');
$router->get( '/m3/:id/editar',     'M3_Mantenimiento\MantenimientoController@editar');
$router->post('/m3/:id/editar',     'M3_Mantenimiento\MantenimientoController@actualizar');

// ── M4 — SEGUIMIENTO DE PRODUCCIÓN ───────────────────────────────────────────
$router->get( '/m4',                           'M4_SeguimientoProduccion\LoteController@index');
$router->get( '/m4/nuevo-lote',                'M4_SeguimientoProduccion\LoteController@nuevoLote');
$router->post('/m4/nuevo-lote',                'M4_SeguimientoProduccion\LoteController@crearLote');
$router->get( '/m4/lote/:codigo',              'M4_SeguimientoProduccion\LoteController@ver');
$router->post('/m4/lote/:codigo/cerrar',       'M4_SeguimientoProduccion\LoteController@cerrarLote');
$router->get( '/m4/lote/:codigo/imprimir',     'M4_SeguimientoProduccion\LoteController@imprimir');

// Rendimiento y mermas
$router->post('/m4/lote/:codigo/rendimiento',  'M4_SeguimientoProduccion\RendimientoController@guardar');
$router->get( '/m4/lote/:codigo/rendimiento',  'M4_SeguimientoProduccion\RendimientoController@ver');

// Consumo de MP por lote
$router->get( '/m4/lote/:codigo/mp',           'M4_SeguimientoProduccion\ConsumoMPController@index');
$router->post('/m4/lote/:codigo/mp/agregar',   'M4_SeguimientoProduccion\ConsumoMPController@agregar');
$router->post('/m4/lote/:codigo/mp/:id/eliminar','M4_SeguimientoProduccion\ConsumoMPController@eliminar');

// Tiempos por etapa
$router->post('/m4/lote/:codigo/tiempos',      'M4_SeguimientoProduccion\TiemposController@guardar');

// ── M5 — TRAZABILIDAD ─────────────────────────────────────────────────────────
$router->get( '/m5',                           'M5_Trazabilidad\TrazabilidadController@index');
$router->get( '/m5/lote/:codigo',              'M5_Trazabilidad\TrazabilidadController@lote');
$router->get( '/m5/insumo/:codigo',            'M5_Trazabilidad\TrazabilidadController@insumo');
$router->get( '/m5/lote/:codigo/pdf',          'M5_Trazabilidad\TrazabilidadController@exportarPdf');

// ── M6 — SPC ──────────────────────────────────────────────────────────────────
$router->get( '/m6',                           'M6_SPC\SPCController@index');
$router->get( '/m6/xbar-r',                    'M6_SPC\GraficoXbarRController@index');
$router->post('/m6/xbar-r/datos',              'M6_SPC\GraficoXbarRController@datos');   // JSON para Chart.js
$router->get( '/m6/capacidad',                 'M6_SPC\CapacidadController@index');
$router->post('/m6/capacidad/calcular',        'M6_SPC\CapacidadController@calcular');   // JSON
$router->get( '/m6/pareto',                    'M6_SPC\ParetoController@index');
$router->post('/m6/pareto/datos',              'M6_SPC\ParetoController@datos');
$router->get( '/m6/normalidad',                'M6_SPC\NormalidadController@index');
$router->post('/m6/normalidad/calcular',       'M6_SPC\NormalidadController@calcular');
$router->get( '/m6/estadistica-descriptiva',   'M6_SPC\EstadisticaController@index');
$router->post('/m6/estadistica-descriptiva/calcular', 'M6_SPC\EstadisticaController@calcular');
$router->get( '/m6/senales',                   'M6_SPC\SenalesController@index');
$router->post('/m6/senales/:id/resolver',      'M6_SPC\SenalesController@resolver');
$router->get( '/m6/analisis-guardados',        'M6_SPC\AnalisisGuardadoController@index');
$router->post('/m6/analisis-guardados/guardar','M6_SPC\AnalisisGuardadoController@guardar');
$router->get( '/m6/analisis-guardados/:id',    'M6_SPC\AnalisisGuardadoController@ver');

// ── M7 — DASHBOARD / KPIs ─────────────────────────────────────────────────────
$router->get( '/m7',                           'M7_Dashboard\DashboardController@index');
$router->get( '/m7/oee',                       'M7_Dashboard\OEEController@index');
$router->post('/m7/oee/datos',                 'M7_Dashboard\OEEController@datos');       // JSON
$router->get( '/m7/produccion',                'M7_Dashboard\ProduccionController@index');
$router->get( '/m7/calidad',                   'M7_Dashboard\CalidadController@index');
$router->get( '/m7/mp',                        'M7_Dashboard\MPController@index');

// ── MENÚ TRIMESTRAL ───────────────────────────────────────────────────────────
$router->get( '/menu',                         'Menu_Trimestral\MenuController@index');
$router->get( '/menu/nuevo',                   'Menu_Trimestral\MenuController@nuevo');
$router->post('/menu/nuevo',                   'Menu_Trimestral\MenuController@crear');
$router->get( '/menu/:id',                     'Menu_Trimestral\MenuController@ver');
$router->get( '/menu/:id/cronograma',          'Menu_Trimestral\CronogramaController@index');
$router->post('/menu/:id/cronograma',          'Menu_Trimestral\CronogramaController@guardar');
$router->get( '/menu/:id/cronograma/imprimir', 'Menu_Trimestral\CronogramaController@imprimir');
$router->post('/menu/:id/siremu/enviar',       'Menu_Trimestral\CronogramaController@registrarEnvioSiremu');

// ── REPORTES ──────────────────────────────────────────────────────────────────
$router->get( '/reportes',                          'Reportes\ReporteController@index');
$router->post('/reportes/recepcion-mp',             'Reportes\ReporteController@recepcionMP');
$router->post('/reportes/control-proceso',          'Reportes\ReporteController@controlProceso');
$router->post('/reportes/liberacion-lote',          'Reportes\ReporteController@liberacionLote');
$router->post('/reportes/seguimiento-produccion',   'Reportes\ReporteController@seguimientoProduccion');
$router->post('/reportes/spc-xbar',                 'Reportes\ReporteController@spcXbar');
$router->post('/reportes/capacidad-proceso',        'Reportes\ReporteController@capacidadProceso');
$router->post('/reportes/mantenimiento',            'Reportes\ReporteController@mantenimiento');
$router->post('/reportes/calibraciones',            'Reportes\ReporteController@calibraciones');
$router->post('/reportes/trazabilidad-lote',        'Reportes\ReporteController@trazabilidadLote');
$router->post('/reportes/kpi-periodo',              'Reportes\ReporteController@kpiPeriodo');

// ── API AJAX (endpoints internos para JavaScript) ─────────────────────────────
$router->get( '/api/productos-por-linea',      'M0_Configuracion\ProductoController@porLinea');
$router->get( '/api/recetas-por-producto',     'M0_Configuracion\RecetaController@porProducto');
$router->get( '/api/parametros-por-producto',  'M0_Configuracion\ParametroController@porProducto');
$router->get( '/api/stock-disponible',         'M1_RecepcionMP\RecepcionController@stockDisponible');
$router->get( '/api/lotes-activos',            'M4_SeguimientoProduccion\LoteController@activos');
$router->get( '/api/spc-tiempo-real/:sesion',  'M6_SPC\SPCController@tiempoReal');
$router->get( '/api/kpi-resumen',              'M7_Dashboard\DashboardController@kpiResumen');