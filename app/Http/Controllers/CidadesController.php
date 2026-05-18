<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Estado;
use App\Models\Cidade;

class CidadesController extends Controller
{
    public function getEstados()
    {
        $estados = Estado::query()
            ->orderBy('nome', 'ASC')
            ->get()
            ->map(function ($estado) {
                return [
                    'value' => $estado->id,
                    'label' => $estado->nome,
                ];
            });

        return response()->json([
            'estados' => $estados,
        ]);
    }

    public function getCidades($id)
    {
        $estado = Estado::where('id', $id)->first();

        if (!$estado) {
            return response()->json([
                'error' => 'Estado não encontrado.'
            ], 404);
        }

        $cidades = Cidade::where('estado_id', $id)
            ->orderBy('nome', 'ASC')
            ->get()
            ->map(function ($cidade) {
                return [
                    'value' => $cidade->id,
                    'label' => $cidade->nome,
                ];
            });

        return response()->json([
            'cidades' => $cidades,
        ]);
    }
}
