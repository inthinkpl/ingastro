<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    orders: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    status: ''
});

const changeStatus = (orderId, nextStatus) => {
    form.status = nextStatus;
    // POPRAWKA: Zmiana na 'order.updateStatus' (liczba pojedyncza) dopasowana do web.php
    form.patch(route('order.updateStatus', orderId));
};

// 🌐 AUTOMATYCZNE ODŚWIEŻANIE W CZASIE RZECZYWISTYM (WEBSOCKETS)
onMounted(() => {
    if (window.Echo) {
        // Słuchamy kanału publicznego 'kds' z Twojego pliku OrderPlaced.php
        window.Echo.channel('kds')
            .listen('.order.placed', (e) => {
                console.log('KDS: Wykryto nowe zamówienie lub zmianę statusu!', e.order);
                
                // Inertia pobiera z kontrolera świeżą listę zamówień w tle (bez mignięcia ekranu)
                router.reload({ 
                    only: ['orders'], 
                    preserveScroll: true 
                });
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leaveChannel('kds');
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-white p-6">
        <header class="mb-8 border-b border-slate-800 pb-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">👨‍🍳</span>
                <h1 class="text-2xl font-black tracking-wider text-orange-400">SYSTEM KDS: MONITOR KUCHENNY</h1>
            </div>
            <span class="text-xs bg-slate-900 border border-slate-800 px-3 py-1 rounded text-slate-400">Aktywne zamówienia: {{ orders.length }}</span>
        </header>

        <div v-if="orders.length === 0" class="text-center py-20 text-slate-600 italic text-sm">
            Brak zamówień do realizacji. Kuchnia czysta!
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div 
                v-for="order in orders" 
                :key="order.id" 
                :class="order.status === 'gotowe' ? 'border-emerald-600 bg-emerald-950/20' : 'border-slate-800 bg-slate-900'"
                class="border rounded-2xl p-5 shadow-xl flex flex-col justify-between space-y-4"
            >
                <div>
                    <div class="flex justify-between items-center border-b border-slate-800 pb-2 mb-3">
                        <span class="font-black text-lg text-orange-400">#{{ order.id }}</span>
                        <span class="text-[10px] uppercase font-black px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">
                            {{ order.type }}
                        </span>
                    </div>

                    <!-- POPRAWKA: Oczyszczone z duplikacji, czytelne pozycje zamówienia wraz z dodatkami (BOM) -->
                    <div class="space-y-3">
                        <div v-for="item in order.items" :key="item.id" class="text-sm bg-slate-950/40 p-3 rounded-xl border border-slate-800/60">
                            <div class="font-bold text-slate-200">
                                <span class="text-orange-400 font-mono">{{ item.quantity }}x</span> {{ item.variant?.product?.name || 'Produkt' }}
                            </div>
                            <div class="text-xs text-slate-400 pl-5 mt-0.5">Rozmiar: {{ item.variant?.size_name }}</div>

                            <!-- Dynamiczne instrukcje modyfikatorów dla kucharza (Extra / Bez) -->
                            <div v-if="item.modifiers && item.modifiers.length > 0" class="pl-5 mt-2 space-y-0.5 border-t border-slate-900/80 pt-1.5">
                                <div 
                                    v-for="mod in item.modifiers" 
                                    :key="mod.id" 
                                    :class="mod.action === 'ADD' ? 'text-emerald-400' : 'text-red-400'" 
                                    class="text-[11px] uppercase font-black flex items-center space-x-1"
                                >
                                    <span>{{ mod.action === 'ADD' ? '➕ EXTRA:' : '❌ BEZ:' }}</span>
                                    <span>{{ mod.ingredient?.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kontrola Statusu (Maszyna Stanów) -->
                <div class="pt-3 border-t border-slate-800/60">
                    <button 
                        v-if="order.status === 'nowe'"
                        @click="changeStatus(order.id, 'w_przygotowaniu')"
                        class="w-full bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs py-2 rounded-xl uppercase tracking-wider transition-colors"
                    >
                        Rozpocznij pracę
                    </button>
                    <button 
                        v-if="order.status === 'w_przygotowaniu'"
                        @click="changeStatus(order.id, 'gotowe')"
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2 rounded-xl uppercase tracking-wider transition-colors"
                    >
                        Oznacz jako gotowe
                    </button>
                    <button 
                        v-if="order.status === 'gotowe'"
                        @click="changeStatus(order.id, 'wydane')"
                        class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-2 rounded-xl uppercase tracking-wider transition-colors"
                    >
                        Wydaj z kuchni
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>