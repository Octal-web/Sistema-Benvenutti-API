<?php

namespace App\Services;

use App\Models\Edicao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EdicaoService
{
    public function cadastrarEdicao($request)
    {
        DB::beginTransaction();

        try {
            $ultimaOrdem = Edicao::query()
                ->whereNull('excluido')
                ->max('ordem');

            $ordem = $ultimaOrdem ? $ultimaOrdem + 1 : 1;

            $edicao = Edicao::create([
                'destino' => $request['destino'],
                'ano' => $request['ano'],
                'visivel' => true,
                'ordem' => $ordem
            ]);

            DB::commit();

            return [
                'edicao' => [
                    'id' => $edicao->id,
                    'destino' => $edicao->destino,
                    'ano' => $edicao->ano,
                    'ordem' => $ordem,
                    'visivel' => (bool) $edicao->visivel,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function atualizarEdicao($request, $id)
    {
        DB::beginTransaction();

        try {
            $edicao = Edicao::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$edicao) {
                throw new \Exception('Edicao não encontrado!');
            }

            $edicao->update([
                'destino' => $request['destino'],
                'ano' => $request['ano']
            ]);

            DB::commit();

            return [
                'edicao' => $edicao,
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
            $edicao = Edicao::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$edicao) {
                throw new \Exception('Edição não encontrada!');
            }

            $edicao->update([
                'visivel' => $request['visivel'],
            ]);

            DB::commit();

            return [
                'edicao' => [
                    'id' => $edicao->id,
                    'destino' => $edicao->destino,
                    'ano' => $edicao->ano,
                    'ordem' => $edicao->ordem,
                    'visivel' => (bool) $edicao->visivel,
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

                $edicao = Edicao::query()
                    ->where([
                        'excluido' => NULL,
                        'id' => $odr['id']
                    ])
                    ->first();

                if (!$edicao) {
                    throw new \Exception("Registro com ID {$odr['id']} não encontrado!");
                }

                $edicao->update([
                    'ordem' => $odr['ordem'],
                ]);
            }

            DB::commit();

            return [
                'edicoes' => 'Ordem atualizada com sucesso!',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function excluirEdicao($id)
    {
        DB::beginTransaction();

        try {
            $edicao = Edicao::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$edicao) {
                throw new \Exception('Edicao não encontrada!');
            }

            $edicao->excluido = Carbon::now();
            $edicao->save();

            DB::commit();

            return [
                'edicao' => $edicao,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
}
