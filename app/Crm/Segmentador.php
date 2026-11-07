<?php

namespace App\Crm;

use Carbon\CarbonInterface;

/** Etiqueta de cada cliente según sus compras. Las reglas se aplican en este orden. */
final class Segmentador
{
    public const SEGMENTOS = ['Prospecto', 'Sin compras', 'Nuevo', 'Inactivo', 'Frecuente', 'Ocasional'];

    public static function segmento(bool $esProspecto, int $compras, ?CarbonInterface $primera, ?CarbonInterface $ultima, CarbonInterface $hoy): string
    {
        return match (true) {
            $esProspecto => 'Prospecto',
            $compras === 0 => 'Sin compras',
            $primera->greaterThan($hoy->copy()->subDays(30)) => 'Nuevo',
            $ultima->lessThan($hoy->copy()->subDays(90)) => 'Inactivo',
            $compras >= 3 => 'Frecuente',
            default => 'Ocasional',
        };
    }
}
