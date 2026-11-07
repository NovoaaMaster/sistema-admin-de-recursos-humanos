<?php

namespace App\Crm;

use App\Models\Prospecto;
use App\Pclap\ClientePclap;
use App\Pclap\ClienteWebPclap;
use App\Pclap\VentaPclap;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lista única de clientes: los registrados en PCLAP (tienda y web, en solo lectura) y los prospectos del CRM.
 * "Público General" no es un cliente y las ventas con solo un nombre escrito (cliente_nuevo) no cuentan para nadie.
 */
class DirectorioClientes
{
    private const DOCUMENTO_PUBLICO_GENERAL = '00000000';

    private ?Collection $cache = null;

    /** @return Collection<int, ResumenCliente> de la compra más reciente a la más antigua; sin compras al final. */
    public function todos(): Collection
    {
        if ($this->cache) {
            return $this->cache;
        }

        $hoy = now();
        $statsTienda = $this->estadisticas('cliente_id', fn ($query) => $query->whereNull('cliente_nuevo')->whereNull('clienteweb_id'));
        $statsWeb = $this->estadisticas('clienteweb_id', fn ($query) => $query->whereNotNull('clienteweb_id'));

        $tienda = ClientePclap::with('persona')->get()
            ->reject(fn ($cliente) => $cliente->persona?->numero_documento === self::DOCUMENTO_PUBLICO_GENERAL)
            ->map(fn ($cliente) => $this->resumen(
                new ClienteRef('tienda', $cliente->id),
                (string) $cliente->persona?->razon_social,
                'Tienda',
                $cliente->persona?->numero_documento,
                $cliente->persona?->telefono,
                $cliente->persona?->email,
                $statsTienda->get($cliente->id),
                $hoy,
            ));

        $web = ClienteWebPclap::all()->map(fn ($cliente) => $this->resumen(
            new ClienteRef('web', $cliente->id),
            (string) $cliente->nombre,
            'Web',
            $cliente->numero_documento,
            $cliente->telefono,
            $cliente->email,
            $statsWeb->get($cliente->id),
            $hoy,
        ));

        $prospectos = Prospecto::all()->map(fn (Prospecto $prospecto) => new ResumenCliente(
            $prospecto->ref(), $prospecto->nombre, 'Prospecto', $prospecto->documento, $prospecto->telefono, $prospecto->email,
            0, 0.0, null, null, 'Prospecto', $prospecto->es_demo,
        ));

        return $this->cache = collect([...$tienda, ...$web, ...$prospectos])
            ->sortBy([
                fn (ResumenCliente $a, ResumenCliente $b) => ($b->ultimaCompra?->getTimestamp() ?? 0) <=> ($a->ultimaCompra?->getTimestamp() ?? 0),
                fn (ResumenCliente $a, ResumenCliente $b) => strcasecmp($a->nombre, $b->nombre),
            ])
            ->values();
    }

    /** Busca por nombre o documento (sin distinguir mayúsculas) y filtra por etiqueta. */
    public function buscar(?string $texto, ?string $segmento): Collection
    {
        $texto = trim((string) $texto);

        return $this->todos()
            ->when($texto !== '', fn ($clientes) => $clientes->filter(
                fn (ResumenCliente $cliente) => mb_stripos($cliente->nombre, $texto) !== false || str_contains((string) $cliente->documento, $texto)
            ))
            ->when($segmento, fn ($clientes) => $clientes->where('segmento', $segmento))
            ->values();
    }

    /** @return array<string, int> los seis segmentos, aunque alguno valga 0 */
    public function conteoPorSegmento(): array
    {
        return array_merge(array_fill_keys(Segmentador::SEGMENTOS, 0), $this->todos()->countBy('segmento')->all());
    }

    public function buscarRef(ClienteRef $ref): ResumenCliente
    {
        // Un prospecto se resuelve solo con la base del CRM: su ficha funciona aunque PCLAP no responda.
        if ($ref->tipo === 'prospecto') {
            $prospecto = Prospecto::findOrFail($ref->id);

            return new ResumenCliente(
                $ref, $prospecto->nombre, 'Prospecto', $prospecto->documento, $prospecto->telefono, $prospecto->email,
                0, 0.0, null, null, 'Prospecto', $prospecto->es_demo,
            );
        }

        return $this->todos()->first(fn (ResumenCliente $cliente) => (string) $cliente->ref === (string) $ref)
            ?? throw (new ModelNotFoundException)->setModel(ResumenCliente::class, [(string) $ref]);
    }

    /** Clientes cuya primera compra cae en el mes en curso. */
    public function nuevosDelMes(): int
    {
        $inicio = now()->startOfMonth();

        return $this->todos()->filter(fn (ResumenCliente $cliente) => $cliente->primeraCompra?->greaterThanOrEqualTo($inicio))->count();
    }

    /** Compras pagadas agrupadas por cliente: cantidad, total, primera y última fecha. */
    private function estadisticas(string $columna, callable $filtro): Collection
    {
        return VentaPclap::pagadas()
            ->tap($filtro)
            ->selectRaw("{$columna} as clave, COUNT(*) as compras, SUM(total) as total, MIN(fecha_hora) as primera, MAX(fecha_hora) as ultima")
            ->groupBy($columna)
            ->toBase()
            ->get()
            ->keyBy('clave');
    }

    private function resumen(ClienteRef $ref, string $nombre, string $origen, ?string $documento, ?string $telefono, ?string $email, ?object $stats, Carbon $hoy): ResumenCliente
    {
        $compras = (int) ($stats->compras ?? 0);
        $primera = $stats?->primera ? Carbon::parse($stats->primera) : null;
        $ultima = $stats?->ultima ? Carbon::parse($stats->ultima) : null;

        return new ResumenCliente(
            $ref, $nombre, $origen, $documento, $telefono, $email,
            $compras, round((float) ($stats->total ?? 0), 2), $primera, $ultima,
            Segmentador::segmento(false, $compras, $primera, $ultima, $hoy),
        );
    }
}
