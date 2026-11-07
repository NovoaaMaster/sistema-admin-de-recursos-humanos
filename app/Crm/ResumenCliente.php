<?php

namespace App\Crm;

use Illuminate\Support\Carbon;

/** Un cliente tal como lo muestra el CRM, venga de PCLAP (tienda o web) o sea un prospecto propio. */
final class ResumenCliente
{
    public function __construct(
        public readonly ClienteRef $ref,
        public readonly string $nombre,
        public readonly string $origen,
        public readonly ?string $documento,
        public readonly ?string $telefono,
        public readonly ?string $email,
        public readonly int $compras,
        public readonly float $totalComprado,
        public readonly ?Carbon $primeraCompra,
        public readonly ?Carbon $ultimaCompra,
        public readonly string $segmento,
        public readonly bool $esDemo = false,
    ) {}

    public function whatsapp(string $mensaje = ''): ?string
    {
        return Contacto::whatsapp($this->telefono, $mensaje);
    }
}
