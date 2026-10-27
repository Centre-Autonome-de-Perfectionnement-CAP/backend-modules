# Baileys vendorisé — notes de provenance

## Provenance

- Source : uploadée par l'utilisateur (`quelques_fichiers_baileys_081419.zip`)
  et son `package.json` (`package_081720.json`), le 15/08/2026.
- Version confirmée : **`baileys` v7.0.0-rc11**, `WhiskeySockets/Baileys`
  (le `package.json` uploadé le confirme littéralement : `"name": "baileys",
  "version": "7.0.0-rc11"`).
- Structurellement identique au dépôt public à cette version (Socket/,
  Signal/, Utils/, WABinary/, WAM/, WAUSync/, Defaults/, WAProto/ déjà
  généré). **Je n'ai pas de diff contre l'upstream** — si votre fork
  contient des patches maison, je ne peux pas garantir qu'ils sont
  préservés ou identifiables ; seul ce qui a été uploadé est vendorisé ici.
- `__tests__/` et `Example/` retirés (inutiles en prod, allègent le module).

## Ce qui a été RÉELLEMENT validé (pas juste supposé)

Dans le sandbox où j'ai construit cette livraison :

1. `npm install` du `package.json` vendorisé (dépendances exactes
   confirmées contre l'upstream, y compris **`whatsapp-rust-bridge`
   0.5.4**, module natif compilé en Rust) → **réussi**, binaire natif
   récupéré sans problème.
2. `tsc -P tsconfig.json` → compile avec seulement 3 erreurs de type
   mineures (voir plus bas), non bloquantes.
3. `tsc-esm-fix --ext=.js` → corrige les imports relatifs sans extension
   du code source original (nécessaire car Node ESM exige les extensions
   explicites à l'exécution, contrairement à ce que tolère TypeScript).
4. **Chargement réel du module compilé dans Node** (`import('./lib/index.js')`)
   → réussi. `makeWASocket`, `initAuthCreds`, `BufferJSON`, `proto`,
   `DisconnectReason` tous exportés et exploitables.
5. **Notre propre code** (`whatsapp.ts`, `sessionStore.ts`, `index.ts`, etc.)
   type-checké avec `tsc --noEmit` contre ce Baileys vendorisé réellement
   compilé (pas un stub) → **0 erreur**.

Ce qui n'a **pas** pu être validé ici : une connexion WhatsApp réelle
(scan de QR code, envoi de message effectif) — impossible sans un vrai
compte WhatsApp et un accès réseau vers les serveurs WhatsApp depuis ce
sandbox. La compilation et le chargement du module sont vérifiés ; le
comportement runtime complet reste à tester chez vous.

## Les 3 erreurs TypeScript non bloquantes rencontrées

```
src/Utils/generics.ts(100,84): error TS2322: Type 'number | Long' is not assignable to type 'number'.
src/Utils/link-preview.ts(45,43): error TS2307: Cannot find module 'link-preview-js'
src/WABinary/generic-utils.ts(116,3): error TS2322: Type '... | BinaryNode | ...' is not assignable to type 'string'.
```

- `link-preview-js` est une dépendance **peer optionnelle** dans le
  `package.json` upstream (`peerDependenciesMeta.link-preview-js.optional:
  true`) — normal qu'elle manque, on ne génère pas d'aperçus de lien pour
  de simples notifications texte. Non installée intentionnellement.
- Les deux autres sont des erreurs de type mineures dans le code Baileys
  lui-même (pas dans notre intégration) — la compilation aboutit quand
  même (`noEmitOnError: false`), le comportement runtime n'est pas affecté
  pour l'usage qu'on en fait (envoi de texte simple).

## Build en déploiement

`node/package.json` orchestre tout :
```bash
npm run build
# → build:vendor (npm install + tsc + tsc-esm-fix dans vendor/baileys/)
# → puis tsc de notre propre code, qui importe vendor/baileys/lib/index.js
```

`vendor/baileys/lib/` n'est **pas livré dans ce zip** (artefact de build,
généré à chaque déploiement comme le reste) — mais son contenu a été
vérifié une fois localement (section ci-dessus) avant d'être retiré.

## Dépendance native — point d'attention déploiement

`whatsapp-rust-bridge` est un module Rust précompilé récupéré via npm.
Vérifiez que votre serveur de production a un accès réseau à npm au moment
du build (déjà nécessaire de toute façon), et que son OS/architecture est
supporté par les binaires prébuilts du paquet (Linux x64 est le cas le
plus courant et sans souci ; à vérifier si votre serveur est ARM ou autre).
