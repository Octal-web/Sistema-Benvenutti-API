<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Participante;
use App\Models\Passaporte;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function atualizarCadastro($dadosUsuario, $dadosParticipante, $dadosPassaporte)
    {
        DB::beginTransaction();

        try {
            $usuario = auth()->user();

            if (!$usuario->ativo) {
                throw new \Exception('Usuário inativo. Não é possível realizar a atualização.');
            }
            
            $usuario->update($dadosUsuario);

            $participante = $usuario->participante;
            
            $participante->update([
                'nome_completo' => $dadosParticipante['nome_completo'],
                'cpf' => preg_replace('/\D/', '', $dadosParticipante['cpf']),
                'data_nascimento' => Carbon::createFromFormat('Y-m-d', $dadosParticipante['data_nascimento'])->format('Y-m-d'),
                'rg' => $dadosParticipante['rg'],
                'data_expedicao_rg' => Carbon::createFromFormat('Y-m-d', $dadosParticipante['data_expedicao_rg'])->format('Y-m-d'),
                'fone_celular' => $dadosParticipante['fone_celular'],
                // 'fone_fixo' => $dadosParticipante['fone_fixo'] ?? null,
                // 'fone_comercial' => $dadosParticipante['fone_comercial'],
                'fone_emergencia' => $dadosParticipante['fone_emergencia'],
                'etapa_cadastro' => 'dados_adicionais',
                'restricao_alimentar' => $dadosParticipante['restricao_alimentar'],
                'restricao_alimentar_qual' => $dadosParticipante['restricao_alimentar'] ? $dadosParticipante['restricao_alimentar_qual'] : null,
                'limitacao' => $dadosParticipante['limitacao'],
                'limitacao_qual' => $dadosParticipante['limitacao'] ? $dadosParticipante['limitacao_qual'] : null,
                'medicamento' => $dadosParticipante['medicamento'],
                'medicamento_qual' => $dadosParticipante['medicamento'] ? $dadosParticipante['medicamento_qual'] : null,
                'medicamento_dosagem' => $dadosParticipante['medicamento'] ? $dadosParticipante['medicamento_dosagem'] : null,
                'problema_saude' => $dadosParticipante['problema_saude'],
                'problema_saude_qual' => $dadosParticipante['problema_saude'] ? $dadosParticipante['problema_saude_qual'] : null,
            ]);

            if ($participante->passaporte) {
                $participante->passaporte()->update([
                    'numero' => $dadosPassaporte['numero'],
                    'paginas_em_branco' => $dadosPassaporte['paginas_em_branco'],
                    'data_emissao' => Carbon::createFromFormat('Y-m-d', $dadosPassaporte['data_emissao'])->format('Y-m-d'),
                    'data_validade' => Carbon::createFromFormat('Y-m-d', $dadosPassaporte['data_validade'])->format('Y-m-d'),
                ]);
            } else {
               $participante->passaporte = Passaporte::create([
                    'numero' => $dadosPassaporte['numero'],
                    'paginas_em_branco' => $dadosPassaporte['paginas_em_branco'],
                    'data_emissao' => Carbon::createFromFormat('Y-m-d', $dadosPassaporte['data_emissao'])->format('Y-m-d'),
                    'data_validade' => Carbon::createFromFormat('Y-m-d', $dadosPassaporte['data_validade'])->format('Y-m-d'),
                    'participante_id' => $participante->id
                ]);
            }

            $participante->destinos()->sync($dadosParticipante['destinos']);
            
            DB::commit();

            return [
                'usuario' => $usuario,
                'participante' => $participante
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocorreu um erro!',
                'errors' => [
                    'general' => [$e->getMessage()]
                ]
            ], 500);
        }
    }
}