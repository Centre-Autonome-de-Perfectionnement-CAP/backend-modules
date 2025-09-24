<?php

namespace Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Services\SharedAssetService;

class InscriptionController extends Controller
{
    protected $assetService;

    public function __construct(SharedAssetService $assetService)
    {
        $this->assetService = $assetService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('inscription::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('inscription::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('inscription::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('inscription::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}


    public function register(Request $request)
    {
        // Traiter l'inscription...

        // Exemple: Stocker un fichier
        if ($request->hasFile('document')) {
            $result = $this->assetService->storeFile(
                $request->file('document'),
                'inscription',
                'documents'
            );
        }

        // Exemple: Envoyer un email avec template partagé
        // return view('core::emails.welcome', ['user' => $user]);

        return response()->json([
            'message' => 'Inscription réussie',
            'file' => $result ?? null
        ]);
    }
}
