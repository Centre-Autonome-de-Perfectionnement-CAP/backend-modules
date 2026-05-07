# Configuration des Queues pour la Diffusion d'Emails

## Pourquoi utiliser les queues ?

La diffusion d'informations importantes par email peut concerner des centaines d'étudiants. Sans queue :
- ⏱️ La requête HTTP timeout après 30-60 secondes
- 🐌 L'utilisateur doit attendre la fin de tous les envois
- ❌ Si une erreur survient, tous les emails restants sont perdus

Avec les queues :
- ✅ Réponse immédiate à l'utilisateur
- ✅ Traitement en arrière-plan
- ✅ Retry automatique en cas d'échec
- ✅ Logs détaillés de chaque envoi
- ✅ Pas de timeout

## Configuration

### 1. Vérifier la configuration dans `.env`

```env
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs
```

### 2. Créer la table des jobs (si pas déjà fait)

```bash
php artisan queue:table
php artisan migrate
```

### 3. Démarrer le worker de queue

**En développement :**
```bash
php artisan queue:work --queue=emails --tries=3 --timeout=300
```

**En production (avec supervisor) :**

Créer le fichier `/etc/supervisor/conf.d/laravel-worker.conf` :

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend-modules/artisan queue:work database --queue=emails --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/backend-modules/storage/logs/worker.log
stopwaitsecs=3600
```

Puis :
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### 4. Vérifier que le worker fonctionne

```bash
# Voir les jobs en attente
php artisan queue:monitor

# Voir les jobs échoués
php artisan queue:failed

# Réessayer les jobs échoués
php artisan queue:retry all
```

## Comment ça marche ?

1. **L'utilisateur clique sur "Diffuser"** dans l'interface
2. **Le contrôleur** :
   - Récupère les étudiants selon les critères
   - Divise les étudiants en chunks de 50
   - Crée un job pour chaque chunk
   - Met les jobs en queue
   - Retourne immédiatement un `broadcast_id`
3. **Le worker** traite les jobs en arrière-plan :
   - Envoie les emails un par un
   - Log chaque succès/échec
   - Met à jour le statut dans le cache
4. **Le frontend** peut suivre l'avancement via polling (optionnel)

## Monitoring

### Logs des envois
```bash
tail -f storage/logs/laravel.log | grep "broadcast"
```

### Statut d'un broadcast
```bash
# Via API
curl -H "Authorization: Bearer TOKEN" \
  http://localhost/api/rh/broadcast-status/{broadcastId}
```

### Jobs en cours
```bash
# Voir la table jobs
mysql -u root -p -e "SELECT * FROM jobs WHERE queue='emails';"
```

## Troubleshooting

### Le worker ne démarre pas
```bash
# Vérifier les permissions
chmod -R 775 storage/
chown -R www-data:www-data storage/

# Vérifier la connexion DB
php artisan tinker
>>> DB::connection()->getPdo();
```

### Les emails ne partent pas
```bash
# Vérifier la config mail dans .env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="CAP-EPAC"

# Tester l'envoi
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

### Jobs bloqués
```bash
# Supprimer tous les jobs en attente
php artisan queue:flush

# Réessayer les jobs échoués
php artisan queue:retry all

# Supprimer les jobs échoués
php artisan queue:forget {id}
```

## Performance

- **50 étudiants par chunk** = équilibre entre performance et mémoire
- **3 tentatives** en cas d'échec
- **5 minutes de timeout** par job
- **Cache de 24h** pour le statut du broadcast

Pour 500 étudiants :
- 10 jobs créés (500 / 50)
- ~2-3 minutes d'envoi total (avec 1 worker)
- ~1 minute avec 2 workers en parallèle
