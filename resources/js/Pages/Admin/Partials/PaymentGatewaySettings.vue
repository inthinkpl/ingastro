<script setup>
import { useForm } from '@inertiajs/vue3';
import { CreditCard, Save } from 'lucide-vue-next';

const props = defineProps({
    currentGateway: String,
    payuEnv: String,
    payuPosId: String,
    payuClientId: String,
    payuClientSecret: String,
    payuSecondKey: String,
});

const form = useForm({
    payment_gateway: props.currentGateway,
    payu_env: props.payuEnv,
    payu_pos_id: props.payuPosId,
    payu_client_id: props.payuClientId,
    payu_client_secret: props.payuClientSecret,
    payu_second_key: props.payuSecondKey
});

const saveSettings = () => {
    form.post(route('admin.settings.save'), {
        preserveScroll: true,
        onSuccess: () => alert('Konfiguracja płatności została pomyślnie zaktualizowana!')
    });
};
</script>

<template>
    <form @submit.prevent="saveSettings" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-xl text-xs">
        <h2 class="text-xs font-bold uppercase tracking-widest text-slate-300 border-b border-slate-800 pb-2 flex items-center space-x-2">
            <CreditCard class="w-4 h-4 text-amber-500" />
            <span>Aktywny Sterownik Płatności Online</span>
        </h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <label :class="form.payment_gateway === 'simulation' ? 'border-amber-500 bg-amber-500/10' : 'border-slate-800 bg-[#0B0F19]'" class="p-4 rounded-2xl border flex items-center space-x-3 cursor-pointer transition select-none">
                <input type="radio" v-model="form.payment_gateway" value="simulation" class="text-red-600 focus:ring-0 bg-[#0B0F19] border-slate-800" />
                <div>
                    <div class="font-bold text-slate-200 uppercase text-[10px] tracking-wide">Symulator BLIK</div>
                    <div class="text-[9px] text-slate-500 mt-0.5">Lokalny ekran testowy</div>
                </div>
            </label>

            <label :class="form.payment_gateway === 'payu' ? 'border-amber-500 bg-amber-500/10' : 'border-slate-800 bg-[#0B0F19]'" class="p-4 rounded-2xl border flex items-center space-x-3 cursor-pointer transition select-none">
                <input type="radio" v-model="form.payment_gateway" value="payu" class="text-red-600 focus:ring-0 bg-[#0B0F19] border-slate-800" />
                <div>
                    <div class="font-bold text-slate-200 uppercase text-[10px] tracking-wide">Bramka PayU</div>
                    <div class="text-[9px] text-slate-500 mt-0.5">REST API v2.1 (Polska)</div>
                </div>
            </label>

            <label :class="form.payment_gateway === 'stripe' ? 'border-amber-500 bg-amber-500/10' : 'border-slate-800 bg-[#0B0F19]'" class="p-4 rounded-2xl border flex items-center space-x-3 cursor-pointer transition select-none">
                <input type="radio" v-model="form.payment_gateway" value="stripe" class="text-red-600 focus:ring-0 bg-[#0B0F19] border-slate-800" />
                <div>
                    <div class="font-bold text-slate-200 uppercase text-[10px] tracking-wide">Bramka Stripe</div>
                    <div class="text-[9px] text-slate-500 mt-0.5">Karty międzynarodowe</div>
                </div>
            </label>
        </div>

        <div v-if="form.payment_gateway === 'payu'" class="space-y-4 pt-4 border-t border-slate-800">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-amber-400 uppercase tracking-wider text-[10px]">Klucze Autoryzacji PayU</h3>
                <div class="flex bg-[#0B0F19] p-1 rounded-xl border border-slate-800">
                    <button type="button" @click="form.payu_env = 'sandbox'" :class="form.payu_env === 'sandbox' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-500'" class="px-2.5 py-0.5 rounded-lg text-[9px] uppercase transition cursor-pointer">Sandbox</button>
                    <button type="button" @click="form.payu_env = 'production'" :class="form.payu_env === 'production' ? 'bg-red-600 text-white font-bold' : 'text-slate-500'" class="px-2.5 py-0.5 rounded-lg text-[9px] uppercase transition cursor-pointer">Production</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Merchant POS ID</label>
                    <input v-model="form.payu_pos_id" type="text" placeholder="np. 300742" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-red-500" required />
                </div>
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">OAuth Client ID</label>
                    <input v-model="form.payu_client_id" type="text" placeholder="np. 300742" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-red-500" required />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">OAuth Client Secret</label>
                    <input v-model="form.payu_client_secret" type="password" placeholder="••••••••••••••••••••••••" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-red-500" required />
                </div>
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Second Key MD5</label>
                    <input v-model="form.payu_second_key" type="password" placeholder="••••••••••••••••••••••••" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-red-500" required />
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-800">
            <button type="submit" :disabled="form.processing" class="bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-bold px-8 py-3 rounded-xl uppercase tracking-wider text-xs transition shadow-lg flex items-center space-x-2 cursor-pointer disabled:opacity-50">
                <Save class="w-4 h-4" />
                <span>{{ form.processing ? 'Zapisywanie...' : 'Zapisz Konfigurację Płatności' }}</span>
            </button>
        </div>
    </form>
</template>