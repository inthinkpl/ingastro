<script setup>
import { useForm } from '@inertiajs/vue3';
import { Save, Loader2, Truck, Sparkles, Pizza, Store } from 'lucide-vue-next';

const props = defineProps({
    restaurantName: { type: String, default: '' },
    restaurantPhone: { type: String, default: '' },
    restaurantAddress: { type: String, default: '' },
    minOrderAmount: { type: [Number, String], default: 40.00 },
    isEcommerceActive: { type: Boolean, default: true },
    freeDeliveryEnabled: { type: Boolean, default: false },
    freeDeliveryMinAmount: { type: [Number, String], default: 60.00 },
    upsellEnabled: { type: Boolean, default: true },
    halfHalfEnabled: { type: Boolean, default: true },
    currentGateway: { type: String, default: 'simulation' },
    payuEnv: { type: String, default: 'sandbox' },
    payuPosId: { type: String, default: '' },
    payuClientId: { type: String, default: '' },
    payuClientSecret: { type: String, default: '' },
    payuSecondKey: { type: String, default: '' }
});

const form = useForm({
    restaurant_name: props.restaurantName,
    restaurant_phone: props.restaurantPhone,
    restaurant_address: props.restaurantAddress,
    min_order_amount: props.minOrderAmount,
    is_ecommerce_active: props.isEcommerceActive,
    free_delivery_enabled: props.freeDeliveryEnabled,
    free_delivery_min_amount: props.freeDeliveryMinAmount,
    upsell_enabled: props.upsellEnabled,
    half_half_enabled: props.halfHalfEnabled,
    payment_gateway: props.currentGateway,
    payu_env: props.payuEnv,
    payu_pos_id: props.payuPosId,
    payu_client_id: props.payuClientId,
    payu_client_secret: props.payuClientSecret,
    payu_second_key: props.payuSecondKey
});

const submit = () => {
    form.post(route('admin.settings.save'), {
        preserveScroll: true
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        
        <!-- SEKCJA STANU SKLEPU E-COMMERCE -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4 shadow-xl">
            <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wide border-b border-slate-800 pb-3 flex items-center space-x-2">
                <Store class="w-4 h-4 text-amber-500" />
                <span>Moduł Zamówień E-Commerce</span>
            </h3>

            <div class="bg-[#0B0F19] p-4 rounded-xl border border-slate-800 flex justify-between items-center">
                <div>
                    <span class="block text-xs font-bold text-slate-200 uppercase tracking-wide">Status Sklepu Internetowego</span>
                    <span class="text-[10px] text-slate-400">Wyłączenie sklepu zablokuje składanie zamówień online przez klientów (zostaną przekierowani na stronę techniczną). Administracja i Menedżerowie zachowają podgląd.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        v-model="form.is_ecommerce_active" 
                        class="sr-only peer"
                    >
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
            </div>
        </div>

        <!-- SEKCJA WIZYTÓWKI LOKALU -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
            <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wide border-b border-slate-800 pb-3">
                Dane Wizytówki Pizzerii
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide mb-1">
                        Nazwa Restauracji
                    </label>
                    <input 
                        v-model="form.restaurant_name" 
                        type="text" 
                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white" 
                        required 
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide mb-1">
                        Telefon kontaktowy
                    </label>
                    <input 
                        v-model="form.restaurant_phone" 
                        type="text" 
                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white" 
                    />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide mb-1">
                        Adres Lokalu
                    </label>
                    <input 
                        v-model="form.restaurant_address" 
                        type="text" 
                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white" 
                    />
                </div>
            </div>
        </div>

        <!-- SEKCJA MINIMALNEGO ZAMÓWIENIA I DARMOWEJ DOSTAWY -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
            <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wide border-b border-slate-800 pb-3 flex items-center space-x-2">
                <Truck class="w-4 h-4 text-amber-500" />
                <span>Zasady Dostawy & Progi Zamówień</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- MINIMALNA KWOTA ZAMÓWIENIA -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide mb-1">
                        Minimum w dostawie (zł)
                    </label>
                    <input 
                        v-model.number="form.min_order_amount" 
                        type="number" 
                        step="0.01" 
                        min="0"
                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white font-mono" 
                        required 
                    />
                    <p class="text-[10px] text-slate-500 mt-1">Poniżej tej kwoty klient nie będzie mógł złożyć zamówienia z dostawą.</p>
                </div>

                <!-- PASEK DARMOWEJ DOSTAWY -->
                <div class="space-y-3 bg-[#0B0F19] p-4 rounded-xl border border-slate-800">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="block text-xs font-bold text-slate-200 uppercase tracking-wide">Paseczek Darmowej Dostawy</span>
                            <span class="text-[10px] text-slate-400">Wyświetla pasek motywacyjny w koszyku klienta</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="form.free_delivery_enabled" 
                                class="sr-only peer"
                            >
                            <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>

                    <div v-if="form.free_delivery_enabled" class="pt-2 border-t border-slate-800">
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide mb-1">
                            Progowa kwota darmowej dostawy (zł)
                        </label>
                        <input 
                            v-model.number="form.free_delivery_min_amount" 
                            type="number" 
                            step="1" 
                            min="0"
                            placeholder="np. 60"
                            class="w-full bg-slate-900 border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white font-mono" 
                            required 
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- SEKCJA PIZZY PÓŁ NA PÓŁ -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
            <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wide border-b border-slate-800 pb-3 flex items-center space-x-2">
                <Pizza class="w-4 h-4 text-amber-500" />
                <span>Konfigurator Pizzy Pół na Pół</span>
            </h3>

            <div class="bg-[#0B0F19] p-4 rounded-xl border border-slate-800 flex justify-between items-center">
                <div>
                    <span class="block text-xs font-bold text-slate-200 uppercase tracking-wide">Moduł Pizzy Pół na Pół</span>
                    <span class="text-[10px] text-slate-400">Udostępnia klientom możliwość komponowania pizzy z dwóch osobnych połówek na banerze oraz w menu</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        v-model="form.half_half_enabled" 
                        class="sr-only peer"
                    >
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
            </div>
        </div>

        <!-- SEKCJA UP-SELLING / CZĘSTO ZAMAWIANE RAZEM -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
            <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wide border-b border-slate-800 pb-3 flex items-center space-x-2">
                <Sparkles class="w-4 h-4 text-amber-500" />
                <span>Rekomendacje & Dodatki w Koszyku (Upselling)</span>
            </h3>

            <div class="bg-[#0B0F19] p-4 rounded-xl border border-slate-800 flex justify-between items-center">
                <div>
                    <span class="block text-xs font-bold text-slate-200 uppercase tracking-wide">Pasek „Często zamawiane razem”</span>
                    <span class="text-[10px] text-slate-400">Automatycznie sugeruje w koszyku szybkie dodanie sosów i napojów 1-kliknięciem</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        v-model="form.upsell_enabled" 
                        class="sr-only peer"
                    >
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
            </div>
        </div>

        <!-- PRZYCISK ZAPISU -->
        <div class="flex justify-end">
            <button 
                type="submit" 
                :disabled="form.processing"
                class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-6 py-2.5 rounded-xl text-xs uppercase tracking-wider flex items-center space-x-2 transition cursor-pointer shadow-lg disabled:opacity-50"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <Save v-else class="w-4 h-4" />
                <span>Zapisz Ustawienia</span>
            </button>
        </div>

    </form>
</template>