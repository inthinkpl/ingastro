<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3'; // <--- Importujemy router Inertia

const props = defineProps({
    initialOrders: Array
});

const orders = ref([...props.initialOrders]);

onMounted(() => {
    window.Echo.channel('kds')
        .listen('.order.placed', (e) => {
            orders.value.push(e.order);
        });
});

// Prawdziwa aktualizacja statusu w bazie danych przez Inertia PATCH
const changeStatus = (orderId, nextStatus) => {
    router.patch(route('orders.updateStatus', orderId), { 
        status: nextStatus 
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Po udanej aktualizacji w DB, synchronizujemy nasz lokalny stan z nowymi propsami
            orders.value = [...props.initialOrders];
        }
    });
};

const formatTime = (dateTimeString) => {
    const date = new Date(dateTimeString);
    return date.toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-white p-6">
        <header class="mb-6 border-b border-slate-800 pb-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">🖥️</span>
                <h1 class="text-2xl font-black tracking-wider text-emerald-400">MONITOR KUCHENNY (KDS)</h1>
            </div>
            <div class="flex items-center space-x-4 text-sm text-slate-400">
                <span class="flex items-center"><span class="h-2 w-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span> Serwer Reverb: Online</span>
                <span class="bg-slate-800 px-3 py-1 rounded text-xs">Do zrobienia: {{ orders.length }}</span>
            </div>
        </header>

        <div v-if="orders.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-500">
            <span class="text-5xl mb-4">🍕</span>
            <p class="text-xl font-medium">Brak zamówień. Kuchnia czysta!</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div 
                v-for="order in orders" 
                :key="order.id" 
                :class="order.status === 'w trakcie' ? 'border-blue-500/50 shadow-blue-950/50' : 'border-slate-800'"
                class="bg-slate-900 border rounded-xl overflow-hidden shadow-2xl flex flex-col justify-between transition-all duration-300"
            >
                <div 
                    :class="order.status === 'w trakcie' ? 'bg-blue-950/80 border-blue-900 text-blue-200' : 'bg-slate-800 border-slate-700'"
                    class="p-4 border-b flex justify-between items-center"
                >
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Zamówienie</span>
                        <div :class="order.status === 'w trakcie' ? 'text-blue-400' : 'text-emerald-400'" class="text-lg font-black">#{{ order.id }}</div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status: {{ order.status }}</span>
                        <div class="text-sm font-bold text-slate-300">{{ formatTime(order.created_at) }}</div>
                    </div>
                </div>

                <div class="p-4 flex-grow space-y-4">
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-widest border-b border-slate-800 pb-1">Pozycje:</div>
                    
                    <div v-for="item in order.items" :key="item.id" class="border-b border-slate-850 pb-3 last:border-0">
                        <div class="flex justify-between items-start">
                            <div class="font-extrabold text-slate-200">
                                <span class="text-orange-400 text-lg mr-1">{{ item.quantity }}x</span> 
                                {{ item.variant.product.name }}
                            </div>
                            <span class="text-xs bg-slate-800 text-slate-400 px-2 py-0.5 rounded font-semibold">
                                {{ item.variant.size_name }}
                            </span>
                        </div>

                        <div v-if="item.modifiers && item.modifiers.length > 0" class="mt-2 space-y-1">
                            <div 
                                v-for="mod in item.modifiers" 
                                :key="mod.id"
                                :class="mod.action === 'ADD' ? 'bg-green-950 text-green-400 border-green-900' : 'bg-red-950 text-red-400 border-red-900'"
                                class="text-xs px-2 py-1 rounded border font-bold uppercase tracking-wide"
                            >
                                <span>{{ mod.action === 'ADD' ? '➕ EXTRA:' : '❌ BEZ:' }}</span> {{ mod.ingredient.name }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-slate-850 border-t border-slate-800 flex items-center justify-between">
                    <span class="text-xs uppercase font-bold px-2 py-1 rounded bg-slate-800 text-slate-400 border border-slate-700">
                        {{ order.type }}
                    </span>
                    
                    <button 
                        v-if="order.status === 'nowe'"
                        @click="changeStatus(order.id, 'w trakcie')"
                        class="bg-orange-600 hover:bg-orange-500 text-white font-black text-xs py-2 px-4 rounded-lg transition-colors uppercase tracking-wider"
                    >
                        Rozpocznij produkcję 👨‍🍳
                    </button>

                    <button 
                        v-if="order.status === 'w trakcie'"
                        @click="changeStatus(order.id, 'gotowe')"
                        class="bg-blue-600 hover:bg-blue-500 text-white font-black text-xs py-2 px-4 rounded-lg transition-colors uppercase tracking-wider"
                    >
                        Wydaj z kuchni ✔
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>