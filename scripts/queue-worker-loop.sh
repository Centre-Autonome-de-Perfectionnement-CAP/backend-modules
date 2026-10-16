#!/usr/bin/env bash
# Worker de file Laravel auto-relancé — utilisé par le déploiement quand
# Supervisor n'est pas configuré (voir supervisor/laravel-worker.conf).
#
#  - --queue=default,emails : le transactionnel (default) passe toujours
#    avant les diffusions de masse (emails) ;
#  - --sleep=1 : une notification est prise en charge en ~1 s ;
#  - --timeout=85 < retry_after (90 s) : jamais d'exécution en double ;
#  - --max-time / --memory : le worker se recycle proprement et la boucle
#    le relance (prend aussi le nouveau code après un déploiement).
#
# Usage : nohup scripts/queue-worker-loop.sh /chemin/vers/current >/dev/null 2>&1 &
APP_DIR="${1:?Chemin de l'application requis}"

while true; do
  cd "$APP_DIR" 2>/dev/null && php artisan queue:work \
    --queue=default,emails \
    --sleep=1 \
    --tries=3 \
    --timeout=85 \
    --max-time=3600 \
    --memory=256
  sleep 2
done
