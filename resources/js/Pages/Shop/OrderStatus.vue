<script setup>
import { onMounted, onUnmounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    order: {
        type: Object,
        required: true
    },
    restaurantPhone: {
        type: String,
        default: '+48 500 600 700'
    }
});

let pollInterval = null;

// Automatyczne odświeżanie stanu zamówienia w tle co 5 sekund
onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({
            only: ['order'],
            preserveScroll: true,
            preserveState: true
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

// Sformatowana, ładna nazwa statusu do wyświetlenia w pigułce
const readableStatus = computed(() => {
    const isDelivery = props.order.type === 'dostawa';
    const statusMap = {
        'nowe': 'Przyjęte do realizacji',
        'w_przygotowaniu': 'W przygotowaniu w kuchni',
        'gotowe': isDelivery ? 'Czeka na kuriera' : 'Gotowe do odbioru',
        'w_dostawie': 'W trasie z kurierem',
        'w drodze': 'W trasie z kurierem',
        'dostarczone': 'Dostarczone',
        'wydane': 'Wydane klientowi',
        'anulowane': 'Zamówienie anulowane'
    };
    return statusMap[props.order.status] || props.order.status;
});

// Wyliczanie numerycznego etapu postępu (1, 2, 3, 4)
const currentStep = computed(() => {
    const status = props.order.status;
    const isDelivery = props.order.type === 'dostawa';

    if (status === 'nowe') return 1;
    if (status === 'w_przygotowaniu') return 2;
    if (status === 'gotowe') {
        return isDelivery ? 2 : 3;
    }
    if (['w_dostawie', 'w drodze'].includes(status)) return 3;
    if (['dostarczone', 'wydane'].includes(status)) return 4;
    return 1;
});

// Dynamiczny tytuł i opis stanu
const statusMeta = computed(() => {
    const status = props.order.status;
    const isDelivery = props.order.type === 'dostawa';

    switch (status) {
        case 'nowe':
            return {
                title: 'Zamówienie przyjęte',
                desc: 'Twoje zamówienie trafiło do pizzerii. Kucharze wkrótce rozpoczną jego przygotowanie.',
                badgeColor: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                icon: '📋'
            };
        case 'w_przygotowaniu':
            return {
                title: 'Wypiekamy w piecu',
                desc: 'Ciasto dojrzewało 48 godzin! Nasz pizzaiolo właśnie przygotowuje i wypieka Twoje danie.',
                badgeColor: 'bg-orange-500/10 text-orange-400 border-orange-500/30',
                icon: '🍕'
            };
        case 'gotowe':
            if (isDelivery) {
                return {
                    title: 'Oczekuje na kuriera',
                    desc: 'Pizza jest już wypieczona, spakowana w torbę termoizolacyjną i czeka na odbiór przez dostawcę.',
                    badgeColor: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                    icon: '📦'
                };
            }
            return {
                title: 'Gotowe do odbioru!',
                desc: 'Zapraszamy do pizzerii! Twoje zamówienie czeka gotowe przy ladzie.',
                badgeColor: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                icon: '🏬'
            };
        case 'w_dostawie':
        case 'w drodze':
            return {
                title: 'W trasie z kurierem',
                desc: 'Kurier odebrał zamówienie z kuchni i jedzie pod Twój adres.',
                badgeColor: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                icon: '🚗'
            };
        case 'dostarczone':
        case 'wydane':
            return {
                title: 'Smacznego!',
                desc: 'Zamówienie zostało pomyślnie zrealizowane. Dziękujemy za wybór Pizzerii Savona!',
                badgeColor: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                icon: '🎉'
            };
        case 'anulowane':
            return {
                title: 'Zamówienie anulowane',
                desc: 'Przepraszamy, to zamówienie zostało anulowane. W razie pytań prosimy o kontakt z obsługą.',
                badgeColor: 'bg-red-500/10 text-red-400 border-red-500/30',
                icon: '❌'
            };
        default:
            return {
                title: 'Przetwarzanie',
                desc: 'Status zamówienia jest aktualizowany...',
                badgeColor: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
                icon: '⏳'
            };
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-white font-sans selection:bg-orange-600 selection:text-white p-4 sm:p-6 flex flex-col justify-center">
        <!-- ZWARTY KONTENER (max-w-xl) PREWENIUJĄCY ROZCIĄGANIE NA SZEROKIM EKRANIE -->
        <div class="max-w-xl mx-auto w-full space-y-4">
            
            <!-- GÓRNY PASEK Z LOGO I NUMEREM ZAMÓWIENIA -->
            <div class="flex justify-between items-center bg-slate-900/90 p-4 rounded-2xl border border-slate-850 backdrop-blur-md shadow-lg">
                <div class="flex items-center space-x-2.5">
                    <span class="text-2xl">🍕</span>
                    <span class="text-lg font-black tracking-widest text-orange-500 uppercase">SAVONA LIVE</span>
                </div>
                <span class="text-xs font-mono font-bold text-slate-300 bg-slate-950 px-3 py-1.5 rounded-xl border border-slate-800">
                    #{{ order.id }}
                </span>
            </div>

            <!-- GŁÓWNA KARTA STATUSU -->
            <div class="bg-slate-900 border border-slate-850 rounded-3xl p-6 sm:p-7 space-y-6 shadow-2xl relative overflow-hidden">
                
                <!-- NAGŁÓWEK AKTUALNEGO STANU -->
                <div class="text-center space-y-3">
                    <div class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-slate-950 border border-slate-800 text-3xl shadow-inner">
                        {{ statusMeta.icon }}
                    </div>
                    <div>
                        <span :class="statusMeta.badgeColor" class="inline-block text-xs uppercase font-black tracking-widest px-3.5 py-1 rounded-full border mb-2">
                            {{ readableStatus }}
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-black uppercase tracking-wide text-white">
                            {{ statusMeta.title }}
                        </h1>
                        <p class="text-sm text-slate-300 max-w-md mx-auto mt-1 leading-relaxed font-medium">
                            {{ statusMeta.desc }}
                        </p>
                    </div>
                </div>

                <!-- PASEK KROKÓW POSTĘPU -->
                <div v-if="order.status !== 'anulowane'" class="space-y-2 pt-1">
                    <div class="grid grid-cols-4 gap-2">
                        <div 
                            v-for="step in 4" 
                            :key="step"
                            :class="step <= currentStep ? 'bg-gradient-to-r from-orange-500 to-red-600 shadow-md shadow-orange-950/50' : 'bg-slate-950 border border-slate-850'"
                            class="h-2.5 rounded-full transition-all duration-500"
                        ></div>
                    </div>
                    <div class="flex justify-between text-[15px] font-bold text-slate-400 px-0.5">
                        <span :class="currentStep >= 1 ? 'text-orange-400 font-black' : ''">1. Przyjęte</span>
                        <span :class="currentStep >= 2 ? 'text-orange-400 font-black' : ''">2. W piecu</span>
                        <span :class="currentStep >= 3 ? 'text-orange-400 font-black' : ''">3. {{ order.type === 'dostawa' ? 'W trasie' : 'Do odbioru' }}</span>
                        <span :class="currentStep >= 4 ? 'text-emerald-400 font-black' : ''">4. Gotowe</span>
                    </div>
                </div>

                <!-- INFORMACJE O ADRESIE I PŁATNOŚCI -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-4 border-t border-slate-850">
                    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-850 space-y-1.5">
                        <span class="text-[11px] uppercase font-bold text-slate-400 tracking-wider block">Sposób Dostawy:</span>
                        <p class="text-sm font-bold text-slate-100">
                            {{ order.type === 'dostawa' ? '🚗 Dostawa kurierem' : '🏬 Odbiór osobisty w pizzerii' }}
                        </p>
                        <p v-if="order.delivery_address" class="text-xs text-slate-300 leading-relaxed pt-1 border-t border-slate-900 mt-1">
                            📍 {{ order.delivery_address }}
                        </p>
                    </div>

                    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-850 space-y-1.5">
                        <span class="text-[11px] uppercase font-bold text-slate-400 tracking-wider block">Płatność:</span>
                        <p class="text-sm font-bold text-slate-100 uppercase">
                            {{ order.payment_method }} 
                            <span :class="order.payment_status === 'opłacone' ? 'text-emerald-400' : 'text-amber-400'" class="text-xs font-mono font-bold">
                                ({{ order.payment_status }})
                            </span>
                        </p>
                        <p class="text-sm text-emerald-400 font-mono font-black pt-1 border-t border-slate-900 mt-1">
                            Suma: {{ (Number(order.total_price) || 0).toFixed(2) }} zł
                        </p>
                    </div>
                </div>

                <!-- SZCZEGÓŁY POZYCJI W ZAMÓWIENIU -->
                <div class="space-y-3 pt-1">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-300">Zamówione Pozycje:</h3>
                    <div class="space-y-2">
                        <div 
                            v-for="item in order.items" 
                            :key="item.id" 
                            class="bg-slate-950/80 p-3.5 rounded-xl border border-slate-850 flex justify-between items-center text-sm"
                        >
                            <div class="space-y-1">
                                <div class="font-bold text-slate-100 uppercase text-xs sm:text-sm">
                                    {{ item.quantity }}x {{ item.variant?.product?.name || 'Pizza' }}
                                    <span class="text-slate-400 text-xs font-normal ml-1">({{ item.variant?.size_name }})</span>
                                </div>
                                <!-- Modyfikatory / Dodatki -->
                                <div v-if="item.modifiers && item.modifiers.length > 0" class="flex flex-wrap gap-1 pt-0.5">
                                    <span 
                                        v-for="mod in item.modifiers" 
                                        :key="mod.id" 
                                        :class="mod.action === 'ADD' ? 'bg-emerald-950 text-emerald-400 border-emerald-900' : 'bg-red-950 text-red-400 border-red-900'"
                                        class="text-[10px] font-bold px-1.5 py-0.5 rounded border uppercase"
                                    >
                                        {{ mod.action === 'ADD' ? '+ ' : 'Bez ' }} {{ mod.ingredient?.name || 'Składnik' }}
                                    </span>
                                </div>
                            </div>
                            <!-- Wyliczenie ceny z fallbackiem -->
                            <span class="font-mono font-bold text-slate-200 text-sm sm:text-base">
                                {{ (Number(item.price ?? item.unit_price ?? item.variant?.price ?? 0) * item.quantity).toFixed(2) }} zł
                            </span>
                        </div>
                    </div>
                </div>

                <!-- INFOLINIA / POMOC -->
                <div class="text-center pt-2 border-t border-slate-850">
                    <p class="text-xs text-slate-400">
                        Masz pytania do swojego zamówienia? Zadzwoń do lokalu: 
                        <a :href="'tel:' + restaurantPhone" class="text-orange-400 font-bold underline ml-1 hover:text-orange-300 transition-colors">
                            {{ restaurantPhone }}
                        </a>
                    </p>
                </div>

            </div>

        </div>
    </div>
</template>