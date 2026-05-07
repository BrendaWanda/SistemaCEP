-- =============================================================================
--  SIACEP — Sistema Integrado de Análisis, Control y Ejecución de Producción
--  Industrias Alimenticias Gustossi SRL
--  Archivo: siacep_schema.sql
--  Versión: 1.0
--  Descripción: Esquema completo de la base de datos relacional.
--               El campo `codigo_lote` en `lotes_produccion` es el eje
--               vertebral de trazabilidad de todo el sistema.
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "-04:00"; -- UTC-4 Bolivia
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS siacep
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_spanish_ci;

USE siacep;


-- =============================================================================
--  SECCIÓN 0 — USUARIOS Y ROLES (Transversal)
-- =============================================================================

CREATE TABLE roles (
    id              TINYINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(60)         NOT NULL,
    descripcion     VARCHAR(200)        NULL,
    creado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB COMMENT='Roles del sistema SIACEP';

-- Datos iniciales de roles
INSERT INTO roles (nombre, descripcion) VALUES
('Administrador / Encargado D.E.', 'Acceso total. Gestiona configuración maestra, menú trimestral y usuarios.'),
('Gerente',                        'Lectura total de todos los módulos. Sin modificación de configuración.'),
('Producción',                     'Registro en M1, M3, M4. Lectura de M5, M6, M7. Reportes.'),
('Control de Calidad',             'Registro en M1, M2. Lectura de M4, M5, M6, M7. Reportes.');

-- ---------------------------------------------------------------------------

CREATE TABLE usuarios (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    rol_id          TINYINT UNSIGNED    NOT NULL,
    nombre          VARCHAR(100)        NOT NULL,
    apellidos       VARCHAR(100)        NOT NULL,
    cargo           VARCHAR(100)        NULL     COMMENT 'Cargo real en la empresa',
    email           VARCHAR(150)        NOT NULL,
    password_hash   VARCHAR(255)        NOT NULL,
    activo          TINYINT(1)          NOT NULL DEFAULT 1,
    creado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE=InnoDB COMMENT='Usuarios del sistema';

-- ---------------------------------------------------------------------------

CREATE TABLE sesiones_log (
    id              BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED        NOT NULL,
    ip              VARCHAR(45)         NULL,
    accion          VARCHAR(100)        NOT NULL COMMENT 'login, logout, timeout',
    creado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_sesiones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Auditoría de accesos al sistema';


-- =============================================================================
--  SECCIÓN M0 — CONFIGURACIÓN MAESTRA
-- =============================================================================
--  Jerarquía: Línea de producción → Producto → Parámetros de control
--  Esta sección hace al sistema escalable a nuevas líneas y productos.
-- =============================================================================

CREATE TABLE lineas_produccion (
    id              SMALLINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    codigo          VARCHAR(20)         NOT NULL COMMENT 'Ej: LIN-PAN, LIN-GAL',
    nombre          VARCHAR(100)        NOT NULL COMMENT 'Ej: Línea de Panificación',
    descripcion     TEXT                NULL,
    activa          TINYINT(1)          NOT NULL DEFAULT 1,
    creado_por      INT UNSIGNED        NOT NULL,
    creado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lineas_codigo (codigo),
    CONSTRAINT fk_lineas_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Líneas de producción de la planta';

-- ---------------------------------------------------------------------------

CREATE TABLE productos (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    linea_id                SMALLINT UNSIGNED   NOT NULL,
    codigo                  VARCHAR(30)         NOT NULL COMMENT 'Ej: PT0198, PAN-COCO-L2',
    nombre                  VARCHAR(150)        NOT NULL,
    descripcion             TEXT                NULL,
    -- Datos del contrato / ficha de estandarización
    lote_contrato           VARCHAR(20)         NULL     COMMENT 'Ej: Lote N°2 Secundaria',
    item_dbc                VARCHAR(10)         NULL     COMMENT 'Ej: Ítem 3',
    peso_nominal_g          DECIMAL(8,3)        NULL     COMMENT 'Peso contractual en gramos',
    tolerancia_pct          DECIMAL(5,2)        NULL     COMMENT 'Tolerancia ± en %. DBC = 1.00',
    lse_g                   DECIMAL(8,3)        NULL     COMMENT 'Límite Superior de Especificación (g)',
    lie_g                   DECIMAL(8,3)        NULL     COMMENT 'Límite Inferior de Especificación (g)',
    -- Rendimiento de referencia (hoja PT del Excel)
    unidades_por_receta     INT                 NULL     COMMENT 'Unidades teóricas por receta',
    unidades_por_bolsa      SMALLINT            NULL,
    bolsas_por_caja         SMALLINT            NULL,
    unidades_por_caja       SMALLINT            NULL,
    peso_caja_kg            DECIMAL(6,2)        NULL,
    vida_util_dias          SMALLINT            NULL,
    temperatura_conserv_min DECIMAL(5,2)        NULL     COMMENT 'T° mín. de conservación (°C)',
    temperatura_conserv_max DECIMAL(5,2)        NULL     COMMENT 'T° máx. de conservación (°C)',
    temperatura_entrega_max DECIMAL(5,2)        NULL,
    -- Propiedades organolépticas de referencia (para comparar en registros)
    ref_color               VARCHAR(100)        NULL,
    ref_olor                VARCHAR(100)        NULL,
    ref_sabor               VARCHAR(100)        NULL,
    ref_textura             VARCHAR(100)        NULL,
    ref_apariencia          VARCHAR(100)        NULL,
    -- Parámetros fisicoquímicos de referencia
    ref_humedad_max_pct     DECIMAL(5,2)        NULL,
    ref_ph_min              DECIMAL(4,2)        NULL,
    ref_ph_max              DECIMAL(4,2)        NULL,
    activo                  TINYINT(1)          NOT NULL DEFAULT 1,
    creado_por              INT UNSIGNED        NOT NULL,
    creado_en               TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_productos_codigo (codigo),
    CONSTRAINT fk_productos_linea   FOREIGN KEY (linea_id)   REFERENCES lineas_produccion (id),
    CONSTRAINT fk_productos_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Productos por línea, con especificaciones del DBC y ficha de estandarización';

-- ---------------------------------------------------------------------------

CREATE TABLE parametros_proceso (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    producto_id     INT UNSIGNED        NOT NULL,
    -- Identificación del parámetro
    etapa           ENUM(
                        'amasado',
                        'formado',
                        'fermentacion',
                        'horneado',
                        'envasado',
                        'producto_terminado'
                    )                   NOT NULL COMMENT 'Etapa del proceso a la que pertenece',
    nombre          VARCHAR(100)        NOT NULL COMMENT 'Ej: Temperatura de horno',
    unidad          VARCHAR(20)         NOT NULL COMMENT 'Ej: °C, min, g, %',
    tipo_dato       ENUM('numerico','texto','booleano','seleccion')
                                        NOT NULL DEFAULT 'numerico',
    -- Especificaciones (para SPC y alertas)
    valor_nominal   DECIMAL(12,4)       NULL,
    valor_min       DECIMAL(12,4)       NULL     COMMENT 'Límite inferior operativo',
    valor_max       DECIMAL(12,4)       NULL     COMMENT 'Límite superior operativo',
    -- Para gráficos de control
    es_variable_spc TINYINT(1)          NOT NULL DEFAULT 0 COMMENT '1 = se grafica en gráfico de control',
    tamanio_subgrupo TINYINT UNSIGNED   NULL     DEFAULT 10 COMMENT 'n de muestras por subgrupo (SPC)',
    -- Opciones si tipo_dato = seleccion
    opciones_json   JSON                NULL     COMMENT 'Ej: ["Conforme","No conforme","NA"]',
    obligatorio     TINYINT(1)          NOT NULL DEFAULT 1,
    orden_display   SMALLINT            NOT NULL DEFAULT 0 COMMENT 'Orden de aparición en el formulario',
    activo          TINYINT(1)          NOT NULL DEFAULT 1,
    creado_por      INT UNSIGNED        NOT NULL,
    creado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_parametros_producto FOREIGN KEY (producto_id) REFERENCES productos (id),
    CONSTRAINT fk_parametros_usuario  FOREIGN KEY (creado_por)  REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Parámetros de control de proceso por producto y etapa. Base del M2 y M6.';

-- ---------------------------------------------------------------------------

CREATE TABLE equipos (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    linea_id                SMALLINT UNSIGNED   NOT NULL,
    codigo                  VARCHAR(30)         NOT NULL COMMENT 'Ej: EQ-HOR-01',
    nombre                  VARCHAR(150)        NOT NULL COMMENT 'Ej: Horno de convección 1',
    tipo                    ENUM(
                                'horno',
                                'divisora',
                                'amasadora',
                                'envasadora',
                                'balanza',
                                'termometro',
                                'higrómetro',
                                'otro'
                            )                   NOT NULL,
    marca                   VARCHAR(100)        NULL,
    modelo                  VARCHAR(100)        NULL,
    serie                   VARCHAR(100)        NULL,
    requiere_calibracion    TINYINT(1)          NOT NULL DEFAULT 0,
    frecuencia_calibr_dias  SMALLINT            NULL     COMMENT 'Cada cuántos días se calibra',
    frecuencia_mant_dias    SMALLINT            NULL     COMMENT 'Cada cuántos días mantenimiento preventivo',
    fecha_ultima_calibr     DATE                NULL,
    fecha_prox_calibr       DATE                NULL     COMMENT 'Calculado automáticamente',
    fecha_ultimo_mant       DATE                NULL,
    fecha_prox_mant         DATE                NULL,
    activo                  TINYINT(1)          NOT NULL DEFAULT 1,
    observaciones           TEXT                NULL,
    creado_por              INT UNSIGNED        NOT NULL,
    creado_en               TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_equipos_codigo (codigo),
    CONSTRAINT fk_equipos_linea   FOREIGN KEY (linea_id)   REFERENCES lineas_produccion (id),
    CONSTRAINT fk_equipos_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Equipos y maquinaria de la planta. Alimenta M3 (mantenimiento)';

-- ---------------------------------------------------------------------------

CREATE TABLE insumos (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    codigo          VARCHAR(20)         NOT NULL COMMENT 'Ej: MP0041 — Harina Letizia',
    tipo            ENUM('materia_prima','material_empaque','insumo_proceso','otro')
                                        NOT NULL DEFAULT 'materia_prima',
    descripcion     VARCHAR(200)        NOT NULL,
    unidad_medida   VARCHAR(20)         NOT NULL COMMENT 'kg, l, und, g',
    -- Especificaciones de referencia para recepción
    esp_sabor_olor  VARCHAR(200)        NULL,
    esp_color       VARCHAR(100)        NULL,
    esp_descripcion_fisica VARCHAR(100) NULL,
    esp_humedad_max DECIMAL(6,2)        NULL,
    esp_densidad_min DECIMAL(8,4)       NULL,
    esp_densidad_max DECIMAL(8,4)       NULL,
    esp_ph_min      DECIMAL(4,2)        NULL,
    esp_ph_max      DECIMAL(4,2)        NULL,
    esp_brix_max    DECIMAL(6,2)        NULL,
    esp_gluten_min  DECIMAL(5,2)        NULL,
    esp_impurezas_max DECIMAL(6,3)      NULL,
    vida_util_referencia VARCHAR(50)    NULL     COMMENT 'Ej: 6 Meses, 2 Años',
    activo          TINYINT(1)          NOT NULL DEFAULT 1,
    creado_por      INT UNSIGNED        NOT NULL,
    creado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_insumos_codigo (codigo),
    CONSTRAINT fk_insumos_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Catálogo de insumos. Base de la BD del archivo Recepcion_de_MP.';

-- ---------------------------------------------------------------------------

CREATE TABLE recetas (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    producto_id     INT UNSIGNED        NOT NULL,
    version         SMALLINT UNSIGNED   NOT NULL DEFAULT 1,
    nombre          VARCHAR(150)        NOT NULL COMMENT 'Ej: Receta Pan con Coco v1',
    descripcion     TEXT                NULL,
    vigente         TINYINT(1)          NOT NULL DEFAULT 1,
    aprobada_por    INT UNSIGNED        NULL,
    aprobada_en     DATE                NULL,
    creado_por      INT UNSIGNED        NOT NULL,
    creado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_recetas_producto FOREIGN KEY (producto_id)  REFERENCES productos (id),
    CONSTRAINT fk_recetas_aprueba  FOREIGN KEY (aprobada_por) REFERENCES usuarios (id),
    CONSTRAINT fk_recetas_crea     FOREIGN KEY (creado_por)   REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Versiones de receta por producto. Refleja la ficha de estandarización UNACE.';

-- ---------------------------------------------------------------------------

CREATE TABLE receta_insumos (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    receta_id       INT UNSIGNED        NOT NULL,
    insumo_id       INT UNSIGNED        NOT NULL,
    cantidad        DECIMAL(12,4)       NOT NULL COMMENT 'Por receta (1 batch)',
    unidad_medida   VARCHAR(20)         NOT NULL,
    es_critico      TINYINT(1)          NOT NULL DEFAULT 0 COMMENT 'Insumo crítico de la ficha DBC',
    observaciones   VARCHAR(200)        NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_ri_receta FOREIGN KEY (receta_id) REFERENCES recetas (id),
    CONSTRAINT fk_ri_insumo FOREIGN KEY (insumo_id) REFERENCES insumos (id)
) ENGINE=InnoDB COMMENT='Composición de ingredientes por receta (lista de materiales BOM)';


-- =============================================================================
--  SECCIÓN M1 — RECEPCIÓN DE MATERIA PRIMA
-- =============================================================================

CREATE TABLE proveedores (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(200)        NOT NULL,
    procedencia     VARCHAR(150)        NULL     COMMENT 'Ej: Santa Cruz-Bolivia, Argentina',
    telefono        VARCHAR(30)         NULL,
    email           VARCHAR(150)        NULL,
    registro_sanitario VARCHAR(50)      NULL,
    activo          TINYINT(1)          NOT NULL DEFAULT 1,
    creado_por      INT UNSIGNED        NOT NULL,
    creado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_proveedores_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Catálogo de proveedores de materia prima';

-- ---------------------------------------------------------------------------

CREATE TABLE recepciones_mp (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    -- Encabezado (hoja REGISTRO-MP del archivo Recepcion_de_MP)
    insumo_id               INT UNSIGNED        NOT NULL,
    proveedor_id            INT UNSIGNED        NOT NULL,
    codigo_recepcion        VARCHAR(30)         NOT NULL COMMENT 'Generado: REC-YYYYMMDD-NNN',
    lote_proveedor          VARCHAR(80)         NOT NULL COMMENT 'Nro de lote del proveedor',
    fecha_fabricacion       DATE                NULL,
    fecha_vencimiento       DATE                NOT NULL,
    fecha_muestreo          DATE                NOT NULL,
    fecha_analisis          DATE                NOT NULL,
    cantidad_recibida       DECIMAL(12,3)       NOT NULL,
    unidad_medida           VARCHAR(20)         NOT NULL,
    tamanio_muestra         DECIMAL(8,4)        NULL,
    nro_registro_sanitario  VARCHAR(60)         NULL,
    -- Características del empaque
    presentacion            VARCHAR(100)        NULL     COMMENT 'Ej: 25 kg',
    envase_primario         VARCHAR(150)        NULL,
    envase_secundario       VARCHAR(150)        NULL,
    obs_empaque             TEXT                NULL,
    -- Análisis organoléptico
    sabor_olor              VARCHAR(200)        NULL,
    conf_sabor_olor         ENUM('conforme','no_conforme','na') NULL,
    color                   VARCHAR(100)        NULL,
    conf_color              ENUM('conforme','no_conforme','na') NULL,
    descripcion_fisica      VARCHAR(150)        NULL,
    conf_descripcion_fisica ENUM('conforme','no_conforme','na') NULL,
    obs_organoleptico       TEXT                NULL,
    -- Análisis fisicoquímico
    esp_humedad             DECIMAL(6,2)        NULL,
    res_humedad             DECIMAL(6,2)        NULL,
    conf_humedad            ENUM('conforme','no_conforme','na') NULL,
    esp_densidad            DECIMAL(8,4)        NULL,
    res_densidad            DECIMAL(8,4)        NULL,
    conf_densidad           ENUM('conforme','no_conforme','na') NULL,
    esp_ph                  VARCHAR(30)         NULL     COMMENT 'Puede ser rango, Ej: 5.40 a 7.51',
    res_ph                  DECIMAL(5,2)        NULL,
    conf_ph                 ENUM('conforme','no_conforme','na') NULL,
    esp_brix                DECIMAL(6,2)        NULL,
    res_brix                DECIMAL(6,2)        NULL,
    conf_brix               ENUM('conforme','no_conforme','na') NULL,
    esp_gluten              VARCHAR(30)         NULL,
    res_gluten              DECIMAL(6,2)        NULL,
    conf_gluten             ENUM('conforme','no_conforme','na') NULL,
    esp_actividad_agua      VARCHAR(30)         NULL,
    res_actividad_agua      DECIMAL(6,4)        NULL,
    conf_actividad_agua     ENUM('conforme','no_conforme','na') NULL,
    obs_fisicoquimico       TEXT                NULL,
    -- Impurezas
    esp_impurezas           VARCHAR(60)         NULL,
    res_impurezas           DECIMAL(6,3)        NULL,
    conf_impurezas          ENUM('conforme','no_conforme','na') NULL,
    obs_impurezas           TEXT                NULL,
    -- Preparación de dilución
    preparacion_disolucion  VARCHAR(100)        NULL,
    dosis_recomendada       VARCHAR(100)        NULL,
    -- Conclusión y decisión (obligatorio SIREMU)
    conclusion              TEXT                NULL,
    decision                ENUM('aprobado','rechazado','observado','cuarentena')
                                                NOT NULL,
    fecha_decision          DATE                NOT NULL,
    -- Responsables (espejo del formulario físico)
    responsable_muestreo_id INT UNSIGNED        NOT NULL,
    responsable_analisis_id INT UNSIGNED        NOT NULL,
    visto_bueno_id          INT UNSIGNED        NULL,
    obs_generales           TEXT                NULL,
    -- Stock resultante
    stock_disponible_kg     DECIMAL(12,3)       NULL     COMMENT 'Cantidad aprobada que ingresa al inventario',
    creado_en               TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recepciones_codigo (codigo_recepcion),
    INDEX idx_rec_insumo   (insumo_id),
    INDEX idx_rec_proveedor (proveedor_id),
    INDEX idx_rec_vencimiento (fecha_vencimiento),
    CONSTRAINT fk_rec_insumo       FOREIGN KEY (insumo_id)              REFERENCES insumos (id),
    CONSTRAINT fk_rec_proveedor    FOREIGN KEY (proveedor_id)           REFERENCES proveedores (id),
    CONSTRAINT fk_rec_muestreo     FOREIGN KEY (responsable_muestreo_id) REFERENCES usuarios (id),
    CONSTRAINT fk_rec_analisis     FOREIGN KEY (responsable_analisis_id) REFERENCES usuarios (id),
    CONSTRAINT fk_rec_visto_bueno  FOREIGN KEY (visto_bueno_id)         REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Registro de recepción y análisis de materia prima. Digitaliza hoja REGISTRO-MP.';

-- ---------------------------------------------------------------------------

CREATE TABLE calibraciones (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    equipo_id               INT UNSIGNED        NOT NULL,
    nro_certificado         VARCHAR(80)         NOT NULL,
    fecha_calibracion       DATE                NOT NULL,
    fecha_vencimiento       DATE                NOT NULL,
    laboratorio_externo     VARCHAR(200)        NULL,
    resultado               ENUM('aprobado','rechazado','condicionado') NOT NULL,
    rango_operacion         VARCHAR(100)        NULL     COMMENT 'Ej: 0-5000 g ±0.5g',
    obs                     TEXT                NULL,
    certificado_archivo     VARCHAR(255)        NULL     COMMENT 'Ruta del PDF del certificado',
    registrado_por_id       INT UNSIGNED        NOT NULL,
    creado_en               TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_cal_equipo   (equipo_id),
    INDEX idx_cal_vence    (fecha_vencimiento),
    CONSTRAINT fk_cal_equipo    FOREIGN KEY (equipo_id)         REFERENCES equipos (id),
    CONSTRAINT fk_cal_registra  FOREIGN KEY (registrado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Historial de calibraciones con certificado. Exigido por el SIREMU.';


-- =============================================================================
--  SECCIÓN M4 — SEGUIMIENTO DE PRODUCCIÓN
-- =============================================================================
--  La tabla `lotes_produccion` es la más importante del sistema.
--  Su campo `codigo_lote` es la clave foránea que une todos los registros.
-- =============================================================================

CREATE TABLE lotes_produccion (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    -- Identificación (código generado por LoteService.php)
    codigo_lote             VARCHAR(20)         NOT NULL
                                                COMMENT 'Formato: NroDia-MesAbrev. Ej: 29-01, 63-03',
    -- Qué se produce
    producto_id             INT UNSIGNED        NOT NULL,
    receta_id               INT UNSIGNED        NOT NULL,
    numero_recetas          DECIMAL(6,2)        NOT NULL COMMENT 'Ej: 16.5 recetas en ese lote',
    -- Cuándo y quién
    fecha_produccion        DATE                NOT NULL,
    turno                   ENUM('mañana','tarde','noche') NOT NULL,
    supervisor_id           INT UNSIGNED        NOT NULL,
    nivel                   VARCHAR(80)         NULL     COMMENT 'Ej: Secundaria y Ed. Especial',
    -- Rendimiento (tomado del excel SEGUIMIENTO_RENDIMIENTOS_HORNEADOS)
    rendimiento_teorico_total INT UNSIGNED      NULL     COMMENT 'numero_recetas × unidades_por_receta',
    rendimiento_real_total  INT UNSIGNED        NULL     COMMENT 'Unidades producidas realmente',
    diferencia_unidades     INT                 NULL     COMMENT 'real - teorico (negativo = deficit)',
    porcentaje_rendimiento  DECIMAL(6,2)        NULL     COMMENT '(real/teorico)*100',
    -- Merma
    merma_producto_kg       DECIMAL(8,3)        NULL     COMMENT 'Merma de producto (kg)',
    merma_envase_kg         DECIMAL(8,3)        NULL     COMMENT 'Merma de envase/bobina (kg)',
    merma_reproceso_kg      DECIMAL(8,3)        NULL     COMMENT 'Kg enviados a reproceso',
    merma_no_conforme_kg    DECIMAL(8,3)        NULL     COMMENT 'Kg de producto no conforme eliminado',
    merma_quemado_kg        DECIMAL(8,3)        NULL     COMMENT 'Kg quemados en horno',
    -- Trazabilidad contractual (DBC GAMLP)
    fecha_elaboracion       DATE                NOT NULL COMMENT 'Para el etiquetado del producto',
    fecha_vencimiento       DATE                NOT NULL,
    -- Estado del lote
    estado                  ENUM(
                                'en_proceso',
                                'cerrado',
                                'liberado',
                                'cuarentena',
                                'rechazado'
                            )                   NOT NULL DEFAULT 'en_proceso',
    observaciones           TEXT                NULL,
    cronograma_produccion_id INT UNSIGNED       NULL     COMMENT 'Vincula al menú trimestral',
    creado_por              INT UNSIGNED        NOT NULL,
    creado_en               TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lotes_codigo (codigo_lote),
    INDEX idx_lotes_producto  (producto_id),
    INDEX idx_lotes_fecha     (fecha_produccion),
    INDEX idx_lotes_estado    (estado),
    CONSTRAINT fk_lotes_producto   FOREIGN KEY (producto_id)  REFERENCES productos (id),
    CONSTRAINT fk_lotes_receta     FOREIGN KEY (receta_id)    REFERENCES recetas (id),
    CONSTRAINT fk_lotes_supervisor FOREIGN KEY (supervisor_id) REFERENCES usuarios (id),
    CONSTRAINT fk_lotes_crea       FOREIGN KEY (creado_por)   REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Lote de producción. Eje vertebral de toda la trazabilidad del sistema.';
-- ---------------------------------------------------------------------------

CREATE TABLE stock_mp (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    insumo_id           INT UNSIGNED        NOT NULL,
    recepcion_id        INT UNSIGNED        NULL     COMMENT 'Origen: recepción que generó el stock',
    lote_proveedor      VARCHAR(80)         NOT NULL COMMENT 'Redundante intencional para consultas rápidas',
    fecha_vencimiento   DATE                NOT NULL,
    cantidad_inicial    DECIMAL(12,3)       NOT NULL,
    cantidad_disponible DECIMAL(12,3)       NOT NULL,
    unidad_medida       VARCHAR(20)         NOT NULL,
    estado              ENUM('disponible','reservado','agotado','vencido','rechazado')
                                            NOT NULL DEFAULT 'disponible',
    actualizado_en      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_stock_insumo (insumo_id),
    INDEX idx_stock_vence  (fecha_vencimiento),
    CONSTRAINT fk_stock_insumo    FOREIGN KEY (insumo_id)    REFERENCES insumos (id),
    CONSTRAINT fk_stock_recepcion FOREIGN KEY (recepcion_id) REFERENCES recepciones_mp (id)
) ENGINE=InnoDB COMMENT='Inventario de materia prima disponible. Actualizado por M1 y M4.';


-- =============================================================================
--  SECCIÓN M3 — MANTENIMIENTO DE EQUIPOS
-- =============================================================================

CREATE TABLE mantenimientos (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    equipo_id               INT UNSIGNED        NOT NULL,
    tipo                    ENUM('preventivo','correctivo','calibracion')
                                                NOT NULL,
    -- Programación
    fecha_programada        DATE                NULL,
    -- Ejecución
    fecha_inicio            DATETIME            NOT NULL,
    fecha_fin               DATETIME            NULL,
    duracion_min            SMALLINT UNSIGNED   NULL     COMMENT 'Calculado: fin - inicio en minutos',
    -- Detalle
    descripcion_trabajo     TEXT                NOT NULL,
    falla_detectada         TEXT                NULL     COMMENT 'Para tipo correctivo',
    causa_raiz              TEXT                NULL,
    accion_correctiva       TEXT                NULL,
    -- Impacto en producción (alimenta OEE)
    paro_produccion         TINYINT(1)          NOT NULL DEFAULT 0,
    tiempo_paro_min         SMALLINT UNSIGNED   NULL     COMMENT 'Minutos de paro si paro_produccion=1',
    lote_afectado_id        INT UNSIGNED        NULL     COMMENT 'Lote en curso durante el paro',
    -- Resultado
    resultado               ENUM('completado','pendiente','en_proceso','requiere_seguimiento')
                                                NOT NULL DEFAULT 'completado',
    componentes_cambiados   TEXT                NULL,
    costo_estimado          DECIMAL(10,2)       NULL,
    -- Responsables
    ejecutado_por_id        INT UNSIGNED        NOT NULL,
    supervisado_por_id      INT UNSIGNED        NULL,
    observaciones           TEXT                NULL,
    creado_en               TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_mant_equipo (equipo_id),
    INDEX idx_mant_fecha  (fecha_inicio),
    CONSTRAINT fk_mant_equipo      FOREIGN KEY (equipo_id)         REFERENCES equipos (id),
    CONSTRAINT fk_mant_lote        FOREIGN KEY (lote_afectado_id)  REFERENCES lotes_produccion (id),
    CONSTRAINT fk_mant_ejecutor    FOREIGN KEY (ejecutado_por_id)  REFERENCES usuarios (id),
    CONSTRAINT fk_mant_supervisor  FOREIGN KEY (supervisado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Registro de mantenimiento preventivo, correctivo y calibraciones.';


-- ---------------------------------------------------------------------------

CREATE TABLE consumo_mp_por_lote (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    lote_id             INT UNSIGNED        NOT NULL,
    stock_mp_id         INT UNSIGNED        NOT NULL,
    insumo_id           INT UNSIGNED        NOT NULL,
    lote_proveedor      VARCHAR(80)         NOT NULL COMMENT 'Redundante para trazabilidad directa',
    cantidad_usada      DECIMAL(12,3)       NOT NULL,
    unidad_medida       VARCHAR(20)         NOT NULL,
    registrado_por_id   INT UNSIGNED        NOT NULL,
    registrado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_consumo_lote   (lote_id),
    INDEX idx_consumo_stock  (stock_mp_id),
    INDEX idx_consumo_insumo (insumo_id),
    CONSTRAINT fk_consumo_lote    FOREIGN KEY (lote_id)           REFERENCES lotes_produccion (id),
    CONSTRAINT fk_consumo_stock   FOREIGN KEY (stock_mp_id)       REFERENCES stock_mp (id),
    CONSTRAINT fk_consumo_insumo  FOREIGN KEY (insumo_id)         REFERENCES insumos (id),
    CONSTRAINT fk_consumo_usuario FOREIGN KEY (registrado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Entradas y salidas de MP por lote. Une M4 con M1 para trazabilidad hacia atrás.';

-- ---------------------------------------------------------------------------

CREATE TABLE tiempos_produccion (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    lote_id             INT UNSIGNED        NOT NULL,
    etapa               ENUM(
                            'amasado',
                            'formado_boleado',
                            'fermentacion',
                            'horneado',
                            'enfriado',
                            'envasado',
                            'total'
                        )                   NOT NULL,
    hora_inicio         DATETIME            NULL,
    hora_fin            DATETIME            NULL,
    duracion_min        SMALLINT UNSIGNED   NULL     COMMENT 'Calculado automáticamente',
    observaciones       VARCHAR(200)        NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_tiempos_lote FOREIGN KEY (lote_id) REFERENCES lotes_produccion (id)
) ENGINE=InnoDB COMMENT='Tiempos por etapa de producción. Alimenta el cálculo de rendimiento del OEE.';


-- =============================================================================
--  SECCIÓN M2 — REGISTRO DE PROCESO
-- =============================================================================
--  Tres sub-registros obligatorios (SIREMU):
--    1. Control de proceso (amasado + horneado)
--    2. Control de envasado
--    3. Análisis de producto terminado + liberación
-- =============================================================================

-- 1. Encabezado de sesión de registro (agrupa los tres sub-registros del turno)
CREATE TABLE sesiones_registro (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    lote_id             INT UNSIGNED        NOT NULL,
    -- Datos del encabezado del formulario (igual al papel)
    fecha               DATE                NOT NULL,
    turno               ENUM('mañana','tarde','noche') NOT NULL,
    supervisor_id       INT UNSIGNED        NOT NULL,
    nivel               VARCHAR(80)         NULL,
    hora_inicio_registro DATETIME           NULL,
    hora_fin_registro   DATETIME            NULL,
    estado              ENUM('en_proceso','completo','revisado') NOT NULL DEFAULT 'en_proceso',
    creado_en           TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_ses_lote (lote_id),
    CONSTRAINT fk_ses_lote       FOREIGN KEY (lote_id)      REFERENCES lotes_produccion (id),
    CONSTRAINT fk_ses_supervisor FOREIGN KEY (supervisor_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Sesión de registro por turno y lote. Agrupa los 3 sub-registros.';

-- ---------------------------------------------------------------------------

-- 2a. Control de proceso — Etapa de amasado/mezclado
CREATE TABLE reg_proceso_amasado (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    sesion_id               INT UNSIGNED        NOT NULL,
    hora                    TIME                NOT NULL,
    -- Características de la masa
    temperatura_masa_c      DECIMAL(5,2)        NULL     COMMENT 'Temperatura de la masa (°C)',
    ph_masa                 DECIMAL(4,2)        NULL,
    observaciones_masa      VARCHAR(200)        NULL,
    registrado_por_id       INT UNSIGNED        NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_amas_sesion  FOREIGN KEY (sesion_id)        REFERENCES sesiones_registro (id),
    CONSTRAINT fk_amas_usuario FOREIGN KEY (registrado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Control de proceso: etapa de amasado. Temperatura y pH de la masa.';

-- ---------------------------------------------------------------------------

-- 2b. Control de proceso — Pesos de masa cruda (subgrupo SPC: n=10)
--     Esta tabla es la base de datos de los gráficos X̄-R del M6.
CREATE TABLE reg_pesos_masa_cruda (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    sesion_id               INT UNSIGNED        NOT NULL,
    hora                    TIME                NOT NULL,
    -- 10 mediciones del subgrupo (n configurable en parametros_proceso)
    peso_01                 DECIMAL(8,2)        NULL,
    peso_02                 DECIMAL(8,2)        NULL,
    peso_03                 DECIMAL(8,2)        NULL,
    peso_04                 DECIMAL(8,2)        NULL,
    peso_05                 DECIMAL(8,2)        NULL,
    peso_06                 DECIMAL(8,2)        NULL,
    peso_07                 DECIMAL(8,2)        NULL,
    peso_08                 DECIMAL(8,2)        NULL,
    peso_09                 DECIMAL(8,2)        NULL,
    peso_10                 DECIMAL(8,2)        NULL,
    -- Estadísticos calculados automáticamente por el sistema
    promedio_xbar           DECIMAL(8,4)        NULL     COMMENT 'Media del subgrupo (X̄)',
    rango_r                 DECIMAL(8,4)        NULL     COMMENT 'Rango del subgrupo (R = max - min)',
    desv_estandar_s         DECIMAL(8,4)        NULL,
    -- Señales de control (calculadas por SpcService.php)
    fuera_de_control        TINYINT(1)          NOT NULL DEFAULT 0,
    regla_violada           VARCHAR(50)         NULL     COMMENT 'Ej: Regla 1 - Punto fuera de UCL',
    alerta_generada         TINYINT(1)          NOT NULL DEFAULT 0,
    -- Etapa a la que corresponde
    etapa                   ENUM('formado_boleado','previo_horno','otro') NOT NULL DEFAULT 'formado_boleado',
    operario_id             INT UNSIGNED        NULL,
    observaciones           VARCHAR(200)        NULL,
    registrado_por_id       INT UNSIGNED        NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_pesos_sesion (sesion_id),
    CONSTRAINT fk_pesos_sesion  FOREIGN KEY (sesion_id)        REFERENCES sesiones_registro (id),
    CONSTRAINT fk_pesos_operario FOREIGN KEY (operario_id)     REFERENCES usuarios (id),
    CONSTRAINT fk_pesos_usuario FOREIGN KEY (registrado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Pesos de masa cruda por subgrupo. FUENTE PRINCIPAL del SPC (gráfico X̄-R).';

-- ---------------------------------------------------------------------------

-- 2c. Control de proceso — Horneado
CREATE TABLE reg_proceso_horneado (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    sesion_id               INT UNSIGNED        NOT NULL,
    hora                    TIME                NOT NULL,
    -- Parámetros del horno
    tiempo_fermentacion_min SMALLINT UNSIGNED   NULL     COMMENT 'Minutos de fermentación',
    temperatura_horno_c     DECIMAL(5,2)        NULL,
    tiempo_horneado_min     SMALLINT UNSIGNED   NULL,
    -- Características organolépticas post-horno
    conf_olor               ENUM('conforme','no_conforme','na') NULL,
    conf_sabor              ENUM('conforme','no_conforme','na') NULL,
    conf_color              ENUM('conforme','no_conforme','na') NULL,
    conf_aspecto            ENUM('conforme','no_conforme','na') NULL,
    conf_textura            ENUM('conforme','no_conforme','na') NULL,
    observaciones           VARCHAR(200)        NULL,
    registrado_por_id       INT UNSIGNED        NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_horno_sesion  FOREIGN KEY (sesion_id)        REFERENCES sesiones_registro (id),
    CONSTRAINT fk_horno_usuario FOREIGN KEY (registrado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Control de proceso: etapa de horneado. Temperatura, tiempo, organolépticos.';

-- ---------------------------------------------------------------------------

-- 3. Control de envasado (hoja C-ENVASADO-G del Excel)
CREATE TABLE reg_control_envasado (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    sesion_id               INT UNSIGNED        NOT NULL,
    hora                    TIME                NOT NULL,
    temperatura_producto_c  DECIMAL(5,2)        NULL,
    temperatura_ambiente_c  DECIMAL(5,2)        NULL,
    -- Pesos en envasado (4 muestras por punto de control)
    peso_unidad_1           DECIMAL(8,2)        NULL,
    peso_unidad_2           DECIMAL(8,2)        NULL,
    peso_unidad_3           DECIMAL(8,2)        NULL,
    peso_unidad_4           DECIMAL(8,2)        NULL,
    promedio_peso_unidad    DECIMAL(8,4)        NULL,
    fuera_especificacion    TINYINT(1)          NOT NULL DEFAULT 0,
    -- Peso de bolsa
    peso_bolsa_1            DECIMAL(8,2)        NULL,
    peso_bolsa_2            DECIMAL(8,2)        NULL,
    peso_bolsa_3            DECIMAL(8,2)        NULL,
    peso_bolsa_4            DECIMAL(8,2)        NULL,
    -- Verificación de codificado y sellado (CheckBox del formulario)
    codif_horizontal        ENUM('conforme','no_conforme','na') NULL,
    codif_vertical          ENUM('conforme','no_conforme','na') NULL,
    sellado_horizontal      ENUM('conforme','no_conforme','na') NULL,
    sellado_vertical        ENUM('conforme','no_conforme','na') NULL,
    -- Totales del punto
    total_unidades          INT UNSIGNED        NULL,
    observaciones           VARCHAR(200)        NULL,
    registrado_por_id       INT UNSIGNED        NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_env_sesion (sesion_id),
    CONSTRAINT fk_env_sesion  FOREIGN KEY (sesion_id)        REFERENCES sesiones_registro (id),
    CONSTRAINT fk_env_usuario FOREIGN KEY (registrado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Control de envasado: pesos, codificado, sellado. Hoja C-ENVASADO-G.';

-- ---------------------------------------------------------------------------

-- 4. Análisis de producto terminado (hoja ANALISIS PT)
CREATE TABLE reg_analisis_pt (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    sesion_id               INT UNSIGNED        NOT NULL,
    hora                    TIME                NOT NULL,
    -- Características organolépticas del PT
    conf_color              ENUM('conforme','no_conforme','na') NULL,
    conf_olor               ENUM('conforme','no_conforme','na') NULL,
    conf_sabor              ENUM('conforme','no_conforme','na') NULL,
    conf_apariencia         ENUM('conforme','no_conforme','na') NULL,
    conf_textura            ENUM('conforme','no_conforme','na') NULL,
    conf_particulas_extranas ENUM('conforme','no_conforme','na') NULL,
    -- Fisicoquímico del PT
    resultado_humedad_pct   DECIMAL(6,2)        NULL,
    resultado_ph            DECIMAL(4,2)        NULL,
    -- Decisión preliminar
    decision_preliminar     ENUM('producto_conforme','producto_no_conforme',
                                'producto_observado','producto_en_proceso','na')
                                                NOT NULL DEFAULT 'producto_en_proceso',
    observaciones           TEXT                NULL,
    registrado_por_id       INT UNSIGNED        NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_apt_sesion  FOREIGN KEY (sesion_id)        REFERENCES sesiones_registro (id),
    CONSTRAINT fk_apt_usuario FOREIGN KEY (registrado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Análisis de producto terminado. Hoja ANALISIS PT del Excel.';

-- ---------------------------------------------------------------------------

-- 5. Liberación de producto terminado (hoja LIBERACION PT — obligatorio SIREMU)
CREATE TABLE reg_liberacion_pt (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    sesion_id               INT UNSIGNED        NOT NULL,
    hora                    TIME                NOT NULL,
    -- Codificación del lote en el envase
    codif_lote_legible      ENUM('conforme','no_conforme','na') NULL,
    codif_lote_correcto     ENUM('conforme','no_conforme','na') NULL,
    codif_fvenc_legible     ENUM('conforme','no_conforme','na') NULL,
    codif_fvenc_correcto    ENUM('conforme','no_conforme','na') NULL,
    -- Calidad del envasado
    envase_primario         ENUM('conforme','no_conforme','na') NULL,
    envase_secundario       ENUM('conforme','no_conforme','na') NULL,
    envase_terciario        ENUM('conforme','no_conforme','na') NULL,
    inocuo                  ENUM('conforme','no_conforme','na') NULL,
    -- Organolépticos finales (confirmación antes de despacho)
    conf_color              ENUM('conforme','no_conforme','na') NULL,
    conf_olor               ENUM('conforme','no_conforme','na') NULL,
    conf_sabor              ENUM('conforme','no_conforme','na') NULL,
    conf_apariencia         ENUM('conforme','no_conforme','na') NULL,
    conf_textura            ENUM('conforme','no_conforme','na') NULL,
    conf_particulas_extranas ENUM('conforme','no_conforme','na') NULL,
    -- Fisicoquímico de liberación
    resultado_humedad_pct   DECIMAL(6,2)        NULL,
    resultado_ph            DECIMAL(4,2)        NULL,
    -- DECISIÓN FINAL (este campo actualiza el estado del lote en lotes_produccion)
    decision_final          ENUM('liberado','cuarentena') NOT NULL,
    observaciones           TEXT                NULL,
    -- Firmas (obligatorio SIREMU)
    supervisor_calidad_id   INT UNSIGNED        NOT NULL,
    encargado_nutricion_id  INT UNSIGNED        NULL,
    jefe_produccion_id      INT UNSIGNED        NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_lib_sesion      FOREIGN KEY (sesion_id)             REFERENCES sesiones_registro (id),
    CONSTRAINT fk_lib_supervisor  FOREIGN KEY (supervisor_calidad_id) REFERENCES usuarios (id),
    CONSTRAINT fk_lib_nutricion   FOREIGN KEY (encargado_nutricion_id) REFERENCES usuarios (id),
    CONSTRAINT fk_lib_jefe        FOREIGN KEY (jefe_produccion_id)    REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Liberación de PT. Obligatorio SIREMU. Su decision_final actualiza lotes_produccion.estado.';


-- =============================================================================
--  SECCIÓN M5 — TRAZABILIDAD Y GENEALOGÍA (Vista / Consulta)
-- =============================================================================
--  M5 no tiene tablas propias de captura.
--  Opera mediante consultas JOIN entre lotes_produccion, consumo_mp_por_lote,
--  recepciones_mp, reg_pesos_masa_cruda, reg_liberacion_pt, mantenimientos.
--
--  Se crea una tabla de eventos de trazabilidad para auditoría de cambios.
-- =============================================================================

CREATE TABLE trazabilidad_eventos (
    id              BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    codigo_lote     VARCHAR(20)         NOT NULL,
    tipo_evento     ENUM(
                        'recepcion_mp',
                        'apertura_lote',
                        'registro_proceso',
                        'paro_mantenimiento',
                        'analisis_pt',
                        'liberacion',
                        'cuarentena',
                        'cierre_lote',
                        'modificacion'
                    )                   NOT NULL,
    descripcion     TEXT                NOT NULL,
    tabla_origen    VARCHAR(60)         NULL     COMMENT 'Tabla que generó el evento',
    registro_id     INT UNSIGNED        NULL     COMMENT 'ID del registro en tabla_origen',
    usuario_id      INT UNSIGNED        NOT NULL,
    creado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_traz_lote  (codigo_lote),
    INDEX idx_traz_tipo  (tipo_evento),
    INDEX idx_traz_fecha (creado_en),
    CONSTRAINT fk_traz_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Log de eventos de trazabilidad por lote. Cronología auditable para SIREMU.';


-- =============================================================================
--  SECCIÓN M6 — SPC (Tablas de soporte para cálculos estadísticos guardados)
-- =============================================================================
--  Los datos fuente están en reg_pesos_masa_cruda y reg_control_envasado.
--  Esta sección guarda los resultados calculados para no recalcular siempre.
-- =============================================================================

CREATE TABLE spc_limites_control (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    producto_id             INT UNSIGNED        NOT NULL,
    parametro_id            INT UNSIGNED        NOT NULL,
    -- Período de análisis base para los límites
    fecha_desde             DATE                NOT NULL,
    fecha_hasta             DATE                NOT NULL,
    n_subgrupos             SMALLINT UNSIGNED   NOT NULL COMMENT 'Número de subgrupos usados',
    tamanio_subgrupo        TINYINT UNSIGNED    NOT NULL COMMENT 'n por subgrupo',
    -- Estadísticos del período base
    gran_media_xbarbar      DECIMAL(10,4)       NOT NULL COMMENT 'Media de medias (X̄̄)',
    media_rangos_rbar       DECIMAL(10,4)       NOT NULL COMMENT 'Media de rangos (R̄)',
    desv_est_proceso        DECIMAL(10,4)       NULL     COMMENT 'σ estimada del proceso',
    -- Límites gráfico X̄
    ucl_xbar                DECIMAL(10,4)       NOT NULL COMMENT 'Límite Control Superior X̄',
    cl_xbar                 DECIMAL(10,4)       NOT NULL COMMENT 'Línea central X̄',
    lcl_xbar                DECIMAL(10,4)       NOT NULL COMMENT 'Límite Control Inferior X̄',
    -- Límites gráfico R
    ucl_r                   DECIMAL(10,4)       NOT NULL,
    cl_r                    DECIMAL(10,4)       NOT NULL,
    lcl_r                   DECIMAL(10,4)       NOT NULL,
    -- Capacidad del proceso
    lse                     DECIMAL(10,4)       NULL     COMMENT 'Límite Superior de Especificación',
    lie                     DECIMAL(10,4)       NULL     COMMENT 'Límite Inferior de Especificación',
    cp                      DECIMAL(8,4)        NULL,
    cpk                     DECIMAL(8,4)        NULL,
    pp                      DECIMAL(8,4)        NULL,
    ppk                     DECIMAL(8,4)        NULL,
    pct_fuera_control       DECIMAL(6,2)        NULL     COMMENT '% puntos fuera de límites',
    proceso_estable         TINYINT(1)          NULL,
    -- Metadata
    calculado_por_id        INT UNSIGNED        NOT NULL,
    calculado_en            TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    vigente                 TINYINT(1)          NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    INDEX idx_spc_producto  (producto_id),
    INDEX idx_spc_parametro (parametro_id),
    CONSTRAINT fk_spc_producto   FOREIGN KEY (producto_id)      REFERENCES productos (id),
    CONSTRAINT fk_spc_parametro  FOREIGN KEY (parametro_id)     REFERENCES parametros_proceso (id),
    CONSTRAINT fk_spc_calcula    FOREIGN KEY (calculado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Límites de control y capacidad calculados por período. Caché del M6.';

-- ---------------------------------------------------------------------------

CREATE TABLE spc_senales_detectadas (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    lote_id                 INT UNSIGNED        NOT NULL,
    sesion_id               INT UNSIGNED        NOT NULL,
    registro_peso_id        INT UNSIGNED        NULL,
    producto_id             INT UNSIGNED        NOT NULL,
    tipo_grafico            ENUM('xbar','r','s','p','np','c','u') NOT NULL DEFAULT 'xbar',
    regla_western_electric  TINYINT UNSIGNED    NOT NULL COMMENT '1=punto fuera, 2=tendencia, etc.',
    descripcion_regla       VARCHAR(150)        NOT NULL,
    valor_detectado         DECIMAL(10,4)       NULL,
    limite_violado          ENUM('ucl','lcl','zona_a','zona_b','zona_c') NULL,
    -- Gestión de la señal
    estado                  ENUM('nueva','investigando','resuelta','falsa_alarma')
                                                NOT NULL DEFAULT 'nueva',
    causa_identificada      TEXT                NULL,
    accion_tomada           TEXT                NULL,
    asignado_a_id           INT UNSIGNED        NULL,
    resuelta_por_id         INT UNSIGNED        NULL,
    resuelta_en             DATETIME            NULL,
    creado_en               TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sen_lote    (lote_id),
    INDEX idx_sen_estado  (estado),
    CONSTRAINT fk_sen_lote    FOREIGN KEY (lote_id)          REFERENCES lotes_produccion (id),
    CONSTRAINT fk_sen_sesion  FOREIGN KEY (sesion_id)        REFERENCES sesiones_registro (id),
    CONSTRAINT fk_sen_peso    FOREIGN KEY (registro_peso_id) REFERENCES reg_pesos_masa_cruda (id),
    CONSTRAINT fk_sen_prod    FOREIGN KEY (producto_id)      REFERENCES productos (id),
    CONSTRAINT fk_sen_asigna  FOREIGN KEY (asignado_a_id)    REFERENCES usuarios (id),
    CONSTRAINT fk_sen_resuelve FOREIGN KEY (resuelta_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Señales de causas especiales detectadas automáticamente. Permite trazabilidad de hallazgos SPC.';

-- ---------------------------------------------------------------------------

CREATE TABLE spc_analisis_guardados (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    nombre                  VARCHAR(150)        NOT NULL COMMENT 'Nombre que el usuario le da al análisis',
    producto_id             INT UNSIGNED        NOT NULL,
    parametro_id            INT UNSIGNED        NOT NULL,
    -- Filtros aplicados
    fecha_desde             DATE                NULL,
    fecha_hasta             DATE                NULL,
    turno_filtro            ENUM('mañana','tarde','noche','todos') NOT NULL DEFAULT 'todos',
    operario_filtro_id      INT UNSIGNED        NULL,
    -- Resultados del análisis de normalidad
    prueba_normalidad       ENUM('anderson_darling','shapiro_wilk') NULL,
    estadistico_prueba      DECIMAL(8,4)        NULL,
    p_valor                 DECIMAL(8,6)        NULL,
    es_normal               TINYINT(1)          NULL,
    -- Estadística descriptiva
    n_total                 INT UNSIGNED        NULL,
    media                   DECIMAL(10,4)       NULL,
    mediana                 DECIMAL(10,4)       NULL,
    desv_est                DECIMAL(10,4)       NULL,
    coef_variacion_pct      DECIMAL(8,4)        NULL,
    minimo                  DECIMAL(10,4)       NULL,
    maximo                  DECIMAL(10,4)       NULL,
    rango_total             DECIMAL(10,4)       NULL,
    -- Interpretación automática generada
    interpretacion_texto    TEXT                NULL,
    creado_por_id           INT UNSIGNED        NOT NULL,
    creado_en               TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_anal_producto  FOREIGN KEY (producto_id)   REFERENCES productos (id),
    CONSTRAINT fk_anal_parametro FOREIGN KEY (parametro_id)  REFERENCES parametros_proceso (id),
    CONSTRAINT fk_anal_usuario   FOREIGN KEY (creado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Análisis SPC guardados por el usuario para consulta y comparación histórica.';


-- =============================================================================
--  SECCIÓN M7 — DASHBOARD / KPIs (Tablas de resumen precalculado)
-- =============================================================================

CREATE TABLE kpi_diario (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    fecha                   DATE                NOT NULL,
    linea_id                SMALLINT UNSIGNED   NOT NULL,
    producto_id             INT UNSIGNED        NULL     COMMENT 'NULL = resumen de toda la línea',
    turno                   ENUM('mañana','tarde','noche','dia_completo')
                                                NOT NULL DEFAULT 'dia_completo',
    -- OEE y sus componentes
    tiempo_planificado_min  SMALLINT UNSIGNED   NULL,
    tiempo_paro_min         SMALLINT UNSIGNED   NULL     DEFAULT 0,
    disponibilidad_pct      DECIMAL(6,2)        NULL     COMMENT 'OEE: Disponibilidad',
    rendimiento_pct         DECIMAL(6,2)        NULL     COMMENT 'OEE: Rendimiento (real/teórico)',
    calidad_pct             DECIMAL(6,2)        NULL     COMMENT 'OEE: Calidad (conformes/total)',
    oee_pct                 DECIMAL(6,2)        NULL     COMMENT 'OEE = Disp × Rend × Cal',
    -- Producción
    unidades_producidas     INT UNSIGNED        NULL,
    unidades_conformes      INT UNSIGNED        NULL,
    unidades_no_conformes   INT UNSIGNED        NULL,
    -- Merma consolidada
    merma_total_kg          DECIMAL(10,3)       NULL,
    merma_pct               DECIMAL(6,2)        NULL,
    -- SPC del día
    lotes_en_control        SMALLINT UNSIGNED   NULL,
    lotes_fuera_control     SMALLINT UNSIGNED   NULL,
    senales_detectadas      SMALLINT UNSIGNED   NULL     DEFAULT 0,
    cpk_promedio_dia        DECIMAL(6,4)        NULL,
    -- Recepciones del día
    mp_recibidas            SMALLINT UNSIGNED   NULL     DEFAULT 0,
    mp_rechazadas           SMALLINT UNSIGNED   NULL     DEFAULT 0,
    calculado_en            TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kpi_fecha_linea_prod_turno (fecha, linea_id, producto_id, turno),
    INDEX idx_kpi_fecha  (fecha),
    INDEX idx_kpi_linea  (linea_id),
    CONSTRAINT fk_kpi_linea    FOREIGN KEY (linea_id)   REFERENCES lineas_produccion (id),
    CONSTRAINT fk_kpi_producto FOREIGN KEY (producto_id) REFERENCES productos (id)
) ENGINE=InnoDB COMMENT='KPIs diarios precalculados. Base del M7 Dashboard. Incluye OEE.';


-- =============================================================================
--  SECCIÓN — MENÚ TRIMESTRAL Y PLANIFICACIÓN (Encargado D.E.)
-- =============================================================================

CREATE TABLE menu_trimestral (
    id              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    anio            YEAR                NOT NULL,
    trimestre       TINYINT UNSIGNED    NOT NULL COMMENT '1, 2, 3 o 4',
    nombre          VARCHAR(150)        NOT NULL COMMENT 'Ej: Menú Trimestre 1 2026 - Lote N°2 Secundaria',
    fecha_inicio    DATE                NOT NULL,
    fecha_fin       DATE                NOT NULL,
    nivel           VARCHAR(80)         NOT NULL COMMENT 'Ej: Secundaria y Ed. Especial',
    recibido_de     VARCHAR(100)        NULL     COMMENT 'Nombre de la UNACE o funcionario',
    fecha_recepcion DATE                NULL,
    obs             TEXT                NULL,
    activo          TINYINT(1)          NOT NULL DEFAULT 1,
    creado_por      INT UNSIGNED        NOT NULL,
    creado_en       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_menu_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Menú trimestral recibido de la UNACE. Base para la planificación de la producción.';

-- ---------------------------------------------------------------------------

CREATE TABLE cronograma_produccion (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    menu_id                 INT UNSIGNED        NOT NULL,
    fecha_produccion        DATE                NOT NULL,
    producto_id             INT UNSIGNED        NOT NULL,
    cantidad_planificada    INT UNSIGNED        NOT NULL COMMENT 'Unidades a producir ese día',
    numero_recetas_plan     DECIMAL(6,2)        NULL     COMMENT 'Número de recetas necesarias',
    turno_asignado          ENUM('mañana','tarde','noche','doble') NULL,
    -- Estado real vs. planificado
    cantidad_producida      INT UNSIGNED        NULL     COMMENT 'Llenado cuando se cierra el lote',
    cumplido                TINYINT(1)          NULL,
    lote_produccion_id      INT UNSIGNED        NULL     COMMENT 'FK al lote que ejecutó este ítem',
    obs                     VARCHAR(200)        NULL,
    creado_por              INT UNSIGNED        NOT NULL,
    creado_en               TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_cron_fecha   (fecha_produccion),
    INDEX idx_cron_menu    (menu_id),
    CONSTRAINT fk_cron_menu    FOREIGN KEY (menu_id)           REFERENCES menu_trimestral (id),
    CONSTRAINT fk_cron_prod    FOREIGN KEY (producto_id)       REFERENCES productos (id),
    CONSTRAINT fk_cron_lote    FOREIGN KEY (lote_produccion_id) REFERENCES lotes_produccion (id),
    CONSTRAINT fk_cron_usuario FOREIGN KEY (creado_por)        REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Cronograma diario de producción derivado del menú trimestral.';

-- ---------------------------------------------------------------------------

CREATE TABLE cronograma_semana_siremu (
    id                      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    fecha_envio             DATE                NOT NULL COMMENT 'Lunes de la semana anterior (DBC)',
    semana_inicio           DATE                NOT NULL,
    semana_fin              DATE                NOT NULL,
    generado_por_id         INT UNSIGNED        NOT NULL,
    enviado_siremu          TINYINT(1)          NOT NULL DEFAULT 0,
    fecha_envio_siremu      DATE                NULL,
    obs                     TEXT                NULL,
    creado_en               TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_cron_sir_usuario FOREIGN KEY (generado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Registro del envío semanal de cronograma al SIREMU. Obligatorio DBC GAMLP.';


-- =============================================================================
--  SECCIÓN — MÓDULO DE REPORTES Y EXPORTACIÓN
-- =============================================================================

CREATE TABLE reportes_generados (
    id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    tipo                VARCHAR(60)         NOT NULL COMMENT 'Ej: recepcion_mp, liberacion_lote, spc_xbar',
    nombre_archivo      VARCHAR(255)        NOT NULL,
    formato             ENUM('pdf','excel','csv') NOT NULL,
    filtros_json        JSON                NULL     COMMENT 'Filtros aplicados al generar el reporte',
    ruta_archivo        VARCHAR(255)        NULL,
    generado_por_id     INT UNSIGNED        NOT NULL,
    generado_en         TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_rep_usuario FOREIGN KEY (generado_por_id) REFERENCES usuarios (id)
) ENGINE=InnoDB COMMENT='Registro de todos los reportes generados por el sistema.';


-- =============================================================================
--  FIN DEL SCHEMA
--  Resumen de tablas:
--  ─────────────────────────────────────────────────────────────────────────
--  TRANSVERSAL (2):    roles, usuarios, sesiones_log
--  M0 Config (6):      lineas_produccion, productos, parametros_proceso,
--                      equipos, insumos, recetas, receta_insumos
--  M1 Recepción (3):   proveedores, recepciones_mp, stock_mp
--  M3 Mant. (2):       mantenimientos, calibraciones
--  M4 Seguim. (3):     lotes_produccion ★, consumo_mp_por_lote,
--                      tiempos_produccion
--  M2 Registro (6):    sesiones_registro, reg_proceso_amasado,
--                      reg_pesos_masa_cruda ★, reg_proceso_horneado,
--                      reg_control_envasado, reg_analisis_pt,
--                      reg_liberacion_pt
--  M5 Trazab. (1):     trazabilidad_eventos
--  M6 SPC (3):         spc_limites_control, spc_senales_detectadas,
--                      spc_analisis_guardados
--  M7 Dashboard (1):   kpi_diario
--  Menú (3):           menu_trimestral, cronograma_produccion,
--                      cronograma_semana_siremu
--  Reportes (1):       reportes_generados
--  ─────────────────────────────────────────────────────────────────────────
--  TOTAL: 31 tablas
--  ★ = tablas clave de trazabilidad
-- =============================================================================
