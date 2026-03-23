<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Participante;
use App\Models\Passaporte;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class StepsService
{
    public function primeiraEtapa($dadosUsuario, $dadosParticipante)
    {
        DB::beginTransaction();

        try {
            $usuario = Usuario::create([
                'nome' => $dadosUsuario['nome'],
                'email' => $dadosUsuario['email'],
                'funcao' => 'participante',
                'password' => Hash::make($dadosUsuario['password']),
            ]);
            
            $participante = Participante::create([
                'cpf' => preg_replace('/\D/', '', $dadosParticipante['cpf']),
                'etapa_cadastro' => 'etapa2',
                'usuario_id' => $usuario->id,
            ]);

            $token = JWTAuth::fromUser($usuario);

            $data['nome'] = $dadosUsuario['nome'];
            $data['email'] = $dadosUsuario['email'];
            $data['senha'] = $dadosUsuario['password'];

            Mail::send('emails.register', $data, function($message)use($data) {
                $message->from('naoresponda@todeschini.viaggiotur.com.br', 'Todeschini')
                        ->to($data['email'])
                        ->bcc('rafael@8poroito.com.br')
                        ->subject('Você está inscrito no Todeschini Experience!');
            });
            
            DB::commit();

            return response()->json([
                'usuario' => $usuario,
                'participante' => $participante,
                'token' => $token
            ], 201);
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

    public function segundaEtapa($dadosUsuario, $dadosParticipante)
    {
        DB::beginTransaction();

        try {
            $usuario = Usuario::findOrFail($dadosUsuario['id']);

            $participante = Participante::query()
                ->where([
                    'excluido' => NULL,
                    'usuario_id' => $usuario->id,
                    'etapa_cadastro' => 'etapa2'
                ])
                ->firstOrFail();

            $usuario->update([
                'email' => $dadosUsuario['email']
            ]);

            $participante->update([
                'nome_completo' => $dadosParticipante['nome_completo'],
                'cpf' => preg_replace('/\D/', '', $dadosParticipante['cpf']),
                'data_nascimento' => Carbon::createFromFormat('d/m/Y', $dadosParticipante['data_nascimento'])->format('Y-m-d'),
                'rg' => $dadosParticipante['rg'],
                'data_expedicao_rg' => Carbon::createFromFormat('d/m/Y', $dadosParticipante['data_expedicao_rg'])->format('Y-m-d'),
                'fone_celular' => $dadosParticipante['fone_celular'],
                'fone_fixo' => $dadosParticipante['fone_fixo'] ?? null,
                'fone_comercial' => $dadosParticipante['fone_comercial'],
                'fone_emergencia' => $dadosParticipante['fone_emergencia'],
                'etapa_cadastro' => 'dados_adicionais',
            ]);

            if (!empty($dadosParticipante['destinos'])) {
                $participante->destinos()->sync($dadosParticipante['destinos']);
            }

            DB::commit();

            return [
                'usuario' => $usuario,
                'participante' => $participante,
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

    public function dadosAdicionais($usuarioId, $dadosParticipante, $dadosPassaporte)
    {
        DB::beginTransaction();

        try {
            $participante = Participante::where('usuario_id', $usuarioId)->firstOrFail();
            
            $participante->update([
                'restricao_alimentar' => $dadosParticipante['restricao_alimentar'],
                'restricao_alimentar_qual' => $dadosParticipante['restricao_alimentar'] ? $dadosParticipante['restricao_alimentar_qual'] : null,
                'limitacao' => $dadosParticipante['limitacao'],
                'limitacao_qual' => $dadosParticipante['limitacao'] ? $dadosParticipante['limitacao_qual'] : null,
                'medicamento' => $dadosParticipante['medicamento'],
                'medicamento_qual' => $dadosParticipante['medicamento'] ? $dadosParticipante['medicamento_qual'] : null,
                'problema_saude' => $dadosParticipante['problema_saude'],
                'problema_saude_qual' => $dadosParticipante['problema_saude'] ? $dadosParticipante['problema_saude_qual'] : null,
                'etapa_cadastro' => 'concluido',
            ]);

            $passaporte = Passaporte::create([
                'numero' => $dadosPassaporte['numero'],
                'paginas_em_branco' => $dadosPassaporte['paginas_em_branco'],
                'data_emissao' => Carbon::createFromFormat('d/m/Y', $dadosPassaporte['data_emissao'])->format('Y-m-d'),
                'data_validade' => Carbon::createFromFormat('d/m/Y', $dadosPassaporte['data_validade'])->format('Y-m-d'),
                'participante_id' => $participante->id
            ]);

            DB::commit();

            return [
                'participante' => $participante,
                'passaporte' => $passaporte
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