<?php

namespace App\Http\Controllers;

use App\Models\Prospecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProspectoController extends Controller
{
    public function create(): View
    {
        return view('prospectos.form', ['prospecto' => new Prospecto(['origen' => 'WhatsApp'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $prospecto = Prospecto::create($this->validar($request));

        return redirect()->route('clientes.show', (string) $prospecto->ref())->with('success', 'Prospecto creado.');
    }

    public function edit(Prospecto $prospecto): View
    {
        return view('prospectos.form', ['prospecto' => $prospecto]);
    }

    public function update(Request $request, Prospecto $prospecto): RedirectResponse
    {
        $prospecto->update($this->validar($request));

        return redirect()->route('clientes.show', (string) $prospecto->ref())->with('success', 'Prospecto actualizado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'empresa' => ['nullable', 'string', 'max:150'],
            'documento' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'origen' => ['required', Rule::in(Prospecto::ORIGENES)],
        ]);
    }
}
