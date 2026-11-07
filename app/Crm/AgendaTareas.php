<?php

namespace App\Crm;

use App\Models\Tarea;
use Illuminate\Support\Collection;

/** Tareas pendientes agrupadas por urgencia. */
class AgendaTareas
{
    /** @return array{vencidas: Collection, hoy: Collection, proximas: Collection} ordenadas por fecha de vencimiento */
    public function grupos(): array
    {
        $hoy = today()->toDateString();
        $pendientes = Tarea::pendientes()->with('oportunidad')->orderBy('vence_el')->orderBy('id')->get();

        return [
            'vencidas' => $pendientes->filter(fn (Tarea $tarea) => $tarea->vence_el->toDateString() < $hoy)->values(),
            'hoy' => $pendientes->filter(fn (Tarea $tarea) => $tarea->vence_el->toDateString() === $hoy)->values(),
            'proximas' => $pendientes->filter(fn (Tarea $tarea) => $tarea->vence_el->toDateString() > $hoy)->values(),
        ];
    }

    public function vencidas(): int
    {
        return Tarea::pendientes()->whereDate('vence_el', '<', today())->count();
    }

    /** Vencidas más las que vencen hoy. */
    public function paraHoy(): int
    {
        return Tarea::pendientes()->whereDate('vence_el', '<=', today())->count();
    }

    public function marcarHecha(Tarea $tarea): Tarea
    {
        $tarea->update(['hecha_en' => now()]);

        return $tarea;
    }
}
