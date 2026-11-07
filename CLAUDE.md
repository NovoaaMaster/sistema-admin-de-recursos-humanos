# CRM (ERP PCLAP) — instrucciones para Claude

Responde siempre en español. Antes de empezar cualquier tarea, pregunta (1–3 preguntas, con opciones y tu recomendación).

## Qué es

Tercer sistema del portafolio-ERP de Carlos: **PCLAP SUPPORT** (ventas, compras e inventario; en línea), **Finanzas** (`../moduloFinanzas`, facturación electrónica SUNAT) y este **CRM**, que gestiona la relación con los clientes: lista con etiquetas, ficha 360°, notas, tareas y un embudo tipo Trello.
Diseño en `docs/diseno-crm.md`. Plan de construcción en `../docs/superpowers/plans/2026-11-06-crm.md`.

## Regla de oro: PCLAP es 100 % solo lectura

- **Nunca** modifiques archivos de `C:\xampp\htdocs\Laravel\PCLAP SUPPORT` ni la versión en línea.
- El CRM lee la base de prueba `dbsistemapclap` con la conexión `pclap` y el usuario MySQL `pclap_lector`, que **solo tiene SELECT** (el mismo que usa Finanzas).
- Los modelos de `app/Pclap/` lanzan una excepción al guardar o borrar. No lo cambies.
- Lo propio del CRM (prospectos, notas, tareas y oportunidades) va en su base `dbcrm`.

## Piezas

| Pieza | Dónde |
|---|---|
| Stack | Laravel 12, PHP 8.2 (XAMPP), MySQL `dbcrm`, Bootstrap 5 + jQuery + base SB Admin, SortableJS para el embudo |
| Identidad visual | Propia: `public/css/crm.css` (barra lateral ciruela `#3B1A4A`, barra superior blanca, violeta `#6D28D9`), Manrope y logo de anillos (`components/logo.blade.php`). No uses los colores de PCLAP (negro y azul) ni los de Finanzas (petróleo). |
| Lógica | `app/Crm/`: DirectorioClientes, Segmentador, FichaCliente, Embudo, AgendaTareas, ClienteRef, Contacto y Avatar |
| Referencia de cliente | `tienda-5`, `web-2` o `prospecto-7` (`App\Crm\ClienteRef`) |
| Datos demo | `database/seeders/DemoSeeder.php` (todo con `es_demo = true`) |

## Comandos

```bash
php artisan serve --port=8002                 # http://127.0.0.1:8002 (portafolio 8000, Finanzas 8001)
php artisan migrate --seed                    # tablas, usuario admin (CRM_ADMIN_* del .env) y datos demo
php artisan db:seed --class=DemoSeeder        # renueva solo los datos demo (fechas relativas a hoy): úsalo antes de una feria
php artisan test                              # pruebas (PCLAP simulado en SQLite en memoria)
```

## Cosas que ya aprendimos

- La base de prueba de PCLAP tiene muy pocos clientes: el CRM se ve lleno gracias al demo. Para más clientes reales, Carlos los registra en su PCLAP de prueba (nunca el CRM).
- Las cotizaciones de PCLAP no tienen cliente ligado: entran al embudo como "Cotizado" con el nombre y el teléfono que traen.
- En Windows, `composer create-project` puede fallar porque el antivirus bloquea un archivo: se completa con `composer install`.
