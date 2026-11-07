<?php

namespace App\Http\Controllers;

use App\Crm\AgendaTareas;
use App\Crm\DirectorioClientes;
use App\Http\Controllers\Concerns\EligeCliente;
use App\Models\Tarea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class TareaController extends Controller
{
    use EligeCliente;

    public function index(AgendaTareas $agenda, DirectorioClientes $directorio): View
    {
        try {
            $clientesPorRef = $directorio->todos()->keyBy(fn ($cliente) => (string) $cliente->ref);
        } catch (Throwable $error) {
            report($error);
            $clientesPorRef = collect();
        }

        return view('tareas.index', [
            'grupos' => $agenda->grupos(),
            'clientesPorRef' => $clientesPorRef,
            'opcionesClientes' => $this->opcionesClientes($directorio),
        ]);
    }

    public function store(Request $request, DirectorioClientes $directorio): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'vence_el' => ['required', 'date'],
            'cliente_ref' => ['nullable', 'string'],
        ]);
        $cliente = $this->clienteElegido($datos['cliente_ref'] ?? null, $directorio);

        Tarea::create([
            'titulo' => $datos['titulo'],
            'vence_el' => $datos['vence_el'],
            'cliente_tipo' => $cliente?->ref->tipo,
            'cliente_id' => $cliente?->ref->id,
            'user_id' => $request->user()->id,
        ]);

        $destino = $cliente
            ? redirect()->route('clientes.show', ['ref' => (string) $cliente->ref, 'pestana' => 'tareas'])
            : redirect()->route('tareas.index');

        return $destino->with('success', 'Tarea programada.');
    }

    /** AJAX: marca la tarea como hecha y devuelve el nuevo número de vencidas para el menú. */
    public function hecha(Tarea $tarea, AgendaTareas $agenda): JsonResponse
    {
        $agenda->marcarHecha($tarea);

        return response()->json([
            'id' => $tarea->id,
            'hecha_en' => $tarea->hecha_en->toIso8601String(),
            'vencidas' => $agenda->vencidas(),
        ]);
    }
}
