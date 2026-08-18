<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Map, X, Save } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    zone: Object,
    drivers: Array
});

const emit = defineEmits(['close']);

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

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.zone) {
            form.name = props.zone.name;
            form.price = props.zone.price;
            form.min_order_price = props.zone.min_order_price;
            form.free_delivery_from = props.zone.free_delivery_from;
            form.max_distance_km = props.zone.max_distance_km;
            form.default_driver_id = props.zone.default_driver_id;
            form.color_code = props.zone.color_code || '#f97316';
            form.is_active = Boolean(props.zone.is_active);
        } else {
            form.reset();
            form.price = 8.00;
            form.min_order_price = 30.00;
            form.free_delivery_from = 60.00;
            form.max_distance_km = 5;
            form.color_code = '#f97316';
            form.is_active = true;
        }
    }
});

const submitForm = () => {
    if (props.zone) {
        form.put(route('manager.delivery_zones.update', props.zone.id), {
            preserveScroll: true,
            onSuccess: () => emit('close')
        });
    } else {
        form.post(route('manager.delivery_zones.store'), {
            preserveScroll: true,
            onSuccess: () => emit('close')
        });
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
            
            <div class="border-b border-slate-800 pb-3 flex justify-between items-center">
                <h3 class="text-xs font-bold text-amber-500 uppercase tracking-widest flex items-center space-x-2">
                    <Map class="w-4 h-4" />
                    <span>{{ zone ? 'Edycja Strefy Dostaw' : 'Nowa Strefa Dostaw' }}</span>
                </h3>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white p-1 cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-3.5 text-xs">
                <div>
                    <label class="block text-slate-400 uppercase font-bold mb-1">Nazwa strefy</label>
                    <input 
                        v-model="form.name" 
                        type="text" 
                        required 
                        placeholder="np. Strefa 1 - Centrum" 
                        class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white focus:border-red-500" 
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 uppercase font-bold mb-1">Koszt dostawy (zł)</label>
                        <input 
                            v-model="form.price" 
                            type="number" 
                            step="0.50" 
                            required 
                            class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-emerald-400 font-mono font-bold focus:border-red-500" 
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 uppercase font-bold mb-1">Max promień (km)</label>
                        <input 
                            v-model="form.max_distance_km" 
                            type="number" 
                            placeholder="np. 5" 
                            class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-red-500" 
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 uppercase font-bold mb-1">Min. koszyk (zł)</label>
                        <input 
                            v-model="form.min_order_price" 
                            type="number" 
                            step="1" 
                            class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-red-500" 
                        />
                    </div>
                    <div>
                        <label class="block text-slate-400 uppercase font-bold mb-1">Free dostawa od (zł)</label>
                        <input 
                            v-model="form.free_delivery_from" 
                            type="number" 
                            step="1" 
                            placeholder="Brak" 
                            class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-amber-400 font-mono font-bold focus:border-red-500" 
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 uppercase font-bold mb-1">Domyślnie przypisany kierowca</label>
                    <select v-model="form.default_driver_id" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500 cursor-pointer">
                        <option :value="null">-- Brak (Przypisz dowolnego) --</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option>
                    </select>
                </div>

                <div class="flex items-center justify-between py-1">
                    <label class="block text-slate-400 uppercase font-bold">Kolor na mapie</label>
                    <input v-model="form.color_code" type="color" class="bg-transparent h-8 w-12 cursor-pointer border-none" />
                </div>

                <div class="flex items-center space-x-2 py-1">
                    <input type="checkbox" v-model="form.is_active" id="zone_active" class="rounded border-slate-800 bg-[#0B0F19] text-red-600 focus:ring-0 h-4 w-4 cursor-pointer" />
                    <label for="zone_active" class="font-bold text-slate-300 uppercase select-none cursor-pointer">Strefa aktywna</label>
                </div>

                <div class="flex space-x-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="$emit('close')" class="w-1/3 bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-400 py-3 rounded-xl font-bold uppercase transition cursor-pointer">
                        Anuluj
                    </button>
                    <button type="submit" :disabled="form.processing" class="w-2/3 bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 font-bold text-white py-3 rounded-xl uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-1 cursor-pointer disabled:opacity-50">
                        <Save class="w-4 h-4" />
                        <span>{{ form.processing ? '...' : 'Zapisz Strefę' }}</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>