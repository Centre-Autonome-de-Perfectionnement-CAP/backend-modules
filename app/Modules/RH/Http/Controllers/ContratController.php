<?php


namespace App\Modules\RH\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\RH\Models\Contrat;
use App\Modules\RH\Http\Requests\CreateContratRequest;
use Illuminate\Support\Str;

class ContratController extends Controller

{
    // 🔹 LISTE
    public function index()
     {
         $contrats = Contrat::with(['professor', 'academicYear'])->get();
         return response()->json([
             'success' => true,
             'data' => $contrats,
         ]);
     }

    // 🔹 CREATE
    public function store(CreateContratRequest $request)
    {
        try {
          $contrat = Contrat::create([
    ...$request->validated(),
    'status' => 'pending',
]);
$contrat->contrat_number = '00' . $contrat->id;
$contrat->save();
            return response()->json([
                'success' => true,
                'message' => 'Contrat créé avec succès',
                'data' => $contrat
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du contrat',
                'error' => $e->getMessage()
            ], 500);
        }
    }
public function update(Request $request, $id)
{
    $contrat = Contrat::findOrFail($id);

    $validated = $request->validate([
        'division'         => 'nullable|string',
        'professor_id'     => 'required|integer|exists:professors,id',
        'academic_year_id' => 'required|integer',
        'start_date'       => 'required|date',
        'end_date'         => 'nullable|date',
        'amount'           => 'required|numeric|min:100',
        'status'           => 'required|string',
    ]);

    $contrat->update($validated);

    return response()->json([
        'success' => true,
        'data'    => $contrat,
    ]);
}
    // 🔹 SHOW
    public function show(Contrat $contrat)
    {
        return response()->json([
            'success' => true,
            'data' => $contrat->load('professor')
        ]);
    }

    // 🔹 DELETE
    public function destroy(Contrat $contrat)
    {
        $contrat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contrat supprimé'
        ]);
    }
}
