<?php

namespace App\Pclap;

use Illuminate\Database\Eloquent\Relations\HasMany;

/** Cotización de PCLAP: solo guarda nombre y teléfono del cliente, sin cliente ligado. */
class ProformaPclap extends ModeloPclap
{
    protected $table = 'proformas';

    protected $casts = [
        'created_at' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ProformaItemPclap::class, 'proforma_id');
    }
}
