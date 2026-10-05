<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Mail\DynamicSaaSMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SaaSMailer
{
    /**
     * @param string $eventKey - klucz maila (np. 'welcome_tenant')
     * @param string $toEmail - adres odbiorcy
     * @param array $variables - tablica asocjacyjna, np. ['admin_name' => 'Jan']
     * @param array $attachments - tablica ścieżek bezwzględnych do załączników (np. faktur PDF)
     */
    public static function send(string $eventKey, string $toEmail, array $variables = [], array $attachments = [])
    {
        $template = EmailTemplate::on('mysql')->where('event_key', $eventKey)->first();

        // Jeśli szablon nie istnieje lub Super Admin go wyłączył - przerywamy
        if (!$template || !$template->is_active) {
            return false;
        }

        // Kopiujemy treść i temat, aby podmienić w nich tagi
        $subject = $template->subject;
        $body = $template->body;

        // Podmiana {zmiennych} w temacie i treści
        foreach ($variables as $key => $value) {
            $search = '{' . $key . '}';
            $subject = str_replace($search, $value, $subject);
            $body = str_replace($search, $value, $body);
        }

        try {
            // Dodajemy Bcc do Ciebie (opcjonalnie)
            $mail = Mail::to($toEmail)->bcc('kontakt@ingastro.pl');
            
            // Wysyłamy asynchronicznie przez kolejkę (queue)
            $mail->queue(new DynamicSaaSMail($subject, $body, $attachments));
            return true;
        } catch (\Exception $e) {
            Log::error("Błąd wysyłki SaaS Email [{$eventKey}]: " . $e->getMessage());
            return false;
        }
    }
}