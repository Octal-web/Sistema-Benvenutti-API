<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Destino;

class RelatoriosController extends Controller
{
    public function getParticipantes($id) {
        $destino = Destino::query()
            ->where([
                'excluido' => NULL,
                'id' => $id
            ])
            ->when(request('inicio') && request('fim'), function ($query) {
                return $query->whereHas('participantes', function ($q) {
                    $q->whereBetween('criado', [
                        request('inicio'), request('fim')
                    ]);
                });
            })
            ->with([
                'participantes.passaporte' => function ($query) {
                    $query->where('excluido', NULL);
                },
                'participantes' => function ($query) {
                    $query->where('excluido', NULL)
                          ->orderBy('nome_completo', 'ASC');
                }
            ])
            ->orderBy('destino', 'ASC')
            ->first();

        if (!$destino) {
            return response()->json(['error' => 'Destino não encontrado'], 404);
        }

        return response()->json([
            'nome_destino' => $destino->destino,
            'participantes' => $destino->participantes->map(function ($participante) {
                return [
                    'nome_completo' => $participante->nome_completo,
                    'data_nascimento' => Carbon::parse($participante->data_nascimento)->format('d/m/Y'),
                    'fone_celular' => $participante->fone_celular,
                    'numero_passaporte' => optional($participante->passaporte)->numero,
                    'data_validade_passaporte' => $participante->passaporte ? Carbon::parse($participante->passaporte->data_validade)->format('d/m/Y') : null,
                    'restricao_alimentar' => $participante->restricao_alimentar_qual,
                    'limitacao' => $participante->limitacao_qual,
                    'medicamento' => $participante->medicamento_qual,
                    'problema_saude' => $participante->problema_saude_qual,
                    'situacao' => $participante->aprovado_bloqueado ? true : false,
                ];
            }),
        ]);
    }

    public function getParticipantesPorDestino($id) {
        $destino = Destino::query()
            ->where([
                'excluido' => NULL,
                'id' => $id
            ])
            ->when(request('inicio') && request('fim'), function ($query) {
                return $query->whereHas('participantes', function ($q) {
                    $q->whereBetween('criado', [
                        request('inicio'), request('fim')
                    ]);
                });
            })
            ->with([
                'participantes.passaporte' => function ($query) {
                    $query->where('excluido', NULL);
                },
                'participantes' => function ($query) {
                    $query->where('excluido', NULL)
                          ->orderBy('nome_completo', 'ASC');
                }
            ])
            ->first();

        if (!$destino) {
            return response()->json(['error' => 'Destino não encontrado.'], 404);
        }

        return response()->json([
            'nome_destino' => $destino->destino,
            'participantes' => $destino->participantes->map(function ($participante) {
                return [
                    'nome_completo' => $participante->nome_completo,
                    'data_nascimento' => Carbon::parse($participante->data_nascimento)->format('d/m/Y'),
                    'numero_passaporte' => optional($participante->passaporte)->numero,
                    'situacao' => $participante->aprovado_bloqueado ? true : false,
                ];
            }),
        ]);
    }

    public function getParticipantesUploads() {
        $statusMap = [
            'aguardando_analise' => 'Aguardando análise',
            'aguardando_postagem' => 'Aguardando postagem',
            'reprovado' => 'Reprovado',
            'aprovado' => 'Aprovado',
        ];

        $participantes = Participante::query()
            ->where([
                'excluido' => NULL,
                'funcao' => 'participante',
            ])
            ->when(request('inicio') && request('fim'), function ($query) {
                return $query->whereBetween('criado', [
                    request('inicio'), request('fim')
                ]);
            })
            ->when(request('status') === 'completos', function ($query) {
                return $query->whereHas('documento', function ($q) {
                    $q->where([
                        ['passaporte', 'NOT', NULL],
                        'passaporte_status' => 'aguardando_analise',
                    ]);
                });
            })
            ->when(request('status') === 'pendencia_upload', function ($query) {
                return $query->whereHas('documento', function ($q) {
                    $q->where(function ($r) {
                        $r->whereNull('passaporte')
                            ->orWhere('passaporte_status', 'aguardando_postagem')
                            ->orWhereNull('certificado')
                            ->orWhere('certificado_status', 'aguardando_postagem');
                    });
                });
            })
            ->when(request('status') === 'nenhum_aprovado', function ($query) {
                return $s->where(function ($s) {
                    $query->whereHas('documento', function ($q) {
                        $q->where(function ($r) {
                            $r->where('passaporte_status', 'aguardando_analise')
                                     ->orWhere('certificado_status', 'aguardando_analise');
                        });
                    })
                    ->orWhereDoesntHave('documento');
                });
            })
            ->with([
                'documento' => function ($q) {
                    $q->where('excluido', NULL);
                },
                'destinos' => function ($q) {
                    $q->where('excluido', NULL);
                }
            ])
            ->orderBy('nome_completo', 'ASC')
            ->get()
            ->map(function ($participante) {
                $precisaCertificadoFebre = $participante->destinos->contains(function ($destino) {
                    return $destino->certificado_febre === true;
                });

                return [
                    'nome_completo' => $participante->nome_completo,
                    'data_nascimento' => Carbon::parse($participante->data_nascimento)->format('d/m/Y'),
                    'passaporte' => optional($participante->documento)->passaporte ?? 'Não enviado',
                    'passaporte_status' => isset($statusMap[optional($participante->documento)->passaporte_status])
                        ? $statusMap[optional($participante->documento)->passaporte_status] 
                        : 'Status inválido',
                    'certificado_febre' => $precisaCertificadoFebre
                        ? (optional($participante->documento)->passaporte ?? 'Não enviado')
                        : '--',
                    'certificado_status' => $precisaCertificadoFebre
                        ? (isset($statusMap[optional($participante->documento)->certificado_status])
                            ? $statusMap[optional($participante->documento)->certificado_status]
                            : 'Status inválido')
                        : '--',
                ];
            });

        return response()->json([
            'participantes' => $participantes,
        ]);
    }

    public function getParticipantesSeguroDeViagem($id) {
        $destino = Destino::query()
            ->where([
                'excluido' => NULL,
                'id' => $id
            ])
            ->when(request('inicio') && request('fim'), function ($query) {
                return $query->whereHas('participantes', function ($q) {
                    $q->whereBetween('criado', [
                        request('inicio'), request('fim')
                    ]);
                });
            })
            ->with([
                'participantes.passaporte' => function ($query) {
                    $query->where('excluido', NULL);
                },
                'participantes' => function ($query) {
                    $query->where('excluido', NULL);
                }
            ])
            ->orderBy('destino', 'ASC')
            ->first();

        if (!$destino) {
            return response()->json(['error' => 'Destino não encontrado'], 404);
        }

        return response()->json([
            'nome_destino' => $destino->destino,
            'participantes' => $destino->participantes->map(function ($participante) {
                return [
                    'nome_completo' => $participante->nome_completo,
                    'cpf' => preg_replace("/^(\d{3})(\d{3})(\d{3})(\d{2})$/", "$1.$2.$3-$4", $participante->cpf),
                    'data_nascimento' => Carbon::parse($participante->data_nascimento)->format('d/m/Y'),
                ];
            }),
        ]);
    }
}