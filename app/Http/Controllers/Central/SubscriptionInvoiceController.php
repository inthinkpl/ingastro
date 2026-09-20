<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Tenant;
use App\Models\SystemSetting;
use App\Models\SubscriptionInvoice;

class SubscriptionInvoiceController extends Controller
{
    /**
     * Pobieranie konkretnej faktury w formacie PDF
     */
    public function download(Request $request, string $invoiceId)
    {
        $tenant = tenant();

        if (!$tenant) {
            abort(403, 'Brak dostępu.');
        }

        $restaurantName = SystemSetting::get('restaurant_name', 'Pizzeria');
        $tenant->load(['plan', 'domains']);

        // Szukamy faktury w bazie lub budujemy obiekt awaryjny dla nowo utworzonych kont
        $invoice = SubscriptionInvoice::where('tenant_id', $tenant->id)
            ->where('id', $invoiceId)
            ->first();

        if (!$invoice) {
            $gross = (float) ($tenant->plan?->price_monthly ?? 0);
            $net = round($gross / 1.23, 2);
            $rawNumber = 'FV/' . date('Y/m/') . strtoupper(substr(md5($tenant->id . $invoiceId), 0, 6));

            $invoice = (object) [
                'number'       => $rawNumber,
                'created_at'   => now(),
                'paid_at'      => now(),
                'plan_name'    => $tenant->plan?->name ?? 'Standard',
                'amount_net'   => $net,
                'amount_gross' => $gross,
            ];
        }

        // Generowanie PDF
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'         => $invoice,
            'tenant'          => $tenant,
            'restaurant_name' => $restaurantName,
        ]);

        $safeFileName = str_replace(['/', '\\'], '-', $invoice->number);

        return $pdf->download("Faktura_{$safeFileName}.pdf");
    }
}