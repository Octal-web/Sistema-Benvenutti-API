<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;

use App\Models\Slide;

use App\Services\SlideService;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class SlidesController extends Controller
{
    protected $slideService;

    public function __construct(SlideService $slideService)
    {
        $this->slideService = $slideService;
    }

    public function getSlides()
    {
        $slides = Slide::query()
            ->where([
                'excluido' => NULL
            ])
            ->orderBy('ordem', 'ASC')
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($slide) {
                return [
                    'id' => $slide->id,
                    'imagem' => config('services.site.storage') . '/content/editions/slides/d/' . $slide->imagem,
                    'imagem_mobile' => config('services.site.storage') . '/content/editions/slides/m/' . $slide->imagem_mobile,
                    'titulo' => $slide->titulo,
                    'visivel' => (bool)  $slide->visivel,
                    'ordem' => $slide->ordem
                ];
            });

        return response()->json([
            'slides' => $slides
        ]);
    }

    public function getSlide($id)
    {
        $slide = Slide::query()
            ->where([
                'excluido' => NULL,
                'id' => $id
            ])
            ->first();

        if (!$slide) {
            return response()->json([
                'error' => 'Slide não encontrado.'
            ], 404);
        }

        $slideData = [
            'id' => $slide->id,
            'titulo' => $slide->titulo,
            'descricao' => $slide->descricao,
            'ordem' => $slide->ordem,
            'imagem' => config('services.site.storage') . '/content/editions/slides/d/' . $slide->imagem,
            'imagem_mobile' => config('services.site.storage') . '/content/editions/slides/m/' . $slide->imagem_mobile,
        ];

        return response()->json([
            'slide' => $slideData
        ]);
    }

    public function createSlide(Request $request)
    {
        $this->validate($request, [
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string|max:255',
            'imagem' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'imagem_mobile' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ], [
            'titulo.required' => 'Por favor, informe o titulo.',
            'titulo.string' => 'O formato do titulo é inválido',
            'titulo.max' => 'O título deve ter no máximo 255 caracteres',
            'descricao.required' => 'Por favor, informe a descrição.',
            'descricao.string' => 'O formato da descrição é inválida',
            'descricao.max' => 'A descrição deve ter no máximo 255 caracteres',
            'imagem.required' => 'Por favor, insira a imagem.',
            'imagem.image' => 'A imagem não está em um formato válido.',
            'imagem.mimes' => 'Os formatos aceitos para a imagem são JPEG, PNG e JPG.',
            'imagem.max' => 'O tamanho máximo de upload é 5MB.',
            'imagem_mobile.required' => 'Por favor, insira a imagem mobile.',
            'imagem_mobile.image' => 'A imagem mobile não está em um formato válido.',
            'imagem_mobile.mimes' => 'Os formatos aceitos para a imagem mobile são JPEG, PNG e JPG.',
            'imagem_mobile.max' => 'O tamanho máximo de upload é 5MB.',
        ]);

        $data = $request->only('titulo', 'descricao');

        $imagens = [
            'imagem_mobile' => $request->file('imagem_mobile'),
            'imagem' => $request->file('imagem')
        ];

        try {
            $response = $this->slideService->cadastrarSlide($data, $imagens);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Slide criado com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar slide.',
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

    public function updateSlide(Request $request, $id)
    {
        $this->validate($request, [
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string|max:255',
            'imagem' => 'nullable|image|mimes:png,jpg|max:5120',
            'imagem_mobile' => 'nullable|image|mimes:png,jpg|max:5120',
        ], [
            'titulo.required' => 'Por favor, informe o titulo.',
            'titulo.string' => 'O formato do titulo é inválido',
            'titulo.max' => 'O título deve ter no máximo 255 caracteres',
            'descricao.required' => 'Por favor, informe a descrição.',
            'descricao.string' => 'O formato da descrição é inválida',
            'descricao.max' => 'A descrição deve ter no máximo 255 caracteres',
            'imagem.image' => 'A imagem não está em um formato válido.',
            'imagem.mimes' => 'Os formatos aceitos para a imagem são JPEG, PNG e JPG.',
            'imagem.max' => 'O tamanho máximo de upload é 5MB.',
            'imagem_mobile.image' => 'A imagem mobile não está em um formato válido.',
            'imagem_mobile.mimes' => 'Os formatos aceitos para a imagem mobile são JPEG, PNG e JPG.',
            'imagem_mobile.max' => 'O tamanho máximo de upload é 5MB.',
        ]);

        $data = $request->only('titulo', 'descricao');

        $imagens = [
            'imagem_mobile' => $request->file('imagem_mobile'),
            'imagem' => $request->file('imagem')
        ];

        try {
            $response = $this->slideService->atualizarSlide($data, $imagens, $id);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Slide atualizado com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar slide.',
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

    public function orderSlides(Request $request)
    {
        $this->validate($request, [
            'odr' => 'required|array',
            'odr.*.id' => 'required|integer',
            'odr.*.ordem' => 'required|integer',
        ], [
            'odr.required' => 'Por favor, informe a lista de ordens.',
            'odr.array' => 'O formato está inválido.',
            'odr.*.id.required' => 'Por favor, informe a ordem dos slides.',
            'odr.*.ordem.required' => 'A ordem dos slides é um valor inválido!'
        ]);

        $dados = $request->input('odr', []);

        try {
            $response = $this->slideService->atualizarOrdem($dados);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Ordem atualizada com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar ordem.',
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

    public function visibleSlide(Request $request, $id)
    {
        $this->validate($request, [
            'visivel' => 'required|boolean',
        ], [
            'visivel.required' => 'Por favor, informe a visibilidade do slide.',
            'visivel.boolean' => 'A visibilidade do slide é um valor inválido!'
        ]);

        $dados = $request->only(['visivel']);

        try {
            $response = $this->slideService->atualizarVisibilidade($dados, $id);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Visibilidade atualizada com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar visibilidade.',
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

    public function deleteSlide($id)
    {
        try {
            $response = $this->slideService->excluirSlide($id);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Slide excluído com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir slide.',
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
