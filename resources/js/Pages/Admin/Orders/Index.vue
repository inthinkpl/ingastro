<script setup>
import { ref } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ShoppingBag, RefreshCw } from 'lucide-vue-next';

// Komponenty cząstkowe
import OrderStatsCards from './Partials/OrderStatsCards.vue';
import OrderFiltersPanel from './Partials/OrderFiltersPanel.vue';
import OrdersTable from './Partials/OrdersTable.vue';
import OrderDetailModal from './Partials/OrderDetailModal.vue';

const props = defineProps({
    orders: Object,
    filters: Object,
    stats: Object
});

// Stan filtrów
const filterForm = ref({
    preset_date: props.filters.preset_date || 'today',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    status: props.filters.status || 'all',
    type: props.filters.type || 'all',
    search: props.filters.search || '',
    sort_by: props.filters.sort_by || 'created_at',
    sort_dir: props.filters.sort_dir || 'desc'
});

// Modal ze szczegółami
const selectedOrder = ref(null);
const isDetailModalOpen = ref(false);

const applyFilters = () => {
    router.get(route('admin.orders.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const handleSetPresetDate = (preset) => {
    filterForm.value.preset_date = preset;
    filterForm.value.date_from = '';
    filterForm.value.date_to = '';
    applyFilters();
};

const handleCustomDateChange = () => {
    filterForm.value.preset_date = '';
    applyFilters();
};

const handleToggleSort = (column) => {
    if (filterForm.value.sort_by === column) {
        filterForm.value.sort_dir = filterForm.value.sort_dir === 'asc' ? 'desc' : 'asc';
    } else {
        filterForm.value.sort_by = column;
        filterForm.value.sort_dir = 'desc';
    }
    applyFilters();
};

const handleOpenDetails = (order) => {
    selectedOrder.value = order;
    isDetailModalOpen.value = true;
};

const handleUpdateOrderStatus = ({ orderId, status }) => {
    router.patch(route('admin.orders.update-status', orderId), {
        status: status
    }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedOrder.value && selectedOrder.value.id === orderId) {
                selectedOrder.value.status = status;
            }
        }
    });
};
</script>

<template>
    <Head title="Menedżer Zamówień - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK -->
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-red-600/10 border border-red-500/20 flex items-center justify-center text-red-500">
                        <ShoppingBag class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Menedżer & Lista Zamówień</h1>
                        <p class="text-xs text-slate-400">Podgląd, filtrowanie oraz zarządzenie procesem kuchennym i dostawami.</p>
                    </div>
                </div>

                <button 
                    @click="applyFilters" 
                    class="bg-slate-900 hover:bg-slate-800 text-slate-200 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition flex items-center space-x-2 border border-slate-800 cursor-pointer shadow-lg"
                >
                    <RefreshCw class="w-4 h-4 text-amber-500" />
                    <span>Odśwież Listę</span>
                </button>
            </header>

            <!-- KARTY STATYSTYK -->
            <OrderStatsCards :stats="stats" />

            <!-- PANEL FILTRÓW -->
            <OrderFiltersPanel 
                v-model:filters="filterForm"
                @apply="applyFilters"
                @set-preset="handleSetPresetDate"
                @custom-date-change="handleCustomDateChange"
            />

            <!-- TABELA ZAMÓWIEŃ -->
            <OrdersTable 
                :orders="orders"
                :current-sort-by="filterForm.sort_by"
                @toggle-sort="handleToggleSort"
                @open-details="handleOpenDetails"
            />

            <!-- MODAL SZCZEGÓŁÓW ZAMÓWIENIA -->
            <OrderDetailModal 
                :is-open="isDetailModalOpen"
                :order="selectedOrder"
                @close="isDetailModalOpen = false"
                @update-status="handleUpdateOrderStatus"
            />

        </div>
    </AuthenticatedLayout>
</template>