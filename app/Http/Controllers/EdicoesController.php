<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Edicao;

class EdicoesController extends Controller
{
    public function __invoke()
    {
        $edicoes = Edicao::query()
            ->where([
                'excluido' => NULL,
                'visivel' => 1
            ])
            ->whereHas('fotos', function ($q) {
                $q->where([
                    'excluido' => NULL,
                    'visivel' => 1,
                ]);
            })
            ->with([
                'fotos' => function ($q) {
                    $q->where([
                        'excluido' => NULL,
                        'visivel' => 1,
                    ])
                        ->orderBy('ordem', 'ASC')
                        ->orderBy('id', 'DESC');
                }
            ])
            ->orderBy('ordem', 'ASC')
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($edicao) {
                return [
                    'id' => $edicao->id,
                    'nome' => $edicao->destino,
                    'ano' => $edicao->ano,
                    'fotos' => $edicao->fotos->map(function ($foto) {
                        return [
                            'id' => $foto->id,
                            'imagem' => config('services.site.storage') . '/content/editions/thumbs/' . $foto->imagem,
                        ];
                    })
                ];
            });

        return response()->json([
            'edicoes' => $edicoes
        ]);
    }
}
