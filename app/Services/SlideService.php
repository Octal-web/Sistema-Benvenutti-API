<?php

namespace App\Services;

use App\Models\Slide;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\Services\ImagemCompressorService;

class SlideService
{
    protected $compressor;

    public function __construct(ImagemCompressorService $compressor)
    {
        $this->compressor = $compressor;
    }

    public function cadastrarSlide($data, $imagens)
    {
        DB::beginTransaction();

        try {
            $imagem = md5(uniqid(rand(), true)) . '.' . strtolower($imagens['imagem']->extension());
            $imagem_mobile = md5(uniqid(rand(), true)) . '.' . strtolower($imagens['imagem_mobile']->extension());

            $ultimaOrdem = Slide::query()
                ->where([
                    'excluido' => NULL,
                ])
                ->max('ordem');

            $ordem = $ultimaOrdem ? $ultimaOrdem + 1 : 1;

            $slide = Slide::create([
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'],
                'visivel' => true,
                'ordem' => $ordem,
                'imagem' => $imagem,
                'imagem_mobile' => $imagem_mobile,
            ]);

            DB::commit();

            $this->compressor->compactarOuReverter($imagens['imagem']->getRealPath(), base_path('../media/content/editions/slides/d/' . $imagem));

            $this->compressor->compactarOuReverter($imagens['imagem_mobile']->getRealPath(), base_path('../media/content/editions/slides/m/' .  $imagem_mobile));

            return [
                'slide' => [
                    'id' => $slide->id,
                    'titulo' => $slide->titulo,
                    'descricao' => $slide->descricao,
                    'ordem' => $slide->ordem,
                    'img' => $slide->imagem,
                    'img_mobile' => $slide->imagem_mobile,
                    'visivel' => (bool) $slide->visivel,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function atualizarSlide($data, $imagens, $id)
    {
        DB::beginTransaction();

        try {
            $slide = Slide::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$slide) {
                throw new \Exception('Slide não encontrado!');
            }

            $slide->update([
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'],
            ]);

            if ($imagens['imagem']) {
                $imagem = md5(uniqid(rand(), true)) . '.' . strtolower($imagens['imagem']->extension());

                $slide->update([
                    'imagem' => $imagem,
                ]);

                $this->compressor->compactarOuReverter($imagens['imagem']->getRealPath(), base_path('../media/content/editions/slides/d/' . $imagem));
            }

            if ($imagens['imagem_mobile']) {
                $imagem_mobile = md5(uniqid(rand(), true)) . '.' . strtolower($imagens['imagem_mobile']->extension());

                $slide->update([
                    'imagem_mobile' => $imagem_mobile,
                ]);

                $this->compressor->compactarOuReverter($imagens['imagem_mobile']->getRealPath(), base_path('../media/content/editions/slides/m/' .  $imagem_mobile));
            }

            DB::commit();

            return [
                'slide' => [
                    'id' => $slide->id,
                    'titulo' => $slide->titulo,
                    'descricao' => $slide->descricao,
                    'ordem' => $slide->ordem,
                    'img' => $slide->imagem,
                    'img_mobile' => $slide->imagem_mobile,
                    'visivel' => (bool) $slide->visivel,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function atualizarOrdem($request)
    {
        DB::beginTransaction();

        try {
            foreach ($request as $odr) {
                if (!isset($odr['id']) || !isset($odr['ordem'])) {
                    throw new \Exception('O formato do request está inválido. É necessário um campo id e ordem.');
                }

                $slide = Slide::query()
                    ->where([
                        'excluido' => NULL,
                        'id' => $odr['id'],
                    ])
                    ->first();

                if (!$slide) {
                    throw new \Exception("Registro com ID {$odr['id']} não encontrado!");
                }

                $slide->update([
                    'ordem' => $odr['ordem'],
                ]);
            }

            DB::commit();

            return [
                'imagens' => 'Ordem atualizada com sucesso!',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function atualizarVisibilidade($request, $id)
    {
        DB::beginTransaction();

        try {
            $slide = Slide::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$slide) {
                throw new \Exception('Slide não encontrada!');
            }

            $slide->update([
                'visivel' => $request['visivel'],
            ]);

            DB::commit();

            return [
                'slide' => [
                    'id' => $slide->id,
                    'titulo' => $slide->titulo,
                    'descricao' => $slide->descricao,
                    'ordem' => $slide->ordem,
                    'imagem' => $slide->imagem,
                    'imagem_mobile' => $slide->imagem_mobile,
                    'visivel' => (bool) $slide->visivel,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function excluirSlide($id)
    {
        DB::beginTransaction();

        try {
            $slide = Slide::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$slide) {
                throw new \Exception('Slide não encontrada!');
            }

            $slide->excluido = Carbon::now();
            $slide->save();

            DB::commit();

            return  $slide;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
}
