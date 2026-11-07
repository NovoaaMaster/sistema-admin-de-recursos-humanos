<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Crea en la conexión "pclap" (SQLite en memoria durante las pruebas) las tablas de PCLAP
 * que lee el CRM, con las mismas columnas que la base real. Los datos se insertan con el
 * query builder porque los modelos de app/Pclap son de solo lectura.
 *
 * Como en PCLAP, el cliente 1 es "Público General" (DNI 00000000).
 */
trait ConEsquemaPclap
{
    protected function crearEsquemaPclap(): void
    {
        $schema = Schema::connection('pclap');

        $schema->create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30);
        });
        $schema->create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('direccion')->nullable();
            $table->string('telefono', 15)->nullable();
            $table->string('tipo')->default('NATURAL');
            $table->string('email')->nullable();
            $table->foreignId('documento_id');
            $table->string('numero_documento', 20);
            $table->timestamps();
        });
        $schema->create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id');
            $table->timestamps();
        });
        $schema->create('clienteweb', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->foreignId('documento_id')->nullable();
            $table->string('numero_documento')->nullable();
            $table->timestamps();
        });
        $schema->create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('nombre');
        });
        $schema->create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id');
            $table->string('cliente_nuevo')->nullable();
            $table->unsignedBigInteger('clienteweb_id')->nullable();
            $table->string('numero_comprobante');
            $table->dateTime('fecha_hora');
            $table->decimal('total', 8, 2);
            $table->string('estado_pago', 20)->default('PAGADO');
        });
        $schema->create('producto_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id');
            $table->foreignId('producto_id');
            $table->integer('cantidad');
            $table->decimal('precio_venta', 10, 2);
        });
        $schema->create('series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id');
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->string('numero_serie');
            $table->string('estado')->default('vendido');
        });
        $schema->create('garantias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->string('numero_serie');
            $table->string('estado')->default('vigente');
            $table->timestamps();
        });
        $schema->create('pedidos_web', function (Blueprint $table) {
            $table->id();
            $table->string('codigo');
            $table->unsignedBigInteger('clienteweb_id');
            $table->decimal('total', 10, 2);
            $table->string('estado');
            $table->timestamps();
        });
        $schema->create('proformas', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_nombre');
            $table->string('cliente_telefono')->nullable();
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
        $schema->create('proforma_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proforma_id');
            $table->string('descripcion');
            $table->integer('cantidad');
            $table->decimal('precio', 10, 2);
            $table->timestamps();
        });

        $db = DB::connection('pclap');
        $db->table('documentos')->insert([['id' => 1, 'nombre' => 'DNI'], ['id' => 3, 'nombre' => 'RUC']]);
        $personaId = $db->table('personas')->insertGetId(['razon_social' => 'Público General', 'documento_id' => 1, 'numero_documento' => '00000000']);
        $db->table('clientes')->insert(['id' => 1, 'persona_id' => $personaId]);
    }

    protected function publicoGeneralId(): int
    {
        return 1;
    }

    protected function crearClienteTienda(string $nombre, string $doc, ?string $telefono = null): int
    {
        $db = DB::connection('pclap');
        $personaId = $db->table('personas')->insertGetId([
            'razon_social' => $nombre,
            'telefono' => $telefono,
            'documento_id' => strlen($doc) === 11 ? 3 : 1,
            'numero_documento' => $doc,
            'created_at' => '2026-07-01 10:00:00',
        ]);

        return $db->table('clientes')->insertGetId(['persona_id' => $personaId, 'created_at' => '2026-07-01 10:00:00']);
    }

    protected function crearClienteWeb(string $nombre, ?string $telefono = null): int
    {
        return DB::connection('pclap')->table('clienteweb')->insertGetId([
            'nombre' => $nombre,
            'email' => null,
            'telefono' => $telefono,
            'created_at' => '2026-07-09 17:42:40',
        ]);
    }

    /**
     * Opciones: cliente_id (por defecto Público General), clienteweb_id, cliente_nuevo, estado_pago,
     * fecha_hora, total (por defecto la suma de líneas) y lineas ([['producto', 'cantidad', 'precio', 'series']]).
     */
    protected function crearVentaPclap(array $opciones = []): int
    {
        $db = DB::connection('pclap');
        $lineas = $opciones['lineas'] ?? [['producto' => 'placa h510', 'cantidad' => 1, 'precio' => 144.00, 'series' => ['515151']]];
        $total = $opciones['total'] ?? array_sum(array_map(fn ($linea) => $linea['cantidad'] * $linea['precio'], $lineas));

        $ventaId = $db->table('ventas')->insertGetId([
            'cliente_id' => $opciones['cliente_id'] ?? $this->publicoGeneralId(),
            'cliente_nuevo' => $opciones['cliente_nuevo'] ?? null,
            'clienteweb_id' => $opciones['clienteweb_id'] ?? null,
            'numero_comprobante' => '0002 - '.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'fecha_hora' => $opciones['fecha_hora'] ?? '2026-10-24 18:57:03',
            'total' => $total,
            'estado_pago' => $opciones['estado_pago'] ?? 'PAGADO',
        ]);

        foreach ($lineas as $linea) {
            $productoId = $db->table('productos')->insertGetId(['codigo' => null, 'nombre' => $linea['producto']]);
            $db->table('producto_venta')->insert([
                'venta_id' => $ventaId,
                'producto_id' => $productoId,
                'cantidad' => $linea['cantidad'],
                'precio_venta' => $linea['precio'],
            ]);
            foreach ($linea['series'] ?? [] as $serie) {
                $db->table('series')->insert(['producto_id' => $productoId, 'venta_id' => $ventaId, 'numero_serie' => $serie]);
            }
        }

        return $ventaId;
    }

    protected function crearGarantia(int $ventaId, string $serie, string $fecha = '2026-10-05 12:00:00'): int
    {
        return DB::connection('pclap')->table('garantias')->insertGetId([
            'venta_id' => $ventaId,
            'numero_serie' => $serie,
            'estado' => 'vigente',
            'created_at' => $fecha,
        ]);
    }

    protected function crearPedidoWeb(int $clientewebId, string $codigo, float $total, string $estado = 'incompleto', string $fecha = '2026-10-10 09:00:00'): int
    {
        return DB::connection('pclap')->table('pedidos_web')->insertGetId([
            'codigo' => $codigo,
            'clienteweb_id' => $clientewebId,
            'total' => $total,
            'estado' => $estado,
            'created_at' => $fecha,
        ]);
    }

    protected function crearProforma(string $nombre, ?string $telefono, float $total, string $fecha = '2026-10-27 21:01:20'): int
    {
        return DB::connection('pclap')->table('proformas')->insertGetId([
            'cliente_nombre' => $nombre,
            'cliente_telefono' => $telefono,
            'total' => $total,
            'created_at' => $fecha,
        ]);
    }
}
