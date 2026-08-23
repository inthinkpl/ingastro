<script setup>
import { 
    Gift, Loader2, ShieldCheck, Coins, ArrowLeft, Send 
} from 'lucide-vue-next';

defineProps({
    form: { type: Object, required: true },
    isLoyaltyEligible: { type: Boolean, default: false },
    loyaltyDiscountAmount: { type: Number, default: 0 },
    showOtpInput: { type: Boolean, default: false },
    otpCode: { type: String, default: '' },
    isSendingOtp: { type: Boolean, default: false },
    isVerifyingOtp: { type: Boolean, default: false },
    loyaltyMessage: { type: String, default: '' },
    appliedDiscount: { type: Object, default: null },
    discountCodeInput: { type: String, default: '' },
    isValidatingCode: { type: Boolean, default: false },
    pointsToEarn: { type: Number, default: 0 },
    cartTotal: { type: Number, required: true },
    minOrderWarning: { type: String, default: null }
});

defineEmits([
    'update:otpCode', 'update:discountCodeInput', 
    'send-otp', 'verify-otp', 'apply-discount', 'remove-discount', 
    'go-to-step2', 'checkout'
]);
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Forma płatności:</label>
            <div class="grid grid-cols-3 gap-1.5">
                <button 
                    @click="form.payment_method = 'blik'" 
                    :class="form.payment_method === 'blik' ? 'bg-amber-500/20 text-amber-400 border-amber-500 font-bold' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                    class="py-2 rounded-xl text-[10px] border text-center cursor-pointer"
                >BLIK</button>
                <button 
                    @click="form.payment_method = 'payu'" 
                    :class="form.payment_method === 'payu' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500 font-bold' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                    class="py-2 rounded-xl text-[10px] border text-center cursor-pointer"
                >PayU</button>
                <button 
                    @click="form.payment_method = 'gotówka'" 
                    :class="form.payment_method === 'gotówka' ? 'bg-blue-500/20 text-blue-400 border-blue-500 font-bold' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                    class="py-2 rounded-xl text-[10px] border text-center cursor-pointer"
                >Gotówka</button>
            </div>
        </div>

        <!-- PROGRAM LOJALNOŚCIOWY -->
        <div v-if="isLoyaltyEligible && loyaltyDiscountAmount === 0" class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-3.5 space-y-2">
            <div class="flex items-center space-x-1.5 text-amber-400 font-bold text-xs">
                <Gift class="w-4 h-4" />
                <span>Masz punkty lojalnościowe!</span>
            </div>
            <p class="text-[10px] text-slate-300 leading-relaxed">
                Ten numer posiada punkty kwalifikujące się do rabatu. Wyślij kod SMS, aby odblokować zniżkę.
            </p>

            <button 
                v-if="!showOtpInput"
                @click="$emit('send-otp')"
                :disabled="isSendingOtp"
                type="button"
                class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs py-2 rounded-xl uppercase cursor-pointer flex items-center justify-center space-x-1"
            >
                <Loader2 v-if="isSendingOtp" class="w-3.5 h-3.5 animate-spin" />
                <span v-else>Wyślij Kod SMS</span>
            </button>

            <div v-else class="space-y-2 pt-1">
                <input 
                    :value="otpCode"
                    @input="$emit('update:otpCode', $event.target.value)"
                    type="text"
                    maxlength="4"
                    placeholder="Wpisz 4-cyfrowy kod z SMS"
                    class="w-full bg-[#0B0F19] border border-amber-500 rounded-xl p-2 text-center text-xs text-white font-mono tracking-widest"
                />
                <button 
                    @click="$emit('verify-otp')"
                    :disabled="isVerifyingOtp || otpCode.length < 4"
                    type="button"
                    class="w-full bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs py-2 rounded-xl uppercase cursor-pointer flex items-center justify-center space-x-1"
                >
                    <Loader2 v-if="isVerifyingOtp" class="w-3.5 h-3.5 animate-spin" />
                    <span v-else>Zastosuj Rabat</span>
                </button>
            </div>

            <p v-if="loyaltyMessage" class="text-[10px] text-amber-400 italic">
                {{ loyaltyMessage }}
            </p>
        </div>

        <div v-if="loyaltyDiscountAmount > 0" class="bg-emerald-950/60 border border-emerald-900 text-emerald-400 p-2.5 rounded-xl text-xs font-bold flex justify-between items-center">
            <span class="flex items-center space-x-1">
                <ShieldCheck class="w-4 h-4 text-emerald-400" />
                <span>Rabat lojalnościowy:</span>
            </span>
            <span class="font-mono text-sm">-{{ loyaltyDiscountAmount.toFixed(2) }} zł</span>
        </div>

        <!-- KOD RABATOWY -->
        <div class="bg-[#0B0F19] p-2.5 rounded-xl border border-slate-800 space-y-2">
            <div v-if="!appliedDiscount" class="flex space-x-2">
                <input 
                    :value="discountCodeInput"
                    @input="$emit('update:discountCodeInput', $event.target.value)"
                    type="text" 
                    placeholder="Kod rabatowy" 
                    class="w-2/3 bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white uppercase font-mono"
                />
                <button 
                    type="button"
                    @click="$emit('apply-discount')"
                    :disabled="!discountCodeInput || isValidatingCode"
                    class="w-1/3 bg-slate-800 hover:bg-red-600 disabled:opacity-50 text-white font-bold rounded-xl text-xs uppercase cursor-pointer flex items-center justify-center"
                >
                    <Loader2 v-if="isValidatingCode" class="w-3.5 h-3.5 animate-spin" />
                    <span v-else>Użyj</span>
                </button>
            </div>

            <div v-if="appliedDiscount" class="flex justify-between items-center text-xs">
                <span class="font-mono font-bold text-emerald-400 uppercase">{{ appliedDiscount.code }}</span>
                <button type="button" @click="$emit('remove-discount')" class="text-slate-500 hover:text-red-400 font-bold cursor-pointer">✕</button>
            </div>
        </div>

        <!-- PUNKTY KROK 3 -->
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-3 flex items-center justify-between text-xs">
            <div class="flex items-center space-x-2 text-amber-400 font-bold">
                <Coins class="w-4 h-4 text-amber-400" />
                <span>Zyskasz za to zamówienie:</span>
            </div>
            <span class="font-mono font-black text-amber-400 bg-amber-500/20 px-2.5 py-1 rounded-lg border border-amber-500/40 text-xs">
                +{{ pointsToEarn }} pkt
            </span>
        </div>

        <div class="flex justify-between items-center pt-2 border-t border-slate-800">
            <span class="text-xs text-slate-300 uppercase font-bold">Do zapłaty:</span>
            <span class="text-2xl font-black font-mono text-emerald-400">{{ cartTotal.toFixed(2) }} zł</span>
        </div>

        <div class="flex space-x-2">
            <button 
                @click="$emit('go-to-step2')"
                class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-3.5 rounded-xl text-xs uppercase cursor-pointer flex items-center justify-center space-x-1"
            >
                <ArrowLeft class="w-4 h-4" />
                <span>Wstecz</span>
            </button>

            <button 
                @click="$emit('checkout')"
                :disabled="!form.phone || (form.type === 'dostawa' && (!form.delivery_address || minOrderWarning)) || form.processing"
                class="w-2/3 bg-red-600 hover:bg-red-700 disabled:bg-slate-800 disabled:text-slate-600 text-white font-bold py-3.5 rounded-xl text-xs uppercase cursor-pointer flex items-center justify-center space-x-2 shadow-lg"
            >
                <Send class="w-4 h-4" />
                <span>{{ form.processing ? 'Wysyłanie...' : 'Wyślij zamówienie' }}</span>
            </button>
        </div>
    </div>
</template>