<?php

namespace App\Modules\Demandes\Policies;

use App\Models\User;
use App\Modules\Demandes\Models\DocumentRequest;

/**
 * CORRECTIF (v2) — Policy basée sur la vérification réelle trouvée dans
 * DocumentRequestController :
 *
 *   if ($user->roles->first()?->slug !== 'secretaire') { ... 403 ... }
 *
 * Note importante découverte dans le code réel (WorkflowConstants) :
 * il existe un système d'ALIAS de rôle ('chef-division' → 'responsable-division')
 * utilisé partout ailleurs dans le module. Cette vérification précise de
 * 'secretaire' n'a PAS d'alias connu dans WorkflowConstants::ROLE_SLUG_ALIASES,
 * donc la comparaison stricte au slug brut, comme dans l'original, est conservée
 * ici sans appel à canonicalRole() — pour ne pas changer un comportement qui
 * fonctionne déjà correctement en production.
 */
class DocumentRequestPolicy
{
    /**
     * Gérer les fichiers secrétaire (upload, update comment, delete).
     * Reproduit exactement : $user->roles->first()?->slug !== 'secretaire'
     */
    public function manageSecretaryFiles(User $user, DocumentRequest $demande): bool
    {
        return $user->roles->first()?->slug === 'secretaire';
    }
}
