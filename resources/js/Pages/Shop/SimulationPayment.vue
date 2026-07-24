<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    order: { type: Object, required: true }
});

const blikCode = ref(['', '', '', '', '', '']);
const isProcessing = ref(false);
const timer = ref(120); // 2 minuty na autoryzację płatności
let timerInterval = null;

// Obsługa automatycznego przeskakiwania pól podczas wpisywania kodu BLIK
const handleInput = (e, index) => {
    const val = e.target.value;
    if (val.length >= 1) {
        blikCode.value[index] = val.substring(0, 1);
        if (index < 5) {
            document.getElementById(`blik-${index + 1}`).focus();
        }
    }
};

const handleKeyDown = (e, index) => {
    if (e.key === 'Backspace' && !blikCode.value[index] && index > 0) {
        document.getElementById(`blik-${index - 1}`).focus();
    }
};

const form = useForm({});

// Potwierdzenie płatności (Symulacja kliknięcia "OK" w aplikacji banku)
const submitPayment = () => {
    const codeString = blikCode.value.join('');
    if (codeString.length < 6) {
        alert('Proszę wprowadzić pełny, 6-cyfrowy kod BLIK.');
        return;
    }

    isProcessing.value = true;
    
    // Sztuczne opóźnienie (1.5 sekundy), aby zasymulować prawdziwe odpytywanie serwerów banku
    setTimeout(() => {
        form.post(route('payment.simulation.confirm', props.order.id), {
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    }, 1500);
};

onMounted(() => {
    timerInterval = setInterval(() => {
        if (timer.value > 0) timer.value--;
        else clearInterval(timerInterval);
    }, 1000);
});

onUnmounted(() => {
    clearInterval(timerInterval);
});
</script>

<template>
    <div class="min-h-screen bg-neutral-950 flex items-center justify-center p-4 font-sans text-white selection:bg-pink-600">
        
        <!-- MATRYCA TŁA BRAMKI -->
        <div class="absolute inset-0 opacity-5 bg-[radial-gradient(#e11d48_1px,transparent_1px)] [background-size:24px_24px] pointer-events-none"></div>

        <!-- PANEL CENTRALNY BRAMKI -->
        <div class="w-full max-w-md bg-neutral-900 border border-neutral-800 rounded-3xl p-6 shadow-2xl space-y-6 relative z-10">
            
            <!-- NAGŁÓWEK BRAMKI -->
            <header class="text-center border-b border-neutral-850 pb-4">
                <div class="text-[10px] uppercase font-black tracking-widest text-rose-500 bg-rose-950/40 border border-rose-900/40 px-3 py-1 rounded-full inline-block mb-2">
                    🔒 Bezpieczna Symulacja Transakcji Online
                </div>
                <h2 class="text-lg font-black tracking-wider uppercase text-neutral-100">Savona GatePay</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Zamówienie wewnętrzne #{{ order.id }}</p>
            </header>

            <!-- KARTA PODSUMOWANIA KWOTY -->
            <div class="bg-neutral-950 border border-neutral-850 rounded-2xl p-4 flex justify-between items-center">
                <div>
                    <span class="text-[10px] uppercase font-bold text-neutral-500 block tracking-wide">Kwota do zapłaty:</span>
                    <span class="text-2xl font-black font-mono text-emerald-400">{{ parseFloat(order.total_price).toFixed(2) }} zł</span>
                </div>
                <div class="text-right">
                    <span class="text-[12px] uppercase font-bold text-neutral-500 block"><p>Czas na płatność: 1:{{ (timer % 60).toString().padStart(2, '0') }}</p>
                    </span>
                </div>
            </div>

            <!-- MODUŁ BLIK -->
            <div class="space-y-4">
                <div class="flex items-center justify-center space-x-2">
                    <!-- Ikona logotypu BLIK w CSS -->
                    <span class="bg-neutral-950 px-2 py-0.5 rounded border border-neutral-800 text-[10px] font-black tracking-widest text-neutral-400 uppercase">
                        <span class="text-rose-500">b</span>lik
                    </span>
                    <label class="text-xs font-bold uppercase text-neutral-300 tracking-wide">Wprowadź 6-cyfrowy kod BLIK</label>
                </div>

                <!-- SZESCIOPOZOWY INPUT KODU -->
                <div class="flex justify-center space-x-2">
                    <input 
                        v-for="(num, idx) in 6" 
                        :key="idx"
                        :id="`blik-${idx}`"
                        v-model="blikCode[idx]"
                        type="number"
                        maxlength="1"
                        @input="handleInput($event, idx)"
                        @keydown="handleKeyDown($event, idx)"
                        :disabled="isProcessing || timer === 0"
                        class="h-12 w-10 bg-neutral-950 border border-neutral-800 rounded-xl text-center text-lg font-black font-mono text-white focus:border-rose-500 focus:ring-0 transition-colors"
                    />
                </div>
            </div>

            <!-- PRZYCISK ZATWIERDZENIA -->
            <div class="pt-2">
                <button 
                    @click="submitPayment"
                    :disabled="isProcessing || timer === 0"
                    class="w-full bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 disabled:from-neutral-800 disabled:to-neutral-800 disabled:text-neutral-600 text-white font-black py-4 rounded-xl text-xs uppercase tracking-wider transition-all shadow-lg flex items-center justify-center space-x-2"
                >
                    <span v-if="isProcessing" class="inline-block animate-spin rounded-full h-3 w-3 border-2 border-white border-t-transparent"></span>
                    <span>{{ isProcessing ? 'Weryfikacja w aplikacji banku...' : '🔒 Autoryzuj i zapłać' }}</span>
                </button>
                <div class="text-center text-[9px] text-neutral-600 mt-3 uppercase font-bold tracking-widest">
                    Pieniądze nie zostaną pobrane • Moduł edukacyjny ERP SaaS
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* Ukrywamy strzałki góra/dół dla inputów typu number */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>