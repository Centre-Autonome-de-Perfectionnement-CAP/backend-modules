<?php

namespace App\Modules\Stages\Exceptions;

use App\Exceptions\BusinessException;

/**
 * Connexion depuis une application du téléphone pas encore reconnue (sur
 * iPhone : l'application installée sur l'écran d'accueil après le navigateur,
 * ou l'inverse). Le stagiaire confirme avec l'empreinte de son téléphone de
 * stage ; la réponse porte le défi à signer.
 */
class DeviceConfirmationRequired extends BusinessException
{
    public function __construct(public readonly array $data)
    {
        parent::__construct(
            'Confirmez avec votre empreinte que ce téléphone est bien votre téléphone de stage.',
            'STAGE_DEVICE_CONFIRM',
            409,
        );
    }
}
