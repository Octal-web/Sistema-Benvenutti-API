<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PdfGeradorService
{
    public function gerar(string $view, string $conteudo, string $titulo)
    {
        $dataGeracao = Carbon::now()->format('d/m/Y \à\s H:i');

        $pdf = Pdf::loadView($view, [
            'regulamento' => $conteudo,
            'titulo'      => $titulo,
            'dataGeracao' => $dataGeracao,
        ]);
                
        $pdf->render();
        $totalPaginas = $pdf->getDomPDF()->getCanvas()->get_page_count();

        $pdf = Pdf::loadView($view, [
            'regulamento'  => $conteudo,
            'titulo'       => $titulo,
            'dataGeracao'  => $dataGeracao,
            'totalPaginas' => $totalPaginas,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $pdf->setOptions([
            'isHtml5ParserEnabled'    => true,
            'isRemoteEnabled'         => true,
            'defaultFont'             => 'Calibri, DejaVu Sans, sans-serif',
            'dpi'                     => 150,
            'isFontSubsettingEnabled' => true,
        ]);

        return $pdf;
    }
}