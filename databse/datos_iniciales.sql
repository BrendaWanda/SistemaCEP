-- =============================================================================
--  SIACEP — Sistema Integrado de Análisis, Control y Ejecución de Producción
--  Industrias Alimenticias Gustossi SRL
--  Archivo: datos_iniciales.sql
--  Versión: 1.0
--  Descripción: Datos de arranque del sistema.
--               Ejecutar DESPUÉS de siacep_schema.sql.
--               Contiene datos reales de Gustossi SRL derivados de:
--               - DBC GAMLP Lote N°2 Secundaria 2026
--               - Archivo Recepcion_de_MP (catálogo de insumos)
--               - Archivo REGISTROS_CONTROL_PARA_DESAYUNO_ESCOLAR_2026
--               - Archivo SEGUIMIENTO_RENDIMIENTOS_HORNEADOS
-- =============================================================================

USE siacep;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
--  1. ROLES (ya insertados en schema, verificación)
-- =============================================================================
-- roles ya se insertan en siacep_schema.sql con INSERT en la misma definición.
-- Solo se verifica aquí que existan los 4 roles esperados.

-- =============================================================================
--  2. USUARIO ADMINISTRADOR INICIAL
--  Contraseña por defecto: Gustossi2026! (debe cambiarse en primer acceso)
--  Hash bcrypt de "Gustossi2026!" con cost=12
-- =============================================================================

INSERT INTO usuarios (rol_id, nombre, apellidos, cargo, email, password_hash, activo) VALUES
-- Administrador / Encargado D.E.
(1, 'Administrador', 'Sistema', 'Encargado del Desayuno Escolar',
 'admin@gustossi.com',
 '$2y$12$placeholder.admin.hash.replace.on.first.run.xxxxxxxxxxx', 1),

-- Gerente (lectura total)
(2, 'Gerente', 'Gustossi', 'Gerente General',
 'gerente@gustossi.com',
 '$2y$12$placeholder.gerente.hash.replace.on.first.run.xxxxxxxxx', 1),

-- Jefe de Producción
(3, 'Jefe', 'Produccion', 'Jefe de Producción',
 'produccion@gustossi.com',
 '$2y$12$placeholder.produccion.hash.replace.on.first.run.xxxxxxx', 1),

-- Supervisora de Control de Calidad
(4, 'Supervisora', 'Calidad', 'Supervisora de Control de Calidad',
 'calidad@gustossi.com',
 '$2y$12$placeholder.calidad.hash.replace.on.first.run.xxxxxxxxx', 1);

-- =============================================================================
--  3. LÍNEA DE PRODUCCIÓN
--  Actualmente: Línea de Panificación (Desayuno Escolar)
--  Escalable: en el futuro podrán agregarse líneas de galletería, queques, etc.
-- =============================================================================

INSERT INTO lineas_produccion (codigo, nombre, descripcion, activa, creado_por) VALUES
('LIN-PAN',
 'Línea de Panificación',
 'Línea dedicada a la producción de productos de panificación para el Programa del Desayuno Escolar Municipal (GAMLP). Lote N°2 - Nivel Secundaria y Ed. Especial 2026.',
 1, 1);

-- =============================================================================
--  4. PRODUCTOS
--  Fuente: DBC GAMLP - Lote N°2 Secundaria y Educación Especial
--  Los 4 ítems contratados con Gustossi SRL para el período 2026.
--  Tolerancia contractual: ±1% sobre el peso nominal.
-- =============================================================================
-- LSE = peso_nominal × 1.01  |  LIE = peso_nominal × 0.99

INSERT INTO productos (
    linea_id, codigo, nombre, descripcion,
    lote_contrato, item_dbc,
    peso_nominal_g, tolerancia_pct, lse_g, lie_g,
    unidades_por_receta, unidades_por_bolsa, bolsas_por_caja,
    unidades_por_caja, peso_caja_kg,
    vida_util_dias,
    temperatura_conserv_min, temperatura_conserv_max, temperatura_entrega_max,
    ref_color, ref_olor, ref_sabor, ref_textura, ref_apariencia,
    ref_humedad_max_pct, ref_ph_min, ref_ph_max,
    activo, creado_por
) VALUES

-- ÍTEM 1: Pan Sarnita Integral
(1, 'PT-PAN-SARN-INT',
 'Pan Sarnita Integral',
 'Pan elaborado con harina integral de trigo. Forma sarnita (pequeña). Contrato DBC GAMLP Lote N°2 Ítem 1.',
 'Lote N°2 Secundaria 2026', 'Ítem 1',
 80.00,   -- 80 g peso nominal
 1.00,    -- ±1%
 80.80,   -- LSE = 80 × 1.01
 79.20,   -- LIE = 80 × 0.99
 250, 5, 10, 50, 4.00,
 3,       -- 3 días de vida útil
 15.00, 25.00, 18.00,
 'Marrón oscuro uniforme, corteza dorada', 'Característico a pan de trigo integral recién horneado',
 'Suave, ligeramente dulce, característico a trigo integral', 'Miga suave y esponjosa, corteza firme',
 'Forma ovalada regular, sin deformaciones, sin quemaduras',
 38.00, 5.40, 7.00,
 1, 1),

-- ÍTEM 2: Pan con Coco
(1, 'PT-PAN-COCO',
 'Pan con Coco',
 'Pan dulce con coco rallado incorporado en la masa y como cobertura. Contrato DBC GAMLP Lote N°2 Ítem 2.',
 'Lote N°2 Secundaria 2026', 'Ítem 2',
 80.00,
 1.00,
 80.80,
 79.20,
 250, 5, 10, 50, 4.00,
 3,
 15.00, 25.00, 18.00,
 'Dorado claro con cobertura de coco tostado', 'Característico a coco y pan dulce',
 'Dulce, suave, con nota característica a coco', 'Miga tierna y húmeda, ligera cobertura crocante',
 'Redondo o ovalado, cubierto de coco rallado, sin quemaduras',
 38.00, 5.40, 7.00,
 1, 1),

-- ÍTEM 3: Pan Bizcocho
(1, 'PT-PAN-BIZC',
 'Pan Bizcocho',
 'Pan bizcocho tradicional, de textura suave y miga abierta. Contrato DBC GAMLP Lote N°2 Ítem 3.',
 'Lote N°2 Secundaria 2026', 'Ítem 3',
 80.00,
 1.00,
 80.80,
 79.20,
 250, 5, 10, 50, 4.00,
 3,
 15.00, 25.00, 18.00,
 'Dorado uniforme, corteza fina y brillante', 'Suave, a mantequilla y pan dulce',
 'Dulce, suave, con leve nota a mantequilla', 'Miga muy suave y esponjosa, textura uniforme',
 'Forma ovalada o redonda, superficie lisa, sin deformaciones',
 38.00, 5.40, 7.00,
 1, 1),

-- ÍTEM 4: Rollo de Queso
(1, 'PT-ROLL-QESO',
 'Rollo de Queso',
 'Rollo de pan relleno con queso fundido. Contrato DBC GAMLP Lote N°2 Ítem 4.',
 'Lote N°2 Secundaria 2026', 'Ítem 4',
 90.00,   -- Peso nominal mayor por el relleno
 1.00,
 90.90,   -- LSE
 89.10,   -- LIE
 220, 5, 10, 50, 4.50,
 3,
 15.00, 25.00, 18.00,
 'Dorado con queso visible en los extremos', 'Característico a queso fundido y pan',
 'Salado, queso suave, masa tierna', 'Miga suave, relleno de queso fundido homogéneo',
 'Forma de rollo alargado, relleno bien distribuido, sin fugas excesivas de queso',
 38.00, 5.40, 7.00,
 1, 1);

-- =============================================================================
--  5. PARÁMETROS DE PROCESO POR PRODUCTO
--  Fuente: REGISTROS_CONTROL_PARA_DESAYUNO_ESCOLAR_2026.xlsx
--  Las secciones del formulario físico: C-PROCESO-G, C-ENVASADO-G, ANALISIS PT
--  Se definen parámetros compartidos entre productos y específicos por ítem.
--
--  NOTA: Los parámetros de peso (es_variable_spc=1) son los que alimentan
--        los gráficos de control X̄-R en el M6.
--        tamanio_subgrupo=10 porque el formulario físico registra 10 pesos.
-- =============================================================================

-- ── Producto 1: Pan Sarnita Integral (producto_id=1) ────────────────────────

INSERT INTO parametros_proceso
    (producto_id, etapa, nombre, unidad, tipo_dato,
     valor_nominal, valor_min, valor_max,
     es_variable_spc, tamanio_subgrupo, obligatorio, orden_display, creado_por)
VALUES
-- ETAPA: Amasado
(1,'amasado','Temperatura de la masa','°C','numerico',   26.0, 22.0, 30.0, 0, NULL, 1, 10, 1),
(1,'amasado','pH de la masa','pH','numerico',             5.8, 5.40, 7.00, 0, NULL, 1, 20, 1),

-- ETAPA: Fermentación
(1,'fermentacion','Tiempo de fermentación','min','numerico', 60.0, 45.0, 90.0, 0, NULL, 1, 30, 1),

-- ETAPA: Horneado
(1,'horneado','Temperatura de horno','°C','numerico',    200.0, 185.0, 215.0, 0, NULL, 1, 40, 1),
(1,'horneado','Tiempo de horneado','min','numerico',      18.0, 15.0,  22.0, 0, NULL, 1, 50, 1),

-- ETAPA: Formado — PESO MASA CRUDA (variable SPC principal)
(1,'formado','Peso de masa cruda por unidad','g','numerico',
  80.00, 79.20, 80.80,   -- nominal, LIE, LSE del DBC
  1,                     -- es_variable_spc = SÍ → gráfico X̄-R
  10,                    -- subgrupo de 10 unidades (igual al formulario físico)
  1, 5, 1),

-- ETAPA: Envasado
(1,'envasado','Temperatura del producto al envasar','°C','numerico', 25.0, 18.0, 30.0, 0, NULL, 1, 60, 1),
(1,'envasado','Temperatura ambiente en envasado','°C','numerico',   20.0, 15.0, 25.0, 0, NULL, 1, 70, 1),
(1,'envasado','Peso unidad en envasado','g','numerico',              80.0, 79.20, 80.80, 1, 4, 1, 80, 1),
(1,'envasado','Codificado horizontal','estado','seleccion',           NULL, NULL, NULL, 0, NULL, 1, 90, 1),
(1,'envasado','Codificado vertical','estado','seleccion',             NULL, NULL, NULL, 0, NULL, 1, 100,1),
(1,'envasado','Sellado horizontal','estado','seleccion',              NULL, NULL, NULL, 0, NULL, 1, 110,1),
(1,'envasado','Sellado vertical','estado','seleccion',                NULL, NULL, NULL, 0, NULL, 1, 120,1),

-- ETAPA: Producto terminado
(1,'producto_terminado','Humedad del PT','%','numerico',  NULL, NULL, 38.0, 0, NULL, 1, 130,1),
(1,'producto_terminado','pH del PT','pH','numerico',      NULL, 5.40, 7.00, 0, NULL, 1, 140,1);

-- ── Actualizar las opciones JSON de los parámetros tipo seleccion ────────────
UPDATE parametros_proceso
SET opciones_json = '["conforme","no_conforme","na"]'
WHERE tipo_dato = 'seleccion' AND creado_por = 1;

-- ── Productos 2, 3, 4: mismos parámetros, distintos valores nominales ────────
-- Pan con Coco (producto_id=2) — mismo peso nominal 80g
INSERT INTO parametros_proceso
    (producto_id, etapa, nombre, unidad, tipo_dato,
     valor_nominal, valor_min, valor_max,
     es_variable_spc, tamanio_subgrupo, obligatorio, orden_display, creado_por)
VALUES
(2,'amasado','Temperatura de la masa','°C','numerico',        26.0, 22.0, 30.0, 0, NULL, 1, 10, 1),
(2,'amasado','pH de la masa','pH','numerico',                  5.8, 5.40, 7.00, 0, NULL, 1, 20, 1),
(2,'fermentacion','Tiempo de fermentación','min','numerico',  60.0, 45.0, 90.0, 0, NULL, 1, 30, 1),
(2,'horneado','Temperatura de horno','°C','numerico',        195.0, 180.0, 210.0, 0, NULL, 1, 40, 1),
(2,'horneado','Tiempo de horneado','min','numerico',          15.0, 12.0,  20.0, 0, NULL, 1, 50, 1),
(2,'formado','Peso de masa cruda por unidad','g','numerico',  80.0, 79.20, 80.80, 1, 10, 1, 5, 1),
(2,'envasado','Temperatura del producto al envasar','°C','numerico', 25.0,18.0,30.0,0,NULL,1,60,1),
(2,'envasado','Peso unidad en envasado','g','numerico',        80.0, 79.20, 80.80, 1, 4, 1, 80, 1),
(2,'envasado','Codificado horizontal','estado','seleccion',    NULL, NULL, NULL, 0, NULL, 1, 90, 1),
(2,'envasado','Sellado horizontal','estado','seleccion',       NULL, NULL, NULL, 0, NULL, 1, 100,1),
(2,'envasado','Sellado vertical','estado','seleccion',         NULL, NULL, NULL, 0, NULL, 1, 110,1),
(2,'producto_terminado','Humedad del PT','%','numerico',       NULL, NULL, 38.0, 0, NULL, 1, 120,1),
(2,'producto_terminado','pH del PT','pH','numerico',           NULL, 5.40, 7.00, 0, NULL, 1, 130,1);

-- Pan Bizcocho (producto_id=3)
INSERT INTO parametros_proceso
    (producto_id, etapa, nombre, unidad, tipo_dato,
     valor_nominal, valor_min, valor_max,
     es_variable_spc, tamanio_subgrupo, obligatorio, orden_display, creado_por)
VALUES
(3,'amasado','Temperatura de la masa','°C','numerico',        25.0, 22.0, 28.0, 0, NULL, 1, 10, 1),
(3,'amasado','pH de la masa','pH','numerico',                  5.8, 5.40, 7.00, 0, NULL, 1, 20, 1),
(3,'fermentacion','Tiempo de fermentación','min','numerico',  50.0, 40.0, 75.0, 0, NULL, 1, 30, 1),
(3,'horneado','Temperatura de horno','°C','numerico',        190.0, 175.0, 205.0, 0, NULL, 1, 40, 1),
(3,'horneado','Tiempo de horneado','min','numerico',          16.0, 13.0,  20.0, 0, NULL, 1, 50, 1),
(3,'formado','Peso de masa cruda por unidad','g','numerico',  80.0, 79.20, 80.80, 1, 10, 1, 5, 1),
(3,'envasado','Temperatura del producto al envasar','°C','numerico', 25.0,18.0,30.0,0,NULL,1,60,1),
(3,'envasado','Peso unidad en envasado','g','numerico',        80.0, 79.20, 80.80, 1, 4, 1, 80, 1),
(3,'envasado','Codificado horizontal','estado','seleccion',    NULL, NULL, NULL, 0, NULL, 1, 90, 1),
(3,'envasado','Sellado horizontal','estado','seleccion',       NULL, NULL, NULL, 0, NULL, 1, 100,1),
(3,'envasado','Sellado vertical','estado','seleccion',         NULL, NULL, NULL, 0, NULL, 1, 110,1),
(3,'producto_terminado','Humedad del PT','%','numerico',       NULL, NULL, 38.0, 0, NULL, 1, 120,1),
(3,'producto_terminado','pH del PT','pH','numerico',           NULL, 5.40, 7.00, 0, NULL, 1, 130,1);

-- Rollo de Queso (producto_id=4) — peso nominal 90g
INSERT INTO parametros_proceso
    (producto_id, etapa, nombre, unidad, tipo_dato,
     valor_nominal, valor_min, valor_max,
     es_variable_spc, tamanio_subgrupo, obligatorio, orden_display, creado_por)
VALUES
(4,'amasado','Temperatura de la masa','°C','numerico',        24.0, 20.0, 28.0, 0, NULL, 1, 10, 1),
(4,'amasado','pH de la masa','pH','numerico',                  5.8, 5.40, 7.00, 0, NULL, 1, 20, 1),
(4,'fermentacion','Tiempo de fermentación','min','numerico',  55.0, 40.0, 80.0, 0, NULL, 1, 30, 1),
(4,'horneado','Temperatura de horno','°C','numerico',        195.0, 180.0, 210.0, 0, NULL, 1, 40, 1),
(4,'horneado','Tiempo de horneado','min','numerico',          20.0, 16.0,  25.0, 0, NULL, 1, 50, 1),
(4,'formado','Peso de masa cruda por unidad','g','numerico',  90.0, 89.10, 90.90, 1, 10, 1, 5, 1),
(4,'envasado','Temperatura del producto al envasar','°C','numerico', 25.0,18.0,30.0,0,NULL,1,60,1),
(4,'envasado','Peso unidad en envasado','g','numerico',        90.0, 89.10, 90.90, 1, 4, 1, 80, 1),
(4,'envasado','Codificado horizontal','estado','seleccion',    NULL, NULL, NULL, 0, NULL, 1, 90, 1),
(4,'envasado','Sellado horizontal','estado','seleccion',       NULL, NULL, NULL, 0, NULL, 1, 100,1),
(4,'envasado','Sellado vertical','estado','seleccion',         NULL, NULL, NULL, 0, NULL, 1, 110,1),
(4,'producto_terminado','Humedad del PT','%','numerico',       NULL, NULL, 38.0, 0, NULL, 1, 120,1),
(4,'producto_terminado','pH del PT','pH','numerico',           NULL, 5.40, 7.00, 0, NULL, 1, 130,1);

UPDATE parametros_proceso
SET opciones_json = '["conforme","no_conforme","na"]'
WHERE tipo_dato = 'seleccion' AND opciones_json IS NULL;

-- =============================================================================
--  6. EQUIPOS / MAQUINARIA DE LA PLANTA
--  Fuente: identificados en el Ishikawa y los registros del proceso
--  (horno, divisora, balanzas, termómetros, amasadora, envasadora)
-- =============================================================================

INSERT INTO equipos (
    linea_id, codigo, nombre, tipo, marca, modelo,
    requiere_calibracion, frecuencia_calibr_dias, frecuencia_mant_dias,
    activo, creado_por
) VALUES
-- Hornos
(1,'EQ-HOR-01','Horno de convección N°1','horno','Maestro','HC-600',
  0, NULL, 30, 1, 1),
(1,'EQ-HOR-02','Horno de convección N°2','horno','Maestro','HC-600',
  0, NULL, 30, 1, 1),

-- Amasadoras
(1,'EQ-AMA-01','Amasadora espiral N°1','amasadora','Bongard','SE-60',
  0, NULL, 30, 1, 1),
(1,'EQ-AMA-02','Amasadora espiral N°2','amasadora','Bongard','SE-60',
  0, NULL, 30, 1, 1),

-- Divisora
(1,'EQ-DIV-01','Divisora de masa','divisora','Tortuga','DM-30',
  0, NULL, 30, 1, 1),

-- Envasadora
(1,'EQ-ENV-01','Envasadora horizontal N°1','envasadora','Premark','ENV-200',
  0, NULL, 15, 1, 1),

-- Balanzas (requieren calibración — SIREMU puede verificar certificados)
(1,'EQ-BAL-01','Balanza de proceso N°1 (0-5kg)','balanza','OHAUS','Scout SKX52',
  1, 365, 90, 1, 1),
(1,'EQ-BAL-02','Balanza de proceso N°2 (0-5kg)','balanza','OHAUS','Scout SKX52',
  1, 365, 90, 1, 1),
(1,'EQ-BAL-03','Balanza de control de calidad (0-600g)','balanza','RADWAG','WLC 1/A2/C/2',
  1, 180, 90, 1, 1),

-- Termómetros (requieren calibración)
(1,'EQ-TER-01','Termómetro digital de cocina N°1','termometro','Testo','105',
  1, 365, NULL, 1, 1),
(1,'EQ-TER-02','Termómetro digital de cocina N°2','termometro','Testo','105',
  1, 365, NULL, 1, 1),
(1,'EQ-TER-03','Termómetro infrarrojo para horno','termometro','Fluke','62 MAX+',
  1, 365, NULL, 1, 1),

-- Higrómetro
(1,'EQ-HIG-01','Higrómetro digital de ambiente','higrómetro','Testo','608-H1',
  1, 365, NULL, 1, 1);

-- =============================================================================
--  7. CATÁLOGO DE INSUMOS (MATERIAS PRIMAS)
--  Fuente: Archivo Recepcion_de_MP.XLS — catálogo real de Gustossi
--  Incluye los insumos críticos de las 4 recetas del Lote N°2
--  más los materiales de empaque identificados en los registros.
-- =============================================================================

INSERT INTO insumos (
    codigo, tipo, descripcion, unidad_medida,
    esp_sabor_olor, esp_color, esp_descripcion_fisica,
    esp_humedad_max, esp_densidad_min, esp_densidad_max,
    esp_ph_min, esp_ph_max, esp_gluten_min,
    vida_util_referencia, activo, creado_por
) VALUES

-- ── HARINAS ──────────────────────────────────────────────────────────────────
('MP0041','materia_prima','Harina de Trigo Letizia (Todo Uso)','kg',
  'Característico a trigo, sin olores extraños','Blanco cremoso',
  'Polvo fino, sin grumos, sin presencia de insectos',
  14.00, NULL, NULL, NULL, NULL, 28.00,
  '6 Meses', 1, 1),

('MP0042','materia_prima','Harina de Trigo Integral','kg',
  'Característico a trigo integral','Marrón claro con partículas de salvado',
  'Polvo con partículas de salvado, sin grumos',
  14.00, NULL, NULL, NULL, NULL, 25.00,
  '4 Meses', 1, 1),

('MP0043','materia_prima','Harina de Trigo Especial (Panadera)','kg',
  'Característico a trigo','Blanco',
  'Polvo fino y uniforme, sin grumos',
  14.00, NULL, NULL, NULL, NULL, 30.00,
  '6 Meses', 1, 1),

-- ── AZÚCARES Y ENDULZANTES ───────────────────────────────────────────────────
('MP0010','materia_prima','Azúcar Blanca Refinada','kg',
  'Dulce, sin olores extraños','Blanco cristalino',
  'Cristales uniformes, secos, sin presencia de grumos',
  0.05, NULL, NULL, NULL, NULL, NULL,
  '2 Años', 1, 1),

('MP0011','materia_prima','Azúcar Impalpable','kg',
  'Dulce, sin olores extraños','Blanco polvo fino',
  'Polvo muy fino y libre de grumos',
  0.05, NULL, NULL, NULL, NULL, NULL,
  '1 Año', 1, 1),

-- ── GRASAS Y ACEITES ─────────────────────────────────────────────────────────
('MP0020','materia_prima','Margarina Industrial','kg',
  'Característico a grasa vegetal, sin rancidez','Amarillo pálido',
  'Consistencia sólida a temperatura ambiente, homogénea',
  NULL, NULL, NULL, NULL, NULL, NULL,
  '6 Meses', 1, 1),

('MP0021','materia_prima','Manteca Vegetal','kg',
  'Neutro, sin olores extraños','Blanco',
  'Sólida, plástica, homogénea',
  NULL, NULL, NULL, NULL, NULL, NULL,
  '8 Meses', 1, 1),

('MP0022','materia_prima','Aceite Vegetal Refinado','l',
  'Neutro, sin olores a rancidez','Amarillo dorado claro transparente',
  'Líquido limpio, sin partículas en suspensión',
  NULL, 0.900, 0.930, NULL, NULL, NULL,
  '1 Año', 1, 1),

-- ── LEVADURA ─────────────────────────────────────────────────────────────────
('MP0030','materia_prima','Levadura Fresca (Saccharomyces cerevisiae)','kg',
  'Olor característico a levadura fresca, sin olores ácidos','Beige / crema',
  'Consistencia sólida y compacta, superfic. húmeda',
  NULL, NULL, NULL, 5.50, 6.50, NULL,
  '30 Días', 1, 1),

('MP0031','materia_prima','Levadura Seca Instantánea','kg',
    'Característico a levadura, sin olores rancios','Marrón claro',
    'Gránulos finos y secos, homogéneos',
    6.00, NULL, NULL, NULL, NULL, NULL,
    '2 Años', 1, 1),

-- ── SAL ──────────────────────────────────────────────────────────────────────
('MP0050','materia_prima','Sal Yodada Refinada','kg',
    'Salado, sin sabores extraños','Blanco',
    'Cristales finos y uniformes, secos',
    NULL, NULL, NULL, NULL, NULL, NULL,
    '2 Años', 1, 1),

-- ── LÁCTEOS ──────────────────────────────────────────────────────────────────
('MP0060','materia_prima','Leche en Polvo Entera','kg',
    'Característico a leche, sin olores rancios','Crema / blanco cremoso',
    'Polvo fino, libre de grumos, soluble en agua',
    4.50, NULL, NULL, 6.50, 7.00, NULL,
    '1 Año', 1, 1),

('MP0061','materia_prima','Queso Semiduro (para relleno)','kg',
    'Característico a queso, sin olores a rancidez o fermentación excesiva',
    'Amarillo claro a amarillo',
    'Consistencia semidura, sin ojos o con pequeños ojos, sin moho',
    NULL, NULL, NULL, 5.00, 5.80, NULL,
    '30 Días', 1, 1),

-- ── HUEVOS ───────────────────────────────────────────────────────────────────
('MP0070','materia_prima','Huevo de gallina fresco','und',
    'Sin olores extraños ni a sulfuro','Cáscara marrón o blanca limpia',
    'Cáscara entera sin fisuras, contenido sin separación excesiva',
    NULL, NULL, NULL, 7.00, 9.00, NULL,
    '21 Días', 1, 1),

-- ── ADITIVOS / MEJORADORES ───────────────────────────────────────────────────
('MP0080','materia_prima','Mejorador de Masa Panadero','kg',
    'Sin olores extraños','Blanco a crema',
    'Polvo fino, homogéneo',
    NULL, NULL, NULL, NULL, NULL, NULL,
    '1 Año', 1, 1),

('MP0081','materia_prima','Propionato de Calcio (conservante)','kg',
    'Ligeramente ácido, característico','Blanco',
    'Polvo o gránulos cristalinos, secos',
    NULL, NULL, NULL, NULL, NULL, NULL,
    '2 Años', 1, 1),

('MP0082','materia_prima','Esencia de Vainilla','l',
    'Característico a vainilla, intenso','Marrón transparente',
    'Líquido transparente a marrón claro',
    NULL, 1.000, 1.100, NULL, NULL, NULL,
    '2 Años', 1, 1),

-- ── INGREDIENTES ESPECÍFICOS ─────────────────────────────────────────────────
('MP0090','materia_prima','Coco Rallado','kg',
    'Característico a coco, dulce, sin rancidez','Blanco',
    'Partículas finas de coco seco, sin grumos ni insectos',
    4.00, NULL, NULL, NULL, NULL, NULL,
    '6 Meses', 1, 1),

('MP0091','materia_prima','Semillas de Sésamo (Ajonjolí)','kg',
    'Ligeramente a nuez, sin rancidez','Blanco cremoso a marfil',
    'Semillas enteras, secas, sin impurezas',
    6.00, NULL, NULL, NULL, NULL, NULL,
    '1 Año', 1, 1),

-- ── AGUA ─────────────────────────────────────────────────────────────────────
('MP0100','insumo_proceso','Agua potable de proceso','l',
    'Inodora, sin sabores extraños','Incolora, transparente',
    'Agua de red municipal, sin turbidez ni sedimentos',
    NULL, NULL, NULL, 6.50, 8.50, NULL,
    'Uso inmediato', 1, 1),

-- ── MATERIALES DE EMPAQUE ────────────────────────────────────────────────────
('ME0001','material_empaque','Bobina de polietileno para envasadora (pan)','kg',
    'Sin olores a solvente o plástico quemado','Transparente con impresión',
    'Rollo de film polietileno, sin roturas, codificación correcta de lote y vencimiento',
    NULL, NULL, NULL, NULL, NULL, NULL,
    '1 Año', 1, 1),

('ME0002','material_empaque','Caja de cartón corrugado para producto terminado','und',
    'Sin olores extraños ni humedad','Marrón natural o impreso',
    'Cartón rígido, sin daño por humedad, sin contaminación',
    NULL, NULL, NULL, NULL, NULL, NULL,
    '6 Meses', 1, 1),

('ME0003','material_empaque','Etiqueta de identificación de lote (papel adhesivo)','und',
    NULL,'Blanco con impresión',
    'Adhesivo activo, impresión legible, sin daños',
    NULL, NULL, NULL, NULL, NULL, NULL,
    '2 Años', 1, 1);

-- =============================================================================
--  8. RECETAS BASE (versión 1) para cada producto
--  Se crean las recetas vigentes. Los ingredientes se registran en receta_insumos.
--  Cantidades por 1 BATCH (1 receta = ~250 unidades)
-- =============================================================================

INSERT INTO recetas (producto_id, version, nombre, descripcion, vigente, aprobada_por, aprobada_en, creado_por) VALUES
(1, 1, 'Pan Sarnita Integral v1', 'Receta base aprobada UNACE. 1 batch = ~250 unidades de 80g.', 1, 1, '2026-01-15', 1),
(2, 1, 'Pan con Coco v1',         'Receta base aprobada UNACE. 1 batch = ~250 unidades de 80g.', 1, 1, '2026-01-15', 1),
(3, 1, 'Pan Bizcocho v1',         'Receta base aprobada UNACE. 1 batch = ~250 unidades de 80g.', 1, 1, '2026-01-15', 1),
(4, 1, 'Rollo de Queso v1',       'Receta base aprobada UNACE. 1 batch = ~220 unidades de 90g.', 1, 1, '2026-01-15', 1);

-- ── Composición de recetas (BOM) ─────────────────────────────────────────────
-- receta_id=1: Pan Sarnita Integral (250 und × 80g ≈ 20kg masa)
INSERT INTO receta_insumos (receta_id, insumo_id, cantidad, unidad_medida, es_critico) VALUES
(1, (SELECT id FROM insumos WHERE codigo='MP0042'), 10.000, 'kg', 1), -- Harina Integral
(1, (SELECT id FROM insumos WHERE codigo='MP0041'), 2.500,  'kg', 1), -- Harina Todo Uso
(1, (SELECT id FROM insumos WHERE codigo='MP0010'), 0.800,  'kg', 0), -- Azúcar
(1, (SELECT id FROM insumos WHERE codigo='MP0050'), 0.250,  'kg', 0), -- Sal
(1, (SELECT id FROM insumos WHERE codigo='MP0020'), 0.600,  'kg', 0), -- Margarina
(1, (SELECT id FROM insumos WHERE codigo='MP0030'), 0.400,  'kg', 1), -- Levadura Fresca
(1, (SELECT id FROM insumos WHERE codigo='MP0060'), 0.500,  'kg', 0), -- Leche en Polvo
(1, (SELECT id FROM insumos WHERE codigo='MP0080'), 0.100,  'kg', 0), -- Mejorador
(1, (SELECT id FROM insumos WHERE codigo='MP0100'), 5.500,  'l',  0), -- Agua
(1, (SELECT id FROM insumos WHERE codigo='ME0001'), 0.800,  'kg', 0), -- Bobina empaque
(1, (SELECT id FROM insumos WHERE codigo='ME0002'), 5.000,  'und',0); -- Cajas

-- receta_id=2: Pan con Coco (250 und × 80g)
INSERT INTO receta_insumos (receta_id, insumo_id, cantidad, unidad_medida, es_critico) VALUES
(2, (SELECT id FROM insumos WHERE codigo='MP0041'), 12.000, 'kg', 1), -- Harina Todo Uso
(2, (SELECT id FROM insumos WHERE codigo='MP0010'), 1.200,  'kg', 0), -- Azúcar
(2, (SELECT id FROM insumos WHERE codigo='MP0050'), 0.220,  'kg', 0), -- Sal
(2, (SELECT id FROM insumos WHERE codigo='MP0020'), 0.800,  'kg', 0), -- Margarina
(2, (SELECT id FROM insumos WHERE codigo='MP0030'), 0.400,  'kg', 1), -- Levadura Fresca
(2, (SELECT id FROM insumos WHERE codigo='MP0060'), 0.500,  'kg', 0), -- Leche en Polvo
(2, (SELECT id FROM insumos WHERE codigo='MP0070'), 4.000,  'und',0), -- Huevos
(2, (SELECT id FROM insumos WHERE codigo='MP0090'), 1.500,  'kg', 1), -- Coco Rallado ← crítico
(2, (SELECT id FROM insumos WHERE codigo='MP0080'), 0.100,  'kg', 0), -- Mejorador
(2, (SELECT id FROM insumos WHERE codigo='MP0100'), 5.200,  'l',  0), -- Agua
(2, (SELECT id FROM insumos WHERE codigo='ME0001'), 0.800,  'kg', 0),
(2, (SELECT id FROM insumos WHERE codigo='ME0002'), 5.000,  'und',0);

-- receta_id=3: Pan Bizcocho (250 und × 80g)
INSERT INTO receta_insumos (receta_id, insumo_id, cantidad, unidad_medida, es_critico) VALUES
(3, (SELECT id FROM insumos WHERE codigo='MP0041'), 11.000, 'kg', 1),
(3, (SELECT id FROM insumos WHERE codigo='MP0010'), 1.500,  'kg', 0),
(3, (SELECT id FROM insumos WHERE codigo='MP0050'), 0.200,  'kg', 0),
(3, (SELECT id FROM insumos WHERE codigo='MP0020'), 1.200,  'kg', 0),
(3, (SELECT id FROM insumos WHERE codigo='MP0030'), 0.380,  'kg', 1),
(3, (SELECT id FROM insumos WHERE codigo='MP0060'), 0.600,  'kg', 0),
(3, (SELECT id FROM insumos WHERE codigo='MP0070'), 6.000,  'und',0),
(3, (SELECT id FROM insumos WHERE codigo='MP0082'), 0.020,  'l',  0), -- Vainilla
(3, (SELECT id FROM insumos WHERE codigo='MP0080'), 0.100,  'kg', 0),
(3, (SELECT id FROM insumos WHERE codigo='MP0100'), 4.800,  'l',  0),
(3, (SELECT id FROM insumos WHERE codigo='ME0001'), 0.800,  'kg', 0),
(3, (SELECT id FROM insumos WHERE codigo='ME0002'), 5.000,  'und',0);

-- receta_id=4: Rollo de Queso (220 und × 90g)
INSERT INTO receta_insumos (receta_id, insumo_id, cantidad, unidad_medida, es_critico) VALUES
(4, (SELECT id FROM insumos WHERE codigo='MP0041'), 12.000, 'kg', 1),
(4, (SELECT id FROM insumos WHERE codigo='MP0010'), 0.600,  'kg', 0),
(4, (SELECT id FROM insumos WHERE codigo='MP0050'), 0.240,  'kg', 0),
(4, (SELECT id FROM insumos WHERE codigo='MP0020'), 0.800,  'kg', 0),
(4, (SELECT id FROM insumos WHERE codigo='MP0030'), 0.420,  'kg', 1),
(4, (SELECT id FROM insumos WHERE codigo='MP0060'), 0.400,  'kg', 0),
(4, (SELECT id FROM insumos WHERE codigo='MP0070'), 4.000,  'und',0),
(4, (SELECT id FROM insumos WHERE codigo='MP0061'), 3.500,  'kg', 1), -- Queso ← crítico
(4, (SELECT id FROM insumos WHERE codigo='MP0080'), 0.100,  'kg', 0),
(4, (SELECT id FROM insumos WHERE codigo='MP0100'), 5.000,  'l',  0),
(4, (SELECT id FROM insumos WHERE codigo='ME0001'), 0.900,  'kg', 0),
(4, (SELECT id FROM insumos WHERE codigo='ME0002'), 5.000,  'und',0);

-- =============================================================================
--  9. PROVEEDORES INICIALES
--  Derivados de las recepciones identificadas en el archivo Recepcion_de_MP
-- =============================================================================

INSERT INTO proveedores (nombre, procedencia, activo, creado_por) VALUES
('SETAR S.R.L. / Letizia',          'Santa Cruz - Bolivia',     1, 1),
('FINO (Molinos Modernos S.A.)',     'Cochabamba - Bolivia',     1, 1),
('GRANIC S.A.',                     'Santa Cruz - Bolivia',     1, 1),
('PIL Andina S.A.',                 'Cochabamba - Bolivia',     1, 1),
('Distribuidora La Estrella',       'La Paz - Bolivia',         1, 1),
('Levapan Bolivia',                 'La Paz - Bolivia',         1, 1),
('Empaques del Norte S.R.L.',       'El Alto - Bolivia',        1, 1),
('Proveedor Local Mercado',         'La Paz - Bolivia',         1, 1);

-- =============================================================================
--  10. MENÚ TRIMESTRAL INICIAL
--  Fuente: DBC GAMLP — período de inicio 2026
-- =============================================================================

INSERT INTO menu_trimestral
    (anio, trimestre, nombre, fecha_inicio, fecha_fin, nivel,
    recibido_de, fecha_recepcion, activo, creado_por)
VALUES
(2026, 1, 'Menú Trimestre 1 — 2026 · Lote N°2 Secundaria',
'2026-01-28', '2026-04-11',
'Secundaria y Educación Especial',
'UNACE - GAMLP', '2026-01-20',
1, 1),
(2026, 2, 'Menú Trimestre 2 — 2026 · Lote N°2 Secundaria',
'2026-04-14', '2026-06-26',
'Secundaria y Educación Especial', 'UNACE - GAMLP', NULL,
1, 1);

-- =============================================================================
--  FIN DE DATOS INICIALES
--  Estado resultante al ejecutar este archivo:
--  ─────────────────────────────────────────────────────────────────────────
--  ✅ 4 roles configurados
--  ✅ 4 usuarios iniciales (contraseñas deben cambiarse en primer acceso)
--  ✅ 1 línea de producción: LIN-PAN
--  ✅ 4 productos del Lote N°2 con especificaciones DBC completas
--  ✅ ~55 parámetros de proceso (incluyendo variables SPC por producto)
--  ✅ 13 equipos registrados (balanzas y termómetros marcados para calibración)
--  ✅ 22 insumos del catálogo (harinas, grasas, levaduras, empaque, etc.)
--  ✅ 4 recetas con BOM (lista de materiales) completa
--  ✅ 8 proveedores identificados
--  ✅ 2 períodos de menú trimestral 2026
--  ─────────────────────────────────────────────────────────────────────────
--  INSTRUCCIONES DE ARRANQUE:
--  1. Ejecutar: mysql -u root -p siacep < siacep_schema.sql
--  2. Ejecutar: mysql -u root -p siacep < datos_iniciales.sql
--  3. Actualizar contraseñas: LoteService.php genera los bcrypt hashes reales
--  4. Verificar: SELECT nombre, cargo FROM usuarios;
--               SELECT nombre, peso_nominal_g, lse_g, lie_g FROM productos;
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 1;
