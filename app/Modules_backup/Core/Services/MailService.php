<?php

namespace App\Modules\Core\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Exception;

class MailService
{
    /**
     * Envoyer un email en utilisant une vue
     *
     * @param string|array $to
     * @param string $subject
     * @param string $view
     * @param array $data
     * @param array $attachments
     * @return bool
     */
    public function sendEmail($to, string $subject, string $view, array $data = [], array $attachments = []): bool
    {
        try {
            Mail::send($view, $data, function ($message) use ($to, $subject, $attachments) {
                $message->to($to)
                    ->subject($subject);

                // Ajouter les pièces jointes
                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        $message->attach($attachment['path'], $attachment['options'] ?? []);
                    } else {
                        $message->attach($attachment);
                    }
                }
            });

            return true;
        } catch (Exception $e) {
            \Log::error('Erreur lors de l\'envoi d\'email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer un email avec un template prédéfini
     *
     * @param string|array $to
     * @param string $subject
     * @param string $template
     * @param array $data
     * @param array $attachments
     * @return bool
     */
    public function sendWithTemplate($to, string $subject, string $template, array $data = [], array $attachments = []): bool
    {
        $view = "core::emails.{$template}";
        return $this->sendEmail($to, $subject, $view, $data, $attachments);
    }

    /**
     * Envoyer un email de notification générique
     *
     * @param string|array $to
     * @param string $subject
     * @param string $title
     * @param string $message
     * @param array $additionalData
     * @return bool
     */
    public function sendNotification($to, string $subject, string $title, string $message, array $additionalData = []): bool
    {
        $data = array_merge([
            'title' => $title,
            'message' => $message,
        ], $additionalData);

        return $this->sendWithTemplate($to, $subject, 'notification', $data);
    }

    /**
     * Envoyer un email en masse
     *
     * @param array $recipients
     * @param string $subject
     * @param string $view
     * @param array $data
     * @return array
     */
    public function sendBulkEmail(array $recipients, string $subject, string $view, array $data = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->sendEmail($recipient, $subject, $view, $data);
        }

        return $results;
    }

    /**
     * Envoyer un email avec une classe Mailable personnalisée
     *
     * @param string|array $to
     * @param Mailable $mailable
     * @return bool
     */
    public function sendMailable($to, Mailable $mailable): bool
    {
        try {
            Mail::to($to)->send($mailable);
            return true;
        } catch (Exception $e) {
            \Log::error('Erreur lors de l\'envoi d\'email Mailable: ' . $e->getMessage());
            return false;
        }
    }
}
