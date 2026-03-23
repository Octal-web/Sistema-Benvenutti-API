<?php

namespace App\Http\Controllers;

use App\Models\Programa;

class ProgramaController extends Controller
{
    public function getData() {
        $programa = Programa::where('excluido', NULL)->first();
        
        return response()->json([
            'programa' => $programa
        ]);
    }
}