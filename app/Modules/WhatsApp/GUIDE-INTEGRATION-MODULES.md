# Envoyer des notifications WhatsApp depuis n'importe quel module

**Zéro configuration.** Pas de fichier à éditer, pas de module à déclarer
quelque part, pas de clé à ajouter. Le module d'origine est détecté
automatiquement d'après le namespace de la classe qui appelle — parce que
ce projet respecte déjà la convention `App\Modules\{NomDuModule}\...`
partout.

## Envoyer un texte

```php
use App\Modules\Core\Services\WhatsAppBridgeClient; // ou App\Modules\WhatsApp\Services\...

class MonService
{
    public function __construct(private WhatsAppBridgeClient $whatsapp) {}

    public function notifier(string $telephone): void
    {
        $this->whatsapp->send(
            $telephone,
            "Votre dossier a été mis à jour.",
            context: 'maj-dossier:REF-123', // optionnel, texte libre pour vos logs
        );
    }
}
```

C'est tout. Le message apparaîtra automatiquement dans l'onglet admin
"Messages envoyés/échoués", tagué avec le nom de votre module (celui dans
`App\Modules\{ICI}\...`), filtrable et visible dans les statistiques.

## Envoyer un fichier (PDF, image, tout document)

```php
$this->whatsapp->sendFile(
    $telephone,
    disk: 'local',                    // n'importe quel disque Laravel configuré
    path: 'attestations/2026/att-42.pdf',
    fileName: 'Attestation.pdf',      // nom vu par le destinataire
    caption: 'Voici votre attestation.',
    context: 'attestation:REF-42',
);
```

Le fichier n'est jamais chargé en base64 en mémoire côté Laravel — le
service Node télécharge lui-même le fichier via une URL interne temporaire
(10 minutes, usage loopback uniquement). Fonctionne avec n'importe quel
disque (`local`, `s3`, etc.), tant que le fichier existe dessus au moment
de l'appel.

## Bonnes pratiques

- **Toujours vérifier la connexion avant un envoi critique** (comme le fait
  déjà `Demandes\SendNotificationJob`) :
  ```php
  if (!$this->whatsapp->isConnected()) {
      // reporter l'envoi plutôt que de le perdre
  }
  ```
- **Passer par une queue** pour ne jamais bloquer une requête HTTP sur
  l'envoi (voir `Demandes\Jobs\SendNotificationJob` comme modèle à copier).
- **`context`** reste un texte libre pour VOTRE usage (référence métier,
  débogage) — ce n'est pas ce qui identifie le module, cette partie-là est
  automatique.

## Si l'auto-détection ne convient pas à un cas particulier

Rare, mais possible (ex: un Trait partagé entre plusieurs modules, ou un
appel depuis une classe hors `App\Modules\*`) — un 5ᵉ paramètre optionnel
permet de forcer le nom du module :

```php
$this->whatsapp->send($telephone, $message, context: '', module: 'RH');
$this->whatsapp->sendFile($telephone, 'local', $path, $fileName, module: 'RH');
```
