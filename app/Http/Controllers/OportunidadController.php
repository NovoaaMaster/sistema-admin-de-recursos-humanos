<?php

namespace App\Http\Controllers;

use App\Crm\DirectorioClientes;
use App\Crm\Embudo;
use App\Http\Controllers\Concerns\EligeCliente;
use App\Models\Oportunidad;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class OportunidadController extends Controller
{
    use EligeCliente;

    /** El embudo: antes de mostrarlo trae las cotizaciones nuevas de PCLAP. */
    public function index(Embudo $embudo, DirectorioClientes $directorio): View
    {
        try {
            $embudo->sincronizarProformas();
            $errorPclap = null;
        } catch (Throwable $error) {
            report($error);
            $errorPclap = 'No se pudieron traer las cotizaciones de PCLAP. Revisa que MySQL esté encendido en XAMPP.';
        }

        return view('embudo.index', [
            'columnas' => $embudo->columnas(),
            'valorAbierto' => $embudo->valorAbierto(),
            'opcionesClientes' => $this->opcionesClientes($directorio),
            'errorPclap' => $errorPclap,
        ]);
    }

    public function store(Request $request, DirectorioClientes $directorio): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'cliente_ref' => ['nullable', 'string'],
            'contacto_nombre' => ['required_without:cliente_ref', 'nullable', 'string', 'max:150'],
            'contacto_telefono' => ['nullable', 'string', 'max:20'],
        ]);
        $cliente = $this->clienteElegido($datos['cliente_ref'] ?? null, $directorio);

        Oportunidad::create([
            'titulo' => $datos['titulo'],
            'monto' => $datos['monto'],
            'etapa' => 'nuevo',
            'orden' => (Oportunidad::where('etapa', 'nuevo')->max('orden') ?? -1) + 1,
            'etapa_desde' => now(),
            'contacto_nombre' => $datos['contacto_nombre'] ?? $cliente->nombre,
            'contacto_telefono' => $datos['contacto_telefono'] ?? $cliente?->telefono,
            'cliente_tipo' => $cliente?->ref->tipo,
            'cliente_id' => $cliente?->ref->id,
        ]);

        return redirect()->route('embudo.index')->with('success', 'Oportunidad creada en "Nuevo".');
    }

    /** AJAX: la tarjeta se soltó en otra posición o columna. Devuelve los totales para redibujar las cabeceras. */
    public function mover(Request $request, Oportunidad $oportunidad, Embudo $embudo): JsonResponse
    {
        $datos = $request->validate([
            'etapa' => ['required', 'string'],
            'posicion' => ['required', 'integer'],
            'motivo' => ['nullable', 'string', 'max:250'],
        ]);

        try {
            $embudo->mover($oportunidad, $datos['etapa'], (int) $datos['posicion'], $datos['motivo'] ?? null);
        } catch (DomainException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }

        return response()->json([
            'id' => $oportunidad->id,
            'etapa' => $oportunidad->etapa,
            'columnas' => collect($embudo->columnas())->mapWithKeys(fn ($columna) => [$columna['etapa'] => [
                'cantidad' => $columna['cantidad'],
                'total' => $columna['total'],
                'porcentaje' => $columna['porcentaje'],
            ]]),
            'valor_abierto' => $embudo->valorAbierto(),
        ]);
    }
}
