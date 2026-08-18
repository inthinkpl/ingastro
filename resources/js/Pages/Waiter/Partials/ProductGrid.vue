<script setup>
import { Pizza } from 'lucide-vue-next';

defineProps({
    products: Array
});

defineEmits(['select-variant']);
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="product in products" :key="product.id" class="bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow-xl space-y-3">
            <div class="font-bold text-white flex items-center space-x-2">
                <Pizza class="w-4 h-4 text-amber-500 shrink-0" />
                <span class="truncate">{{ product.name }}</span>
            </div>
            
            <div class="space-y-2">
                <button 
                    v-for="variant in product.variants" 
                    :key="variant.id"
                    @click="$emit('select-variant', { product, variant })"
                    class="w-full bg-[#0B0F19] hover:bg-red-600 hover:text-white border border-slate-800 text-left text-xs p-2.5 rounded-xl transition flex justify-between items-center cursor-pointer group"
                >
                    <span class="font-medium text-slate-300 group-hover:text-white">{{ variant.size_name }}</span>
                    <span class="font-mono font-bold text-amber-400 group-hover:text-white">{{ variant.price }} zł</span>
                </button>
            </div>
        </div>
    </div>
</template>