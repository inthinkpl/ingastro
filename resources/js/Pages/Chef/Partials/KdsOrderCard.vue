<script setup>
import { Utensils, ShoppingBag, Truck, Play, CheckCircle2, ArrowRight, Plus, Minus, PackageCheck } from 'lucide-vue-next';

const props = defineProps({
    order: Object,
    processing: Boolean
});

const emit = defineEmits(['change-status']);

const getOrderTypeBadge = (type) => {
    switch (type) {
        case 'lokal':
            return { label: 'Lokal', icon: Utensils, class: 'bg-blue-950/60 text-blue-400 border-blue-900' };
        case 'wynos':
            return { label: 'Wynos', icon: ShoppingBag, class: 'bg-purple-950/60 text-purple-400 border-purple-900' };
        case 'dostawa':
            return { label: 'Dostawa', icon: Truck, class: 'bg-amber-950/60 text-amber-400 border-amber-900' };
        default:
            return { label: type, icon: Utensils, class: 'bg-slate-800 text-slate-300 border-slate-700' };
    }
};
</script>

<template>
    <div 
        :class="[
            order.status === 'gotowe' ? 'border-emerald-500/80 bg-emerald-950/20 shadow-emerald-950/30' : 'border-slate-800 bg-slate-900',
            order.status === 'w_przygotowaniu' ? 'border-amber-500/50 bg-slate-900/90' : ''
        ]"
        class="border rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-4 transition duration-300"
    >
        <div class="space-y-4">
            <!-- NAGŁÓWEK BONU -->
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="font-black text-lg text-amber-500 font-mono">#{{ order.id }}</span>
                    <span v-if="order.table_number" class="text-xs font-bold text-slate-400 bg-[#0B0F19] px-2 py-0.5 rounded-md border border-slate-800">
                        Stolik {{ order.table_number }}
                    </span>
                </div>

                <span :class="getOrderTypeBadge(order.type).class" class="text-[10px] uppercase font-bold px-2 py-1 rounded-lg border flex items-center space-x-1">
                    <component :is="getOrderTypeBadge(order.type).icon" class="w-3 h-3" />
                    <span>{{ getOrderTypeBadge(order.type).label }}</span>
                </span>
            </div>

            <!-- ADRES DLA DOSTAWY -->
            <p v-if="order.type === 'dostawa' && order.delivery_address" class="text-xs text-slate-400 -mt-2 truncate font-medium">
                {{ order.delivery_address }}
            </p>

            <!-- POZYCJE DANIOWE (BOM) -->
            <div class="space-y-2.5 max-h-[280px] overflow-y-auto pr-1">
                <div 
                    v-for="item in order.items" 
                    :key="item.id" 
                    class="bg-[#0B0F19] p-3 rounded-2xl border border-slate-800/80 text-xs space-y-1"
                >
                    <div class="font-bold text-white flex items-baseline space-x-1.5">
                        <span class="text-amber-400 font-mono text-sm font-black">{{ item.quantity }}x</span>
                        <span class="text-slate-100 text-sm">{{ item.variant?.product?.name || 'Produkt' }}</span>
                    </div>

                    <div class="text-[11px] text-slate-400 font-medium pl-5">
                        Rozmiar: <span class="text-slate-200">{{ item.variant?.size_name }}</span>
                    </div>

                    <!-- MODYFIKATORY (EXTRA / BEZ) -->
                    <div v-if="item.modifiers && item.modifiers.length > 0" class="pl-5 pt-1.5 border-t border-slate-800/60 space-y-1 mt-1.5">
                        <div 
                            v-for="mod in item.modifiers" 
                            :key="mod.id" 
                            :class="mod.action === 'ADD' ? 'text-emerald-400' : 'text-red-400'" 
                            class="text-[10px] uppercase font-bold flex items-center space-x-1"
                        >
                            <Plus v-if="mod.action === 'ADD'" class="w-3 h-3 shrink-0" />
                            <Minus v-else class="w-3 h-3 shrink-0" />
                            <span>{{ mod.action === 'ADD' ? 'EXTRA:' : 'BEZ:' }} {{ mod.ingredient?.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRZYCISKI AKCJI (MASZYNA STANÓW KDS) -->
        <div class="pt-3 border-t border-slate-800">
            <!-- KROK 1: NOWE ZAMÓWIENIE -> ROZPOCZNIJ PRACĘ -->
            <button 
                v-if="order.status === 'nowe'"
                @click="$emit('change-status', { orderId: order.id, nextStatus: 'w_przygotowaniu' })"
                :disabled="processing"
                class="w-full bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-black text-xs py-3 rounded-xl uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50"
            >
                <Play class="w-4 h-4 fill-current" />
                <span>Rozpocznij pracę</span>
            </button>

            <!-- KROK 2: W PRZYGOTOWANIU -> OZNACZ JAKO GOTOWE (ZOSTAJE W KDS) -->
            <button 
                v-if="order.status === 'w_przygotowaniu'"
                @click="$emit('change-status', { orderId: order.id, nextStatus: 'gotowe' })"
                :disabled="processing"
                class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black text-xs py-3 rounded-xl uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50"
            >
                <CheckCircle2 class="w-4 h-4" />
                <span>Oznacz jako gotowe</span>
            </button>

            <!-- KROK 3: GOTOWE -> WYDAJ Z KUCHNI (ZNIKA Z KDS, DLA DOSTAWY USTA W TRASIE) -->
            <button 
                v-if="order.status === 'gotowe'"
                @click="$emit('change-status', { orderId: order.id, nextStatus: 'wydane' })"
                :disabled="processing"
                class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs py-3 rounded-xl uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50 animate-pulse"
            >
                <Truck v-if="order.type === 'dostawa'" class="w-4 h-4" />
                <PackageCheck v-else class="w-4 h-4" />
                <span>Wydaj z kuchni {{ order.type === 'dostawa' ? '(W trasę)' : '' }}</span>
            </button>
        </div>
    </div>
</template>