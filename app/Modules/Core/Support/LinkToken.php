<?php

namespace App\Modules\Core\Support;

use Illuminate\Support\Str;

/**
 * Jeton des liens personnels envoyés par WhatsApp ou par email (activation,
 * invitations) : court, pour un lien lisible, et placé dans le chemin
 * (…/activer/Ab12Cd34Ef56Gh78) plutôt qu'en paramètre.
 *
 * 16 caractères (plus de 10^28 possibilités) : impossible à deviner, d'autant
 * que chaque lien est à usage unique, limité dans le temps et que les essais
 * sont limités. Seule son empreinte est conservée en base. Les liens déjà
 * envoyés avec l'ancien format (48 caractères) restent acceptés.
 */
final class LinkToken
{
    public const LENGTH = 16;

    public const RULE = ['required', 'string', 'regex:/^(?:[A-Za-z0-9]{16}|[A-Za-z0-9]{48})$/'];

    public static function make(): string
    {
        return Str::random(self::LENGTH);
    }
}
