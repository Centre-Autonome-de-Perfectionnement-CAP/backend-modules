<?php

namespace App\Modules\Inscription\Http\Requests\Concerns;

/**
 * Règles d'upload communes aux dossiers de candidature.
 *
 * Contexte (bug « ça marche sur PC, pas sur téléphone ») : depuis un
 * téléphone, les pièces sont souvent des PHOTOS (2 à 12 Mo chacune, parfois
 * au format HEIC sur iPhone) au lieu de PDF scannés légers. Sans limite
 * explicite, un fichier trop gros était rejeté par PHP avant Laravel et
 * l'utilisateur recevait une erreur incompréhensible (« champ requis » ou
 * « échec de téléversement »), et la photo HEIC était refusée par la règle
 * `image`.
 *
 * Désormais : formats acceptés explicites (dont HEIC/HEIF pour les pièces),
 * taille maximale par fichier claire, et messages en français qui disent à
 * l'utilisateur quoi faire. Les exécutables et autres formats arbitraires,
 * auparavant acceptés, sont refusés.
 */
trait DossierUploadRules
{
    /** Taille max d'une pièce (Ko) - cohérente avec la config PHP (public/.user.ini). */
    protected const MAX_PIECE_KB = 5120; // 5 Mo (limite historique, conservée)

    /** Pièce justificative (scan PDF, photo, document Word). */
    protected function pieceRule(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx',
            'max:' . self::MAX_PIECE_KB,
        ];
    }

    /** Photo d'identité : affichée dans le progiciel et sur la fiche PDF. */
    protected function photoRule(): array
    {
        return ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_PIECE_KB];
    }

    /** Messages d'erreur d'upload, compréhensibles sur mobile. */
    protected function uploadMessages(): array
    {
        $mo = (int) (self::MAX_PIECE_KB / 1024);

        return [
            '*.uploaded' => "Le fichier n'a pas pu être envoyé (probablement trop volumineux, maximum {$mo} Mo). Réduisez sa taille ou envoyez un PDF.",
            '*.max'      => "Le fichier dépasse la taille maximale autorisée ({$mo} Mo).",
            '*.mimes'    => 'Format non accepté. Formats autorisés : PDF, JPG, PNG, WEBP, HEIC, DOC, DOCX.',
            '*.file'     => 'Le fichier envoyé est invalide. Veuillez le sélectionner à nouveau.',
            'photo.mimes'=> "La photo doit être au format JPG, PNG ou WEBP. Sur iPhone : Réglages > Appareil photo > Formats > « Le plus compatible », ou faites une capture d'écran de la photo.",
            'files.*.uploaded' => "Le fichier n'a pas pu être envoyé (probablement trop volumineux, maximum {$mo} Mo).",
            'files.*.max'      => "Le fichier dépasse la taille maximale autorisée ({$mo} Mo).",
            'files.*.mimes'    => 'Format non accepté. Formats autorisés : PDF, JPG, PNG, WEBP, HEIC, DOC, DOCX.',
        ];
    }
}
