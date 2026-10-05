<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'event_key' => 'welcome_tenant',
                'name' => 'Powitanie Nowego Klienta (Rejestracja)',
                'subject' => 'Witaj w InGastro! Twój system dla {restaurant_name} jest gotowy.',
                'body' => '<p>Cześć {admin_name},</p><p>Witamy na pokładzie! Twój system dla lokalu <strong>{restaurant_name}</strong> został pomyślnie utworzony.</p><p>Możesz zalogować się do swojego panelu pod adresem:<br><a href="{login_url}">{login_url}</a></p><p>Rozpoczęliśmy Twój 14-dniowy darmowy okres próbny. Życzymy samych sukcesów!</p>',
                'available_variables' => ['admin_name' => 'Imię admina', 'restaurant_name' => 'Nazwa lokalu', 'login_url' => 'Link do panelu logowania'],
            ],
            [
                'event_key' => 'subscription_updated',
                'name' => 'Zmiana Pakietu Modułów (A la Carte)',
                'subject' => 'Twoja subskrypcja InGastro została zaktualizowana',
                'body' => '<p>Witaj {admin_name},</p><p>Twoja konfiguracja modułów w lokalu {restaurant_name} została pomyślnie zmieniona.</p><p>Twoje aktywne moduły to: <strong>{active_modules}</strong>.</p><p>Nowa kwota subskrypcji to: <strong>{mrr_amount} zł / mies.</strong></p>',
                'available_variables' => ['admin_name' => 'Imię admina', 'restaurant_name' => 'Nazwa lokalu', 'active_modules' => 'Lista aktywnych modułów', 'mrr_amount' => 'Nowa kwota abonamentu'],
            ],
            [
                'event_key' => 'invoice_generated',
                'name' => 'Nowa Faktura za Subskrypcję',
                'subject' => 'Faktura nr {invoice_number} od InGastro',
                'body' => '<p>Witaj,</p><p>W załączniku przesyłamy fakturę nr {invoice_number} za korzystanie z systemu InGastro.</p><p>Kwota do zapłaty / pobrana: <strong>{invoice_amount} zł</strong>.</p><p>Dziękujemy za współpracę!</p>',
                'available_variables' => ['invoice_number' => 'Numer faktury', 'invoice_amount' => 'Kwota na fakturze brutto'],
            ]
        ];

        foreach ($templates as $t) {
            EmailTemplate::updateOrCreate(['event_key' => $t['event_key']], $t);
        }
    }
}