<?php
namespace App\Controllers\M0_Configuracion;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\LineaProduccion;

class LineaController extends Controller
{
    private LineaProduccion $model;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m0_configuracion');
        $this->model = new LineaProduccion();
    }

    public function index(): void
    {
        $lineas = $this->model->todasConConteo();
        $this->render('m0_configuracion/lineas/index', [
            'pageTitle'  => 'Líneas de Producción',
            'breadcrumb' => [
                ['label' => 'Configuración', 'url' => APP_URL . '/m0/lineas'],
                ['label' => 'Líneas de Producción'],
            ],
            'lineas'   => $lineas,
            'canWrite' => Auth::canWrite('m0_configuracion'),
        ]);
    }

    public function nueva(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->render('m0_configuracion/lineas/form', [
            'pageTitle'  => 'Nueva Línea de Producción',
            'breadcrumb' => [
                ['label' => 'Configuración', 'url' => APP_URL . '/m0/lineas'],
                ['label' => 'Líneas', 'url' => APP_URL . '/m0/lineas'],
                ['label' => 'Nueva'],
            ],
            'linea'  => null,
            'accion' => 'crear',
        ]);
    }

    public function crear(): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();

        $codigo = strtoupper(trim($this->input('codigo')));
        $nombre = $this->input('nombre');
        $desc   = $this->input('descripcion');

        $errores = [];
        if (empty($codigo)) $errores[] = 'El código es requerido.';
        if (empty($nombre)) $errores[] = 'El nombre es requerido.';
        if ($this->model->codigoExiste($codigo)) {
            $errores[] = "El código '{$codigo}' ya existe.";
        }

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/m0/lineas/nueva');
        }

        $this->model->create([
            'codigo'      => $codigo,
            'nombre'      => $nombre,
            'descripcion' => $desc,
            'activa'      => 1,
            'creado_por'  => Auth::id(),
        ]);

        $this->redirectWithSuccess('/m0/lineas', "Línea '{$nombre}' creada correctamente.");
    }

    public function editar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $linea = $this->model->find((int)$params['id']);
        if (!$linea) {
            $this->flash('error', 'Línea no encontrada.');
            $this->redirect('/m0/lineas');
        }

        $this->render('m0_configuracion/lineas/form', [
            'pageTitle'  => 'Editar Línea: ' . $linea['nombre'],
            'breadcrumb' => [
                ['label' => 'Configuración', 'url' => APP_URL . '/m0/lineas'],
                ['label' => 'Líneas', 'url' => APP_URL . '/m0/lineas'],
                ['label' => 'Editar'],
            ],
            'linea'  => $linea,
            'accion' => 'editar',
        ]);
    }

    public function actualizar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id = (int)$params['id'];

        $codigo = strtoupper(trim($this->input('codigo')));
        $nombre = $this->input('nombre');
        $desc   = $this->input('descripcion');

        $errores = [];
        if (empty($codigo)) $errores[] = 'El código es requerido.';
        if (empty($nombre)) $errores[] = 'El nombre es requerido.';
        if ($this->model->codigoExiste($codigo, $id)) {
            $errores[] = "El código '{$codigo}' ya está en uso.";
        }

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect("/m0/lineas/{$id}/editar");
        }

        $this->model->update($id, [
            'codigo'      => $codigo,
            'nombre'      => $nombre,
            'descripcion' => $desc,
        ]);

        $this->redirectWithSuccess('/m0/lineas', 'Línea actualizada correctamente.');
    }

    public function toggleActivo(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id    = (int)$params['id'];
        $linea = $this->model->find($id);
        if (!$linea) $this->jsonError('No encontrado', 404);

        $nuevoEstado = $linea['activa'] ? 0 : 1;
        $this->model->update($id, ['activa' => $nuevoEstado]);
        $msg = $nuevoEstado ? 'Línea activada.' : 'Línea desactivada.';
        if ($this->isAjax()) $this->jsonSuccess(['activa' => $nuevoEstado], $msg);
        $this->redirectWithSuccess('/m0/lineas', $msg);
    }

    // POST /m0/lineas/:id/eliminar ← NUEVO
    public function eliminar(array $params): void
    {
        Auth::requireWrite('m0_configuracion');
        $this->verifyCsrf();
        $id    = (int)$params['id'];
        $linea = $this->model->find($id);

        if (!$linea) {
            $this->flash('error', 'Línea no encontrada.');
            $this->redirect('/m0/lineas');
        }

        // Verificar que no tenga productos asociados
        $tieneProductos = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM productos WHERE linea_id = ?", [$id]
        );
        if ($tieneProductos > 0) {
            $this->flash('error',
                "No se puede eliminar '{$linea['nombre']}' — tiene {$tieneProductos} producto(s) asociado(s). Desactívela en su lugar.");
            $this->redirect('/m0/lineas');
        }

        $nombre = $linea['nombre'];
        $this->model->delete($id);
        $this->redirectWithSuccess('/m0/lineas',
            "Línea '{$nombre}' eliminada correctamente.");
    }
}