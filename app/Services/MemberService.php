<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Participante;
use App\Models\Passaporte;
use App\Models\Documento;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MemberService
{
    public function cadastrarParticipante($dadosUsuario, $dadosParticipante, $dadosDestinos, $dadosPassaporte, $imgDocumentos)
    {
        DB::beginTransaction();

        try {
            $usuario = Usuario::create([
                'nome' => $dadosUsuario['nome'],
                'email' => $dadosUsuario['email'],
                'funcao' => 'participante',
                'password' => Hash::make($dadosUsuario['password']),
                'ativo' => $dadosUsuario['ativo']
            ]);
            
            $participante = Participante::create([
                'nome_completo' => $dadosParticipante['nome_completo'],
                'cpf' => preg_replace('/\D/', '', $dadosParticipante['cpf']),
                'data_nascimento' => Carbon::createFromFormat('Y-m-d', $dadosParticipante['data_nascimento'])->format('Y-m-d'),
                'rg' => $dadosParticipante['rg'],
                'data_expedicao_rg' => Carbon::createFromFormat('Y-m-d', $dadosParticipante['data_expedicao_rg'])->format('Y-m-d'),
                'fone_celular' => $dadosParticipante['fone_celular'],
                'fone_emergencia' => $dadosParticipante['fone_emergencia'],
                // 'fone_fixo' => $dadosParticipante['fone_fixo'] ?? null,
                // 'fone_comercial' => $dadosParticipante['fone_comercial'],
                'restricao_alimentar' => $dadosParticipante['restricao_alimentar'],
                'restricao_alimentar_qual' => $dadosParticipante['restricao_alimentar'] ? $dadosParticipante['restricao_alimentar_qual'] : null,
                'limitacao' => $dadosParticipante['limitacao'],
                'limitacao_qual' => $dadosParticipante['limitacao'] ? $dadosParticipante['limitacao_qual'] : null,
                'medicamento' => $dadosParticipante['medicamento'],
                'medicamento_qual' => $dadosParticipante['medicamento'] ? $dadosParticipante['medicamento_qual'] : null,
                'medicamento_dosagem' => ($dadosParticipante['medicamento'] && isset($dadosParticipante['medicamento_dosagem'])) ? $dadosParticipante['medicamento_dosagem'] : null,
                'problema_saude' => $dadosParticipante['problema_saude'],
                'problema_saude_qual' => $dadosParticipante['problema_saude'] ? $dadosParticipante['problema_saude_qual'] : null,
                'aprovado_bloqueado' => $dadosParticipante['aprovado_bloqueado'],
                'conferido' => $dadosParticipante['conferido'],
                'confirmado' => $dadosParticipante['confirmado'],
                'usuario_id' => $usuario->id,
                'etapa_cadastro' => 'concluido',
            ]);

            if (!empty($dadosDestinos['destinos'])) {
                $participante->destinos()->sync($dadosDestinos['destinos']);
            }

            $passaporte = Passaporte::create([
                'numero' => $dadosPassaporte['numero'],
                'paginas_em_branco' => $dadosPassaporte['paginas_em_branco'],
                'data_emissao' => Carbon::createFromFormat('Y-m-d', $dadosPassaporte['data_emissao'])->format('Y-m-d'),
                'data_validade' => Carbon::createFromFormat('Y-m-d', $dadosPassaporte['data_validade'])->format('Y-m-d'),
                'participante_id' => $participante->id
            ]);

            $documento = null;

            if ($imgDocumentos['passaporte_arquivo'] || $imgDocumentos['certificado_arquivo']) {
                if ($imgDocumentos['passaporte_arquivo']) {
                    $passaporte = md5(uniqid(rand(), true)) . '.' . $imgDocumentos['passaporte_arquivo']->getClientOriginalExtension();
                }

                // if ($imgDocumentos['rg']) {
                //     $rg = md5(uniqid(rand(), true)) . '.' . $imgDocumentos['rg']->getClientOriginalExtension();
                // }
                
                if ($imgDocumentos['certificado_arquivo']) {
                    $certificado = md5(uniqid(rand(), true)) . '.' . $imgDocumentos['certificado_arquivo']->getClientOriginalExtension();
                }

                $documento = Documento::create([
                    'passaporte' => isset($passaporte) ? $passaporte : null,
                    'passaporte_status' => $imgDocumentos['passaporte_status'],
                    // 'rg' => $rg ? $rg : null,
                    'certificado' => isset($certificado) ? $certificado : null,
                    'certificado_status' => $imgDocumentos['certificado_status'],
                    'participante_id' => $participante->id,
                ]);
            }
        
            if ($imgDocumentos['passaporte_arquivo']) {
                $imgDocumentos['passaporte_arquivo']->move(base_path('../todeschini-media/uploads/documents/passport/'), $passaporte);
            }
            
            // if ($imgDocumentos['rg']) {
            //     $imgDocumentos['rg']->move(base_path('../todeschini/uploads/documents/rg/'), $rg);
            // }

            if ($imgDocumentos['certificado_arquivo']) {
                $imgDocumentos['certificado_arquivo']->move(base_path('../todeschini-media/uploads/documents/certificate/'), $certificado);
            }

            DB::commit();

            return response()->json([
                'usuario' => $usuario,
                'participante' => $participante,
                'passaporte' => $passaporte,
                'documento' => $documento
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

    public function atualizarParticipante($dadosUsuario, $dadosParticipante, $dadosDestinos, $dadosPassaporte, $imgDocumentos, $id)
    {

        DB::beginTransaction();

        try {
            $usuario = Usuario::query()
                ->where([
                    'id' => $id,
                    'funcao' => 'participante',
                    'excluido' => NULL
                ])
                ->with(['participante.passaporte' => function ($q) {
                    $q->where('excluido', NULL);
                }])
                ->with(['participante.documento' => function ($q) {
                    $q->where('excluido', NULL);
                }])
                ->with(['participante' => function ($q) {
                    $q->where('excluido', NULL);
                }])
                ->first();

            if (!$usuario) {
                throw new \Exception('Não há registro desse e-mail no programa!');
            }

            $usuario->update([
                'nome' => $dadosUsuario['nome'],
                'email' => $dadosUsuario['email'],
                'password' => isset($dadosUsuario['password']) && $dadosUsuario['password'] ? Hash::make($dadosUsuario['password']) : $usuario->password,
                'ativo' => $dadosUsuario['ativo']
            ]);

            $participante = $usuario->participante;
            $participante->update([
                'nome_completo' => $dadosParticipante['nome_completo'],
                'cpf' => preg_replace('/\D/', '', $dadosParticipante['cpf']),
                'data_nascimento' => Carbon::createFromFormat('Y-m-d', $dadosParticipante['data_nascimento'])->format('Y-m-d'),
                'rg' => $dadosParticipante['rg'],
                'data_expedicao_rg' => Carbon::createFromFormat('Y-m-d', $dadosParticipante['data_expedicao_rg'])->format('Y-m-d'),
                'fone_celular' => $dadosParticipante['fone_celular'],
                'fone_emergencia' => $dadosParticipante['fone_emergencia'],
                // 'fone_fixo' => $dadosParticipante['fone_fixo'] ?? null,
                // 'fone_comercial' => $dadosParticipante['fone_comercial'],
                'restricao_alimentar' => $dadosParticipante['restricao_alimentar'],
                'restricao_alimentar_qual' => $dadosParticipante['restricao_alimentar'] ? $dadosParticipante['restricao_alimentar_qual'] : null,
                'limitacao' => $dadosParticipante['limitacao'],
                'limitacao_qual' => $dadosParticipante['limitacao'] ? $dadosParticipante['limitacao_qual'] : null,
                'medicamento' => $dadosParticipante['medicamento'],
                'medicamento_qual' => $dadosParticipante['medicamento'] ? $dadosParticipante['medicamento_qual'] : null,
                'medicamento_dosagem' => ($dadosParticipante['medicamento'] && isset($dadosParticipante['medicamento_dosagem'])) ? $dadosParticipante['medicamento_dosagem'] : null,
                'problema_saude' => $dadosParticipante['problema_saude'],
                'problema_saude_qual' => $dadosParticipante['problema_saude'] ? $dadosParticipante['problema_saude_qual'] : null,
                'aprovado_bloqueado' => $dadosParticipante['aprovado_bloqueado'],
                'conferido' => $dadosParticipante['conferido'],
                'confirmado' => $dadosParticipante['confirmado'],
                'etapa_cadastro' => 'concluido',
            ]);

            if (!empty($dadosDestinos['destinos'])) {
                $participante->destinos()->sync($dadosDestinos['destinos']);
            }

            if ($dadosPassaporte) {
                if ($participante->passaporte) {
                    $participante->passaporte->update([
                        'numero' => $dadosPassaporte['numero'],
                        'paginas_em_branco' => $dadosPassaporte['paginas_em_branco'],
                        'data_emissao' => Carbon::createFromFormat('Y-m-d', $dadosPassaporte['data_emissao'])->format('Y-m-d'),
                        'data_validade' => Carbon::createFromFormat('Y-m-d', $dadosPassaporte['data_validade'])->format('Y-m-d'),
                    ]);
                } else {
                    Passaporte::create([
                        'numero' => $dadosPassaporte['numero'],
                        'paginas_em_branco' => $dadosPassaporte['paginas_em_branco'],
                        'data_emissao' => Carbon::createFromFormat('Y-m-d', $dadosPassaporte['data_emissao'])->format('Y-m-d'),
                        'data_validade' => Carbon::createFromFormat('Y-m-d', $dadosPassaporte['data_validade'])->format('Y-m-d'),
                        'participante_id' => $participante->id,
                    ]);
                }
            }

            $documento = $participante->documento ?? new Documento(['participante_id' => $participante->id]);

            if ($imgDocumentos['passaporte_arquivo'] || $imgDocumentos['certificado_arquivo']) {
                if ($imgDocumentos['passaporte_arquivo']) {
                    $passaporte = md5(uniqid(rand(), true)) . '.' . $imgDocumentos['passaporte_arquivo']->getClientOriginalExtension();
                    $imgDocumentos['passaporte_arquivo']->move(base_path('../todeschini-media/uploads/documents/passport/'), $passaporte);
                    $documento->passaporte = $passaporte;
                }


                // if ($imgDocumentos['rg']) {
                //     $rg = md5(uniqid(rand(), true)) . '.' . $imgDocumentos['rg']->getClientOriginalExtension();
                //     $imgDocumentos['rg']->move(public_path('uploads/documents/rg/'), $rg);
                //     $documento->rg = $rg;
                // }

                if ($imgDocumentos['certificado_arquivo']) {
                    $certificado = md5(uniqid(rand(), true)) . '.' . $imgDocumentos['certificado_arquivo']->getClientOriginalExtension();
                    $imgDocumentos['certificado_arquivo']->move(base_path('../todeschini-media/uploads/documents/certificate/'), $certificado);
                    $documento->certificado = $certificado;
                }

            }

            $documento->passaporte_status = $imgDocumentos['passaporte_status'];
            $documento->certificado_status = $imgDocumentos['certificado_status'];
                
            $documento->save();

            DB::commit();

            return response()->json([
                'usuario' => $usuario,
                'participante' => $participante,
                'passaporte' => $participante->passaporte,
                'documento' => $documento,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao solicitar a alteração: ' . $e->getMessage());
        }
    }

    public function excluirParticipantes($ids)
    {
        DB::beginTransaction();

        try {
            $response = Usuario::query()
                ->where([
                    'excluido' => NULL,
                    'funcao' => 'participante'
                ])
                ->whereIn('id', $ids)
                ->update([
                    'excluido' => Carbon::now()
                ]);

            if ($response === 0) {
                return response()->json([
                    'message' => 'Nenhum registro encontrado para exclusão.',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'message' => 'Registros excluídos com sucesso.',
                'excluidos' => $ids,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocorreu um erro ao excluir os registros!',
                'errors' => [
                    'general' => [$e->getMessage()],
                ],
            ], 500);
        }
    }

    public function aprovarParticipantes($ids)
    {
        DB::beginTransaction();

        try {
            $response = Participante::query()
                ->where([
                    'excluido' => NULL,
                ])
                ->whereIn('usuario_id', $ids)
                ->update([
                    'aprovado_bloqueado' => true
                ]);

            if ($response === 0) {
                return response()->json([
                    'message' => 'Nenhum registro encontrado para aprovação.',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'message' => 'Registros aprovados com sucesso.',
                'excluidos' => $ids,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocorreu um erro ao aprovar os registros!',
                'errors' => [
                    'general' => [$e->getMessage()],
                ],
            ], 500);
        }
    }

    public function conferirParticipantes($ids)
    {
        DB::beginTransaction();

        try {
            $response = Participante::query()
                ->where([
                    'excluido' => NULL,
                ])
                ->whereIn('usuario_id', $ids)
                ->update([
                    'conferido' => true
                ]);

            if ($response === 0) {
                return response()->json([
                    'message' => 'Nenhum registro encontrado para aprovação.',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'message' => 'Registros conferidos com sucesso.',
                'excluidos' => $ids,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocorreu um erro ao conferir os registros!',
                'errors' => [
                    'general' => [$e->getMessage()],
                ],
            ], 500);
        }
    }

    public function confirmarParticipantes($ids)
    {
        DB::beginTransaction();

        try {
            $response = Participante::query()
                ->where([
                    'excluido' => NULL,
                ])
                ->whereIn('usuario_id', $ids)
                ->update([
                    'confirmado' => true
                ]);

            if ($response === 0) {
                return response()->json([
                    'message' => 'Nenhum registro encontrado para aprovação.',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'message' => 'Registros confirmados com sucesso.',
                'excluidos' => $ids,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocorreu um erro ao confirmar os registros!',
                'errors' => [
                    'general' => [$e->getMessage()],
                ],
            ], 500);
        }
    }
}