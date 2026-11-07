<?php

namespace App\Crm;

use InvalidArgumentException;
use Stringable;

/**
 * Identifica a un cliente del CRM: un cliente de la tienda o de la web de PCLAP, o un prospecto propio.
 * En las URL se escribe "tipo-id": tienda-5, web-2, prospecto-7.
 */
final class ClienteRef implements Stringable
{
    public const TIPOS = ['tienda', 'web', 'prospecto'];

    public function __construct(
        public readonly string $tipo,
        public readonly int $id,
    ) {
        if (! in_array($tipo, self::TIPOS, true) || $id < 1) {
            throw new InvalidArgumentException("Referencia de cliente inválida: {$tipo}-{$id}");
        }
    }

    public static function fromString(string $valor): self
    {
        if (preg_match('/^(tienda|web|prospecto)-([1-9]\d*)$/', $valor, $partes) !== 1) {
            throw new InvalidArgumentException("Referencia de cliente inválida: {$valor}");
        }

        return new self($partes[1], (int) $partes[2]);
    }

    public function __toString(): string
    {
        return $this->tipo.'-'.$this->id;
    }
}
