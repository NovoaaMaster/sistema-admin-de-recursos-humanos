<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oportunidad extends Model
{
    use HasFactory, PerteneceACliente;

    protected $table = 'oportunidades';

    public const ETAPAS = ['nuevo', 'contactado', 'cotizado', 'ganado', 'perdido'];

    public const ABIERTAS = ['nuevo', 'contactado', 'cotizado'];

    public const NOMBRES = [
        'nuevo' => 'Nuevo',
        'contactado' => 'Contactado',
        'cotizado' => 'Cotizado',
        'ganado' => 'Ganado',
        'perdido' => 'Perdido',
    ];

    /** Color de cada etapa en el embudo y en los resúmenes. */
    public const COLORES = [
        'nuevo' => '#a78bfa',
        'contactado' => '#8b5cf6',
        'cotizado' => '#6d28d9',
        'ganado' => '#16a34a',
        'perdido' => '#94a3b8',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'monto' => 'decimal:2',
        'orden' => 'integer',
        'etapa_desde' => 'datetime',
        'cerrada_en' => 'datetime',
        'es_demo' => 'boolean',
    ];
}
