<script setup>
import { computed } from 'vue';
import { Receipt, Utensils, ShoppingBag, Truck, Trash2, Send } from 'lucide-vue-next';

const props = defineProps({
    cart: Array,
    orderType: String,
    tableNumber: [String, Number],
    processing: Boolean
});

const emit = defineEmits(['update:orderType', 'update:tableNumber', 'remove-item', 'submit-order']);

const totalSum = computed(() => {
    return props.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2);
});
</script>

<template>
    <div class="flex flex-col h-full justify-between space-y-4">
        <div>
            <h2 class="text-xs font-bold text-slate-300 uppercase tracking-widest mb-4 border-b border-slate-800 pb-2 flex items-center space-x-2">
                <Receipt class="w-4 h-4 text-amber-500" />
                <span>Aktualny Rachunek</span>
            </h2>
            
            <!-- WRAŻLIWY SELEKTOR TYPU ZAMÓWIENIA -->
            <div class="grid grid-cols-3 gap-2 mb-4">
                <button 
                    @click="$emit('update:orderType', 'lokal')" 
                    :class="orderType === 'lokal' ? 'bg-red-600 text-white font-bold shadow-md' : 'bg-[#0B0F19] text-slate-400 hover:text-white'" 
                    class="py-2.5 text-xs uppercase tracking-wider font-bold rounded-xl border border-slate-800 transition flex flex-col items-center justify-center space-y-1 cursor-pointer"
                >
                    <Utensils class="w-3.5 h-3.5" />
                    <span>Lokal</span>
                </button>
                <button 
                    @click="$emit('update:orderType', 'wynos')" 
                    :class="orderType === 'wynos' ? 'bg-red-600 text-white font-bold shadow-md' : 'bg-[#0B0F19] text-slate-400 hover:text-white'" 
                    class="py-2.5 text-xs uppercase tracking-wider font-bold rounded-xl border border-slate-800 transition flex flex-col items-center justify-center space-y-1 cursor-pointer"
                >
                    <ShoppingBag class="w-3.5 h-3.5" />
                    <span>Wynos</span>
                </button>
                <button 
                    @click="$emit('update:orderType', 'dostawa')" 
                    :class="orderType === 'dostawa' ? 'bg-red-600 text-white font-bold shadow-md' : 'bg-[#0B0F19] text-slate-400 hover:text-white'" 
                    class="py-2.5 text-xs uppercase tracking-wider font-bold rounded-xl border border-slate-800 transition flex flex-col items-center justify-center space-y-1 cursor-pointer"
                >
                    <Truck class="w-3.5 h-3.5" />
                    <span>Dostawa</span>
                </button>
            </div>

            <!-- NUMER STOLIKA -->
            <div v-if="orderType === 'lokal'" class="mb-4">
                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Numer Stolika</label>
                <input 
                    :value="tableNumber" 
                    @input="$emit('update:tableNumber', $event.target.value)"
                    type="number" 
                    placeholder="np. 4" 
                    class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-sm text-white font-mono font-bold focus:border-red-500" 
                />
            </div>

            <!-- POZYCJE RACHUNKU -->
            <div class="space-y-2 overflow-y-auto max-h-[45vh] pr-1">
                <div v-for="(item, idx) in cart" :key="idx" class="bg-[#0B0F19] border border-slate-800 p-3 rounded-xl flex flex-col text-xs space-y-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-bold text-white">{{ item.name }}</div>
                            <div class="text-slate-400 text-[11px]">{{ item.size }} x {{ item.quantity }}</div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="font-mono font-bold text-emerald-400 text-sm">{{ (item.price * item.quantity).toFixed(2) }} zł</span>
                            <button @click="$emit('remove-item', idx)" class="text-slate-500 hover:text-red-400 font-bold p-1 cursor-pointer transition">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    
                    <!-- TAGI MODYFIKATORÓW -->
                    <div v-if="item.modifiers.length > 0" class="flex flex-wrap gap-1 pt-1.5 border-t border-slate-800/80">
                        <span 
                            v-for="mod in item.modifiers" 
                            :key="mod.ingredient_id"
                            :class="mod.action === 'ADD' ? 'bg-emerald-950/80 text-emerald-400 border-emerald-900' : 'bg-red-950/80 text-red-400 border-red-900'"
                            class="text-[9px] font-bold px-1.5 py-0.5 rounded-md border uppercase"
                        >
                            {{ mod.action === 'ADD' ? '+' : '-' }} {{ mod.name }}
                        </span>
                    </div>
                </div>

                <div v-if="cart.length === 0" class="text-center py-12 text-xs text-slate-500 italic">
                    Rachunek jest obecnie pusty.
                </div>
            </div>
        </div>

        <!-- DOLE PODSUMOWANIE -->
        <div class="border-t border-slate-800 pt-4">
            <div class="flex justify-between items-center mb-4">
                <span class="text-xs text-slate-400 uppercase font-bold">Suma:</span>
                <span class="text-2xl font-black font-mono text-emerald-400">{{ totalSum }} zł</span>
            </div>
            
            <button 
                @click="$emit('submit-order')"
                :disabled="cart.length === 0 || processing"
                class="w-full bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 disabled:opacity-40 text-white font-bold py-3.5 rounded-xl text-xs uppercase tracking-wider shadow-lg flex items-center justify-center space-x-2 cursor-pointer"
            >
                <Send class="w-4 h-4" />
                <span>{{ processing ? 'Rejestracja...' : 'Wyślij na kuchnię' }}</span>
            </button>
        </div>
    </div>
</template>