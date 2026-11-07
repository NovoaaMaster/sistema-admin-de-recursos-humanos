<?php

namespace App\Crm;

/** Avatar de iniciales: el color sale del nombre, así cada cliente conserva el suyo en todas las pantallas. */
final class Avatar
{
    /** Tonos oscuros: el texto blanco se lee bien sobre todos. */
    public const COLORES = ['#6D28D9', '#9D174D', '#0E7490', '#92400E', '#166534', '#4338CA', '#86198F', '#9A3412'];

    public static function iniciales(string $nombre): string
    {
        $palabras = preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY);

        if (! $palabras) {
            return '?';
        }

        return mb_strtoupper(implode('', array_map(fn ($palabra) => mb_substr($palabra, 0, 1), array_slice($palabras, 0, 2))));
    }

    public static function color(string $nombre): string
    {
        return self::COLORES[crc32(mb_strtolower(trim($nombre))) % count(self::COLORES)];
    }
}
