<script setup>
import { useForm } from '@inertiajs/vue3';
import { MapPin, Truck, Save } from 'lucide-vue-next';

const props = defineProps({
    restaurantName: String,
    restaurantPhone: String,
    restaurantAddress: String,
    minOrderAmount: [Number, String]
});

const form = useForm({
    restaurant_name: props.restaurantName,
    restaurant_phone: props.restaurantPhone,
    restaurant_address: props.restaurantAddress,
    min_order_amount: props.minOrderAmount,
});

const saveSettings = () => {
    form.post(route('admin.settings.save'), {
        preserveScroll: true,
        onSuccess: () => alert('Ustawienia zostały pomyślnie zaktualizowane!')
    });
};
</script>

<template>
    <form @submit.prevent="saveSettings" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-xl text-xs">
        <div class="space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-widest text-slate-300 border-b border-slate-800 pb-2 flex items-center space-x-2">
                <MapPin class="w-4 h-4 text-amber-500" />
                <span>Dane Teleadresowe Lokalu</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Nazwa Pizzerii</label>
                    <input v-model="form.restaurant_name" type="text" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-3 text-white font-medium focus:border-red-500" required />
                </div>
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Telefon Kontaktowy</label>
                    <input v-model="form.restaurant_phone" type="text" placeholder="np. +48 785 555 455" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-3 text-white font-mono focus:border-red-500" />
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-400 uppercase mb-1">Adres Fizyczny Restauracji</label>
                <input v-model="form.restaurant_address" type="text" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-3 text-white font-medium focus:border-red-500" />
            </div>
        </div>

        <div class="space-y-4 pt-4 border-t border-slate-800">
            <h2 class="text-xs font-bold uppercase tracking-widest text-slate-300 border-b border-slate-800 pb-2 flex items-center space-x-2">
                <Truck class="w-4 h-4 text-amber-500" />
                <span>Zasady Dostaw i Koszyka</span>
            </h2>

            <div>
                <label class="block font-bold text-slate-400 uppercase mb-1">Minimalna kwota zamówienia w dostawie (zł)</label>
                <div class="relative max-w-xs">
                    <input v-model="form.min_order_amount" type="number" step="0.50" min="0" required class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-3 text-emerald-400 font-mono font-bold text-sm focus:border-red-500" />
                    <span class="absolute right-3 top-3 text-xs font-bold text-slate-500">PLN</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-800">
            <button type="submit" :disabled="form.processing" class="bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-bold px-8 py-3 rounded-xl uppercase tracking-wider text-xs transition shadow-lg flex items-center space-x-2 cursor-pointer disabled:opacity-50">
                <Save class="w-4 h-4" />
                <span>{{ form.processing ? 'Zapisywanie...' : 'Zapisz Wizytówkę i Zasady' }}</span>
            </button>
        </div>
    </form>
</template>