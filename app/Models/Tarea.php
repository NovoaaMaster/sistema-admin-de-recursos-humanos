<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarea extends Model
{
    use HasFactory, PerteneceACliente;

    protected $guarded = ['id'];

    protected $casts = [
        'vence_el' => 'date',
        'hecha_en' => 'datetime',
        'es_demo' => 'boolean',
    ];

    public function scopePendientes(Builder $query): void
    {
        $query->whereNull('hecha_en');
    }

    public function oportunidad(): BelongsTo
    {
        return $this->belongsTo(Oportunidad::class);
    }
}
