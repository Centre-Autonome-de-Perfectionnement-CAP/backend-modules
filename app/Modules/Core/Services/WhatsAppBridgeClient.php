<?php

namespace App\Modules\Core\Services;

/**
 * Classe de compatibilité — CE FICHIER REMPLACE l'ancienne implémentation.
 *
 * L'implémentation réelle a déménagé dans App\Modules\WhatsApp\Services\WhatsAppBridgeClient
 * (voir ce fichier pour la logique complète + le correctif X-Api-Key).
 *
 * Cette classe vide qui étend la nouvelle garantit que TOUT le code existant
 * qui fait `use App\Modules\Core\Services\WhatsAppBridgeClient;` continue de
 * fonctionner à l'identique, sans aucune modification :
 *   - App\Modules\Demandes\Services\WhatsAppService
 *   - App\Modules\Attestation\Services\WhatsAppNotificationService
 *   - App\Console\Commands\SendGroupedReminders
 *
 * Ne rien ajouter ici. Toute nouvelle logique va dans la classe parente.
 */
class WhatsAppBridgeClient extends \App\Modules\WhatsApp\Services\WhatsAppBridgeClient
{
}
