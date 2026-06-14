<?php
/** @var \App\Core\Router $router */
// =============================================================================
//  SIACEP — Definición de rutas
//  Archivo: config/routes.php
// =============================================================================

// ── AUTH ──────────────────────────────────────────────────────────────────────
$router->get( '/auth/login',    'Auth\AuthController@showLogin');
$router->post('/auth/login',    'Auth\AuthController@login');
$router->get( '/auth/logout',   'Auth\AuthController@logout');
$router->get( '/auth/perfil',   'Auth\AuthController@perfil');
$router->post('/auth/perfil',   'Auth\AuthController@actualizarPerfil');
$router->post('/auth/password', 'Auth\AuthController@cambiarPassword');

// ── HOME / DASHBOARD OPERATIVO ────────────────────────────────────────────────
$router->get('/',          'M7_Dashboard\DashboardController@index');
$router->get('/dashboard', 'M7_Dashboard\DashboardController@index');

// ── KPIs Y OEE ───────────────────────────────────────────────────────────────
$router->get('/m7', 'M7_Dashboard\DashboardController@kpi');

// ── USUARIOS ──────────────────────────────────────────────────────────────────
$router->get( '/usuarios',            'Usuarios\UsuarioController@index');
$router->get( '/usuarios/nuevo',      'Usuarios\UsuarioController@nuevo');
$router->post('/usuarios/nuevo',      'Usuarios\UsuarioController@crear');
$router->get( '/usuarios/:id/editar', 'Usuarios\UsuarioController@editar');
$router->post('/usuarios/:id/editar', 'Usuarios\UsuarioController@actualizar');
$router->post('/usuarios/:id/toggle', 'Usuarios\UsuarioController@toggleActivo');

// ── M0 — CONFIGURACIÓN MAESTRA ───────────────────────────────────────────────

// Líneas
$router->get( '/m0/lineas',            'M0_Configuracion\LineaController@index');
$router->get( '/m0/lineas/nueva',      'M0_Configuracion\LineaController@nueva');
$router->post('/m0/lineas/nueva',      'M0_Configuracion\LineaController@crear');
$router->get( '/m0/lineas/:id/editar', 'M0_Configuracion\LineaController@editar');
$router->post('/m0/lineas/:id/editar', 'M0_Configuracion\LineaController@actualizar');
$router->post('/m0/lineas/:id/toggle', 'M0_Configuracion\LineaController@toggleActivo');

// Productos — fijas antes que :id
$router->get( '/m0/productos',             'M0_Configuracion\ProductoController@index');
$router->get( '/m0/productos/nuevo',       'M0_Configuracion\ProductoController@nuevo');
$router->post('/m0/productos/nuevo',       'M0_Configuracion\ProductoController@crear');
$router->get( '/m0/productos/:id/editar',  'M0_Configuracion\ProductoController@editar');
$router->post('/m0/productos/:id/editar',  'M0_Configuracion\ProductoController@actualizar');
$router->post('/m0/productos/:id/toggle',  'M0_Configuracion\ProductoController@toggleActivo');
// Parámetros — fijas antes que :id
$router->get( '/m0/productos/:id/parametros',       'M0_Configuracion\ParametroController@index');
$router->get( '/m0/productos/:id/parametros/nuevo', 'M0_Configuracion\ParametroController@nuevo');
$router->post('/m0/productos/:id/parametros/nuevo', 'M0_Configuracion\ParametroController@crear');
$router->get( '/m0/parametros/:id/editar',          'M0_Configuracion\ParametroController@editar');
$router->post('/m0/parametros/:id/editar',          'M0_Configuracion\ParametroController@actualizar');
$router->post('/m0/parametros/:id/eliminar',        'M0_Configuracion\ParametroController@eliminar');
// :id al final
$router->get( '/m0/productos/:id',         'M0_Configuracion\ProductoController@ver');

// Insumos
$router->get( '/m0/insumos',            'M0_Configuracion\InsumoController@index');
$router->get( '/m0/insumos/nuevo',      'M0_Configuracion\InsumoController@nuevo');
$router->post('/m0/insumos/nuevo',      'M0_Configuracion\InsumoController@crear');
$router->get( '/m0/insumos/:id/editar', 'M0_Configuracion\InsumoController@editar');
$router->post('/m0/insumos/:id/editar', 'M0_Configuracion\InsumoController@actualizar');

// Equipos
$router->get( '/m0/equipos',            'M0_Configuracion\EquipoController@index');
$router->get( '/m0/equipos/nuevo',      'M0_Configuracion\EquipoController@nuevo');
$router->post('/m0/equipos/nuevo',      'M0_Configuracion\EquipoController@crear');
$router->get( '/m0/equipos/:id/editar', 'M0_Configuracion\EquipoController@editar');
$router->post('/m0/equipos/:id/editar', 'M0_Configuracion\EquipoController@actualizar');

// Recetas — fijas antes que :id
$router->get( '/m0/recetas',            'M0_Configuracion\RecetaController@index');
$router->get( '/m0/recetas/nueva',      'M0_Configuracion\RecetaController@nueva');
$router->post('/m0/recetas/nueva',      'M0_Configuracion\RecetaController@crear');
$router->get( '/m0/recetas/:id/editar', 'M0_Configuracion\RecetaController@editar');
$router->post('/m0/recetas/:id/editar', 'M0_Configuracion\RecetaController@actualizar');
$router->get( '/m0/recetas/:id',        'M0_Configuracion\RecetaController@ver');

// ── M1 — RECEPCIÓN DE MATERIA PRIMA ──────────────────────────────────────────
// Fijas antes que :id
$router->get( '/m1',                     'M1_RecepcionMP\RecepcionController@index');
$router->get( '/m1/nueva',               'M1_RecepcionMP\RecepcionController@nueva');
$router->post('/m1/nueva',               'M1_RecepcionMP\RecepcionController@guardar');
$router->get( '/m1/historial',           'M1_RecepcionMP\RecepcionController@historial');
$router->get( '/m1/stock',               'M1_RecepcionMP\RecepcionController@stock');
$router->get( '/m1/alertas-vencimiento', 'M1_RecepcionMP\RecepcionController@alertasVencimiento');
$router->get( '/m1/proveedores',               'M1_RecepcionMP\ProveedorController@index');
$router->get( '/m1/proveedores/nuevo',         'M1_RecepcionMP\ProveedorController@nuevo');
$router->post('/m1/proveedores/nuevo',         'M1_RecepcionMP\ProveedorController@crear');
$router->get( '/m1/proveedores/:id/editar',    'M1_RecepcionMP\ProveedorController@editar');
$router->post('/m1/proveedores/:id/editar',    'M1_RecepcionMP\ProveedorController@actualizar');
// :id al final
$router->get( '/m1/:id',         'M1_RecepcionMP\RecepcionController@ver');
$router->get( '/m1/:id/imprimir','M1_RecepcionMP\RecepcionController@imprimir');

// ── M2 — REGISTRO DE PROCESO ─────────────────────────────────────────────────
// Fijas antes que :id
$router->get( '/m2',              'M2_RegistroProceso\SesionRegistroController@index');
$router->get( '/m2/nueva-sesion', 'M2_RegistroProceso\SesionRegistroController@nuevaSesion');
$router->post('/m2/nueva-sesion', 'M2_RegistroProceso\SesionRegistroController@crearSesion');
// :id al final
$router->get( '/m2/sesion/:id',             'M2_RegistroProceso\SesionRegistroController@ver');
$router->get( '/m2/sesion/:id/imprimir',    'M2_RegistroProceso\SesionRegistroController@imprimir');
$router->post('/m2/sesion/:id/liberacion',  'M2_RegistroProceso\LiberacionController@guardar');
$router->post('/m2/sesion/:id/inspeccion-atributos', 'M2_RegistroProceso\InspeccionAtributosController@guardar');
$router->post('/m2/sesion/:id/valores',                    'M2_RegistroProceso\RegistroDinamicoController@guardarValores');
$router->post('/m2/sesion/:id/subgrupo',                   'M2_RegistroProceso\RegistroDinamicoController@guardarSubgrupo');
$router->get( '/m2/sesion/:id/subgrupo/:parametro_id/datos','M2_RegistroProceso\RegistroDinamicoController@datosGrafico');

// ── M3 — MANTENIMIENTO ────────────────────────────────────────────────────────
// Fijas antes que :id
$router->get( '/m3',            'M3_Mantenimiento\MantenimientoController@index');
$router->get( '/m3/nuevo',      'M3_Mantenimiento\MantenimientoController@nuevo');
$router->post('/m3/nuevo',      'M3_Mantenimiento\MantenimientoController@guardar');
$router->get( '/m3/alertas',    'M3_Mantenimiento\MantenimientoController@alertas');
$router->get( '/m3/calendario', 'M3_Mantenimiento\MantenimientoController@calendario');
// :id al final
$router->get( '/m3/:id',        'M3_Mantenimiento\MantenimientoController@ver');
$router->get( '/m3/:id/editar', 'M3_Mantenimiento\MantenimientoController@editar');
$router->post('/m3/:id/editar', 'M3_Mantenimiento\MantenimientoController@actualizar');

// ── M4 — SEGUIMIENTO DE PRODUCCIÓN ───────────────────────────────────────────
// Fijas antes que :codigo
$router->get( '/m4',            'M4_SeguimientoProduccion\LoteController@index');
$router->get( '/m4/nuevo-lote', 'M4_SeguimientoProduccion\LoteController@nuevoLote');
$router->post('/m4/nuevo-lote', 'M4_SeguimientoProduccion\LoteController@crearLote');
// :codigo al final
$router->get( '/m4/lote/:codigo',                'M4_SeguimientoProduccion\LoteController@ver');
$router->post('/m4/lote/:codigo/cerrar',          'M4_SeguimientoProduccion\LoteController@cerrarLote');
$router->get( '/m4/lote/:codigo/imprimir',        'M4_SeguimientoProduccion\LoteController@imprimir');
$router->get( '/m4/lote/:codigo/rendimiento',     'M4_SeguimientoProduccion\RendimientoController@ver');
$router->post('/m4/lote/:codigo/rendimiento',     'M4_SeguimientoProduccion\RendimientoController@guardar');
$router->get( '/m4/lote/:codigo/mp',              'M4_SeguimientoProduccion\ConsumoMPController@index');
$router->post('/m4/lote/:codigo/mp/agregar',      'M4_SeguimientoProduccion\ConsumoMPController@agregar');
$router->post('/m4/lote/:codigo/mp/:id/eliminar', 'M4_SeguimientoProduccion\ConsumoMPController@eliminar');

// ── M5 — TRAZABILIDAD ────────────────────────────────────────────────────────
$router->get('/m5',                  'M5_Trazabilidad\TrazabilidadController@index');
$router->get('/m5/lote/:codigo/pdf', 'M5_Trazabilidad\TrazabilidadController@exportarPdf');
$router->get('/m5/lote/:codigo',     'M5_Trazabilidad\TrazabilidadController@lote');
$router->get('/m5/insumo/:codigo',   'M5_Trazabilidad\TrazabilidadController@insumo');

// ── M6: Control Estadístico de Proceso (SPC) ──
$router->get('/m6',         'M6_SPC\SpcController@index');
$router->get('/m6/analizar','M6_SPC\SpcController@analizar');

// ── MENÚ TRIMESTRAL ───────────────────────────────────────────────────────────
// Fijas antes que :id
$router->get( '/menu-trimestral',            'MenuTrimestral\MenuTrimestralController@index');
$router->get( '/menu-trimestral/nuevo',      'MenuTrimestral\MenuTrimestralController@nuevo');
$router->post('/menu-trimestral/nuevo',      'MenuTrimestral\MenuTrimestralController@guardar');
// :id al final
$router->get( '/menu-trimestral/:id',        'MenuTrimestral\MenuTrimestralController@ver');
$router->get( '/menu-trimestral/:id/editar', 'MenuTrimestral\MenuTrimestralController@editar');
$router->post('/menu-trimestral/:id/editar', 'MenuTrimestral\MenuTrimestralController@actualizar');

// ── REPORTES ──────────────────────────────────────────────────────────────────
$router->get( '/reportes',                        'Reportes\ReporteController@index');
$router->post('/reportes/recepcion-mp',           'Reportes\ReporteController@recepcionMP');
$router->post('/reportes/control-proceso',        'Reportes\ReporteController@controlProceso');
$router->post('/reportes/liberacion-lote',        'Reportes\ReporteController@liberacionLote');
$router->post('/reportes/seguimiento-produccion', 'Reportes\ReporteController@seguimientoProduccion');
$router->post('/reportes/spc-xbar',               'Reportes\ReporteController@spcXbar');
$router->post('/reportes/capacidad-proceso',      'Reportes\ReporteController@capacidadProceso');
$router->post('/reportes/mantenimiento',          'Reportes\ReporteController@mantenimiento');
$router->post('/reportes/calibraciones',          'Reportes\ReporteController@calibraciones');
$router->post('/reportes/trazabilidad-lote',      'Reportes\ReporteController@trazabilidadLote');
$router->post('/reportes/kpi-periodo',            'Reportes\ReporteController@kpiPeriodo');

// ── APIs INTERNAS ─────────────────────────────────────────────────────────────
$router->get('/api/productos-por-linea',     'M0_Configuracion\ProductoController@porLinea');
$router->get('/api/recetas-por-producto',    'M0_Configuracion\RecetaController@porProducto');
$router->get('/api/parametros-por-producto', 'M0_Configuracion\ParametroController@porProducto');
$router->get('/api/stock-disponible',        'M1_RecepcionMP\RecepcionController@stockDisponible');
$router->get('/api/lotes-activos',           'M4_SeguimientoProduccion\LoteController@activos');
$router->get('/api/spc-tiempo-real/:sesion', 'M6_SPC\SPCController@tiempoReal');
$router->get('/api/kpi-resumen',             'M7_Dashboard\DashboardController@kpiResumen');