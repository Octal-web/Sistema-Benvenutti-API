<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Slide;

class SlidesController extends Controller
{

    public function __invoke()
    {
        $slides = Slide::query()
            ->where([
                'excluido' => NULL,
                'visivel' => 1
            ])
            ->orderBy('ordem', 'ASC')
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($slide) {
                return [
                    'id' => $slide->id,
                    'imagem' => config('services.site.storage') . '/content/slides/d/' . $slide->imagem,
                    'imagem_mobile' => config('services.site.storage') . '/content/slides/m/' . $slide->imagem_mobile,
                    'titulo' => $slide->titulo,
                    'descricao' => $slide->descricao
                ];
            });

        return response()->json([
            'slides' => $slides
        ]);
    }
}
