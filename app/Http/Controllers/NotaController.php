<?php

namespace App\Http\Controllers;

use App\Crm\ClienteRef;
use App\Crm\DirectorioClientes;
use App\Models\Nota;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class NotaController extends Controller
{
    public function store(Request $request, string $ref, DirectorioClientes $directorio): RedirectResponse
    {
        try {
            $cliente = $directorio->buscarRef(ClienteRef::fromString($ref));
        } catch (ModelNotFoundException|InvalidArgumentException) {
            abort(404);
        }

        $datos = $request->validate(['texto' => ['required', 'string', 'max:2000']]);

        Nota::create([
            'cliente_tipo' => $cliente->ref->tipo,
            'cliente_id' => $cliente->ref->id,
            'texto' => $datos['texto'],
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('clientes.show', ['ref' => $ref, 'pestana' => 'notas'])->with('success', 'Nota guardada.');
    }
}
