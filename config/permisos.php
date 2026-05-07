<?php
// =============================================================================
//  SIACEP — Mapa de permisos por prefijo de ruta
//  Archivo: config/permisos.php
//  El middleware de Auth verifica este mapa antes de despachar cada ruta.
//  Cada prefijo de URL se mapea al módulo correspondiente en Auth::PERMISOS.
// =============================================================================

return [
    '/auth'     => null,             // Público (login/logout)
    '/m0'       => 'm0_configuracion',
    '/usuarios' => 'usuarios',
    '/m1'       => 'm1_recepcion_mp',
    '/m2'       => 'm2_registro_proceso',
    '/m3'       => 'm3_mantenimiento',
    '/m4'       => 'm4_seguimiento',
    '/m5'       => 'm5_trazabilidad',
    '/m6'       => 'm6_spc',
    '/m7'       => 'm7_dashboard',
    '/menu'     => 'menu_trimestral',
    '/reportes' => 'reportes',
    '/api'      => null,             // Los endpoints API verifican internamente
    '/'         => null,             // Dashboard raíz — solo requiere login
];