<?php

namespace App\Modules\Core\Support;

/**
 * Mise en forme commune des messages WhatsApp (tous modules).
 *
 * Structure, aérée et lisible sur téléphone, sans aucun émoji :
 *
 *   *CAP-EPAC*
 *   _Titre du message_
 *
 *   Bonjour Nom,
 *
 *   Paragraphe principal.
 *
 *   *Référence :* XXX
 *   *Montant :* 10 000 FCFA
 *
 *   Consigne ou lien.
 */
final class WhatsAppText
{
    /**
     * @param string[] $blocks Blocs séparés par une ligne vide ; les blocs vides sont ignorés.
     */
    public static function compose(string $title, array $blocks): string
    {
        $blocks = array_values(array_filter(
            array_map(fn ($b) => trim((string) $b), $blocks),
            fn ($b) => $b !== ''
        ));

        return "*CAP-EPAC*\n_{$title}_\n\n" . implode("\n\n", $blocks);
    }

    /**
     * Lignes « *Libellé :* valeur » ; les valeurs vides sont omises.
     *
     * @param array<string, mixed> $fields
     */
    public static function fields(array $fields): string
    {
        $lines = [];
        foreach ($fields as $label => $value) {
            $value = trim((string) $value);
            if ($value === '') continue;
            $lines[] = "*{$label} :* {$value}";
        }
        return implode("\n", $lines);
    }
}
