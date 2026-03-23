<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use App\Models\Usuario;
use App\Models\Log;

use Carbon\Carbon;

class UsuariosController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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

    public function getUsuario() {
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

        $formattedUsuario = [
            'nome_completo' => $usuario->participante->nome_completo ?? '',
            'cpf' => $usuario->participante->cpf ? sprintf('%s%s%s.%s%s%s.%s%s%s-%s%s', ...str_split($usuario->participante->cpf)) : '',
            'data_nascimento' => $usuario->participante->data_nascimento ?? '',
            'email' => $usuario->email ?? '',
            'rg' => $usuario->participante->rg ?? '',
            'data_expedicao_rg' => $usuario->participante->data_expedicao_rg ?? '',
            'numero' => $usuario->participante->passaporte->numero ?? '',
            'paginas_em_branco' => $usuario->participante->passaporte->paginas_em_branco ?? '',
            'data_emissao' => $usuario->participante->passaporte->data_emissao ?? '',
            'data_validade' => $usuario->participante->passaporte->data_validade ?? '',
            // 'fone_fixo' => $usuario->participante->fone_fixo ?? '',
            // 'fone_comercial' => $usuario->participante->fone_comercial ?? '',
            'fone_celular' => $usuario->participante->fone_celular ?? '',
            'fone_emergencia' => $usuario->participante->fone_emergencia ?? '',
            'aprovado_bloqueado' => $usuario->participante->aprovado_bloqueado ? true : false,
            'primeiro_acesso' => $usuario->participante->primeiro_acesso ? true : false,
            'restricao_alimentar' => $usuario->participante->restricao_alimentar ? true : false,
            'restricao_alimentar_qual' => $usuario->participante->restricao_alimentar ? $usuario->participante->restricao_alimentar_qual : NULL,
            'limitacao' => $usuario->participante->limitacao ? true : false,
            'limitacao_qual' => $usuario->participante->limitacao ? $usuario->participante->limitacao_qual : NULL,
            'medicamento' => $usuario->participante->medicamento ? true : false,
            'medicamento_qual' => $usuario->participante->medicamento ? $usuario->participante->medicamento_qual : NULL,
            'medicamento_dosagem' => $usuario->participante->medicamento ? $usuario->participante->medicamento_dosagem : NULL,
            'problema_saude' => $usuario->participante->problema_saude ? true : false,
            'problema_saude_qual' => $usuario->participante->problema_saude ? $usuario->participante->problema_saude_qual : NULL,
            'destinos' => $usuario->participante->destinos->map(function ($destino) {
                return $destino->id;
            })->toArray(),
        ];

        return response()->json($formattedUsuario);
    }

    public function setPrimeiroAcesso(Request $request) {
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

    public function logout(Request $request) {
        auth()->logout();
        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function updateUsuario(Request $request) {
        $participanteId = auth()->user()->participante->id;
        $usuarioId = auth()->user()->id;

        $this->validate($request, [
            'nome_completo' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email,' . $usuarioId,
            'cpf' => 'required|cpf|unique:cadastros_participantes,cpf,' . $participanteId,
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
            'paginas_em_branco' => 'required|integer|digits_between:1,2',
            'numero' => 'required|max:30',
            'data_emissao' => 'required|date_format:Y-m-d|before_or_equal:today',
            'data_validade' => 'required|date_format:Y-m-d',
        ], [
            'nome_completo.required' => 'Por favor, informe seu nome completo.',
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está registrado no programa.',
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
            'paginas_em_branco.required' => 'Por favor, informe a quantidade de páginas em branco.',
            'paginas_em_branco.integer' => 'O valor de páginas em branco deve estar em formato de número.',
            'paginas_em_branco.digits_between' => 'O número de páginas em branco deve ter no máximo 2 caracteres.',
            'numero.required' => 'Por favor, informe o número do passaporte.',
            'numero.max' => 'O número do passaporte deve ter no máximo 30 caracteres.',
            'data_emissao.required' => 'Por favor, informe a emissão do passaporte.',
            'data_emissao.date_format' => 'A data de emissão do passaporte deve estar no formato d/m/Y.',
            'data_emissao.before_or_equal' => 'A data de emissão do passaporte não pode ser uma data futura.',
            'data_validade.required' => 'Por favor, informe a validade do passaporte.',
            'data_validade.date_format' => 'A data de validade do passaporte deve estar no formato d/m/Y.',
        ]);

        $dadosUsuario = $request->only(['email']);
        $dadosParticipante = $request->only(['nome_completo', 'cpf', 'data_nascimento', 'rg', 'data_expedicao_rg', 'fone_celular', 'fone_emergencia', 'destinos', 'restricao_alimentar', 'restricao_alimentar_qual', 'limitacao', 'limitacao_qual', 'medicamento', 'medicamento_qual', 'medicamento_dosagem', 'problema_saude', 'problema_saude_qual']);
        $dadosPassaporte = $request->only(['numero', 'paginas_em_branco', 'data_emissao', 'data_validade']);

        try {
            $response = $this->userService->atualizarCadastro($dadosUsuario, $dadosParticipante, $dadosPassaporte);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastro atualizado com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar cadastro.',
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