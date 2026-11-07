<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\OportunidadController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProspectoController;
use App\Http\Controllers\TareaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', PanelController::class)->name('panel');

    // {ref} = tienda-5, web-2 o prospecto-7 (ver App\Crm\ClienteRef)
    $ref = '(tienda|web|prospecto)-[1-9][0-9]*';
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/{ref}', [ClienteController::class, 'show'])->where('ref', $ref)->name('clientes.show');
    Route::post('/clientes/{ref}/notas', [NotaController::class, 'store'])->where('ref', $ref)->name('notas.store');

    Route::get('/prospectos/crear', [ProspectoController::class, 'create'])->name('prospectos.create');
    Route::post('/prospectos', [ProspectoController::class, 'store'])->name('prospectos.store');
    Route::get('/prospectos/{prospecto}/editar', [ProspectoController::class, 'edit'])->name('prospectos.edit');
    Route::put('/prospectos/{prospecto}', [ProspectoController::class, 'update'])->name('prospectos.update');

    Route::get('/tareas', [TareaController::class, 'index'])->name('tareas.index');
    Route::post('/tareas', [TareaController::class, 'store'])->name('tareas.store');
    Route::patch('/tareas/{tarea}/hecha', [TareaController::class, 'hecha'])->name('tareas.hecha');

    Route::get('/embudo', [OportunidadController::class, 'index'])->name('embudo.index');
    Route::post('/oportunidades', [OportunidadController::class, 'store'])->name('oportunidades.store');
    Route::patch('/oportunidades/{oportunidad}/mover', [OportunidadController::class, 'mover'])->name('oportunidades.mover');
});
