<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Programa;
use Illuminate\Support\Carbon;

class ProgramaController extends Controller
{
    public function index()
    {
        $programa = Programa::first();

        if (!$programa) {
            return response()->json([
                'error' => 'Programa não encontrado.'
            ], 404);
        }

        $cadastrosAtivos = Carbon::now()->between(
            Carbon::parse($programa->data_inicio),
            Carbon::parse($programa->data_final)
        );

        return response()->json([
            'programa' => [
                'titulo' => $programa->titulo,
                'descricao' => $programa->descricao,
                'data_inicio' => $programa->data_inicio,
                'data_final' => $programa->data_final,
                'regulamento' => $programa->regulamento
                    ? config('services.site.storage') . '/content/files/' . $programa->regulamento
                    : null,
            ],
            'cadastros_ativos' => $cadastrosAtivos
        ]);
    }

    public function getRegulamento()
    {
        $programa = Programa::first();

        if (!$programa || empty($programa->regulamento)) {
            return response()->json([
                'success' => false,
                'message' => 'Regulamento não encontrado.'
            ], 404);
        }

        return response()->json([
            'regulamento' => $programa->regulamento,
        ]);
    }

    public function getTermoAdesao()
    {
        $programa = Programa::first();

        if (!$programa || empty($programa->termo_adesao)) {
            return response()->json([
                'success' => false,
                'message' => 'Termo de adesão não encontrado.'
            ], 404);
        }

        $usuario   = Auth::user();
        $participante = $usuario->participante;

        $map = [
            '--nome_do_participante--' => $usuario->nome,
            '--cpf_do_participante--'  => $participante->cpf ?? '',
            '--email--'                => $usuario->email,
            '--fone_celular--'         => $participante->fone_celular ?? '',
        ];

        $htmlFinal = str_replace(array_keys($map), array_values($map), $programa->termo_adesao);

        return response()->json([
            'termo_adesao' => $htmlFinal,
        ]);
    }
}
