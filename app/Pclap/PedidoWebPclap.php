<?php

namespace App\Pclap;

class PedidoWebPclap extends ModeloPclap
{
    protected $table = 'pedidos_web';

    protected $casts = [
        'created_at' => 'datetime',
        'total' => 'decimal:2',
    ];
}
