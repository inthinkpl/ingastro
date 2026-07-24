<script setup>
import { onMounted, onUnmounted, computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    orders: {
        type: Array,
        default: () => []
    },
    pizzeria_coords: {
        type: Object,
        default: () => ({ lat: 53.1325, lng: 23.1533 })
    }
});

let pollInterval = null;
let map = null;
let markersGroup = null;

// SILNIK DŹWIĘKOWY (WEB AUDIO API - SYNTEZATOR GONGU)
const isAudioEnabled = ref(false);
const previousOrdersCount = ref(props.orders?.length || 0);

const enableAudio = () => {
    isAudioEnabled.value = true;
    playChime(); // Odgrywamy testowy dźwięk przy włączeniu
};

const playChime = () => {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();

        // Ton 1 (520Hz)
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(520, ctx.currentTime);
        gain1.gain.setValueAtTime(0.3, ctx.currentTime);
        gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(ctx.currentTime);
        osc1.stop(ctx.currentTime + 0.4);

        // Ton 2 (650Hz - wyższy)
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(650, ctx.currentTime + 0.15);
        gain2.gain.setValueAtTime(0.4, ctx.currentTime + 0.15);
        gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.7);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(ctx.currentTime + 0.15);
        osc2.stop(ctx.currentTime + 0.7);
    } catch (e) {
        console.warn('Błąd odtwarzania dźwięku:', e);
    }
};

// GENERATOR LINKU MULTI-WAYPOINT GOOGLE MAPS
const googleMapsUrl = computed(() => {
    const safeOrders = props.orders || [];
    const validOrders = safeOrders.filter(o => (o.lat && o.lng) || o.delivery_address);
    if (validOrders.length === 0) return '#';

    const startLat = props.pizzeria_coords?.lat || 53.1325;
    const startLng = props.pizzeria_coords?.lng || 23.1533;
    const origin = `${startLat},${startLng}`;
    
    const getPoint = (o) => (o.lat && o.lng) ? `${o.lat},${o.lng}` : encodeURIComponent(o.delivery_address);

    const lastOrder = validOrders[validOrders.length - 1];
    const destination = getPoint(lastOrder);

    const waypoints = validOrders
        .slice(0, validOrders.length - 1)
        .map(o => getPoint(o))
        .join('|');

    let url = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}`;
    if (waypoints) url += `&waypoints=${waypoints}`;
    return url;
});

// AUTOMATYCZNY POLLING
onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({ 
            only: ['orders'], 
            preserveScroll: true, 
            preserveState: true 
        });
    }, 5000);

    if (window.L) {
        initMap();
    } else {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => initMap();
        document.head.appendChild(script);
    }
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

// RYSOWANIE MAPY LEAFLET
const renderMarkers = () => {
    if (!map || !markersGroup || !window.L) return;

    markersGroup.clearLayers();

    L.marker([props.pizzeria_coords.lat, props.pizzeria_coords.lng])
        .addTo(markersGroup)
        .bindPopup('<b>🍕 Pizzeria Savona (Start)</b>');

    const safeOrders = props.orders || [];
    safeOrders.forEach((order, index) => {
        if (order && order.lat && order.lng) {
            const seq = order.route_sequence || (index + 1);
            
            const customIcon = L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color: ${order.status === 'w_dostawie' ? '#10b981' : '#f59e0b'}; color: white; font-weight: 900; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">${seq}</div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });

            L.marker([order.lat, order.lng], { icon: customIcon })
                .addTo(markersGroup)
                .bindPopup(`<b>Dostawa #${seq} (${order.status})</b><br>${order.delivery_address}<br>Zamówienie #${order.id}`);
        }
    });
};

const initMap = () => {
    const mapContainer = document.getElementById('driver-map');
    if (!mapContainer || map) return;

    map = L.map('driver-map').setView([props.pizzeria_coords.lat, props.pizzeria_coords.lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    markersGroup = L.layerGroup().addTo(map);
    renderMarkers();
};

// OBSERWATOR ZMIAN: WYKRYWANIE NOWYCH ZAMÓWIEŃ I SYGNAŁ DŹWIĘKOWY
watch(() => props.orders, (newOrders) => {
    renderMarkers();

    const newCount = newOrders?.length || 0;
    // Jeśli liczba zamówień wzrosła – odgrywamy sygnał audio!
    if (newCount > previousOrdersCount.value && isAudioEnabled.value) {
        playChime();
    }
    previousOrdersCount.value = newCount;
}, { deep: true });

// AKCJE KIEROWCY
const pickupOrder = (orderId) => {
    router.post(route('driver.pickup', orderId));
};

const completeOrder = (orderId) => {
    if (confirm('Czy dostarczyłeś to zamówienie i pobrałeś płatność?')) {
        router.post(route('driver.complete', orderId));
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-white p-4 font-sans space-y-4">
        
        <!-- NAGŁÓWEK KIEROWCY -->
        <div class="bg-slate-900 p-4 rounded-2xl border border-slate-850 space-y-3">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-base font-black text-orange-400 uppercase tracking-wider">🚗 Trasa Kuriera</h1>
                    <p class="text-[11px] text-slate-400">Masz {{ orders?.length || 0 }} aktywne pozycje na trasie.</p>
                </div>
                
                <!-- PRZYCISK WŁĄCZENIA DŹWIĘKU (WYMAGANY PRZEZ PRZEGLĄDARKI MOBILE) -->
                <button 
                    @click="enableAudio" 
                    :class="isAudioEnabled ? 'bg-emerald-950/80 text-emerald-400 border-emerald-800' : 'bg-orange-600 text-white font-black animate-pulse'"
                    class="px-3 py-2 rounded-xl text-xs border flex items-center space-x-1.5 transition-all shadow-md"
                >
                    <span>{{ isAudioEnabled ? '🔊 Dźwięk aktywny' : '🔔 Włącz dźwięk powiadomień' }}</span>
                </button>
            </div>

            <!-- PRZYCISK NAWIGACJI -->
            <a 
                :href="googleMapsUrl" 
                target="_blank"
                :class="(orders?.length || 0) === 0 ? 'pointer-events-none opacity-50' : ''"
                class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-4 py-3 rounded-xl text-xs font-black uppercase tracking-wider flex items-center justify-center space-x-2 shadow-lg transition-all"
            >
                <span>🧭 Uruchom Nawigację (Google Maps)</span>
            </a>
        </div>

        <!-- MAPA -->
        <div class="bg-slate-900 border border-slate-850 rounded-2xl overflow-hidden h-60 w-full relative z-10 shadow-md">
            <div id="driver-map" class="h-full w-full"></div>
        </div>

        <!-- LISTA PUNKTÓW -->
        <div class="space-y-3">
            <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider pl-1">Lista Zamówień do Obsługi:</h2>

            <div v-for="(order, idx) in (orders || [])" :key="order.id" class="bg-slate-900 border border-slate-850 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-3.5">
                    <div :class="order.status === 'w_dostawie' ? 'bg-emerald-600' : 'bg-amber-500'" class="h-9 w-9 rounded-full text-white font-black text-sm flex items-center justify-center shrink-0 shadow-md">
                        {{ order.route_sequence || (idx + 1) }}
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-bold text-xs uppercase text-slate-100">{{ order.delivery_address }}</h3>
                            <span :class="order.status === 'w_dostawie' ? 'bg-emerald-950 text-emerald-400 border-emerald-900' : 'bg-amber-950 text-amber-400 border-amber-900'" class="text-[9px] font-black uppercase px-2 py-0.5 rounded border">
                                {{ order.status === 'w_dostawie' ? 'W trasie' : 'W kuchni' }}
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">
                            Zamówienie #{{ order.id }} — <span class="text-emerald-400 font-mono font-bold">{{ order.total_price }} zł</span> ({{ order.payment_method }})
                        </p>
                    </div>
                </div>

                <!-- DYNAMICZNY PRZYCISK -->
                <div>
                    <button 
                        v-if="order.status !== 'w_dostawie'"
                        @click="pickupOrder(order.id)" 
                        class="bg-amber-600 hover:bg-amber-500 text-white px-3.5 py-2 rounded-xl text-[10px] font-black uppercase transition-all shadow-md"
                    >
                        📦 Odebrałem z kuchni
                    </button>

                    <button 
                        v-else
                        @click="completeOrder(order.id)" 
                        class="bg-emerald-600 hover:bg-emerald-500 text-white px-3.5 py-2 rounded-xl text-[10px] font-black uppercase transition-all shadow-md"
                    >
                        ✓ Dostarczono
                    </button>
                </div>
            </div>

            <div v-if="(orders?.length || 0) === 0" class="text-center py-12 text-slate-600 italic text-xs bg-slate-900/50 rounded-2xl border border-slate-850">
                Brak przypisanych dostaw. Odpocznij chwilę w pizzerii! 🍕
            </div>
        </div>

    </div>
</template>