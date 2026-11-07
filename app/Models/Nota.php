<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nota extends Model
{
    use PerteneceACliente;

    protected $guarded = ['id'];

    protected $casts = [
        'es_demo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
