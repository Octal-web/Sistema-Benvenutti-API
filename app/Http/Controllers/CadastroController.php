<?php

namespace App\Http\Controllers;

use App\Services\StepsService;
use Illuminate\Http\Request;

class CadastroController extends Controller
{
    protected $stepsService;

    public function __construct(StepsService $stepsService)
    {
        $this->stepsService = $stepsService;
    }

    public function etapa1(Request $request) {
        $this->validate($request, [
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
            'cpf' => 'required|cpf|unique:cadastros_participantes,cpf',
        ], [
            'nome.required' => 'Por favor, informe seu nome.',
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está registrado no programa.',
            'password.required' => 'Por favor, informe sua senha.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
            'password_confirmation.required' => 'Por favor, confirme sua senha.',
            'cpf.required' => 'Por favor, informe seu CPF.',
            'cpf.cpf' => 'Por favor, informe um CPF válido.',
            'cpf.unique' => 'Este CPF já está registrado no programa.',
        ]);

        $dadosUsuario = $request->only(['nome', 'email', 'password']);
        $dadosParticipante = $request->only(['cpf']);

        try {
            $response = $this->stepsService->primeiraEtapa($dadosUsuario, $dadosParticipante);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastro realizado com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar cadastro.',
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

    public function etapa2(Request $request) {
        $dadosUsuario = auth()->user();
        $participanteId = auth()->user()->participante->id;

        $this->validate($request, [
            'nome_completo' => 'required|string|max:255',
            'cpf' => 'required|cpf|unique:cadastros_participantes,cpf,' . $participanteId,
            'data_nascimento' => 'required|date_format:d/m/Y',
            'rg' => 'required|string|max:20',
            'data_expedicao_rg' => 'required|date_format:d/m/Y',
            'fone_celular' => 'required|celular_com_ddd',
            'fone_fixo' => 'nullable|telefone_com_ddd',
            'fone_comercial' => 'required|celular_com_ddd',
            'fone_emergencia' => 'required|celular_com_ddd',
            'destinos' => 'required|array|min:1',
            'destinos.*' => 'exists:destinos,id',
        ], [
            'nome_completo.required' => 'Por favor, informe seu nome completo.',
            'cpf.required' => 'Por favor, informe seu CPF.',
            'cpf.cpf' => 'Por favor, informe um CPF válido.',
            'cpf.unique' => 'Este CPF já está registrado no programa.',
            'data_nascimento.required' => 'Por favor, informe sua data de nascimento.',
            'data_nascimento.date' => 'Por favor, informe uma data de nascimento válida.',
            'rg.required' => 'Por favor, informe seu RG.',
            'data_expedicao_rg.required' => 'Por favor, informe a data de expedição do seu RG.',
            'data_expedicao_rg.date' => 'Por favor, informe uma data válida para a expedição do RG.',
            'fone_celular.required' => 'Por favor, informe seu telefone.',
            'fone_celular.celular_com_ddd' => 'Por favor, informe um telefone válido.',
            'fone_fixo.telefone_com_ddd' => 'Por favor, informe um telefone fixo válido.',
            'fone_comercial.required' => 'Por favor, informe seu telefone comercial.',
            'fone_comercial.celular_com_ddd' => 'Por favor, informe um telefone comercial válido.',
            'fone_emergencia.required' => 'Por favor, informe um contato para emergências.',
            'fone_emergencia.celular_com_ddd' => 'Por favor, informe um contato para emergências válido.',
            'destinos.required' => 'Por favor, selecione pelo menos um destino.',
            'destinos.array' => 'Ocorreu um erro ao informar os destinos, atualize a página.',
            'destinos.min' => 'Por favor, selecione pelo menos um destino.',
            'destinos.*.exists' => 'Um ou mais destinos informados são inválidos.',
        ]);

        $dadosParticipante = $request->only(['nome_completo', 'cpf', 'data_nascimento', 'rg', 'data_expedicao_rg', 'fone_celular', 'fone_fixo', 'fone_comercial', 'fone_emergencia', 'destinos']);

        try {
            $response = $this->stepsService->segundaEtapa($dadosUsuario, $dadosParticipante);

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

    public function etapa3(Request $request) {
        $this->validate($request, [
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
            'data_emissao' => 'required|date_format:d/m/Y',
            'data_validade' => 'required|date_format:d/m/Y',
        ], [
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
            'paginas_em_branco.integer' => 'O valor de páginas em branco deve estar em formato de número.',
            'paginas_em_branco.digits_between' => 'O número de páginas em branco deve ter no máximo 2 caracteres.',
            'numero.required' => 'Por favor, informe o número do passaporte.',
            'numero.max' => 'O número do passaporte deve ter no máximo 30 caracteres.',
            'data_emissao.required' => 'Por favor, informe a emissão do passaporte.',
            'data_emissao.date_format' => 'A data de emissão do passaporte deve estar no formato d/m/Y.',
            'data_validade.required' => 'Por favor, informe a validade do passaporte.',
            'data_validade.date_format' => 'A data de validade do passaporte deve estar no formato d/m/Y.',
        ]);

        $dadosUsuario = auth()->user();
        $dadosParticipante = $request->only(['restricao_alimentar', 'restricao_alimentar_qual', 'limitacao', 'limitacao_qual', 'medicamento', 'medicamento_qual', 'problema_saude', 'problema_saude_qual']);
        $dadosPassaporte = $request->only(['numero', 'paginas_em_branco', 'data_emissao', 'data_validade']);

        try {
            $response = $this->stepsService->dadosAdicionais($dadosUsuario->id, $dadosParticipante, $dadosPassaporte);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Dados atualizados com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar os dados.',
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