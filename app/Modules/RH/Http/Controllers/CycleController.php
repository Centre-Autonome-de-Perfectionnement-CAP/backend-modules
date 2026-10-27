<?php
// app/Modules/RH/Http/Controllers/CycleController.php
namespace App\Modules\RH\Http\Controllers;

use App\Modules\RH\Models\Cycle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CycleController extends Controller
{
    public function index()
    {
        try {
            // Récupérer tous les cycles (avec pagination si nécessaire)
            $cycles = Cycle::all(); // Ou Cycle::paginate(10);

            return response()->json([
                'success' => true,
                'data' => $cycles
            ]);
        } catch (\Exception $e) {
            // Logger l'erreur pour le débogage
            \Log::error('Erreur lors de la récupération des cycles : ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.'
            ], 500);
        }
    }
}