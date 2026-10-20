<?php

namespace App\Modules\Stages\Support;

final class StageStatus
{
    /** Demande déposée, en attente d'étude par le responsable de division. */
    public const SUBMITTED = 'submitted';
    /** Proposition envoyée, en attente de la réponse du demandeur. */
    public const PROPOSAL_SENT = 'proposal_sent';
    /** Le demandeur a proposé d'autres conditions : à étudier. */
    public const COUNTER_PROPOSED = 'counter_proposed';
    /** Accord trouvé : note de service émise. */
    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';
    /** Proposition restée sans réponse. */
    public const EXPIRED = 'expired';

    public const LABELS = [
        self::SUBMITTED        => 'Nouvelle demande',
        self::PROPOSAL_SENT    => 'Proposition envoyée',
        self::COUNTER_PROPOSED => 'Contre-proposition reçue',
        self::ACCEPTED         => 'Acceptée — note de service émise',
        self::REJECTED         => 'Non retenue',
        self::EXPIRED          => 'Expirée (sans réponse)',
    ];

    /** Libellés côté demandeur. */
    public const PUBLIC_LABELS = [
        self::SUBMITTED        => "En cours d'étude",
        self::PROPOSAL_SENT    => 'Une proposition vous attend',
        self::COUNTER_PROPOSED => 'Votre proposition est en cours d\'étude',
        self::ACCEPTED         => 'Acceptée',
        self::REJECTED         => 'Non retenue',
        self::EXPIRED          => 'Expirée',
    ];

    /** Une demande « active » bloque un nouveau dépôt avec le même contact. */
    public const ACTIVE = [self::SUBMITTED, self::PROPOSAL_SENT, self::COUNTER_PROPOSED];

    /** Le responsable peut faire une proposition / rejeter dans ces statuts. */
    public const OPEN_FOR_DECISION = [self::SUBMITTED, self::PROPOSAL_SENT, self::COUNTER_PROPOSED];

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    public static function publicLabel(string $status): string
    {
        return self::PUBLIC_LABELS[$status] ?? $status;
    }
}
