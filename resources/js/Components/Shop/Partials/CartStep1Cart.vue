<script setup>
import { 
    Truck, Tag, Gift, X, Sparkles, Plus, Coins, ArrowRight 
} from 'lucide-vue-next';

defineProps({
    cart: { type: Array, required: true },
    subtotal: { type: Number, required: true },
    freeDeliverySettings: { type: Object, default: () => ({}) },
    freeDeliveryRemaining: { type: Number, default: 0 },
    freeDeliveryProgress: { type: Number, default: 0 },
    autoDiscount: { type: Number, default: 0 },
    freeItemsFromPromo: { type: Array, default: () => [] },
    upsellSettings: { type: Object, default: () => ({}) },
    upsellProducts: { type: Array, default: () => [] },
    pointsToEarn: { type: Number, default: 0 }
});

defineEmits(['remove-item', 'add-upsell', 'go-to-step2']);
</script>

<template>
    <div class="space-y-4">
        <!-- PASEK POSTĘPU DARMOWEJ DOSTAWY -->
        <div v-if="freeDeliverySettings?.enabled && cart.length > 0" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 space-y-2">
            <div class="flex justify-between items-center text-xs">
                <span class="font-bold text-slate-300 flex items-center space-x-1.5">
                    <Truck class="w-4 h-4 text-amber-500" />
                    <span v-if="freeDeliveryRemaining > 0">Darmowa dostawa</span>
                    <span v-else class="text-emerald-400 font-extrabold">Masz DARMOWĄ dostawę! 🎉</span>
                </span>
                <span class="font-mono font-bold text-amber-400 text-[11px]">{{ freeDeliveryProgress }}%</span>
            </div>

            <div class="w-full bg-slate-900 h-2.5 rounded-full overflow-hidden border border-slate-800">
                <div 
                    class="h-full transition-all duration-500 ease-out rounded-full"
                    :class="freeDeliveryRemaining === 0 ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : 'bg-gradient-to-r from-amber-500 to-red-500'"
                    :style="{ width: freeDeliveryProgress + '%' }"
                ></div>
            </div>

            <p v-if="freeDeliveryRemaining > 0" class="text-[11px] text-slate-400">
                Dołóż jeszcze <strong class="text-amber-400 font-mono">{{ freeDeliveryRemaining.toFixed(2) }} zł</strong>, aby nie płacić za dostawę!
            </p>
        </div>

        <!-- AUTOMATYCZNE PROMOCJE W KOSZYKU -->
        <div v-if="autoDiscount > 0 || freeItemsFromPromo.length > 0" class="bg-emerald-950/60 border border-emerald-900 rounded-xl p-3 space-y-2">
            <div class="flex items-center space-x-1.5 text-emerald-400 font-bold text-xs uppercase tracking-wider">
                <Tag class="w-4 h-4" />
                <span>Naliczono Promocję!</span>
            </div>

            <div v-if="autoDiscount > 0" class="flex justify-between items-center text-xs text-slate-300">
                <span>Rabat automatyczny:</span>
                <span class="font-mono font-bold text-emerald-400">-{{ autoDiscount.toFixed(2) }} zł</span>
            </div>

            <div v-for="(free, i) in freeItemsFromPromo" :key="i" class="flex items-center justify-between text-xs text-emerald-300 bg-emerald-900/40 p-2 rounded-lg border border-emerald-800">
                <span class="flex items-center space-x-1.5 font-bold">
                    <Gift class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                    <span>{{ free.name }}</span>
                </span>
                <span class="font-mono font-black text-xs uppercase bg-emerald-500 text-slate-950 px-1.5 py-0.5 rounded">GRATIS</span>
            </div>
        </div>

        <!-- LISTA POZYCJI -->
        <div class="space-y-3 max-h-[40vh] overflow-y-auto pr-1">
            <div v-for="(item, idx) in cart" :key="idx" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 text-xs space-y-1.5">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-bold text-white uppercase">{{ item.name }}</div>
                        <div class="text-slate-400 text-[11px]">{{ item.size }} — {{ item.quantity }} szt.</div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono font-bold text-emerald-400">{{ (item.price * item.quantity).toFixed(2) }} zł</span>
                        <button @click="$emit('remove-item', idx)" class="text-slate-500 hover:text-red-400 transition cursor-pointer">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div v-if="item.modifiers && item.modifiers.length > 0" class="flex flex-wrap gap-1 mt-1 pt-1 border-t border-slate-800/60">
                    <span 
                        v-for="mod in item.modifiers" 
                        :key="mod.ingredient_id"
                        :class="mod.action === 'ADD' ? 'bg-emerald-950/60 text-emerald-400 border-emerald-900' : 'bg-red-950/60 text-red-400 border-red-900'"
                        class="text-[9px] font-bold px-1.5 py-0.5 rounded border uppercase"
                    >
                        {{ mod.action === 'ADD' ? 'Ekstra' : 'Bez' }} {{ mod.name }}
                    </span>
                </div>
            </div>

            <div v-if="cart.length === 0" class="text-center py-8 text-xs text-slate-500 italic">
                Koszyk jest pusty. Wybierz pozycję z menu.
            </div>
        </div>

        <!-- SEKCJA UP-SELLING -->
        <div v-if="upsellSettings?.enabled && cart.length > 0 && upsellProducts.length > 0" class="pt-3 border-t border-slate-800 space-y-2">
            <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider flex items-center space-x-1">
                <Sparkles class="w-3.5 h-3.5" />
                <span>Często zamawiane razem</span>
            </span>

            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-thin">
                <div 
                    v-for="item in upsellProducts" 
                    :key="item.id"
                    class="bg-[#0B0F19] p-2 rounded-xl border border-slate-800 shrink-0 w-32 flex flex-col justify-between text-left space-y-1.5"
                >
                    <div>
                        <span class="text-[11px] font-bold text-white block truncate">{{ item.name }}</span>
                        <span class="text-[10px] text-amber-400 font-mono font-bold block">{{ item.variants[0]?.price }} zł</span>
                    </div>
                    <button 
                        @click="$emit('add-upsell', item)"
                        class="w-full bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-slate-200 py-1 rounded-lg text-[10px] font-bold uppercase transition flex items-center justify-center space-x-0.5 cursor-pointer"
                    >
                        <Plus class="w-3 h-3" />
                        <span>Dodaj</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- PUNKTY KROK 1 -->
        <div v-if="cart.length > 0" class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-2.5 flex items-center justify-between text-xs">
            <div class="flex items-center space-x-2 text-amber-400 font-bold">
                <Coins class="w-4 h-4 text-amber-400" />
                <span>Punkty za zamówienie:</span>
            </div>
            <span class="font-mono font-black text-amber-400 bg-amber-500/20 px-2 py-0.5 rounded-lg border border-amber-500/40 text-xs">
                +{{ pointsToEarn }} pkt
            </span>
        </div>

        <!-- PODSUMOWANIE FINANSOWE KROKU 1 -->
        <div v-if="cart.length > 0" class="pt-2 border-t border-slate-800 space-y-2">
            <div class="flex justify-between items-center text-xs">
                <span class="text-slate-400 font-bold uppercase">Wartość dań:</span>
                <span 
                    class="font-mono font-bold" 
                    :class="autoDiscount > 0 ? 'text-slate-500 line-through text-xs' : 'text-emerald-400 text-lg font-black'"
                >
                    {{ subtotal.toFixed(2) }} zł
                </span>
            </div>

            <!-- WIERSZ PO NALICZENIU PROMOCJI -->
            <div v-if="autoDiscount > 0" class="flex justify-between items-center text-xs bg-amber-500/10 p-2 rounded-xl border border-amber-500/30">
                <span class="text-amber-400 font-extrabold uppercase flex items-center space-x-1">
                    <Tag class="w-3.5 h-3.5" />
                    <span>Wartość dań z promocją:</span>
                </span>
                <span class="text-lg font-black font-mono text-emerald-400">
                    {{ Math.max(0, subtotal - autoDiscount).toFixed(2) }} zł
                </span>
            </div>

            <button 
                @click="$emit('go-to-step2')"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3.5 rounded-xl text-xs uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-2 cursor-pointer mt-2"
            >
                <span>Złóż Zamówienie</span>
                <ArrowRight class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>