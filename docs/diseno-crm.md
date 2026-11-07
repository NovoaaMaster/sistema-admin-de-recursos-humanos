# CRM del ERP PCLAP — diseño de la Fase 1

Fecha: 6 de noviembre de 2026

## Por qué

Carlos arma su portafolio para ferias de trabajo (empresas y Estado) como un **ERP de varios sistemas**: PCLAP SUPPORT (ventas, compras e inventario, en línea), **Finanzas** (facturación electrónica SUNAT, terminado) y ahora un **CRM** (gestión de relaciones con el cliente).

El CRM muestra quiénes son los clientes, cuánto y cuándo compran, a quién hay que llamar y qué ventas están en camino. Debe verse profesional en una demo en vivo, y el **embudo tipo Trello** es su momento más llamativo.

El CRM reemplaza por ahora al módulo de RR.HH. La carpeta `moduloRrhh` queda vacía para más adelante.

## Objetivos

- Ver en una sola lista a todos los clientes registrados de PCLAP (tienda y web) y a los prospectos propios del CRM, cada uno con su etiqueta: Nuevo, Frecuente, Inactivo, Ocasional, Sin compras o Prospecto.
- Ficha completa de cada cliente: sus compras en PCLAP (con números de serie), garantías, pedidos web, y las notas, tareas y oportunidades del CRM.
- Tareas de seguimiento (vencidas, de hoy y próximas) que se marcan como hechas.
- Embudo de oportunidades con las columnas Nuevo → Contactado → Cotizado → Ganado / Perdido, donde las tarjetas se arrastran. Las cotizaciones de PCLAP entran solas en "Cotizado".
- Datos de demostración propios, para que no se vea vacío en la feria.

## Lo que no se hará en esta fase

- Campañas masivas por WhatsApp, reportes con gráficos, correos automáticos, roles de usuario y RR.HH.
- No se agregan clientes de PCLAP desde el CRM: los clientes se registran en PCLAP. El CRM solo crea **prospectos** (personas que aún no compran).

## Lo que haría fracasar el proyecto

- Modificar PCLAP de cualquier forma (archivos, base de datos o versión en línea).
- Que se vea como PCLAP o como Finanzas.
- Que en la feria las pantallas aparezcan vacías.

## Restricciones (decididas por Carlos)

- **Solo lectura hacia PCLAP.** Lee la base de prueba `dbsistemapclap` con el usuario MySQL `pclap_lector`, que solo tiene SELECT y ya existe porque lo usa Finanzas. El CRM guarda sus propias notas, tareas, prospectos y oportunidades en su base, `dbcrm`.
- **Carpeta nueva:** `C:\xampp\htdocs\Portafolio\Portfolio\moduloCrm`.
- **Tamaño:** versión simple más el embudo tipo Trello.
- **Paleta ciruela:**
  - Barra lateral ciruela oscuro `#3B1A4A`.
  - Barra superior blanca.
  - Botones violeta `#6D28D9`.
  - Elemento activo del menú en `#C4B5FD`.
  - Fondo `#F7F5F9`.
  - Login con fondo `#3B1A4A`.
- Mismas herramientas que Finanzas: Laravel 12, PHP 8.2 de XAMPP, MySQL, Bootstrap 5 + jQuery + AJAX, con la base SB Admin.

## Pantallas

1. **Login:** un solo administrador, cuyo correo y contraseña se leen del `.env`.
2. **Panel:**
   - Indicadores: clientes, nuevos del mes, inactivos, tareas para hoy (incluye las vencidas) y valor del embudo abierto (suma de las oportunidades que no están ganadas ni perdidas).
   - Debajo: la lista de tareas para hoy y las últimas oportunidades movidas.
3. **Clientes:**
   - Tabla con nombre, origen (Tienda, Web o Prospecto), teléfono, número de compras, total comprado, última compra y etiqueta.
   - Buscador por nombre o documento, filtro por etiqueta y botón "Nuevo prospecto".
4. **Ficha del cliente:**
   - Cabecera con nombre, documento, teléfono, correo, etiqueta y botón de WhatsApp (`wa.me/51…`, si el teléfono tiene 9 dígitos).
   - Pestañas:
     - Resumen: total comprado, número de compras, ticket promedio, primera y última compra.
     - Compras: ventas con sus productos y números de serie.
     - Garantías.
     - Pedidos web (solo clientes web).
     - Notas: agregar y listar.
     - Tareas: agregar, listar y marcar como hechas.
     - Oportunidades.
   - Los prospectos usan la misma ficha, sin las pestañas de PCLAP, y además se pueden editar.
5. **Tareas:** tres grupos (vencidas, hoy y próximas), con un botón "Hecha" que funciona por AJAX y un formulario para crear tareas.
6. **Embudo:**
   - Cinco columnas con el total en soles de cada una. Las tarjetas muestran título, contacto, monto y antigüedad, y se arrastran entre columnas con SortableJS.
   - Al soltar una tarjeta, se guardan su etapa y su orden por AJAX. Si se suelta en "Perdido", se pide el motivo.
   - Botón "Nueva oportunidad" en un modal.
   - Al abrir el embudo se importan las cotizaciones nuevas de PCLAP.

## Datos

**Lectura desde PCLAP** (conexión `pclap`; modelos que lanzan una excepción al guardar o borrar, igual que en Finanzas):

| Tabla | Para qué |
|---|---|
| `clientes` → `personas` → `documentos` | Clientes de la tienda: razón social, documento, teléfono y correo. Se excluye "Público General" (documento `00000000`). |
| `clienteweb` | Cuentas de la tienda web: nombre, correo, teléfono y documento. |
| `ventas` (+ `producto_venta`, `productos`, `series`) | Compras. Para un cliente de tienda: `cliente_id` sin `cliente_nuevo`. Para un cliente web: `clienteweb_id`. Solo cuentan las ventas con `estado_pago = 'PAGADO'`. |
| `garantias` | Garantías por `venta_id`, con la serie y el estado. |
| `pedidos_web` | Pedidos web por `clienteweb_id`, con código, total y estado. |
| `proformas` (+ `proforma_items`) | Cotizaciones: solo tienen nombre, teléfono y total, sin cliente ligado. Se convierten en oportunidades "Cotizado". |

**Tablas propias en `dbcrm`:**

- `prospectos`: nombre, empresa, documento, teléfono, correo, origen (WhatsApp, Feria, Web, Referido u Otro) y `es_demo`.
- `notas`: referencia del cliente, texto y usuario.
- `tareas`: título, fecha de vencimiento, fecha en que se hizo (nula si sigue pendiente), referencia del cliente (opcional), oportunidad (opcional) y `es_demo`.
- `oportunidades`:
  - Título, monto y etapa (nuevo, contactado, cotizado, ganado o perdido).
  - Orden dentro de la columna.
  - Contacto: nombre y teléfono.
  - Referencia del cliente (opcional).
  - `pclap_proforma_id` (único y opcional): el número de la cotización de PCLAP de la que salió.
  - Motivo de pérdida, fecha de cierre y `es_demo`.

**Referencia del cliente:** el par `cliente_tipo` (`tienda`, `web` o `prospecto`) + `cliente_id`. En las URL se escribe `tienda-5`, `web-2` o `prospecto-7`.

**Etiquetas** (se calculan al momento, con este orden de prioridad):
1. *Prospecto*: es un prospecto del CRM.
2. *Sin compras*: cliente de PCLAP con 0 compras pagadas.
3. *Nuevo*: su primera compra fue hace menos de 30 días.
4. *Inactivo*: su última compra fue hace más de 90 días.
5. *Frecuente*: tiene 3 compras o más.
6. *Ocasional*: el resto.

## Identidad visual

- La base SB Admin se **copia** de Finanzas, y encima va una hoja propia, `public/css/crm.css`.
- Estructura invertida respecto de los otros sistemas: barra superior **blanca** y barra lateral **ciruela**. PCLAP tiene las dos negras; Finanzas, la superior petróleo y la lateral blanca.
- Tipografía **Manrope**, distinta de la de sistema de PCLAP y de IBM Plex Sans de Finanzas.
- Logo propio: dos círculos enlazados (la relación con el cliente), en violeta.
- Los títulos del menú en tipo oración y la página actual marcada, como en Finanzas.
- El momento llamativo es el embudo: columnas con su total en soles, tarjetas que se levantan al arrastrarlas y el monto de cada tarjeta con cifras de ancho fijo.

## Que se vea complejo y bonito, pero sea simple por dentro (pedido de Carlos)

Carlos aprobó el contenido y pidió que en el portafolio el CRM **se vea complejo y bonito**, aunque sea simple. Para lograrlo se usan recursos visuales baratos que dan sensación de un sistema grande, sin agregar módulos (decisión de Claude):

- **Avatares con iniciales** de color estable por cliente (el color sale del nombre) en la lista, la ficha, las tarjetas del embudo y las tareas.
- **Etiquetas de colores** para cada segmento (Nuevo, Frecuente, Inactivo, Ocasional, Sin compras, Prospecto), con un **resumen por etiqueta** encima de la lista de clientes que también funciona como filtro.
- **Pestaña "Actividad" en la ficha:** una línea de tiempo que mezcla por fecha las compras, garantías y pedidos web de PCLAP con las notas, tareas y oportunidades del CRM. Es solo ordenar por fecha lo que ya se lee.
- **Embudo con más información:** cada columna muestra su cantidad de tarjetas, su total en soles y una barra con el porcentaje que representa del embudo. Las tarjetas indican los días que llevan en la etapa.
- **Contadores en el menú:** las tareas vencidas aparecen como un número junto a "Tareas".
- **Buscador global** en la barra superior: lleva a la lista de clientes filtrada.
- **Pantallas vacías con indicaciones** que dicen qué hacer, y avisos (toast) al guardar.

Nada de esto agrega tablas ni lógica nueva: son formas de mostrar los mismos datos.

## Decisiones de quien construye (Claude)

- Las ventas a "Público General" que solo tienen un nombre escrito (`cliente_nuevo`) no cuentan para ningún cliente, porque no hay forma de contactar a esa persona. Carlos aprobó esto en la descripción.
- Los clientes de la tienda y los de la web se muestran por separado aunque tengan el mismo documento. Unirlos queda para una fase futura.
- Las cotizaciones de PCLAP se importan una sola vez (con `pclap_proforma_id` único). Después, mover la oportunidad en el CRM no cambia nada en PCLAP. La importación se hace al abrir el **panel** o el **embudo**, para que el valor del embudo siempre las incluya.
- "Nuevos del mes" son los clientes cuya **primera compra** cae en el mes en curso, con el mismo criterio de fechas que la etiqueta *Nuevo*.
- La lista de clientes se ordena por la última compra (de la más reciente a la más antigua); los prospectos y los clientes sin compras van al final. Muestra 25 por página.
- Usuario administrador por defecto: `crm@gmail.com` con contraseña `123456`, siguiendo el estilo que eligió Carlos para Finanzas. Se cambia en el `.env`.
- Servidor en el puerto **8002**. El portafolio usa el 8000 y Finanzas el 8001.
- Las librerías del navegador se cargan desde un CDN, igual que en Finanzas: SortableJS para arrastrar, más Bootstrap, jQuery, Font Awesome y SweetAlert2.
- **Datos de demostración** con `DemoSeeder`, solo en `dbcrm` y marcados con `es_demo`:
  - 12 prospectos con nombres y empresas peruanas de ejemplo.
  - 10 oportunidades repartidas en las 5 etapas.
  - 8 tareas: algunas vencidas, otras para hoy y otras próximas.
  - Algunas notas.
  - En las listas llevan una etiqueta discreta "Demo".

## Lo que queda a criterio de quien construye

Los nombres de clases y rutas, el diseño exacto de cada pantalla dentro de la identidad visual, los textos de la interfaz (en español, con verbos claros) y cómo se organizan las pruebas.

## Cómo se verifica

1. `php artisan test` pasa en verde. En las pruebas, PCLAP es SQLite en memoria con las columnas reales, igual que en Finanzas.
2. Recorrido real en `http://127.0.0.1:8002`:
   - Iniciar sesión y ver la lista con los clientes reales de PCLAP más los prospectos demo.
   - Abrir la ficha de "Almacenes Premium" (cliente web).
   - Crear una nota y una tarea, y marcar la tarea como hecha.
   - Arrastrar una oportunidad hasta "Ganado".
   - Ver las 2 cotizaciones de PCLAP en "Cotizado".
3. Capturas del login, el panel, el embudo y una ficha, para revisar que se vean distintos de PCLAP y de Finanzas.
4. **PCLAP intacto:** ningún archivo de PCLAP cambia (se comprueba con `find -newer` contra una marca) y `pclap_lector` sigue sin poder escribir.
