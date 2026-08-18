<script setup>
defineProps({
    categories: { type: Array, required: true },
    activeCategory: { type: String, default: 'Wszystko' }
});

defineEmits(['select-category']);
</script>

<template>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-8">
        <button 
            v-for="cat in categories" 
            :key="cat"
            @click="$emit('select-category', cat)"
            :class="[
                activeCategory === cat 
                    ? 'bg-gradient-to-r from-red-600 via-red-600 to-amber-600 text-white border-red-500 shadow-lg shadow-red-600/25 scale-[1.02]' 
                    : 'bg-slate-900/90 text-slate-300 border-slate-800 hover:border-slate-700 hover:bg-slate-800/80 hover:text-white'
            ]"
            class="relative py-4 px-5 rounded-2xl border transition-all duration-300 flex items-center justify-between cursor-pointer group text-left shadow-md overflow-hidden"
        >
            <span class="font-black text-xs sm:text-sm uppercase tracking-wider block z-10">
                {{ cat }}
            </span>

            <!-- Świecący wskaźnik aktywnej kategorii -->
            <span 
                v-if="activeCategory === cat" 
                class="flex h-2.5 w-2.5 relative z-10"
            >
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-400"></span>
            </span>
            <span 
                v-else 
                class="w-1.5 h-1.5 rounded-full bg-slate-700 group-hover:bg-amber-500/60 transition"
            ></span>
        </button>
    </div>
</template>