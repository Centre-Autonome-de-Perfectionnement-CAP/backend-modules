<?php

namespace App\Modules\Attestation\DTOs;

/**
 * B1.4 — DTO résultat d'une vérification d'éligibilité
 *
 * Remplace les réponses JSON inline construites dans checkAvailability().
 * Valeur-objet immutable : pas de setters.
 */
final readonly class StudentEligibilityDTO
{
    private function __construct(
        public bool    $available,
        public ?string $reason,
    ) {}

    public static function available(): self
    {
        return new self(available: true, reason: null);
    }

    public static function unavailable(string $reason): self
    {
        return new self(available: false, reason: $reason);
    }

    public function toArray(): array
    {
        return array_filter([
            'available' => $this->available,
            'reason'    => $this->reason,
        ], fn ($v) => $v !== null);
    }
}
