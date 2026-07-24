<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    zones: Array,
    drivers: Array,
});

const isModalOpen = ref(false);
const editingZoneId = ref(null);

const form = useForm({
    name: '',
    price: 8.00,
    min_order_price: 30.00,
    free_delivery_from: 60.00,
    max_distance_km: 5,
    default_driver_id: null,
    color_code: '#f97316',
    is_active: true,
});

const openCreateModal = () => {
    editingZoneId.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (zone) => {
    editingZoneId.value = zone.id;
    form.name = zone.name;
    form.price = zone.price;
    form.min_order_price = zone.min_order_price;
    form.free_delivery_from = zone.free_delivery_from;
    form.max_distance_km = zone.max_distance_km;
    form.default_driver_id = zone.default_driver_id;
    form.color_code = zone.color_code;
    form.is_active = Boolean(zone.is_active);
    isModalOpen.value = true;
};

const submitForm = () => {
    if (editingZoneId.value) {
        form.put(route('manager.delivery_zones.update', editingZoneId.value), {
            onSuccess: () => isModalOpen.value = false
        });
    } else {
        form.post(route('manager.delivery_zones.store'), {
            onSuccess: () => isModalOpen.value = false
        });
    }
};

const deleteZone = (zoneId) => {
    if (confirm('Czy na pewno chcesz usunąć tę strefę dostaw?')) {
        router.delete(route('manager.delivery_zones.destroy', zoneId));
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-white p-6 font-sans">
        <div class="max-w-6xl mx-auto space-y-6">
            
            <!-- NAGŁÓWEK -->
            <div class="flex justify-between items-center bg-slate-900 p-6 rounded-2xl border border-slate-850">
                <div>
                    <h1 class="text-xl font-black text-orange-400 uppercase tracking-wider">🗺️ Zarządzanie Strefami Dostaw</h1>
                    <p class="text-xs text-slate-400 mt-1">Definiuj opłaty, darmowe dostawy oraz przypisuj kierowców do konkretnych rejonów miasta.</p>
                </div>
                <button @click="openCreateModal" class="bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-500 hover:to-red-500 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-md">
                    + Dodaj Nową Strefę
                </button>
            </div>

            <!-- SIATKA CARDS STREF -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="zone in zones" :key="zone.id" class="bg-slate-900 border border-slate-850 rounded-2xl p-5 flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute top-0 left-0 h-1.5 w-full" :style="{ backgroundColor: zone.color_code }"></div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-start pt-1">
                            <div>
                                <h3 class="font-black text-base uppercase text-slate-100 tracking-wide">{{ zone.name }}</h3>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border" :class="zone.is_active ? 'bg-emerald-950/60 text-emerald-400 border-emerald-900' : 'bg-red-950/60 text-red-400 border-red-900'">
                                    {{ zone.is_active ? 'Aktywna' : 'Nieaktywna' }}
                                </span>
                            </div>
                            <span class="font-mono font-black text-lg text-emerald-400 bg-slate-950 px-2.5 py-1 rounded-xl border border-slate-850">
                                {{ zone.price }} zł
                            </span>
                        </div>

                        <div class="space-y-2 text-xs bg-slate-950 p-3 rounded-xl border border-slate-850">
                            <div class="flex justify-between text-slate-400">
                                <span>Promień strefy:</span>
                                <span class="font-mono font-bold text-slate-200">do {{ zone.max_distance_km || '∞' }} km</span>
                            </div>
                            <div class="flex justify-between text-slate-400">
                                <span>Min. wartość zamówienia:</span>
                                <span class="font-mono font-bold text-slate-200">{{ zone.min_order_price }} zł</span>
                            </div>
                            <div class="flex justify-between text-slate-400">
                                <span>Darmowa dostawa od:</span>
                                <span class="font-mono font-bold text-amber-400">{{ zone.free_delivery_from ? zone.free_delivery_from + ' zł' : 'Brak' }}</span>
                            </div>
                            <div class="flex justify-between text-slate-400 pt-1 border-t border-slate-900">
                                <span>Domyślny kurier:</span>
                                <span class="font-bold text-orange-400">{{ zone.default_driver ? zone.default_driver.name : 'Brak (Dowolny)' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- PRZYCISKI AKCJI -->
                    <div class="flex space-x-2 pt-4 mt-4 border-t border-slate-850">
                        <button @click="openEditModal(zone)" class="w-1/2 bg-slate-800 hover:bg-slate-750 text-slate-200 py-2 rounded-xl text-xs font-bold uppercase transition-colors">Edytuj</button>
                        <button @click="deleteZone(zone.id)" class="w-1/2 bg-red-950/40 hover:bg-red-900/60 text-red-400 py-2 rounded-xl text-xs font-bold uppercase transition-colors border border-red-900/30">Usuń</button>
                    </div>
                </div>
            </div>

            <!-- MODAL TWÓRZ / EDYTUJ -->
            <div v-if="isModalOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl space-y-4">
                    <h2 class="text-base font-black text-orange-400 uppercase tracking-wide">
                        {{ editingZoneId ? 'Edycja Strefy Dostaw' : 'Nowa Strefa Dostaw' }}
                    </h2>

                    <form @submit.prevent="submitForm" class="space-y-3 text-xs">
                        <div>
                            <label class="block text-slate-400 uppercase font-bold mb-1">Nazwa strefy</label>
                            <input v-model="form.name" type="text" required placeholder="np. Strefa 1 - Centrum" class="w-full bg-slate-950 border border-slate-850 rounded-xl p-2.5 text-white" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-400 uppercase font-bold mb-1">Koszt dostawy (zł)</label>
                                <input v-model="form.price" type="number" step="0.50" required class="w-full bg-slate-950 border border-slate-850 rounded-xl p-2.5 text-white" />
                            </div>
                            <div>
                                <label class="block text-slate-400 uppercase font-bold mb-1">Max promień (km)</label>
                                <input v-model="form.max_distance_km" type="number" placeholder="np. 5" class="w-full bg-slate-950 border border-slate-850 rounded-xl p-2.5 text-white" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-400 uppercase font-bold mb-1">Min. koszyk (zł)</label>
                                <input v-model="form.min_order_price" type="number" step="1" class="w-full bg-slate-950 border border-slate-850 rounded-xl p-2.5 text-white" />
                            </div>
                            <div>
                                <label class="block text-slate-400 uppercase font-bold mb-1">Free dostawa od (zł)</label>
                                <input v-model="form.free_delivery_from" type="number" step="1" placeholder="Brak" class="w-full bg-slate-950 border border-slate-850 rounded-xl p-2.5 text-white" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-400 uppercase font-bold mb-1">Domyślnie przypisany kierowca</label>
                            <select v-model="form.default_driver_id" class="w-full bg-slate-950 border border-slate-850 rounded-xl p-2.5 text-white">
                                <option :value="null">-- Brak (System przypisze wolnego) --</option>
                                <option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <label class="block text-slate-400 uppercase font-bold">Kolor na mapie</label>
                            <input v-model="form.color_code" type="color" class="bg-transparent h-8 w-12 cursor-pointer" />
                        </div>

                        <div class="flex space-x-3 pt-4 border-t border-slate-800">
                            <button type="button" @click="isModalOpen = false" class="w-1/3 bg-slate-800 py-3 rounded-xl font-bold uppercase text-slate-300">Anuluj</button>
                            <button type="submit" :disabled="form.processing" class="w-2/3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-500 hover:to-red-500 text-white font-black py-3 rounded-xl uppercase">Zapisz Strefę</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</template>