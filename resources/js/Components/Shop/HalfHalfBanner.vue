<script setup>
import { Pizza } from 'lucide-vue-next';

defineEmits(['open-modal']);
</script>

<template>
    <div class="mb-12 bg-gradient-to-r from-amber-500/10 via-red-600/10 to-amber-500/10 border border-amber-500/30 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl">
        <div class="flex items-center space-x-4">
            <div class="p-3 bg-amber-500/20 border border-amber-500/40 text-amber-400 rounded-2xl shrink-0">
                <Pizza class="w-8 h-8" />
            </div>
            <div>
                <h3 class="text-base sm:text-lg font-black text-white uppercase tracking-wide">
                    Nie możesz się zdecydować? Stwórz Pizzę Pół na Pół! 🍕🍕
                </h3>
                <p class="text-xs text-slate-300 mt-0.5">
                    Połącz dwa dowolne smaki na jednej pizzy. Cena zostanie automatycznie obliczona.
                </p>
            </div>
        </div>
        <button 
            @click="$emit('open-modal')"
            class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-6 py-3 rounded-xl text-xs uppercase tracking-wider transition cursor-pointer shrink-0 shadow-lg"
        >
            Skomponuj Pół na Pół
        </button>
    </div>
</template>