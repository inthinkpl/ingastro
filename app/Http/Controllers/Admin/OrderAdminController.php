<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class OrderAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items.variant.product', 'items.modifiers.ingredient']);

        // 1. FILTROWANIE PO DACIE
        if ($request->filled('preset_date')) {
            switch ($request->preset_date) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', Carbon::yesterday());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                    break;
            }
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
        } else {
            // Domyślnie pokazuje zamówienia z dzisiejszego dnia
            $query->whereDate('created_at', Carbon::today());
        }

        // 2. FILTROWANIE PO STATUSIE
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // 3. FILTROWANIE PO TYPIE (dostawa / wynos)
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // 4. WYSZUKIWARKA (ID zamówienia lub Adres)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%");
            });
        }

        // STATYSTYKI DLA PRZEFILTROWANEGO ZBIORU
        $statsQuery = clone $query;
        $stats = [
            'total_orders'  => (clone $statsQuery)->count(),
            'total_revenue' => (clone $statsQuery)->whereNotIn('status', ['anulowane'])->sum('total_price'),
            'delivery_count'=> (clone $statsQuery)->where('type', 'dostawa')->count(),
            'takeout_count' => (clone $statsQuery)->where('type', 'wynos')->count(),
        ];

        // 5. SORTOWANIE
        $sortBy  = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $orders = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders'  => $orders,
            'filters' => $request->only(['preset_date', 'date_from', 'date_to', 'status', 'type', 'search', 'sort_by', 'sort_dir']),
            'stats'   => $stats
        ]);
    }

    // Aktualizacja statusu zamówienia z panelu admina/managera
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $order->update(['status' => $request->status]);

        return back()->with('message', 'Status zamówienia został zaktualizowany.');
    }
}