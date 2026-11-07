<?php

namespace App\Http\Controllers;

use App\Crm\AgendaTareas;
use App\Crm\DirectorioClientes;
use App\Crm\Embudo;
use App\Models\Oportunidad;
use App\Models\Prospecto;
use Illuminate\View\View;
use Throwable;

class PanelController extends Controller
{
    public function __invoke(DirectorioClientes $directorio, Embudo $embudo, AgendaTareas $agenda): View
    {
        $clientes = $nuevosDelMes = $inactivos = null;
        $clientesPorRef = collect();
        $errorPclap = null;

        // Lo que viene de PCLAP puede fallar (MySQL apagado); tareas y embudo siguen con la base del CRM.
        try {
            $embudo->sincronizarProformas();
            $todos = $directorio->todos();
            $clientes = $todos->where('origen', '!=', 'Prospecto')->count();
            $nuevosDelMes = $directorio->nuevosDelMes();
            $inactivos = $directorio->conteoPorSegmento()['Inactivo'];
            $clientesPorRef = $todos->keyBy(fn ($cliente) => (string) $cliente->ref);
        } catch (Throwable $error) {
            report($error);
            $errorPclap = 'No se pudo leer la base de PCLAP. Revisa que MySQL esté encendido en XAMPP.';
        }

        $grupos = $agenda->grupos();

        return view('panel', [
            'clientes' => $clientes,
            'prospectos' => Prospecto::count(),
            'nuevosDelMes' => $nuevosDelMes,
            'inactivos' => $inactivos,
            'tareasHoy' => $agenda->paraHoy(),
            'valorEmbudo' => $embudo->valorAbierto(),
            'columnas' => $embudo->columnas(),
            'tareas' => $grupos['vencidas']->concat($grupos['hoy'])->take(6),
            'clientesPorRef' => $clientesPorRef,
            'recientes' => Oportunidad::latest('updated_at')->limit(5)->get(),
            'errorPclap' => $errorPclap,
        ]);
    }
}
