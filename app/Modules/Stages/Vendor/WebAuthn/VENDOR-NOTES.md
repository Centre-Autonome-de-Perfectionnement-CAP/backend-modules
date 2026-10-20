# lbuchs/WebAuthn (vendorisé)

Bibliothèque serveur WebAuthn (FIDO2) utilisée pour le pointage par empreinte
des stagiaires (module Stages).

- Source : https://github.com/lbuchs/WebAuthn
- Version : commit fb4bcee0ea8a5bc25e5dc358172d65bf15b5f419 (5 septembre 2025)
- Licence : MIT (voir LICENSE)
- Contenu : dossier `src/` d'origine, non modifié.
- Chargement : autoload PSR-4 `lbuchs\WebAuthn\` enregistré par
  `StagesServiceProvider::register()` (aucune modification de composer.json).

Aucune donnée biométrique n'arrive au serveur : seule la clé publique de
l'appareil est enregistrée, et chaque pointage est une signature vérifiée.
