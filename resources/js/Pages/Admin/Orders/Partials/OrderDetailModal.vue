<script setup>
import { X } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    order: Object
});

const emit = defineEmits(['close', 'update-status']);
</script>

<template>
    <div v-if="isOpen && order" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-2xl shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
            
            <div class="border-b border-slate-800 pb-4 flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold text-amber-500 uppercase tracking-widest block">Szczegóły Zamówienia</span>
                    <h2 class="text-2xl font-black text-white font-mono">Zamówienie #{{ order.id }}</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Złożone: {{ new Date(order.created_at).toLocaleString('pl-PL') }}</p>
                </div>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white transition cursor-pointer">
                    <X class="w-6 h-6" />
                </button>
            </div>

            <!-- RĘCZNA ZMIANA STATUSU -->
            <div class="bg-[#0B0F19] p-4 rounded-2xl border border-slate-800 space-y-2">
                <label class="block text-xs font-bold uppercase text-slate-400">Ręczna zmiana statusu w kuchni/systemie:</label>
                <div class="flex flex-wrap gap-2">
                    <button @click="$emit('update-status', { orderId: order.id, status: 'nowe' })" class="px-3 py-1.5 rounded-xl text-xs font-bold border bg-blue-500/10 text-blue-400 border-blue-500/30 hover:bg-blue-500/20 transition cursor-pointer">Nowe</button>
                    <button @click="$emit('update-status', { orderId: order.id, status: 'w_przygotowaniu' })" class="px-3 py-1.5 rounded-xl text-xs font-bold border bg-amber-500/10 text-amber-400 border-amber-500/30 hover:bg-amber-500/20 transition cursor-pointer">W piecu</button>
                    <button @click="$emit('update-status', { orderId: order.id, status: 'gotowe' })" class="px-3 py-1.5 rounded-xl text-xs font-bold border bg-emerald-500/10 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/20 transition cursor-pointer">Gotowe</button>
                    <button @click="$emit('update-status', { orderId: order.id, status: 'w_dostawie' })" class="px-3 py-1.5 rounded-xl text-xs font-bold border bg-indigo-500/10 text-indigo-400 border-indigo-500/30 hover:bg-indigo-500/20 transition cursor-pointer">W trasie</button>
                    <button @click="$emit('update-status', { orderId: order.id, status: 'dostarczone' })" class="px-3 py-1.5 rounded-xl text-xs font-bold border bg-emerald-600/20 text-emerald-400 border-emerald-500/40 hover:bg-emerald-600/30 transition cursor-pointer">Zrealizowane</button>
                    <button @click="$emit('update-status', { orderId: order.id, status: 'anulowane' })" class="px-3 py-1.5 rounded-xl text-xs font-bold border bg-red-500/10 text-red-400 border-red-500/30 hover:bg-red-500/20 transition cursor-pointer">Anuluj</button>
                </div>
            </div>

            <!-- POZYCJE DAŃ -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase text-slate-400 tracking-wider">Zamówione Pozycje:</h3>
                <div class="space-y-2">
                    <div v-for="item in order.items" :key="item.id" class="bg-[#0B0F19] p-3.5 rounded-2xl border border-slate-800 space-y-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-white uppercase text-xs sm:text-sm">
                                    {{ item.quantity }}x {{ item.variant?.product?.name || item.name || 'Pizza' }}
                                    <span class="text-slate-400 font-normal text-xs ml-1">({{ item.variant?.size_name }})</span>
                                </h4>
                            </div>
                            <span class="font-mono font-bold text-emerald-400 text-xs sm:text-sm">
                                {{ (Number(item.price ?? item.unit_price ?? 0) * item.quantity).toFixed(2) }} zł
                            </span>
                        </div>

                        <!-- MODYFIKATORY -->
                        <div v-if="item.modifiers && item.modifiers.length > 0" class="flex flex-wrap gap-1 pt-1 border-t border-slate-800/80">
                            <span v-for="mod in item.modifiers" :key="mod.id" :class="mod.action === 'ADD' ? 'bg-emerald-950 text-emerald-400 border-emerald-900' : 'bg-red-950 text-red-400 border-red-900'" class="text-[9px] font-bold px-1.5 py-0.5 rounded border uppercase">
                                {{ mod.action === 'ADD' ? '+ Extra' : '- Bez' }} {{ mod.ingredient?.name || mod.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RAZEM DO ZAPŁATY -->
            <div class="bg-[#0B0F19] p-4 rounded-2xl border border-slate-800 flex justify-between items-center">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Metoda Płatności:</span>
                    <span class="text-xs font-bold text-white uppercase">{{ order.payment_method }} ({{ order.payment_status }})</span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Razem Do Zapłaty:</span>
                    <span class="text-2xl font-black font-mono text-emerald-400">{{ Number(order.total_price || 0).toFixed(2) }} zł</span>
                </div>
            </div>

        </div>
    </div>
</template>