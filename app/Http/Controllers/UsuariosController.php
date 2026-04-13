<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Usuario;
use App\Services\UsuarioService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UsuariosController extends Controller
{
    protected $usuarioService;

    public function __construct(UsuarioService $usuarioService)
    {
        $this->usuarioService = $usuarioService;
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Por favor, insira seu e-mail.',
            'email.email' => 'Por favor, insira um e-mail válido.',
            'password.required' => 'Por favor, insira sua senha.',
        ]);

        $usuario = Usuario::query()
            ->where([
                'email' => $request->email,
                'excluido' => NULL
            ])
            ->first();

        if (!$usuario || !$usuario->isParticipante()) {
            return response()->json(['error' => 'unauthorized_user'], 403);
        }

        $credentials = $request->only('email', 'password');
        $lembrar = $request->input('lembrar', false);

        $expiresAt = $lembrar ? Carbon::now()->addDays(7) : Carbon::now()->addDay();

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $log = new Log;
        $log->usuario_id = $usuario->id;
        $log->save();

        $token = JWTAuth::claims(['exp' => $expiresAt])->attempt($credentials);

        return response()->json([
            'token' => $token,
            'usuario' => $usuario,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    public function getUsuario()
    {
        $participanteAutenticado = auth()->user();

        $ranking = Usuario::query()
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
                }
            ])
            ->get()
            ->map(function ($usuario) {
                $pontos = $usuario->participante->pontos ?? collect();

                $total = $pontos->sum(function ($ponto) {
                    return $ponto->tipo === 'adicao'
                        ? $ponto->quantidade
                        : -$ponto->quantidade;
                });

                return [
                    'id' => $usuario->id,
                    'pontos' => $total,
                ];
            })
            ->sortByDesc('pontos')
            ->values();

        $posicao = null;
        $totalPontos = 0;

        foreach ($ranking as $index => $item) {
            if ($item['id'] == $participanteAutenticado->id) {
                $posicao = $index + 1;
                break;
            }
        }

        $pontos = $participanteAutenticado->participante->pontos ?? collect();
        $totalPontos = $pontos->sum(function ($ponto) {
            return $ponto->tipo === 'adicao'
                ? $ponto->quantidade
                : -$ponto->quantidade;
        });

        $formattedUsuario = [
            'id' => $participanteAutenticado->id,
            'nome' => $participanteAutenticado->nome ?? '',
            'total_pontos' => $totalPontos ?? '',
            'posicao' => $posicao ?? '',

        ];

        return response()->json($formattedUsuario);
    }

    public function setPrimeiroAcesso(Request $request)
    {
        $usuario = auth()->user()->load([
            'participante.destinos' => function ($query) {
                $query->where('ano_vigente', 2024);
            },
            'participante.passaporte' => function ($query) {
                $query->where('excluido', NULL);
            },
            'participante.destinos' => function ($query) {
                $query->where('excluido', NULL);
            },
        ]);

        if (!$usuario->participante->primeiro_acesso) {
            $usuario->ativo = true;
            $usuario->participante->primeiro_acesso = true;
            $usuario->save();
            $usuario->participante->save();
        }
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function updateUsuario(Request $request)
    {
        $usuario = auth()->user();

        $participanteId = $usuario->id;

        $this->validate(
            $request,
            [
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
            ],
            [
                'password.required' => 'A senha é obrigatória.',
                'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
                'password.confirmed' => 'As senhas não correspondem.',
                'password_confirmation.required' => 'A confirmação da senha é obrigatória.',
                'password_confirmation.min' => 'A confirmação da senha deve ter pelo menos 8 caracteres.',
            ]
        );

        $dadosSenha = $request->only(['password']);

        try {
            $response = $this->usuarioService->atualizarCadastro($participanteId, $dadosSenha);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Senha atualizada com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar senha.',
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
