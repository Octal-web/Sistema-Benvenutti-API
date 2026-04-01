<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Services\SenhaService;

use App\Models\Token;

class SenhaController extends Controller
{
    protected $senhaService;

    public function __construct(SenhaService $senhaService)
    {
        $this->senhaService = $senhaService;
    }

    public function resetSenha(Request $request)
    {
        $this->validate(
            $request,
            [
                'email' => 'required|email',
            ],
            [
                'email.required' => 'Por favor, informe o seu e-mail.',
                'email.email' => 'Por favor, informe um e-mail válido.',
            ]
        );

        $dadosUsuario = $request->only(['email']);

        try {
            $response = $this->senhaService->solicitarSenha($dadosUsuario);

            return response()->json([
                'success' => true,
                'message' => 'Solicitação feita com sucesso. Confira o seu e-mail para alterar a sua senha.',
                'data' => $response
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao solicitar a alteração da senha',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getToken($token)
    {
        $token = Token::query()
            ->where([
                'utilizado' => NULL,
                ['expira', '>', Carbon::now()->format('Y-m-d H:i:s')],
                'token' => $token,
            ])
            ->first();

        return response()->json([
            'token' => $token
        ]);
    }

    public function updateSenha(Request $request, $token)
    {
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
            $response = $this->senhaService->atualizarSenha($dadosSenha, $token);

            return response()->json([
                'success' => true,
                'message' => 'Senha alterada com sucesso. Faça o seu login.',
                'data' => $response
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar a senha',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
