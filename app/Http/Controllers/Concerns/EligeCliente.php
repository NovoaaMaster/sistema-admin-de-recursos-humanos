<?php

namespace App\Http\Controllers\Concerns;

use App\Crm\ClienteRef;
use App\Crm\DirectorioClientes;
use App\Crm\ResumenCliente;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Para formularios con un selector de cliente opcional (campo "cliente_ref"). */
trait EligeCliente
{
    /** El cliente elegido, o null si no se eligió ninguno. Si no existe, error de validación en "cliente_ref". */
    protected function clienteElegido(?string $ref, DirectorioClientes $directorio): ?ResumenCliente
    {
        if (blank($ref)) {
            return null;
        }

        try {
            return $directorio->buscarRef(ClienteRef::fromString($ref));
        } catch (Throwable) {
            throw ValidationException::withMessages(['cliente_ref' => 'El cliente elegido no existe.']);
        }
    }

    /** @return array<string, string> ref => "Nombre (Origen)" para un <select>; vacío si PCLAP no responde. */
    protected function opcionesClientes(DirectorioClientes $directorio): array
    {
        try {
            return $directorio->todos()
                ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
                ->mapWithKeys(fn (ResumenCliente $cliente) => [(string) $cliente->ref => "{$cliente->nombre} ({$cliente->origen})"])
                ->all();
        } catch (Throwable $error) {
            report($error);

            return [];
        }
    }
}
