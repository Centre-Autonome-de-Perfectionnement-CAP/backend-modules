# Configuration Email - Formulaire de Contact

## Vue d'ensemble

Le système envoie automatiquement un email aux administrateurs lorsqu'un message de contact est reçu via le formulaire du site web.

## 📧 Destinataires

- **Principal** : contact@cap-epac.online
- **Copie (CC)** : owomax@gmail.com

## ⚙️ Configuration requise dans .env

Ajoutez ou modifiez les variables suivantes dans le fichier `.env` :

```env
# Configuration Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-application
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@cap-epac.online
MAIL_FROM_NAME="CAP - Centre Autonome de Perfectionnement"
```

## 🔐 Configuration Gmail (Recommandé)

### Option 1 : Mot de passe d'application (Recommandé)

1. Connectez-vous à votre compte Gmail
2. Allez sur https://myaccount.google.com/security
3. Activez la validation en deux étapes
4. Cherchez "Mots de passe des applications"
5. Générez un nouveau mot de passe d'application
6. Utilisez ce mot de passe dans `MAIL_PASSWORD`

### Option 2 : Autre service SMTP

Vous pouvez aussi utiliser :
- **SendGrid** : https://sendgrid.com/
- **Mailgun** : https://www.mailgun.com/
- **Amazon SES** : https://aws.amazon.com/ses/
- **Mailtrap** (pour tests) : https://mailtrap.io/

## 📝 Template Email

Le template HTML se trouve dans :
```
resources/views/emails/new_contact_notification.blade.php
```

### Contenu du template
- En-tête avec logo CAP
- Informations du contact (nom, email, sujet)
- Message complet
- Date de réception
- Pied de page avec coordonnées CAP

### Variables disponibles
- `$contactName` : Nom de la personne
- `$contactEmail` : Email de la personne
- `$contactSubject` : Sujet du message
- `$contactMessage` : Contenu du message
- `$contactDate` : Date et heure de réception

## 🧪 Test de la configuration email

### 1. Tester la connexion SMTP

```bash
php artisan tinker
```

Puis exécutez :

```php
Mail::raw('Test email CAP', function ($message) {
    $message->to('contact@cap-epac.online')
            ->cc('owomax@gmail.com')
            ->subject('Test configuration email');
});
```

### 2. Tester le formulaire de contact

1. Accédez à la page `/contact` du site
2. Remplissez le formulaire
3. Vérifiez la réception de l'email dans les deux boîtes

### 3. Vérifier les logs

```bash
tail -f storage/logs/laravel.log
```

Recherchez les entrées :
- `New contact message received`
- `Contact notification email sent`
- `Failed to send contact notification email` (en cas d'erreur)

## 🚨 Gestion des erreurs

### Le message est enregistré même si l'email échoue

Le système est conçu pour **ne pas bloquer** la création du message si l'envoi d'email échoue :

```php
try {
    $this->notifyAdmins($contact);
} catch (\Exception $e) {
    Log::error('Failed to send contact notification email', [
        'contact_id' => $contact->id,
        'error' => $e->getMessage()
    ]);
}
```

Cela garantit que :
- Le message est toujours enregistré en base de données
- L'utilisateur reçoit toujours une confirmation de succès
- Les erreurs email sont loguées pour investigation

### Erreurs courantes

#### 1. "Connection refused"
```
Solution : Vérifiez MAIL_HOST et MAIL_PORT
```

#### 2. "Invalid credentials"
```
Solution : Vérifiez MAIL_USERNAME et MAIL_PASSWORD
Pour Gmail, utilisez un mot de passe d'application
```

#### 3. "SSL certificate problem"
```
Solution : Changez MAIL_ENCRYPTION de ssl à tls ou vice versa
```

#### 4. "Authentication failed"
```
Solution Gmail : Activez l'accès des applications moins sécurisées
OU utilisez un mot de passe d'application
```

## 📊 Monitoring

### Vérifier les emails envoyés

Consultez les logs Laravel :

```bash
# Voir tous les emails de contact envoyés aujourd'hui
grep "Contact notification email sent" storage/logs/laravel.log | grep $(date +%Y-%m-%d)
```

### Statistiques

```php
// Dans tinker ou un contrôleur
$today = Carbon::today();
$messagesReceived = Contact::whereDate('created_at', $today)->count();
echo "Messages reçus aujourd'hui : $messagesReceived";
```

## 🔄 Mode Queue (Optionnel - Recommandé pour production)

Pour éviter de ralentir le formulaire, vous pouvez mettre l'envoi d'email en queue :

### 1. Configurer la queue

Dans `.env` :
```env
QUEUE_CONNECTION=database
```

### 2. Créer la table queue

```bash
php artisan queue:table
php artisan migrate
```

### 3. Modifier le Mailable

```php
// Dans NewContactNotification.php
class NewContactNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    // ...
}
```

### 4. Démarrer le worker

```bash
php artisan queue:work
```

Ou utilisez Supervisor pour production :

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/worker.log
```

## 🎨 Personnalisation

### Modifier les destinataires

Dans `ContactService.php` :

```php
Mail::to('nouveau-email@example.com')
    ->cc(['copie1@example.com', 'copie2@example.com'])
    ->bcc('copie-cachee@example.com')
    ->send(new NewContactNotification($contact));
```

### Ajouter des pièces jointes

Dans `NewContactNotification.php` :

```php
public function attachments(): array
{
    return [
        Attachment::fromPath('/path/to/file.pdf'),
    ];
}
```

### Modifier le template

Éditez directement :
```
resources/views/emails/new_contact_notification.blade.php
```

## 📱 Email de confirmation à l'utilisateur (Future amélioration)

Pour envoyer aussi un email de confirmation à l'utilisateur qui a envoyé le message :

```php
// Dans ContactService
Mail::to($contact->email)
    ->send(new ContactConfirmation($contact));
```

Créer le Mailable correspondant avec un message de type :
"Merci pour votre message. Nous vous répondrons dans les meilleurs délais."

---

**Configuration mise à jour le 6 novembre 2025**
