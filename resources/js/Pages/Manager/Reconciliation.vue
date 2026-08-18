<script setup>
import { computed } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Banknote, Wallet, Truck, Receipt, CheckCircle2 } from 'lucide-vue-next';

// Komponenty cząstkowe
import ReconciliationList from './Reconciliation/Partials/ReconciliationList.vue';

const props = defineProps({
    driversData: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

// KPI: Przeliczanie sumarycznych wartości w terenie
const totalCashInField = computed(() => {
    return props.driversData.reduce((sum, item) => sum + (Number(item.cash_to_collect) || 0), 0);
});

const totalDriversWithCash = computed(() => {
    return props.driversData.filter(item => (Number(item.cash_to_collect) || 0) > 0).length;
});

const totalPendingOrders = computed(() => {
    return props.driversData.reduce((sum, item) => sum + (Number(item.orders_count) || 0), 0);
});

// Funkcja zatwierdzająca odbiór gotówki
const handleSettleDriver = ({ driverId, name, amount }) => {
    if (confirm(`Czy na pewno odebrałeś od kuriera [${name}] pełną kwotę ${Number(amount).toFixed(2)} zł i chcesz zamknąć jego zmianę finansową?`)) {
        router.post(route('manager.reconciliation.settle', driverId), {}, {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Rozliczenia Kurierów - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK MODUŁU -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <Banknote class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-emerald-400 uppercase tracking-wider">Rozliczenia Gotówkowe Kurierów</h1>
                        <p class="text-xs text-slate-400">Kontroluj stan gotówki w terenie, przeglądaj dostarczone bony i zamykaj raporty kasowe.</p>
                    </div>
                </div>
            </header>

            <!-- KOMUNIKAT FLASH -->
            <div v-if="successMessage" class="bg-emerald-950/60 border border-emerald-900 text-emerald-400 p-3.5 rounded-2xl text-xs font-bold flex items-center space-x-2 shadow-lg">
                <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
                <span>{{ successMessage }}</span>
            </div>

            <!-- PODSUMOWANIE KPI -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-slate-900 border border-slate-800 p-4 rounded-3xl flex items-center space-x-4 shadow-xl">
                    <div class="bg-amber-500/10 p-3 rounded-2xl text-amber-500 border border-amber-500/20">
                        <Wallet class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Gotówka w terenie</span>
                        <span class="text-xl font-black text-amber-400 font-mono">{{ totalCashInField.toFixed(2) }} zł</span>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-4 rounded-3xl flex items-center space-x-4 shadow-xl">
                    <div class="bg-emerald-500/10 p-3 rounded-2xl text-emerald-400 border border-emerald-500/20">
                        <Truck class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Nierozliczeni Kurierzy</span>
                        <span class="text-xl font-black text-white font-mono">{{ totalDriversWithCash }} os.</span>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-4 rounded-3xl flex items-center space-x-4 shadow-xl">
                    <div class="bg-blue-500/10 p-3 rounded-2xl text-blue-400 border border-blue-500/20">
                        <Receipt class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Bony Gotówkowe</span>
                        <span class="text-xl font-black text-blue-400 font-mono">{{ totalPendingOrders }} szt.</span>
                    </div>
                </div>
            </div>

            <!-- LISTA KURIERÓW DO ROZLICZENIA -->
            <ReconciliationList 
                :drivers-data="driversData"
                @settle-driver="handleSettleDriver"
            />

        </div>
    </AuthenticatedLayout>
</template>