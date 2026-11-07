<?php

namespace App\Crm;

use App\Models\Oportunidad;
use App\Pclap\ProformaPclap;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Embudo de ventas: columnas por etapa, tarjetas que se arrastran y cotizaciones que llegan de PCLAP. */
class Embudo
{
    /** Copia como oportunidades "Cotizado" las cotizaciones de PCLAP que aún no se importaron. Devuelve cuántas. */
    public function sincronizarProformas(): int
    {
        $importadas = Oportunidad::whereNotNull('pclap_proforma_id')->pluck('pclap_proforma_id');
        $nuevas = ProformaPclap::whereNotIn('id', $importadas)->orderBy('id')->get();
        $siguiente = (Oportunidad::where('etapa', 'cotizado')->max('orden') ?? -1) + 1;

        foreach ($nuevas as $proforma) {
            Oportunidad::create([
                'titulo' => "Cotización N° {$proforma->id}",
                'monto' => $proforma->total,
                'etapa' => 'cotizado',
                'orden' => $siguiente++,
                'etapa_desde' => $proforma->created_at ?? now(),
                'contacto_nombre' => $proforma->cliente_nombre,
                'contacto_telefono' => $proforma->cliente_telefono,
                'pclap_proforma_id' => $proforma->id,
            ]);
        }

        return $nuevas->count();
    }

    /** Las cinco columnas en orden, con sus tarjetas, cantidad, total en soles y porcentaje del embudo. */
    public function columnas(): array
    {
        $porEtapa = Oportunidad::orderBy('orden')->orderBy('id')->get()->groupBy('etapa');
        $granTotal = (float) Oportunidad::sum('monto');

        return array_map(function (string $etapa) use ($porEtapa, $granTotal) {
            $tarjetas = $porEtapa->get($etapa, collect());
            $total = round((float) $tarjetas->sum('monto'), 2);

            return [
                'etapa' => $etapa,
                'nombre' => Oportunidad::NOMBRES[$etapa],
                'tarjetas' => $tarjetas,
                'cantidad' => $tarjetas->count(),
                'total' => $total,
                'porcentaje' => $granTotal > 0 ? (int) round($total * 100 / $granTotal) : 0,
            ];
        }, Oportunidad::ETAPAS);
    }

    /**
     * Lleva la tarjeta a $etapa en la posición $posicion (se ajusta al rango válido) y renumera las columnas.
     * Perdido exige motivo; ganado y perdido marcan la fecha de cierre.
     */
    public function mover(Oportunidad $oportunidad, string $etapa, int $posicion, ?string $motivo = null): Oportunidad
    {
        if (! in_array($etapa, Oportunidad::ETAPAS, true)) {
            throw new DomainException("La etapa \"{$etapa}\" no existe en el embudo.");
        }

        // El motivo se pide al entrar a "perdido"; al reordenar dentro de esa columna se conserva el que tenía.
        $motivo = trim((string) $motivo);
        if ($etapa === 'perdido' && $oportunidad->etapa !== 'perdido' && $motivo === '') {
            throw new DomainException('Escribe por qué se perdió la oportunidad.');
        }

        return DB::transaction(function () use ($oportunidad, $etapa, $posicion, $motivo) {
            $anterior = $oportunidad->etapa;
            $cambiaDeEtapa = $anterior !== $etapa;
            $cerrada = in_array($etapa, ['ganado', 'perdido'], true);

            $columna = Oportunidad::where('etapa', $etapa)
                ->whereKeyNot($oportunidad->getKey())
                ->orderBy('orden')->orderBy('id')
                ->lockForUpdate()
                ->get();
            $columna->splice(max(0, min($posicion, $columna->count())), 0, [$oportunidad]);

            $oportunidad->fill([
                'etapa' => $etapa,
                'etapa_desde' => $cambiaDeEtapa ? now() : $oportunidad->etapa_desde,
                'cerrada_en' => $cerrada ? ($cambiaDeEtapa ? now() : ($oportunidad->cerrada_en ?? now())) : null,
                'motivo_perdida' => $etapa === 'perdido' ? ($motivo !== '' ? $motivo : $oportunidad->motivo_perdida) : null,
            ]);
            $this->numerar($columna);

            if ($cambiaDeEtapa) {
                $this->numerar(Oportunidad::where('etapa', $anterior)->orderBy('orden')->orderBy('id')->get());
            }

            return $oportunidad;
        });
    }

    /** Suma de las oportunidades que siguen abiertas (nuevo, contactado y cotizado). */
    public function valorAbierto(): float
    {
        return round((float) Oportunidad::whereIn('etapa', Oportunidad::ABIERTAS)->sum('monto'), 2);
    }

    private function numerar(iterable $tarjetas): void
    {
        foreach (collect($tarjetas)->values() as $indice => $tarjeta) {
            $tarjeta->orden = $indice;
            $tarjeta->save();
        }
    }
}
