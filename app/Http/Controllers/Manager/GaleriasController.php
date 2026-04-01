<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Galeria;
use App\Services\GaleriaService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class GaleriasController extends Controller
{
    protected $galeriaService;

    public function __construct(GaleriaService $galeriaService)
    {
        $this->galeriaService = $galeriaService;
    }

    public function getGalerias()
    {
        $galeria = Galeria::query()
            ->where([
                'excluido' => NULL,
            ])
            ->orderBy('ano', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($galeria) {
                return [
                    'id' => $galeria->id,
                    'nome' => $galeria->destino,
                    'ano' => $galeria->ano
                ];
            });

        return response()->json([
            'galerias' => $galeria

        ]);
    }

    public function getGaleria($id)
    {
        $galeria = Galeria::query()
            ->where([
                'id' => $id,
                'excluido' => NULL,
            ])
            ->first();

        $galeriaData = [
            'id' => $galeria->id,
            'destino' => $galeria->destino,
            'ano' => $galeria->ano
        ];

        return response()->json([
            'galeria' => $galeriaData
        ]);
    }

    public function createGaleria(Request $request)
    {
        $anoAtual = date('Y');

        $this->validate($request, [
            'destino' => 'required|max:100',
            'ano' =>  'required|integer|min:1900|max:' . $anoAtual
        ], [
            'destino.required' => 'Por favor, informe o destino.',
            'destino.max' => 'O destino deve ter no máximo 100 caracteres.',
            'ano.required' => 'Por favor, informe o ano.',
            'ano.integer' => 'Valor inválido para o ano.',
            'ano.min' => 'O ano deve ser no mínimo de 1900.',
            'ano.max' => 'O ano deve ser no máximo de atualmente: ' . $anoAtual
        ]);

        $dados = $request->only(['ano', 'destino']);

        try {
            $response = $this->galeriaService->cadastrarGaleria($dados);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Galeria criada com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar galeria',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateGaleria(Request $request, $id)
    {
        $anoAtual = date('Y');

        $this->validate($request, [
            'destino' => 'required|max:100',
            'ano' =>  'required|integer|min:1900|max:' . $anoAtual
        ], [
            'destino.required' => 'Por favor, informe o destino.',
            'destino.max' => 'O destino deve ter no máximo 100 caracteres.',
            'ano.required' => 'Por favor, informe o ano.',
            'ano.integer' => 'Valor inválido para o ano.',
            'ano.min' => 'O ano deve ser no mínimo de 1900.',
            'ano.max' => 'O ano deve ser no máximo de atualmente: ' . $anoAtual
        ]);

        $dados = $request->only(['ano', 'destino']);

        try {
            $response = $this->galeriaService->atualizarGaleria($dados, $id);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Galeria atualizada com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar galeria',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteGaleria($id)
    {
        try {
            $this->galeriaService->excluirGaleria($id);

            return response()->json([
                'success' => true,
                'message' => 'Galeria excluida com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir galeria.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
