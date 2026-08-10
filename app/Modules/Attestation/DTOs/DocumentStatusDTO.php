<?php

namespace App\Modules\Attestation\DTOs;

/**
 * B1.4 — DTO représentant le statut d'un document pour l'étudiant
 *
 * Utilisé par AttestationController::getStatus() et getBulletinStatus()
 * pour éviter la construction de tableaux ad-hoc dispersés.
 */
final readonly class DocumentStatusDTO
{
    public function __construct(
        public string  $type,
        public string  $status,           // 'disponible' ou slug workflow
        public ?string $reference     = null,
        public ?string $submittedAt   = null,
        public ?string $rejectedReason = null,
    ) {}

    /**
     * Construit depuis une ligne document_requests existante.
     */
    public static function fromExisting(object $row): self
    {
        return new self(
            type:           $row->type,
            status:         $row->status,
            reference:      $row->reference,
            submittedAt:    $row->submitted_at,
            rejectedReason: $row->rejected_reason ?? null,
        );
    }

    /**
     * Construit pour un document éligible mais sans demande active.
     */
    public static function available(string $type): self
    {
        return new self(type: $type, status: 'disponible');
    }

    public function toArray(): array
    {
        return array_filter([
            'type'           => $this->type,
            'status'         => $this->status,
            'reference'      => $this->reference,
            'submittedAt'    => $this->submittedAt,
            'rejectedReason' => $this->rejectedReason,
        ], fn ($v) => $v !== null);
    }
}
