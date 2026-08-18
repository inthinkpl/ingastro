<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Clock, Users, Calendar, Plus, Download, DollarSign } from 'lucide-vue-next';

// Partials
import ActiveShiftsCards from './Partials/ActiveShiftsCards.vue';
import WorkHistoryTable from './Partials/WorkHistoryTable.vue';
import ManualShiftModal from './Partials/ManualShiftModal.vue';

const props = defineProps({
    activeShifts: Array,   // Obecnie pracujący na żywo
    historyShifts: Object, // Paginowana historia zmian
    users: Array,          // Lista pracowników do filtrów
    stats: Object,         // Dzisiejsze podsumowania (np. łączny czas, szacowany koszt)
    filters: Object
});

const activeTab = ref('live'); // 'live' | 'history'
const isManualModalOpen = ref(false);
const selectedShiftToEdit = ref(null);

const handleOpenCreateShift = () => {
    selectedShiftToEdit.value = null;
    isManualModalOpen.value = true;
};

const handleOpenEditShift = (shift) => {
    selectedShiftToEdit.value = shift;
    isManualModalOpen.value = true;
};

const handleExportCsv = () => {
    window.location.href = route('admin.rcp.export', props.filters);
};
</script>

<template>
    <Head title="Rejestracja Czasu Pracy (RCP) - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK -->
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500">
                        <Clock class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Ewidencja i Czas Pracy (RCP)</h1>
                        <p class="text-xs text-slate-400">Karta obecności na żywo, rozliczenia godzinowe i historia zmian zespołu.</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button 
                        @click="handleExportCsv"
                        class="bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 px-3.5 py-2 rounded-xl text-xs font-bold uppercase transition flex items-center space-x-2 cursor-pointer"
                    >
                        <Download class="w-4 h-4 text-emerald-400" />
                        <span>Eksport CSV</span>
                    </button>

                    <button 
                        @click="handleOpenCreateShift"
                        class="bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-bold text-xs uppercase tracking-wider px-4 py-2 rounded-xl transition shadow-lg flex items-center space-x-2 cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Ręczny Wpis Zmiany</span>
                    </button>
                </div>
            </header>

            <!-- PODSUMOWANIE DZISIEJSZEGO DNIA (KPI) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex items-center space-x-4">
                    <div class="bg-emerald-500/10 p-3 rounded-xl text-emerald-400 border border-emerald-500/20">
                        <Users class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Obecnie w pracy</span>
                        <span class="text-xl font-black text-white font-mono">{{ activeShifts?.length || 0 }} os.</span>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex items-center space-x-4">
                    <div class="bg-amber-500/10 p-3 rounded-xl text-amber-500 border border-amber-500/20">
                        <Clock class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Godziny Dzisiaj (Razem)</span>
                        <span class="text-xl font-black text-amber-400 font-mono">{{ stats?.total_hours_today || '0.0' }} h</span>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex items-center space-x-4">
                    <div class="bg-purple-500/10 p-3 rounded-xl text-purple-400 border border-purple-500/20">
                        <DollarSign class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Estymowany Koszt Pracy</span>
                        <span class="text-xl font-black text-purple-400 font-mono">{{ Number(stats?.labor_cost_today || 0).toFixed(2) }} zł</span>
                    </div>
                </div>
            </div>

            <!-- PODMENUU / PRZEŁĄCZNIK ZAKŁADEK -->
            <div class="flex border-b border-slate-800 space-x-4">
                <button 
                    @click="activeTab = 'live'"
                    :class="activeTab === 'live' ? 'border-amber-500 text-amber-500' : 'border-transparent text-slate-400 hover:text-white'"
                    class="pb-3 text-xs font-bold uppercase tracking-wider border-b-2 transition flex items-center space-x-2 cursor-pointer"
                >
                    <Users class="w-4 h-4" />
                    <span>Na żywo w lokalu ({{ activeShifts?.length || 0 }})</span>
                </button>

                <button 
                    @click="activeTab = 'history'"
                    :class="activeTab === 'history' ? 'border-amber-500 text-amber-500' : 'border-transparent text-slate-400 hover:text-white'"
                    class="pb-3 text-xs font-bold uppercase tracking-wider border-b-2 transition flex items-center space-x-2 cursor-pointer"
                >
                    <Calendar class="w-4 h-4" />
                    <span>Historia & Ewidencja Zmian</span>
                </button>
            </div>

            <!-- ZAWARTOŚĆ ZAKŁADEK -->
            <div v-if="activeTab === 'live'">
                <ActiveShiftsCards :shifts="activeShifts" />
            </div>

            <div v-else>
                <WorkHistoryTable 
                    :shifts="historyShifts"
                    :users="users"
                    :filters="filters"
                    @edit-shift="handleOpenEditShift"
                />
            </div>

            <!-- MODAL RĘCZNEJ EDYCJI ZMIANY -->
            <ManualShiftModal 
                :is-open="isManualModalOpen"
                :shift="selectedShiftToEdit"
                :users="users"
                @close="isManualModalOpen = false"
            />

        </div>
    </AuthenticatedLayout>
</template>