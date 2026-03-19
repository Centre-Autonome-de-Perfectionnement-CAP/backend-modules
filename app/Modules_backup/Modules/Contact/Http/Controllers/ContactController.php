<?php

namespace App\Modules\Contact\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contact\Http\Requests\StoreContactRequest;
use App\Modules\Contact\Models\Contact;
use App\Modules\Contact\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    protected $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * Store a newly created contact message.
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        try {
            $contact = $this->contactService->createContact($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.',
                'data' => [
                    'id' => $contact->id,
                    'created_at' => $contact->created_at->format('Y-m-d H:i:s'),
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display a listing of contacts (Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');

        $query = Contact::query()->latest();

        if ($status && in_array($status, ['new', 'read', 'replied'])) {
            $query->where('status', $status);
        }

        $contacts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }

    /**
     * Display the specified contact (Admin only).
     */
    public function show(Contact $contact): JsonResponse
    {
        // Marquer comme lu si c'est la première fois
        if ($contact->status === 'new') {
            $contact->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => $contact
        ]);
    }

    /**
     * Update the specified contact (Admin only).
     */
    public function update(Request $request, Contact $contact): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:new,read,replied',
            'admin_notes' => 'sometimes|nullable|string|max:5000',
        ]);

        $contact->update($validated);

        if (isset($validated['status']) && $validated['status'] === 'replied') {
            $contact->markAsReplied();
        }

        return response()->json([
            'success' => true,
            'message' => 'Le contact a été mis à jour avec succès',
            'data' => $contact->fresh()
        ]);
    }

    /**
     * Remove the specified contact (Admin only).
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Le message a été supprimé avec succès'
        ]);
    }

    /**
     * Get contact statistics (Admin only).
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Contact::count(),
            'new' => Contact::where('status', 'new')->count(),
            'read' => Contact::where('status', 'read')->count(),
            'replied' => Contact::where('status', 'replied')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
