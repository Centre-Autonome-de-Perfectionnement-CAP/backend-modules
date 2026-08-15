# Module WhatsApp — livraison

## MISE À JOUR (15/08/2026, 2ᵉ session) — Envoi de fichiers + généralisation multi-modules

**Demandes — vérifié pour de vrai, pas supposé.** J'ai relu
`SendNotificationJob.php` et `Demandes\Services\WhatsAppService.php` en
entier : la chaîne `SendNotificationJob::handleWhatsApp()` →
`WhatsAppService::send()` → `WhatsAppBridgeClient::send()` passe déjà
`$context` correctement à 3 arguments. Ça marchait déjà avant cette
session pour le texte — confirmé, pas juste "ça devrait marcher".

### Nouveau : détection automatique du module (zéro config)

`WhatsAppBridgeClient::send()`/`sendFile()` détectent maintenant **automatiquement**
quel module a fait l'appel, via `debug_backtrace()` sur le namespace de
l'appelant (`App\Modules\{X}\...`). Aucun module — RH, Finance, Inscription,
peu importe — n'a besoin de rien déclarer : il suffit d'injecter
`WhatsAppBridgeClient` et d'appeler `send()`. Voir
`app/Modules/WhatsApp/GUIDE-INTEGRATION-MODULES.md` pour le mode d'emploi
complet (texte + fichier), destiné aux développeurs des autres modules.

Override manuel possible via un 5ᵉ paramètre `$module` si l'auto-détection
ne convient pas à un cas particulier (trait partagé, etc.).

### Nouveau : envoi de fichiers

- `WhatsAppBridgeClient::sendFile($phone, $disk, $path, $fileName, $caption, $context, $module)`
  — fonctionne avec n'importe quel disque Laravel (local, s3...).
- Le fichier n'est **jamais** chargé en base64 en mémoire côté Laravel : une
  URL interne temporaire (10 min, loopback) est générée
  (`GET /api/whatsapp/internal/files/{token}`, token opaque en cache), et
  c'est le Node/Baileys qui télécharge lui-même le fichier — efficace même
  pour des fichiers volumineux.
- Nouvel endpoint Node **séparé** `/send-file` — `/send-message` garde sa
  signature strictement inchangée, aucun risque de régression dessus.
- `whatsapp.ts` : nouvelle méthode `sendFileMessage()` (image inline si
  `image/*`, document générique sinon).
- Logique de résolution JID (avec les fallbacks numéros béninois) extraite
  dans une fonction partagée `resolveJid()` — évite la duplication entre
  `/send-message` et `/send-file`.

### Nouveau : gestion module par module côté admin

- `wa_message_log` a 5 nouvelles colonnes : `module`, `file_name`,
  `media_type`, `file_disk`, `file_path` (les deux derniers pour que le
  **retry** d'un message avec fichier fonctionne même après expiration du
  token de 10 minutes — on régénère une URL fraîche à partir de `disk`+`path`
  stockés durablement).
- `GET /admin/messages?module=X` — filtre combinable avec `?status=`.
- `GET /admin/messages/modules` — liste des modules déjà vus + compteurs,
  alimente le filtre déroulant du frontend (se remplit tout seul, rien à
  configurer quand un nouveau module commence à envoyer).
- `GET /admin/stats` retourne maintenant aussi `by_module` (ventilation
  sent/failed/total par module).
- `retryMessage()` gère maintenant le retry de fichiers, pas seulement de
  texte (détecte via `file_disk`/`file_path` renseignés ou non), et
  préserve le module d'origine (passé explicitement, pas ré-auto-détecté
  depuis le contrôleur admin qui n'est évidemment pas le module d'origine).

### Frontend mis à jour

- `MessagesTable.tsx` : filtre déroulant par module (`CFormSelect`, vérifié
  contre l'usage réel — 33 occurrences dans vos fichiers), colonne Module,
  badge fichier joint (icône différente image/document).
- `StatsPanel.tsx` : tableau de répartition par module sous les compteurs
  globaux.
- `whatsapp.service.ts` : types étendus (`module`, `file_name`,
  `media_type`), `getModules()`.

### Revalidé réellement (pas juste relu)

Après ces changements, j'ai reconstruit le Baileys vendorisé
(`npm install` → `tsc` → `tsc-esm-fix`) et refait tourner `tsc --noEmit`
sur l'intégralité de notre code Node (`index.ts`, `whatsapp.ts`,
`messageStore.ts`, etc.) contre ce Baileys réellement compilé : **0 erreur**.
Vérification d'équilibre des accolades sur tous les fichiers PHP touchés,
et diff complet des 3 fichiers frontend existants patchés pour confirmer
qu'aucune modification non intentionnelle ne s'est glissée.

### Toujours pas testé (limite du sandbox, inchangée)

Un envoi de fichier réel vers un vrai compte WhatsApp — nécessite une
connexion Baileys active, impossible à simuler ici.

---


Code écrit directement à partir de vos fichiers réels (pas de suppositions
sur le contenu). Tout est dans `app/Modules/WhatsApp/` sauf la classe de
compatibilité `Core/Services/WhatsAppBridgeClient.php` (remplace l'existant)
et les 3 fichiers racine patchés (`config/services.php`,
`bootstrap/providers.php`).

## ✅ Baileys — vendorisé et validé réellement (plus bloquant)

Grâce aux fichiers uploadés (`quelques_fichiers_baileys_081419.zip` +
`package_081720.json`), Baileys v7.0.0-rc11 est maintenant **vendorisé
dans `node/vendor/baileys/`** — plus aucune dépendance npm ou dossier
externe. J'ai réellement testé la chaîne complète dans mon sandbox
(`npm install` → `tsc` → `tsc-esm-fix` → chargement du module compilé
dans Node → type-check de notre propre code contre ce Baileys compilé) :
tout passe. Détails et limites dans `node/vendor/baileys/VENDOR-NOTES.md`.

Ce qui n'a pas pu être testé ici (pas de compte WhatsApp/réseau
disponible dans ce sandbox) : une vraie connexion WhatsApp de bout en
bout. À valider chez vous après déploiement.

## Ce qui a été livré et pourquoi

| Fichier | Ce qu'il fait |
|---|---|
| `Services/WhatsAppBridgeClient.php` | Nouveau domicile. **Corrige un bug réel de l'ancien fichier** : le header `X-Api-Key` n'était envoyé nulle part — activer `WHATSAPP_BRIDGE_API_KEY` aurait cassé tous les envois en silence. Corrigé dans `send()` et `isConnected()`. Ajout `getStatus()`/`logoutSession()` pour l'admin. |
| `Core/Services/WhatsAppBridgeClient.php` | Classe vide `extends` la nouvelle — tous les `use App\Modules\Core\Services\WhatsAppBridgeClient` existants (Demandes, Attestation, SendGroupedReminders) continuent de marcher sans y toucher. |
| `Services/WhatsAppProcessManager.php` | `isRunning()` + `startDetached()` verrouillé (`Cache::lock`). **N'est jamais appelé depuis `boot()`** (risque de double-spawn à chaque requête HTTP) — seulement depuis la commande artisan, en filet de secours sans Supervisor. |
| `Console/Commands/StartWhatsAppNode.php` | `php artisan whatsapp:node:start` — filet de secours uniquement. |
| `Http/Controllers/WhatsAppAdminController.php` | `status`, `destroySession`, `messages`, `retryMessage`, `stats`, `webhookReceive`. Rôle **admin strict** vérifié en interne (`assertAdmin()`), pattern identique à `AdminTableController` — il n'existe pas de middleware `role:*` dans ce projet, je n'en ai pas inventé un. |
| `Models/WaMessageLog.php` | Modèle Eloquent minimal pour `wa_message_log`. |
| `routes/api.php` | `/api/whatsapp/admin/*` (Sanctum) + `/api/whatsapp/internal/webhook` (clé API, pas Sanctum — c'est le Node qui appelle, pas un utilisateur). |
| `Providers/WhatsAppServiceProvider.php` | Pattern identique à `CoursServiceProvider` : `Route::prefix('api')->middleware('api')->group(...)`. **L'oubli de `->middleware('api')` aurait cassé le CORS** sur ces routes — vérifié contre votre code réel. |
| `database/migrations/*` | `wa_sessions`, **`wa_session_keys`** (magasin de clés Signal — indispensable, une seule table `creds` ne suffit pas, Baileys a besoin de pre-keys/sessions/sender-keys persistés par contact), `wa_message_log`. |
| `node/src/config.ts` | Charge l'unique `.env` de `backend-modules/` par **recherche ascendante** (pas de `../../../../..` fragile, pas de symlink). Échoue bruyamment si variables requises manquantes. |
| `node/src/db/crypto.ts` | AES-256-GCM, clé dérivée SHA-256 de `WA_SESSION_ENCRYPTION_KEY`, IV aléatoire par opération (pattern Convessa). |
| `node/src/db/connection.ts` | Pool mysql2, réutilise directement `DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD` (pas de renommage). |
| `node/src/db/sessionStore.ts` | `useDbAuthState()` — remplace `useMultiFileAuthState`. Stocke creds **et** magasin de clés Signal. |
| `node/src/db/messageStore.ts` | Journalisation de chaque tentative d'envoi. |
| `node/src/whatsapp.ts` | Modifié : `useDbAuthState` au lieu de fichiers, plus aucun accès filesystem pour les sessions. |
| `node/src/index.ts` | Modifié : **dashboard supprimé**, **CORS et Socket.io supprimés** (plus aucun navigateur ne parle directement au Node — voir plus bas), relais webhook vers Laravel, logging systématique des envois. Signatures des 3 endpoints REST inchangées. |
| `supervisor/whatsapp-node.conf` | `autostart=true` + `autorestart=true` — c'est ÇA qui réalise "le démarrage du backend démarre le Node", pas `proc_open` dans `boot()`. |

## Écarts assumés par rapport au plan initial (et pourquoi)

1. **Pas de Reverb/Echo/Pusher pour le QR** — ce projet n'a aucune
   infra de broadcast configurée (`config/broadcasting.php` n'existe même
   pas). J'ai remplacé par du **polling** (`GET /admin/status` toutes les
   2-3s côté frontend, à faire en Phase 4). Zéro dépendance nouvelle.
2. **Socket.io et CORS retirés du Node** — devenus inutiles : plus aucun
   navigateur ne parle directement au Node (le frontend admin parle à
   Laravel, qui poll le Node côté serveur). Les garder aurait été de la
   config et une dépendance en plus pour rien.
3. **`WhatsAppProcessManager::startDetached()` n'est jamais appelé
   automatiquement** — Supervisor `autostart=true` est la vraie garantie
   "Laravel démarre → Node démarre". La commande artisan est un filet de
   secours pour les environnements sans Supervisor.

## Ce qu'il reste À FAIRE MANUELLEMENT (je ne peux pas le faire moi-même)

1. **Trancher Baileys** (voir section bloquante ci-dessus)
2. Copier `app/Modules/WhatsApp/` dans votre vrai `backend-modules/`
3. Remplacer `Core/Services/WhatsAppBridgeClient.php` par la version fournie
4. Fusionner `config/services.php` et `bootstrap/providers.php` (fournis
   complets, mais vérifiez qu'aucun autre module n'a été ajouté depuis chez
   vous entre-temps)
5. Ajouter dans `.env` (générer avec `openssl rand -hex 32`) :
   ```
   WHATSAPP_BRIDGE_API_KEY=
   WA_SESSION_ENCRYPTION_KEY=
   WA_AUTO_START=false   # true seulement si PAS de Supervisor
   WHATSAPP_NODE_PORT=3005
   ```
   (`WHATSAPP_BRIDGE_URL` et `WHATSAPP_BRIDGE_TIMEOUT` existent déjà chez vous)
6. `composer dump-autoload`
7. `php artisan migrate` (crée `wa_sessions`, `wa_session_keys`, `wa_message_log`)
8. Une fois Baileys tranché : `cd node && npm install && npm run build`
9. Copier `supervisor/whatsapp-node.conf` vers `/etc/supervisor/conf.d/`,
   `supervisorctl reread && supervisorctl update`
10. Vider `whatsapp-service/auth/` (ancien service) — pas fait ici, hors périmètre des fichiers reçus
11. Tester : `php artisan route:list --path=whatsapp`, puis un envoi réel
    via `Demandes\WhatsAppService` pour confirmer zéro régression
12. `deploy.yml` — je n'ai pas ce fichier, donc pas patché. Ajouter après
    `composer install` : build npm du Node + reload Supervisor (voir
    contenu de `supervisor/whatsapp-node.conf` en commentaire pour le détail)

## Pas encore fait (annoncé, pas oublié)

- ~~Phase 4 (frontend admin React)~~ → **fait**, voir section ci-dessous.

## Frontend admin (Phase 4) — livré dans `frontend-patch-livraison/`

Analysé contre vos 400 fichiers réels (stack CoreUI React, contexte
`useAuth()`, service HTTP Axios avec intercepteur Bearer). Pattern répliqué
à l'identique de `AdminDbGuard`/`AdminDbRoutes` (le seul autre module admin
existant chez vous).

| Fichier | Rôle |
|---|---|
| `src/services/whatsapp.service.ts` | Appels HTTP — types calés exactement sur les réponses réelles de `WhatsAppAdminController.php` |
| `src/views/pages/whatsapp/WhatsAppGuard.tsx` | Garde d'accès, rôle `admin` strict |
| `src/views/pages/whatsapp/WhatsAppRoutes.tsx` | Montage des routes du module |
| `src/views/pages/whatsapp/WhatsAppDashboard.tsx` | Conteneur à onglets (pattern `CNav`/`CTabContent` vérifié contre `ContentManagement.tsx`, pas la démo CoreUI) |
| `.../components/ConnectionPanel.tsx` | Connexion + QR + Déconnexion réunis en un seul panneau cohérent (polling 3s sur `GET /admin/status`) |
| `.../components/MessagesTable.tsx` | Réutilisé pour "Messages envoyés" et "Messages échoués", bouton **Retry inline** sur les lignes en échec |
| `.../components/StatsPanel.tsx` | Compteurs sent/failed/queued/sending/total |

**3 onglets au lieu des 7 items du plan initial** — Connexion/QR/Déconnexion
n'ont de sens que réunis (le QR ne s'affiche que si déconnecté, le bouton
logout que si connecté), et Retry vit directement dans le tableau des
échecs plutôt que dans un onglet à part qui aurait juste affiché le même
tableau filtré. Fonctionnellement complet, juste moins fragmenté.

**3 fichiers EXISTANTS patchés** (diffs complets vérifiés, aucun changement
non intentionnel) :
- `src/_nav/index.tsx` — nouvelle fonction `getMainNavigation(role)` qui
  ajoute l'entrée "WhatsApp" au menu uniquement pour le rôle `admin`
  (`mainNavigation` original conservé tel quel pour ne rien casser)
- `src/components/AppSidebar.tsx` — utilise `getMainNavigation(role)`
- `src/App.tsx` — route `/whatsapp/*` montée

**Composants CoreUI vérifiés un par un** contre l'usage réel dans vos 400
fichiers (pas la démo) — aucun composant inventé ou d'une mauvaise version.

**Non testé** (pas de build frontend complet disponible ici — seul `src/`
a été fourni, sans `package.json`/`vite.config` du frontend lui-même) :
compilation Vite réelle, rendu visuel. Structurellement solide et
cohérent avec vos conventions, mais un `npm run build` chez vous est le
seul moyen de le confirmer à 100%.

## Pour tout remonter chez vous

1. Copier `backend-modules/*` dans votre `backend-modules/` réel (voir
   étapes détaillées plus haut)
2. Copier `frontend-patch-livraison/src/*` par-dessus votre
   `app-cap-frontend/src/` (ou `app-cap/src/`, à confirmer selon lequel
   sert le panneau admin) — ce sont des AJOUTS + 3 fichiers patchés, pas un
   remplacement complet du dossier `src/`
3. `npm run build` côté frontend pour confirmer que ça compile chez vous
