<?php

namespace Tests\Feature\Verif;

/**
 * Téléphone de stage simulé : identifiant d'appareil envoyé à chaque appel
 * de l'espace stagiaire, et sessions de test liées à cet appareil.
 */
trait InternDevice
{
    protected function device(): string
    {
        return 'telephone-de-stage-de-test-0123456789';
    }

    protected function deviceHash(?string $device = null): string
    {
        return hash('sha256', $device ?? $this->device());
    }

    protected function useInternDevice(): void
    {
        $this->withHeader('X-Stagiaire-Appareil', $this->device());
    }
}
