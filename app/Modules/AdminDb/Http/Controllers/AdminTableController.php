<?php

namespace App\Modules\AdminDb\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * Outil d'administration brute des tables — RÉSERVÉ AUX RÔLES LISTÉS DANS
 * self::ALLOWED_ROLES.
 *
 * Objectif : donner un accès CRUD minimal sur une liste BLANCHE de tables,
 * sans accès direct à la base de données (phpMyAdmin, console SQL, etc.).
 * Générique : ne connaît aucune colonne à l'avance, elles sont lues
 * dynamiquement via Schema::getColumnListing().
 *
 * SÉCURITÉ :
 *   - self::ALLOWED_TABLES est la SEULE source de vérité sur les tables
 *     accessibles. Toute table absente de cette liste renvoie 404, quel
 *     que soit son nom réel en base (pas d'accès arbitraire à la BD).
 *   - self::HIDDEN_COLUMNS masque les colonnes sensibles (mot de passe...)
 *     à la lecture ET interdit leur écriture via filterPayload().
 *   - assertAdmin() bloque tout utilisateur dont le rôle n'est pas dans
 *     self::ALLOWED_ROLES.
 *
 * MISE À JOUR : 'responsable-division' ajouté à ALLOWED_ROLES — aucun
 * compte 'admin' n'était réellement utilisé en pratique, ce rôle porte
 * désormais aussi cette responsabilité. Cette constante DOIT rester
 * synchronisée avec ALLOWED_ROLES dans AdminDbGuard.tsx (frontend).
 *
 * SUPPRESSION FACILE : pour retirer cet outil —
 *   1. Supprimer le dossier App/Modules/AdminDb au complet.
 *   2. Retirer AdminDbServiceProvider de bootstrap/providers.php.
 *   Aucune autre partie du code n'y fait référence.
 */
class AdminTableController extends Controller
{
    /**
     * Whitelist des tables administrables. Ajouter une entrée ici pour
     * étendre l'outil à une autre table plus tard.
     */
    private const ALLOWED_TABLES = ['document_requests', 'users'];

    /**
     * Rôles autorisés à utiliser cet outil. À garder synchronisé avec
     * ALLOWED_ROLES dans src/views/pages/admin-db/AdminDbGuard.tsx.
     */
    private const ALLOWED_ROLES = ['admin', 'responsable-division'];

    /**
     * Colonnes jamais renvoyées ni modifiables, par table.
     */
    private const HIDDEN_COLUMNS = [
        'users' => ['password', 'remember_token'],
    ];

    private const PER_PAGE = 50;

    // ── Gardes ───────────────────────────────────────────────────────────────

    private function assertAdmin(Request $request): void
    {
        $user = $request->user();
        $slug = $user?->roles->first()?->slug;

        if (!$user || !in_array($slug, self::ALLOWED_ROLES, true)) {
            abort(403, "Accès réservé aux rôles autorisés.");
        }
    }

    private function assertAllowedTable(string $table): void
    {
        if (!in_array($table, self::ALLOWED_TABLES, true) || !Schema::hasTable($table)) {
            abort(404, "Table inconnue ou non autorisée : {$table}");
        }
    }

    private function visibleColumns(string $table): array
    {
        $all    = Schema::getColumnListing($table);
        $hidden = self::HIDDEN_COLUMNS[$table] ?? [];
        return array_values(array_diff($all, $hidden));
    }

    // ── Endpoints ────────────────────────────────────────────────────────────

    /**
     * GET /api/admin-db/tables
     * Liste des tables administrables (whitelist) + nombre de lignes.
     */
    public function tables(Request $request): JsonResponse
    {
        $this->assertAdmin($request);

        $tables = array_map(fn (string $t) => [
            'name'  => $t,
            'count' => Schema::hasTable($t) ? DB::table($t)->count() : 0,
        ], self::ALLOWED_TABLES);

        return response()->json(['success' => true, 'data' => $tables]);
    }

    /**
     * GET /api/admin-db/tables/{table}?page=1
     * Colonnes + lignes paginées d'une table autorisée.
     */
    public function show(Request $request, string $table): JsonResponse
    {
        $this->assertAdmin($request);
        $this->assertAllowedTable($table);

        $columns = $this->visibleColumns($table);
        $page    = max((int) $request->query('page', 1), 1);

        $query = DB::table($table)->select($columns);
        if (in_array('id', $columns, true)) {
            $query->orderByDesc('id');
        }

        $total = DB::table($table)->count();
        $rows  = $query->forPage($page, self::PER_PAGE)->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'table'   => $table,
                'columns' => $columns,
                'rows'    => $rows,
                'page'    => $page,
                'perPage' => self::PER_PAGE,
                'total'   => $total,
            ],
        ]);
    }

    /**
     * POST /api/admin-db/tables/{table}
     * Création d'une ligne. Body = { colonne: valeur, ... }.
     */
    public function store(Request $request, string $table): JsonResponse
    {
        $this->assertAdmin($request);
        $this->assertAllowedTable($table);

        $payload = $this->filterPayload($request, $table);
        $payload = $this->applyTimestamps($payload, $table, isCreate: true);

        try {
            $id = DB::table($table)->insertGetId($payload);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => ['id' => $id]], 201);
    }

    /**
     * PUT /api/admin-db/tables/{table}/{id}
     */
    public function update(Request $request, string $table, int $id): JsonResponse
    {
        $this->assertAdmin($request);
        $this->assertAllowedTable($table);

        $payload = $this->filterPayload($request, $table);
        $payload = $this->applyTimestamps($payload, $table, isCreate: false);

        try {
            $affected = DB::table($table)->where('id', $id)->update($payload);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if ($affected === 0) {
            return response()->json(['success' => false, 'message' => 'Ligne introuvable.'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /api/admin-db/tables/{table}/{id}
     */
    public function destroy(Request $request, string $table, int $id): JsonResponse
    {
        $this->assertAdmin($request);
        $this->assertAllowedTable($table);

        try {
            $deleted = DB::table($table)->where('id', $id)->delete();
        } catch (\Throwable $e) {
            // Ex : violation de contrainte de clé étrangère.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if ($deleted === 0) {
            return response()->json(['success' => false, 'message' => 'Ligne introuvable.'], 404);
        }

        return response()->json(['success' => true]);
    }

    // ── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Ne garde du payload que les colonnes réellement présentes dans la
     * table et non masquées (password, remember_token...). Empêche
     * l'écriture de colonnes arbitraires ou sensibles depuis le frontend.
     */
    private function filterPayload(Request $request, string $table): array
    {
        $allowedColumns = $this->visibleColumns($table);
        $body = $request->all();

        $payload = array_intersect_key($body, array_flip($allowedColumns));
        // Jamais d'écriture directe sur id (auto-incrément).
        unset($payload['id']);

        return $payload;
    }

    /**
     * Renseigne created_at/updated_at si ces colonnes existent et ne sont
     * pas déjà fournies, pour éviter des dates nulles.
     */
    private function applyTimestamps(array $payload, string $table, bool $isCreate): array
    {
        $allowedColumns = $this->visibleColumns($table);

        if ($isCreate && in_array('created_at', $allowedColumns, true) && !array_key_exists('created_at', $payload)) {
            $payload['created_at'] = now();
        }
        if (in_array('updated_at', $allowedColumns, true) && !array_key_exists('updated_at', $payload)) {
            $payload['updated_at'] = now();
        }

        return $payload;
    }
}