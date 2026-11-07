<?php

namespace Database\Seeders;

use App\Models\Nota;
use App\Models\Oportunidad;
use App\Models\Prospecto;
use App\Models\Tarea;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración para que el CRM no se vea vacío en una feria. Solo escribe en la base del CRM,
 * todo con es_demo = true. Las fechas son relativas a hoy: vuelve a ejecutarlo antes de una demo
 * (php artisan db:seed --class=DemoSeeder) y las tareas "para hoy" volverán a ser de hoy.
 * Los teléfonos usan el bloque 900 000 1xx, inventado para la demo.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        Tarea::where('es_demo', true)->delete();
        Nota::where('es_demo', true)->delete();
        Oportunidad::where('es_demo', true)->delete();
        Prospecto::where('es_demo', true)->delete();

        $prospectos = collect([
            ['Rosa Quispe Mamani', 'Bodega Rosita', 'Feria'],
            ['Jorge Huamán Torres', 'Constructora Andes SAC', 'Referido'],
            ['Lucía Fernández Rojas', 'Colegio San Martín', 'Web'],
            ['Miguel Ángel Paredes', 'Estudio Contable Paredes', 'WhatsApp'],
            ['Carmen Salazar Vega', 'Clínica Dental Sonrisa', 'WhatsApp'],
            ['Diego Ramírez Castillo', 'Cabinas Ramírez', 'Feria'],
            ['Ana Lucía Torres', 'Municipalidad Distrital (área de TI)', 'Referido'],
            ['Pedro Chávez Ríos', 'Transportes Chávez EIRL', 'Web'],
            ['Valeria Mendoza Soto', 'Agencia Creativa Lima', 'WhatsApp'],
            ['Luis Alberto Gómez', 'Ferretería El Tornillo', 'Otro'],
            ['Sofía Rivas Campos', 'Grupo de investigación universitario', 'Feria'],
            ['Ernesto Villanueva', 'Hostal Villa Sol', 'WhatsApp'],
        ])->mapWithKeys(fn ($datos, $i) => [$datos[0] => Prospecto::create([
            'nombre' => $datos[0],
            'empresa' => $datos[1],
            'origen' => $datos[2],
            'telefono' => '900000'.(101 + $i),
            'es_demo' => true,
            'created_at' => now()->subDays(40 - $i * 3),
        ])]);

        $de = fn (string $nombre) => ['cliente_tipo' => 'prospecto', 'cliente_id' => $prospectos[$nombre]->id];

        // [título, monto, etapa, contacto, días en la etapa, motivo si se perdió]
        $oportunidades = [
            ['Laptops para el colegio', 12000, 'nuevo', 'Lucía Fernández Rojas', 2],
            ['Cámaras de seguridad', 2800, 'nuevo', 'Carmen Salazar Vega', 5],
            ['Renovación de PCs de oficina', 7500, 'nuevo', 'Miguel Ángel Paredes', 1],
            ['Equipos para el área de TI', 9800, 'contactado', 'Ana Lucía Torres', 4],
            ['Mantenimiento anual de PCs', 1800, 'contactado', 'Pedro Chávez Ríos', 9],
            ['Impresoras multifuncionales', 3200, 'cotizado', 'Valeria Mendoza Soto', 3],
            ['Memorias y SSD para cabinas', 1450, 'cotizado', 'Diego Ramírez Castillo', 6],
            ['Laptop para investigación', 4200, 'ganado', 'Sofía Rivas Campos', 2],
            ['Router y cableado', 950, 'ganado', 'Ernesto Villanueva', 12],
            ['PC gamer', 6500, 'perdido', 'Jorge Huamán Torres', 8, 'Eligió otra tienda por precio'],
        ];
        $orden = [];
        foreach ($oportunidades as $datos) {
            [$titulo, $monto, $etapa, $contacto, $dias] = $datos;
            $orden[$etapa] = ($orden[$etapa] ?? -1) + 1;
            Oportunidad::create([
                'titulo' => $titulo,
                'monto' => $monto,
                'etapa' => $etapa,
                'orden' => $orden[$etapa],
                'etapa_desde' => now()->subDays($dias),
                'contacto_nombre' => $contacto,
                'contacto_telefono' => $prospectos[$contacto]->telefono,
                'motivo_perdida' => $datos[5] ?? null,
                'cerrada_en' => in_array($etapa, ['ganado', 'perdido'], true) ? now()->subDays($dias) : null,
                'es_demo' => true,
                ...$de($contacto),
            ]);
        }

        // [título, días desde hoy (negativo = vencida), cliente]
        $tareas = [
            ['Enviar cotización de cámaras', -2, 'Carmen Salazar Vega'],
            ['Confirmar visita técnica', -1, 'Pedro Chávez Ríos'],
            ['Confirmar pedido de laptops', 0, 'Lucía Fernández Rojas'],
            ['Enviar proforma formal con RUC', 0, 'Ana Lucía Torres'],
            ['Llamar por las memorias y SSD', 0, 'Diego Ramírez Castillo'],
            ['Visitar Constructora Andes', 2, 'Jorge Huamán Torres'],
            ['Seguimiento a la cotización de impresoras', 3, 'Valeria Mendoza Soto'],
            ['Ofrecer garantía extendida', 7, 'Sofía Rivas Campos'],
        ];
        foreach ($tareas as [$titulo, $dias, $cliente]) {
            Tarea::create(['titulo' => $titulo, 'vence_el' => today()->addDays($dias), 'es_demo' => true, ...$de($cliente)]);
        }

        $notas = [
            ['Rosa Quispe Mamani', 'La conocimos en la feria; busca una laptop para su hija que empieza la universidad.'],
            ['Lucía Fernández Rojas', 'El colegio necesita 15 laptops antes de marzo. Pide factura y entrega en el local.'],
            ['Ana Lucía Torres', 'Compra por la municipalidad: necesita cotización formal con RUC y ficha técnica.'],
            ['Carmen Salazar Vega', 'Prefiere que la llamen después de las 6 p. m.'],
            ['Jorge Huamán Torres', 'Compró la PC en otra tienda, pero quedó interesado en el mantenimiento de sus equipos.'],
            ['Valeria Mendoza Soto', 'Le interesa pagar en cuotas; revisar opciones de pago antes de llamarla.'],
        ];
        foreach ($notas as $i => [$cliente, $texto]) {
            Nota::create(['texto' => $texto, 'es_demo' => true, 'created_at' => now()->subDays(6 - $i), ...$de($cliente)]);
        }
    }
}
