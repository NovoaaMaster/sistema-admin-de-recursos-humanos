<?php

namespace App\Crm;

use App\Models\Nota;
use App\Models\Oportunidad;
use App\Models\Tarea;
use App\Pclap\GarantiaPclap;
use App\Pclap\PedidoWebPclap;
use App\Pclap\VentaPclap;
use Illuminate\Support\Collection;

/** Todo lo que se sabe de un cliente: lo que hizo en PCLAP (solo lectura) y su seguimiento en el CRM. */
class FichaCliente
{
    public function __construct(private DirectorioClientes $directorio) {}

    public function para(ClienteRef $ref): array
    {
        $cliente = $this->directorio->buscarRef($ref);

        $compras = $this->compras($ref);
        $garantias = $compras->isEmpty()
            ? collect()
            : GarantiaPclap::with('producto')->whereIn('venta_id', $compras->pluck('id'))->latest('created_at')->get();
        $pedidosWeb = $ref->tipo === 'web'
            ? PedidoWebPclap::where('clienteweb_id', $ref->id)->latest('created_at')->get()
            : collect();

        $notas = Nota::de($ref)->with('user')->latest()->get();
        $tareas = Tarea::de($ref)->orderByRaw('hecha_en is not null')->orderBy('vence_el')->get();
        $oportunidades = Oportunidad::de($ref)->latest('updated_at')->get();

        return [
            'cliente' => $cliente,
            'compras' => $compras,
            'garantias' => $garantias,
            'pedidosWeb' => $pedidosWeb,
            'notas' => $notas,
            'tareas' => $tareas,
            'oportunidades' => $oportunidades,
            'ticketPromedio' => $cliente->compras > 0 ? round($cliente->totalComprado / $cliente->compras, 2) : 0.0,
            'actividad' => $this->actividad($compras, $garantias, $pedidosWeb, $notas, $tareas, $oportunidades),
        ];
    }

    /** Mismas reglas que DirectorioClientes: ventas pagadas; las de tienda sin cliente_nuevo ni cliente web. */
    private function compras(ClienteRef $ref): Collection
    {
        if ($ref->tipo === 'prospecto') {
            return collect();
        }

        return VentaPclap::pagadas()
            ->with(['productos', 'series'])
            ->when($ref->tipo === 'web',
                fn ($query) => $query->where('clienteweb_id', $ref->id),
                fn ($query) => $query->where('cliente_id', $ref->id)->whereNull('cliente_nuevo')->whereNull('clienteweb_id'))
            ->orderByDesc('fecha_hora')
            ->get();
    }

    /** Línea de tiempo: todo lo anterior ordenado del más reciente al más antiguo. */
    private function actividad(Collection $compras, Collection $garantias, Collection $pedidosWeb, Collection $notas, Collection $tareas, Collection $oportunidades): Collection
    {
        $soles = fn ($monto) => 'S/ '.number_format((float) $monto, 2);

        return collect()
            ->concat($compras->map(fn ($venta) => [
                'fecha' => $venta->fecha_hora,
                'tipo' => 'compra',
                'titulo' => 'Compra '.$venta->numero_comprobante,
                'detalle' => $soles($venta->total).' · '.$venta->productos->pluck('nombre')->implode(', '),
            ]))
            ->concat($garantias->map(fn ($garantia) => [
                'fecha' => $garantia->created_at,
                'tipo' => 'garantia',
                'titulo' => 'Garantía de '.($garantia->producto?->nombre ?? 'un producto'),
                'detalle' => 'Serie '.$garantia->numero_serie,
            ]))
            ->concat($pedidosWeb->map(fn ($pedido) => [
                'fecha' => $pedido->created_at,
                'tipo' => 'pedido',
                'titulo' => 'Pedido web '.$pedido->codigo,
                'detalle' => $soles($pedido->total).' · '.ucfirst((string) $pedido->estado),
            ]))
            ->concat($notas->map(fn (Nota $nota) => [
                'fecha' => $nota->created_at,
                'tipo' => 'nota',
                'titulo' => 'Nota',
                'detalle' => $nota->texto,
            ]))
            ->concat($tareas->map(fn (Tarea $tarea) => [
                'fecha' => $tarea->created_at,
                'tipo' => 'tarea',
                'titulo' => 'Tarea: '.$tarea->titulo,
                'detalle' => $tarea->hecha_en ? 'Hecha el '.$tarea->hecha_en->format('d/m/Y') : 'Vence el '.$tarea->vence_el->format('d/m/Y'),
            ]))
            ->concat($oportunidades->map(fn (Oportunidad $oportunidad) => [
                'fecha' => $oportunidad->created_at,
                'tipo' => 'oportunidad',
                'titulo' => 'Oportunidad: '.$oportunidad->titulo,
                'detalle' => $soles($oportunidad->monto).' · '.Oportunidad::NOMBRES[$oportunidad->etapa],
            ]))
            ->filter(fn ($evento) => $evento['fecha'] !== null)
            ->sortByDesc(fn ($evento) => $evento['fecha']->getTimestamp())
            ->values();
    }
}
