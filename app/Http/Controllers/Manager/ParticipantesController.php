<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;

use App\Models\Usuario;

use App\Services\MemberService;

use Illuminate\Http\Request;
use Carbon\Carbon;

class ParticipantesController extends Controller
{
    protected $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    public function getParticipantes() {
        $participantes = Usuario::query()
            ->where([
                'excluido' => NULL,
                'funcao' => 'participante',
            ])
            ->with([
                'participante' => function ($q) {
                    $q->where('excluido', NULL)
                      ->with(['pontos' => function ($query) {
                        $query->where('excluido', NULL);
                      }]);
                },
                'logs' => function ($q) {
                    $q->latest('criado')
                    ->take(1);
                },
            ])
            ->get()
            ->map(function ($usuario) {
                $pontos = $usuario->participante->pontos ?? collect();

                $totalPontos = $pontos->sum(function ($ponto) {
                    return $ponto->tipo === 'adicao'
                        ? $ponto->quantidade
                        : -$ponto->quantidade;
                });

                return [
                    'id' => $usuario->id,
                    'nome' => $usuario->nome,
                    'email' => $usuario->email,
                    'etapa_cadastro' => $usuario->participante->etapa_cadastro,
                    'pontos' => $totalPontos,

                    'ultimo_acesso' => $usuario->logs->isNotEmpty()
                        ? $usuario->logs->first()->criado->toDateTimeString()
                        : 'Nunca',

                    'ultimo_acesso_ha' => $usuario->logs->isNotEmpty()
                        ? Carbon::parse($usuario->logs->first()->criado)->diffForHumans()
                        : 'Nunca',
                ];
            })
            ->sortByDesc('pontos')
            ->values()
            ->map(function ($usuario, $index) {
                $usuario['posicao'] = $index + 1;
                return $usuario;
            });

        return response()->json([
            'participantes' => $participantes,
        ]);
    }

    public function inviteParticipante(Request $request) {
        $this->validate($request, [
            'email' => 'required|email|unique:usuarios,email|max:255',
        ], [
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está registrado no programa.',
        ]);

        $dadosConvidado = $request->only(['email']);

        try {
            $response = $this->memberService->convidarParticipante($dadosConvidado);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Convite enviado com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar convite.',
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

    public function getParticipante($id) {
        $participante = Usuario::query()
            ->where([
                'excluido' => NULL,
                'funcao' => 'participante',
                'id' => $id
            ])
            ->with(['participante' => function ($q) {
                $q->where('excluido', NULL)
                  ->with(['pontos' => function ($query) {
                        $query->where('excluido', NULL)
                              ->orderBy('criado', 'ASC');
                  }]);
            }])
            ->first();

        if (!$participante) {
            return response()->json([
                'error' => 'Participante não encontrado.'
            ], 404);
        }

        $etapas_cadastro = [
            'convidado' => 'Convite enviado',
            'concluido' => 'Concluído',
        ];

        $participanteData = [
            'id' => $participante->id,
            'nome' => $participante->nome,
            'email' => $participante->email,
            'cpf' => $participante->participante->cpf ? vsprintf("%s%s%s.%s%s%s.%s%s%s-%s%s", str_split($participante->participante->cpf)) : NULL,
            'rg' => $participante->participante->rg,
            'data_expedicao_rg' => $participante->participante->data_expedicao_rg,
            'data_nascimento' => $participante->participante->data_nascimento,
            'fone_celular' => $participante->participante->fone_celular,
            'fone_emergencia' => $participante->participante->fone_emergencia,
            'restricao_alimentar' => $participante->participante->restricao_alimentar ? true : false,
            'restricao_alimentar_qual' => $participante->participante->restricao_alimentar_qual,
            'limitacao' => $participante->participante->limitacao ? true : false,
            'limitacao_qual' => $participante->participante->limitacao_qual,
            'medicamento' => $participante->participante->medicamento ? true : false,
            'medicamento_qual' => $participante->participante->medicamento_qual,
            'medicamento_dosagem' => $participante->participante->medicamento_dosagem,
            'problema_saude' => $participante->participante->problema_saude ? true : false,
            'problema_saude_qual' => $participante->participante->problema_saude_qual,
            'etapa_cadastro' => $participante->participante->etapa_cadastro,
            'ativo' => $participante->ativo,
            'pontos' => $participante->participante->pontos->map(function ($ponto) {
                return [
                    'id' => $ponto->id,
                    'quantidade' => $ponto->quantidade,
                    'tipo' => $ponto->tipo,
                    'descricao' => $ponto->descricao
                ];
            })
        ];

        return response()->json([
            'participante' => $participanteData,
        ]);
    }

    public function updateParticipante(Request $request, $id) {
        $participante = Usuario::query()
            ->where([
                'id' => $id,
                'excluido' => NULL
            ])
            ->with([
                'participante' => function ($q) {
                    $q->where('excluido', NULL);
                }
            ])
            ->first();

        if (!$participante) {
            return response()->json([
                'error' => 'Participante não encontrado.'
            ], 404);
        }
        
        $this->validate($request, [
            'nome' => 'required|string|max:255',
            'nome_completo' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email,' . $participante->id,
            'password' => 'nullable|string|min:6',
            'ativo' => 'required|boolean',
            'cpf' => 'required|cpf|unique:cadastros_participantes,cpf,' . $participante->participante->id,
            'data_nascimento' => 'required|date_format:Y-m-d|before_or_equal:today',
            'rg' => 'required|string|max:20',
            'data_expedicao_rg' => 'required|date_format:Y-m-d|before_or_equal:today',
            'fone_celular' => 'required|celular_com_ddd',
            // 'fone_fixo' => 'nullable|telefone_com_ddd',
            // 'fone_comercial' => 'required|celular_com_ddd',
            'fone_emergencia' => 'required|celular_com_ddd',
            'destinos' => 'required|array|min:1',
            'destinos.*' => 'exists:destinos,id',
            'restricao_alimentar' => 'required|boolean',
            'restricao_alimentar_qual' => 'nullable|max:120',
            'limitacao' => 'required|boolean',
            'limitacao_qual' => 'nullable|max:120',
            'medicamento' => 'required|boolean',
            'medicamento_qual' => 'nullable|max:120',
            'problema_saude' => 'required|boolean',
            'problema_saude_qual' => 'nullable|max:120',
            'aprovado_bloqueado' => 'required|boolean',
            'conferido' => 'required|boolean',
            'confirmado' => 'required|boolean',
            'paginas_em_branco' => 'required|integer|digits_between:1,2',
            'numero' => 'required|max:30',
            'data_emissao' => 'required|date_format:Y-m-d|before_or_equal:today',
            'data_validade' => 'required|date_format:Y-m-d',
            'passaporte_arquivo' => 'nullable|mimes:jpg,png,pdf|max:10240',
            'passaporte_status' => 'required',
            // 'rg_arquivo' => 'required|mimes:jpg,png,pdf|max:10240',
            'certificado_arquivo' => 'nullable|mimes:jpg,png,pdf|max:10240',
            'certificado_status' => 'required',
        ], [
            'nome.required' => 'Por favor, informe seu nome.',
            'nome_completo.required' => 'Por favor, informe seu nome completo.',
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está registrado no programa.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
            // 'password.confirmed' => 'As senhas não conferem.',
            'ativo.required' => 'Por favor, informe se o usuário está ativo.',
            'ativo.boolean' => 'Valor inválido para ativação do usuário, atualize a página.',
            'cpf.required' => 'Por favor, informe seu CPF.',
            'cpf.cpf' => 'Por favor, informe um CPF válido.',
            'cpf.unique' => 'Este CPF já está registrado no programa.',
            'data_nascimento.required' => 'Por favor, informe sua data de nascimento.',
            'data_nascimento.date' => 'Por favor, informe uma data de nascimento válida.',
            'data_nascimento.before_or_equal' => 'A data de nascimento não pode ser uma data futura.',
            'rg.required' => 'Por favor, informe seu RG.',
            'data_expedicao_rg.required' => 'Por favor, informe a data de expedição do seu RG.',
            'data_expedicao_rg.date' => 'Por favor, informe uma data válida para a expedição do RG.',
            'data_expedicao_rg.before_or_equal' => 'A data de expedição do RG não pode ser uma data futura.',
            'fone_celular.required' => 'Por favor, informe seu telefone.',
            'fone_celular.celular_com_ddd' => 'Por favor, informe um telefone válido.',
            // 'fone_fixo.telefone_com_ddd' => 'Por favor, informe um telefone fixo válido.',
            // 'fone_comercial.required' => 'Por favor, informe seu telefone comercial.',
            // 'fone_comercial.celular_com_ddd' => 'Por favor, informe um telefone comercial válido.',
            'fone_emergencia.required' => 'Por favor, informe um contato para emergências.',
            'fone_emergencia.celular_com_ddd' => 'Por favor, informe um contato para emergências válido.',
            'destinos.required' => 'Por favor, selecione pelo menos um destino.',
            'destinos.array' => 'Ocorreu um erro ao informar os destinos, atualize a página.',
            'destinos.min' => 'Por favor, selecione pelo menos um destino.',
            'destinos.*.exists' => 'Um ou mais destinos informados são inválidos.',
            'restricao_alimentar.required' => 'Por favor, informe se há restrição alimentar.',
            'restricao_alimentar.boolean' => 'Valor inválido para restrição alimentar, atualize a página.',
            'restricao_alimentar_qual.max' => 'A restrição alimentar deve ter no máximo 120 caracteres.',
            'limitacao.required' => 'Por favor, informe se há limitação.',
            'limitacao.boolean' => 'Valor inválido para limitação, atualize a página.',
            'limitacao_qual.max' => 'A limitação deve ter no máximo 120 caracteres.',
            'medicamento.required' => 'Por favor, informe se há medicamento.',
            'medicamento.boolean' => 'Valor inválido para medicamento, atualize a página.',
            'medicamento_qual.max' => 'O medicamento deve ter no máximo 120 caracteres.',
            'problema_saude.required' => 'Por favor, informe se há problema de saúde.',
            'problema_saude.boolean' => 'Valor inválido para problema de saúde, atualize a página.',
            'problema_saude_qual.max' => 'O problema de saúde deve ter no máximo 120 caracteres.',
            'aprovado_bloqueado.required' => 'Por favor, informe se o participante está aprovado/bloqueado.',
            'aprovado_bloqueado.boolean' => 'Valor inválido para aprovado/bloqueado, atualize a página.',
            'conferido.required' => 'Por favor, informe se o participante foi conferido.',
            'conferido.boolean' => 'Valor inválido para conferido, atualize a página.',
            'confirmado.required' => 'Por favor, informe se o participante está confirmado.',
            'confirmado.boolean' => 'Valor inválido para confirmado, atualize a página.',
            'paginas_em_branco.required' => 'Por favor, informe o número de páginas em branco',
            'paginas_em_branco.integer' => 'O valor de páginas em branco deve estar em formato de número.',
            'paginas_em_branco.digits_between' => 'O número de páginas em branco deve ter no máximo 2 caracteres.',
            'numero.required' => 'Por favor, informe o número do passaporte.',
            'numero.max' => 'O número do passaporte deve ter no máximo 30 caracteres.',
            'data_emissao.required' => 'Por favor, informe a emissão do passaporte.',
            'data_emissao.date_format' => 'A data de emissão do passaporte deve estar no formato Y-m-d.',
            'data_emissao.before_or_equal' => 'A data de emissão do passaporte não pode ser uma data futura.',
            'data_validade.required' => 'Por favor, informe a validade do passaporte.',
            'data_validade.date_format' => 'A data de validade do passaporte deve estar no formato Y-m-d.',
            'passaporte_arquivo.mimes' => 'Os formatos aceitos para o passaporte são JPG, PNG e PDF.',
            'passaporte_status.required' => 'Por favor, informe o status do passaporte.',
            // 'rg_arquivo.mimes' => 'Os formatos aceitos para o RG são JPG, PNG e PDF.',
            'certificado_arquivo.mimes' => 'Os formatos aceitos para o certificado são JPG, PNG e PDF.',
            'certificado_status.required' => 'Por favor, informe o status do certificado.',
        ]);

        $dadosUsuario = $request->only(['nome', 'email', 'password', 'ativo']);
        $dadosParticipante = $request->only(['nome_completo', 'cpf', 'data_nascimento', 'rg', 'data_expedicao_rg', 'fone_celular', 'fone_emergencia', 'restricao_alimentar', 'restricao_alimentar_qual', 'limitacao', 'limitacao_qual', 'medicamento', 'medicamento_qual', 'medicamento_dosagem', 'problema_saude', 'problema_saude_qual', 'aprovado_bloqueado', 'conferido', 'confirmado']);
        $dadosDestinos = $request->only(['destinos']);
        $dadosPassaporte = $request->only(['numero', 'data_emissao', 'data_validade', 'paginas_em_branco']);
        $imgDocumentos = ['passaporte_arquivo' => $request->file('passaporte_arquivo'), 'certificado_arquivo' => $request->file('certificado_arquivo'), 'passaporte_status' => $request['passaporte_status'], 'certificado_status' => $request['certificado_status']];

        try {
            $response = $this->memberService->atualizarParticipante($dadosUsuario, $dadosParticipante, $dadosDestinos, $dadosPassaporte, $imgDocumentos, $id);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastro atualizado com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar o cadastro.',
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

    public function deleteParticipantes($ids)
    {
        $explodeIds = explode(',', $ids);

        try {
            $response = $this->memberService->excluirParticipantes($explodeIds);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastros excluidos com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir cadastros.',
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

    public function approveParticipantes($ids)
    {
        $explodeIds = explode(',', $ids);

        try {
            $response = $this->memberService->aprovarParticipantes($explodeIds);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastros aprovados com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao aprovar cadastros.',
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

    public function checkParticipantes($ids)
    {
        $explodeIds = explode(',', $ids);

        try {
            $response = $this->memberService->conferirParticipantes($explodeIds);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastros conferidos com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao conferir cadastros.',
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

    public function confirmParticipantes($ids)
    {
        $explodeIds = explode(',', $ids);

        try {
            $response = $this->memberService->confirmarParticipantes($explodeIds);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastros confirmados com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao confirmar cadastros.',
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