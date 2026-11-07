<?php

namespace App\Http\Controllers;

use App\Crm\ClienteRef;
use App\Crm\DirectorioClientes;
use App\Crm\FichaCliente;
use App\Crm\Segmentador;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class ClienteController extends Controller
{
    private const POR_PAGINA = 25;

    public function index(Request $request, DirectorioClientes $directorio): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'segmento' => ['nullable', Rule::in(Segmentador::SEGMENTOS)],
        ]);

        try {
            $clientes = $directorio->buscar($filtros['q'] ?? null, $filtros['segmento'] ?? null);
            $conteo = $directorio->conteoPorSegmento();
            $errorPclap = null;
        } catch (Throwable $error) {
            report($error);
            $clientes = collect();
            $conteo = array_fill_keys(Segmentador::SEGMENTOS, 0);
            $errorPclap = 'No se pudo leer la base de PCLAP. Revisa que MySQL esté encendido en XAMPP.';
        }

        $pagina = LengthAwarePaginator::resolveCurrentPage();

        return view('clientes.index', [
            'clientes' => new LengthAwarePaginator(
                $clientes->forPage($pagina, self::POR_PAGINA)->values(),
                $clientes->count(),
                self::POR_PAGINA,
                $pagina,
                ['path' => $request->url(), 'query' => $request->query()],
            ),
            'conteo' => $conteo,
            'filtros' => $filtros,
            'errorPclap' => $errorPclap,
        ]);
    }

    public function show(Request $request, string $ref, FichaCliente $ficha): View|RedirectResponse
    {
        try {
            $datos = $ficha->para(ClienteRef::fromString($ref));
        } catch (ModelNotFoundException|InvalidArgumentException) {
            abort(404);
        } catch (Throwable $error) {
            report($error);

            return redirect()->route('clientes.index')->with('error', 'No se pudo leer la base de PCLAP. Revisa que MySQL esté encendido en XAMPP.');
        }

        return view('clientes.show', $datos + ['pestana' => $request->query('pestana', 'actividad')]);
    }
}
