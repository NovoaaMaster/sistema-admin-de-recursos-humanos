<?php

namespace App\Crm;

final class Contacto
{
    /**
     * Enlace de WhatsApp para un celular peruano (9 dígitos que empiezan con 9).
     * Acepta espacios, guiones y el prefijo +51; si no queda un celular válido devuelve null.
     */
    public static function whatsapp(?string $telefono, string $mensaje = ''): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $telefono);

        if (strlen($digitos) === 11 && str_starts_with($digitos, '51')) {
            $digitos = substr($digitos, 2);
        }

        if (preg_match('/^9\d{8}$/', $digitos) !== 1) {
            return null;
        }

        return 'https://wa.me/51'.$digitos.($mensaje !== '' ? '?text='.rawurlencode($mensaje) : '');
    }
}
