<script setup>
import { ref, computed } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Map, Plus, CheckCircle2 } from 'lucide-vue-next';

// Komponenty cząstkowe
import ZoneCardsGrid from './DeliveryZones/Partials/ZoneCardsGrid.vue';
import DeliveryZoneModal from './DeliveryZones/Partials/DeliveryZoneModal.vue';

const props = defineProps({
    zones: { type: Array, default: () => [] },
    drivers: { type: Array, default: () => [] }
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

// Stany okien modalnych
const isModalOpen = ref(false);
const selectedZone = ref(null);

const handleOpenCreate = () => {
    selectedZone.value = null;
    isModalOpen.value = true;
};

const handleOpenEdit = (zone) => {
    selectedZone.value = zone;
    isModalOpen.value = true;
};

const handleDelete = (zoneId) => {
    if (confirm('Czy na pewno chcesz usunąć tę strefę dostaw?')) {
        router.delete(route('manager.delivery_zones.destroy', zoneId), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Strefy Dostaw - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500">
                        <Map class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Zarządzanie Strefami Dostaw</h1>
                        <p class="text-xs text-slate-400">Definiuj opłaty, darmowe dostawy oraz przypisuj kierowców do rejonów miasta.</p>
                    </div>
                </div>

                <button 
                    @click="handleOpenCreate"
                    class="bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition shadow-lg flex items-center space-x-2 cursor-pointer shrink-0"
                >
                    <Plus class="w-4 h-4" />
                    <span>Dodaj Nową Strefę</span>
                </button>
            </header>

            <!-- KOMUNIKAT FLASH -->
            <div v-if="successMessage" class="bg-emerald-950/60 border border-emerald-900 text-emerald-400 p-3.5 rounded-2xl text-xs font-bold flex items-center space-x-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
                <span>{{ successMessage }}</span>
            </div>

            <!-- SIATKA CARDS STREF -->
            <ZoneCardsGrid 
                :zones="zones"
                @open-edit="handleOpenEdit"
                @delete="handleDelete"
            />

            <!-- MODAL FORMULARZA STREFY -->
            <DeliveryZoneModal 
                :is-open="isModalOpen"
                :zone="selectedZone"
                :drivers="drivers"
                @close="isModalOpen = false"
            />

        </div>
    </AuthenticatedLayout>
</template>