<script setup>
import { onMounted, onUnmounted, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { 
    ClipboardList, Flame, Package, Store, Car, PartyPopper, 
    XCircle, Hourglass, Phone, ArrowLeft, MapPin, CreditCard, 
    CheckCircle2, Truck, PackageCheck, AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    order: {
        type: Object,
        required: true
    },
    restaurantPhone: {
        type: String,
        default: '+48 785 555 455'
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

// Sformatowana nazwa statusu do wyświetlenia w pigułce
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

// Dynamiczny tytuł, opis i ikona SVG stanu
const statusMeta = computed(() => {
    const status = props.order.status;
    const isDelivery = props.order.type === 'dostawa';

    switch (status) {
        case 'nowe':
            return {
                title: 'Zamówienie przyjęte',
                desc: 'Twoje zamówienie trafiło do pizzerii. Kucharze wkrótce rozpoczną jego przygotowanie.',
                badgeColor: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                icon: ClipboardList
            };
        case 'w_przygotowaniu':
            return {
                title: 'Wypiekamy w piecu',
                desc: 'Ciasto dojrzewało 48 godzin! Nasz pizzaiolo właśnie przygotowuje i wypieka Twoje danie.',
                badgeColor: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                icon: Flame
            };
        case 'gotowe':
            if (isDelivery) {
                return {
                    title: 'Oczekuje na kuriera',
                    desc: 'Pizza jest już wypieczona, spakowana w torbę termoizolacyjną i czeka na odbiór przez dostawcę.',
                    badgeColor: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                    icon: Package
                };
            }
            return {
                title: 'Gotowe do odbioru!',
                desc: 'Zapraszamy do pizzerii! Twoje zamówienie czeka gotowe przy ladzie.',
                badgeColor: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                icon: Store
            };
        case 'w_dostawie':
        case 'w drodze':
            return {
                title: 'W trasie z kurierem',
                desc: 'Kurier odebrał zamówienie z kuchni i jedzie pod Twój adres.',
                badgeColor: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                icon: Car
            };
        case 'dostarczone':
        case 'wydane':
            return {
                title: 'Smacznego!',
                desc: 'Zamówienie zostało pomyślnie zrealizowane. Dziękujemy za wybór Pizzerii Savona!',
                badgeColor: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                icon: PartyPopper
            };
        case 'anulowane':
            return {
                title: 'Zamówienie anulowane',
                desc: 'Przepraszamy, to zamówienie zostało anulowane. W razie pytań prosimy o kontakt z obsługą.',
                badgeColor: 'bg-red-500/10 text-red-400 border-red-500/30',
                icon: XCircle
            };
        default:
            return {
                title: 'Przetwarzanie',
                desc: 'Status zamówienia jest aktualizowany...',
                badgeColor: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
                icon: Hourglass
            };
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#0B0F19] text-slate-300 font-sans antialiased selection:bg-red-500 selection:text-white p-4 sm:p-6 flex flex-col justify-center">
        
        <!-- ZWARTY KONTENER (max-w-xl) -->
        <div class="max-w-xl mx-auto w-full space-y-4">
            
            <!-- GÓRNY PASEK Z LOGO I NUMEREM ZAMÓWIENIA -->
            <div class="flex justify-between items-center bg-slate-900/90 p-4 rounded-2xl border border-slate-800 backdrop-blur-md shadow-lg">
                <Link href="/" class="flex items-center space-x-2">
                    <span class="text-xl font-black text-red-500 tracking-wider">SAVONA</span>
                    <span class="text-[10px] bg-amber-500 text-slate-950 px-2 py-0.5 rounded-full font-bold uppercase tracking-wide">live</span>
                </Link>

                <div class="flex items-center space-x-2">
                    <span class="text-xs font-mono font-bold text-slate-300 bg-[#0B0F19] px-3 py-1.5 rounded-xl border border-slate-800">
                        #{{ order.id }}
                    </span>
                    <Link href="/" class="bg-slate-800 hover:bg-slate-700 text-slate-300 p-2 rounded-xl transition flex items-center justify-center">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                </div>
            </div>

            <!-- GŁÓWNA KARTA STATUSU -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-7 space-y-6 shadow-2xl relative overflow-hidden">
                
                <!-- NAGŁÓWEK AKTUALNEGO STANU -->
                <div class="text-center space-y-3">
                    <div class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-[#0B0F19] border border-slate-800 text-amber-500 shadow-inner">
                        <component :is="statusMeta.icon" class="w-8 h-8" />
                    </div>

                    <div>
                        <span :class="statusMeta.badgeColor" class="inline-block text-xs uppercase font-bold tracking-widest px-3.5 py-1 rounded-full border mb-2">
                            {{ readableStatus }}
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-black uppercase tracking-wide text-white">
                            {{ statusMeta.title }}
                        </h1>
                        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1 leading-relaxed font-medium">
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
                            :class="step <= currentStep ? 'bg-gradient-to-r from-red-600 to-amber-500 shadow-md shadow-red-950/50' : 'bg-[#0B0F19] border border-slate-800'"
                            class="h-2.5 rounded-full transition-all duration-500"
                        ></div>
                    </div>
                    <div class="flex justify-between text-[11px] font-bold text-slate-500 px-0.5">
                        <span :class="currentStep >= 1 ? 'text-amber-400 font-bold' : ''">1. Przyjęte</span>
                        <span :class="currentStep >= 2 ? 'text-amber-400 font-bold' : ''">2. W piecu</span>
                        <span :class="currentStep >= 3 ? 'text-amber-400 font-bold' : ''">3. {{ order.type === 'dostawa' ? 'W trasie' : 'Do odbioru' }}</span>
                        <span :class="currentStep >= 4 ? 'text-emerald-400 font-bold' : ''">4. Gotowe</span>
                    </div>
                </div>

                <!-- INFORMACJE O ADRESIE I PŁATNOŚCI -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-4 border-t border-slate-800">
                    <div class="bg-[#0B0F19] p-4 rounded-2xl border border-slate-800 space-y-1.5">
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Sposób Dostawy:</span>
                        <p class="text-xs font-bold text-slate-100 flex items-center space-x-1.5">
                            <component :is="order.type === 'dostawa' ? Car : Store" class="w-4 h-4 text-amber-500 shrink-0" />
                            <span>{{ order.type === 'dostawa' ? 'Dostawa kurierem' : 'Odbiór osobisty w pizzerii' }}</span>
                        </p>
                        <p v-if="order.delivery_address" class="text-xs text-slate-400 leading-relaxed pt-1 border-t border-slate-850 mt-1 flex items-start space-x-1">
                            <MapPin class="w-3.5 h-3.5 text-slate-500 mt-0.5 shrink-0" />
                            <span>{{ order.delivery_address }}</span>
                        </p>
                    </div>

                    <div class="bg-[#0B0F19] p-4 rounded-2xl border border-slate-800 space-y-1.5">
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Płatność:</span>
                        <p class="text-xs font-bold text-slate-100 uppercase flex items-center space-x-1.5">
                            <CreditCard class="w-4 h-4 text-amber-500 shrink-0" />
                            <span>{{ order.payment_method }}</span>
                            <span :class="order.payment_status === 'opłacone' ? 'text-emerald-400' : 'text-amber-400'" class="text-[11px] font-mono font-bold">
                                ({{ order.payment_status }})
                            </span>
                        </p>
                        <p class="text-xs text-emerald-400 font-mono font-bold pt-1 border-t border-slate-850 mt-1">
                            Suma: {{ (Number(order.total_price) || 0).toFixed(2) }} zł
                        </p>
                    </div>
                </div>

                <!-- SZCZEGÓŁY POZYCJI W ZAMÓWIENIU -->
                <div class="space-y-3 pt-1">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Zamówione Pozycje:</h3>
                    <div class="space-y-2">
                        <div 
                            v-for="item in order.items" 
                            :key="item.id" 
                            class="bg-[#0B0F19] p-3.5 rounded-xl border border-slate-800 flex justify-between items-center text-xs"
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

                            <span class="font-mono font-bold text-slate-200 text-xs sm:text-sm">
                                {{ (Number(item.price ?? item.unit_price ?? item.variant?.price ?? 0) * item.quantity).toFixed(2) }} zł
                            </span>
                        </div>
                    </div>
                </div>

                <!-- INFOLINIA / POMOC -->
                <div class="text-center pt-2 border-t border-slate-800">
                    <p class="text-xs text-slate-400 flex items-center justify-center space-x-1">
                        <Phone class="w-3.5 h-3.5 text-amber-500" />
                        <span>Masz pytania do zamówienia? Zadzwoń:</span>
                        <a :href="'tel:' + restaurantPhone" class="text-amber-400 font-bold hover:underline ml-1">
                            {{ restaurantPhone }}
                        </a>
                    </p>
                </div>

            </div>

        </div>
    </div>
</template>