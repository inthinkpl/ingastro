<script setup>
import { AlertTriangle, X, Check } from 'lucide-vue-next';

defineProps({
    isOpen: Boolean,
    missingIngredientName: String
});

defineEmits(['confirm', 'cancel']);
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-amber-900/60 rounded-3xl p-6 w-full max-w-sm shadow-2xl text-center space-y-4">
            
            <div class="h-12 w-12 rounded-full bg-amber-500/10 border border-amber-500/30 flex items-center justify-center mx-auto text-amber-500">
                <AlertTriangle class="w-6 h-6" />
            </div>

            <div>
                <h3 class="text-sm font-bold text-amber-500 uppercase tracking-wider">Brak Surowca w Magazynie</h3>
                <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                    W magazynie brakuje surowca: <span class="text-white font-bold underline">{{ missingIngredientName }}</span>.<br>
                    Czy chcesz wymusić <span class="text-emerald-400 font-bold">przyjęcie zamówienia</span>?
                </p>
            </div>

            <div class="flex space-x-3 pt-2">
                <button 
                    type="button" 
                    @click="$emit('cancel')" 
                    class="w-1/2 bg-[#0B0F19] hover:bg-slate-800 text-slate-300 font-bold py-2.5 rounded-xl text-xs uppercase tracking-wider transition border border-slate-800 flex items-center justify-center space-x-1 cursor-pointer"
                >
                    <X class="w-4 h-4" />
                    <span>Anuluj</span>
                </button>
                
                <button 
                    type="button" 
                    @click="$emit('confirm')" 
                    class="w-1/2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-2.5 rounded-xl text-xs uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-1 cursor-pointer"
                >
                    <Check class="w-4 h-4" />
                    <span>Przyjmij</span>
                </button>
            </div>
        </div>
    </div>
</template>