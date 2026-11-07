<?php

namespace App\Pclap;

use Illuminate\Database\Eloquent\Relations\HasMany;

/** Cuenta de cliente de la tienda web de PCLAP. */
class ClienteWebPclap extends ModeloPclap
{
    protected $table = 'clienteweb';

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function pedidos(): HasMany
    {
        return $this->hasMany(PedidoWebPclap::class, 'clienteweb_id');
    }
}
