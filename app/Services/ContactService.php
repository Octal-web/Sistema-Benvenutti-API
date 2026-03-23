<?php

namespace App\Services;

use App\Models\Contato;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    public function enviarContato($dadosContato)
    {
        DB::beginTransaction();

        try {
            $usuario = auth()->user();

            $contato = Contato::create([
                'nome' => $dadosContato['nome'],
                'email' => $dadosContato['email'],
                'cidade' => $dadosContato['cidade'],
                'telefone' => $dadosContato['telefone'],
                'mensagem' => $dadosContato['mensagem'],
                'usuario_id' => $usuario->id,
            ]);

            $data['nome'] = $dadosContato['nome'];
            $data['email'] = $dadosContato['email'];
            $data['cidade'] = $dadosContato['cidade'];
            $data['telefone'] = $dadosContato['telefone'];
            $data['mensagem'] = $dadosContato['mensagem'];

            Mail::send('emails.contact', $data, function($message)use($data) {
                $message->from('naoresponda@todeschini.viaggiotur.com.br', 'Todeschini')
                        ->to('richard@senzaconfini.com.br')
                        ->bcc('rafael@8poroito.com.br')
                        ->subject('Uma nova solicitação de contato foi feita');
            });

            DB::commit();

            return [
                'contato' => $contato
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao enviar o contato: ' . $e->getMessage());
        }
    }
}
