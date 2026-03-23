<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Participante;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DocsService
{
    public function enviarDocumentos($participanteId, $participanteEmail, $imgDocumentos)
    {
        DB::beginTransaction();

        try {
            $passaporte = md5(uniqid(rand(), true)) . '.' . $imgDocumentos['passaporte']->getClientOriginalExtension();
            // $rg = md5(uniqid(rand(), true)) . '.' . $imgDocumentos['rg']->getClientOriginalExtension();
            
            if ($imgDocumentos['certificado']) {
                $certificado = md5(uniqid(rand(), true)) . '.' . $imgDocumentos['certificado']->getClientOriginalExtension();
            }

            $documento = Documento::create([
                'passaporte' => $passaporte,
                'passaporte_status' => 'aguardando_analise',
                // 'rg' => $rg,
                'certificado' => isset($certificado) ? $certificado : null,
                'certificado_status' => isset($certificado) ? 'aguardando_analise' : 'aguardando_postagem',
                'participante_id' => $participanteId,
            ]);
            
            $participante = Participante::findOrFail($participanteId);
            $participante->etapa_cadastro = 'concluido';
            $participante->save();

            $data['email'] = $participanteEmail;

            Mail::send('emails.confirm', $data, function($message)use($data) {
                $message->from('naoresponda@todeschini.viaggiotur.com.br', 'Todeschini')
                        ->to($data['email'])
                        ->bcc('rafael@8poroito.com.br')
                        ->subject('Seu cadastro foi finalizado!');
            });

            DB::commit();

            $imgDocumentos['passaporte']->move(base_path('../todeschini-media/uploads/documents/passport/'), $passaporte);
            // $imgDocumentos['rg']->move(base_path('../todeschini/uploads/documents/rg/'), $rg);

            if ($imgDocumentos['certificado']) {
                $imgDocumentos['certificado']->move(base_path('../todeschini-media/uploads/documents/certificate/'), $certificado);
            }
            
            return [
                'documento' => $documento
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao cadastrar os documentos: ' . $e->getMessage());
        }
    }
}
