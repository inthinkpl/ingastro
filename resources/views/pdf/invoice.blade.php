<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Faktura VAT {{ $invoice->number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header-table { w-full; width: 100%; margin-bottom: 30px; }
        .title { font-size: 20px; font-weight: bold; color: #d97706; text-transform: uppercase; }
        .section-title { font-weight: bold; margin-bottom: 5px; border-b: 1px solid #ccc; padding-bottom: 3px; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table th, .details-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .details-table th { background-color: #f3f4f6; }
        .text-right { text-align: right; }
        .total-box { margin-top: 20px; text-align: right; font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <div class="title">FAKTURA VAT</div>
                <div><strong>Numer:</strong> {{ $invoice->number }}</div>
                <div><strong>Data wystawienia:</strong> {{ $invoice->created_at->format('Y-m-d') }}</div>
            </td>
            <td class="text-right">
                <strong>SAVONA ERP SaaS System</strong><br>
                ul. Główna 1, 00-001 Warszawa<br>
                NIP: 1234567890
            </td>
        </tr>
    </table>

    <table class="header-table">
        <tr>
            <td width="50%">
                <div class="section-title">SPRZEDAWCA:</div>
                <strong>SAVONA ERP Sp. z o.o.</strong><br>
                ul. Główna 1<br>
                00-001 Warszawa<br>
                NIP: 1234567890
            </td>
            <td width="50%">
                <div class="section-title">NABYWCA:</div>
                <strong>{{ $restaurant_name }}</strong><br>
                ID Tenanta: {{ $tenant->id }}<br>
                Domena: {{ $tenant->domains->first()?->domain }}
            </td>
        </tr>
    </table>

    <table class="details-table">
        <thead>
            <tr>
                <th>Poz.</th>
                <th>Nazwa usługi / Plan</th>
                <th>Okres rozliczeniowy</th>
                <th class="text-right">Kwota Netto</th>
                <th class="text-right">VAT</th>
                <th class="text-right">Kwota Brutto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Subskrypcja SaaS - Plan {{ $invoice->plan_name }}</td>
                <td>{{ $invoice->created_at->format('Y-m-d') }} - {{ $invoice->created_at->addMonth()->format('Y-m-d') }}</td>
                <td class="text-right">{{ number_format($invoice->amount_net, 2, ',', ' ') }} zł</td>
                <td class="text-right">23%</td>
                <td class="text-right">{{ number_format($invoice->amount_gross, 2, ',', ' ') }} zł</td>
            </tr>
        </tbody>
    </table>

    <div class="total-box">
        Razem do zapłaty: {{ number_format($invoice->amount_gross, 2, ',', ' ') }} PLN
    </div>

    <div style="margin-top: 40px; font-size: 10px; color: #777; text-align: center;">
        Dziękujemy za korzystanie z systemu SAVONA ERP. Dokument wygenerowany elektronicznie, nie wymaga podpisu.
    </div>

</body>
</html>