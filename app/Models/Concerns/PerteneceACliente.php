<?php

namespace App\Models\Concerns;

use App\Crm\ClienteRef;
use Illuminate\Database\Eloquent\Builder;

/** Para registros del CRM ligados a un cliente por (cliente_tipo, cliente_id). */
trait PerteneceACliente
{
    public function scopeDe(Builder $query, ClienteRef $ref): void
    {
        $query->where('cliente_tipo', $ref->tipo)->where('cliente_id', $ref->id);
    }

    public function clienteRef(): ?ClienteRef
    {
        return $this->cliente_tipo && $this->cliente_id ? new ClienteRef($this->cliente_tipo, (int) $this->cliente_id) : null;
    }
}
