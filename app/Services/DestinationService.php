<?php

namespace App\Services;

use App\Models\Destino;
use Illuminate\Support\Facades\DB;

class DestinationService
{
    public function criarDestino($dadosDestino)
    {
        DB::beginTransaction();

        try {
            $destino = Destino::create([
                'destino' => $dadosDestino['destino'],
                'anovigente' => $dadosDestino['anovigente'],
                'certificado_febre' => $dadosDestino['certificado_febre'],
            ]);

            DB::commit();

            return $destino;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao criar destino: ' . $e->getMessage());
        }
    }

    public function atualizarDestino($id, $dadosDestino)
    {
        DB::beginTransaction();

        try {
            $destino = Destino::find($id);

            if (!$destino) {
                throw new \Exception('Destino não encontrado.');
            }

            $destino->update([
                'destino' => $dadosDestino['destino'],
                'anovigente' => $dadosDestino['anovigente'],
                'certificado_febre' => $dadosDestino['certificado_febre'],
            ]);

            DB::commit();

            return $destino;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao atualizar destino: ' . $e->getMessage());
        }
    }
}
