<?php

namespace App\Pclap;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GarantiaPclap extends ModeloPclap
{
    protected $table = 'garantias';

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoPclap::class, 'producto_id');
    }
}
