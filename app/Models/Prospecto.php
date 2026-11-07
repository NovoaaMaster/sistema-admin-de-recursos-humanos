<?php

namespace App\Models;

use App\Crm\ClienteRef;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospecto extends Model
{
    use HasFactory;

    public const ORIGENES = ['WhatsApp', 'Feria', 'Web', 'Referido', 'Otro'];

    protected $guarded = ['id'];

    protected $casts = [
        'es_demo' => 'boolean',
    ];

    public function ref(): ClienteRef
    {
        return new ClienteRef('prospecto', $this->id);
    }
}
